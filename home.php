<?php
// home.php

// Se qualcuno apre direttamente home.php, rimandalo al router (opzionale)
if (basename($_SERVER['SCRIPT_NAME']) === 'home.php') {
    header('Location: index.php?page=home');
    exit;
}

// Assumo che la sessione sia già partita in bootstrap.php
$isAdmin      = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');
$currentUserId = $_SESSION['person_id'] ?? null;


// 0) Gestione POST: aggiungi / rimuovi corso (follow / unfollow)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $currentUserId !== null) {
    $courseId = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;

    if ($courseId > 0) {
        if (isset($_POST['follow_course'])) {
            // FOLLOW → prendo l'offerta più recente (anno + semestre) per quel course_id
            $sqlOffering = "
                SELECT id
                FROM course_offering
                WHERE course_id = ?
                ORDER BY year DESC, semester DESC
                LIMIT 1
            ";
            $stmtOff = mysqli_prepare($conn, $sqlOffering);
            if ($stmtOff) {
                mysqli_stmt_bind_param($stmtOff, "i", $courseId);
                mysqli_stmt_execute($stmtOff);
                $resOff = mysqli_stmt_get_result($stmtOff);
                if ($rowOff = mysqli_fetch_assoc($resOff)) {
                    $offeringId = (int)$rowOff['id'];

                    // inserisco nella tabella di follow (se non esiste già)
                    $sqlFollow = "
                        INSERT IGNORE INTO course_offering_follow (offering_id, user_id)
                        VALUES (?, ?)
                    ";
                    $stmtFollow = mysqli_prepare($conn, $sqlFollow);
                    if ($stmtFollow) {
                        mysqli_stmt_bind_param($stmtFollow, "ii", $offeringId, $currentUserId);
                        mysqli_stmt_execute($stmtFollow);
                        mysqli_stmt_close($stmtFollow);
                    }
                }
                mysqli_stmt_close($stmtOff);
            }
        } elseif (isset($_POST['unfollow_course'])) {
            // UNFOLLOW → rimuovo tutti i follow per quel course_id (tutte le offering di quel corso)
            $sqlUnfollow = "
                DELETE cof
                FROM course_offering_follow cof
                JOIN course_offering co ON cof.offering_id = co.id
                WHERE co.course_id = ? AND cof.user_id = ?
            ";
            $stmtUnf = mysqli_prepare($conn, $sqlUnfollow);
            if ($stmtUnf) {
                mysqli_stmt_bind_param($stmtUnf, "ii", $courseId, $currentUserId);
                mysqli_stmt_execute($stmtUnf);
                mysqli_stmt_close($stmtUnf);
            }
        }
    }

    // redirect per evitare il resubmit del form
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '#'));
    exit;
}



// -------------------------
// 0) Parametri da $_GET
// -------------------------
$searchTerm = trim($_GET['q'] ?? '');
$sort       = $_GET['sort'] ?? 'date'; // 'date' o 'votes'

$filterMyCourses = false;
$courseFilter    = null;

if (isset($_GET['course_id']) && $_GET['course_id'] !== '') {
    if ($_GET['course_id'] === 'my' && $currentUserId !== null) {
        $filterMyCourses = true;
    } else {
        $courseFilter = (int)$_GET['course_id'];
    }
}

// "Load more" logic: quanti appunti mostrare
$defaultLimit = 5;
$loadMoreStep = 5;
$limit        = isset($_GET['limit']) ? max($defaultLimit, (int)$_GET['limit']) : $defaultLimit;

// -------------------------
// 1) Corsi disponibili (tutti i corsi)
// -------------------------
$allCourseOptions = [];
$sqlCourseOptions = "
    SELECT id, name FROM course;
";
$resultCourseOptions = mysqli_query($conn, $sqlCourseOptions);
if ($resultCourseOptions) {
    while ($row = mysqli_fetch_assoc($resultCourseOptions)) {
        $allCourseOptions[] = $row;
    }
}


// -------------------------
// 1b) Corsi già seguiti dall'utente (per disabilitare il bottone)
// -------------------------
$followedCourseIds = [];

if ($currentUserId !== null) {
    $sqlFollowed = "
        SELECT DISTINCT c.id AS course_id
        FROM course_offering_follow cof
        JOIN course_offering co ON cof.offering_id = co.id
        JOIN course c ON co.course_id = c.id
        WHERE cof.user_id = ?
    ";
    $stmtF = mysqli_prepare($conn, $sqlFollowed);
    if ($stmtF) {
        mysqli_stmt_bind_param($stmtF, "i", $currentUserId);
        mysqli_stmt_execute($stmtF);
        $resF = mysqli_stmt_get_result($stmtF);
        while ($rowF = mysqli_fetch_assoc($resF)) {
            $followedCourseIds[] = (int)$rowF['course_id'];
        }
        mysqli_stmt_close($stmtF);
    }
}

// -------------------------
// 1c) Corsi per il filtro note
//     - se loggato e segue corsi → mostra solo quelli
//     - altrimenti → tutti i corsi
// -------------------------
$filterCourseOptions = $allCourseOptions;

if ($currentUserId !== null && !empty($followedCourseIds)) {
    $filterCourseOptions = array_values(array_filter(
        $allCourseOptions,
        function ($c) use ($followedCourseIds) {
            return in_array((int)$c['id'], $followedCourseIds, true);
        }
    ));
}


// -------------------------
// 2) Costruisco la query base (senza LIMIT)
// -------------------------
$conditions = ["n.status = 'published'"];
$params = [];
$types  = "";

// filtro corso specifico
if (!is_null($courseFilter)) {
    $conditions[] = "c.id = ?";
    $types       .= "i";
    $params[]     = $courseFilter;
}

// filtro "My courses" → note dei corsi seguiti
if ($filterMyCourses && $currentUserId !== null) {
    $conditions[] = "EXISTS (
        SELECT 1
        FROM course_offering_follow cof
        WHERE cof.offering_id = co.id
          AND cof.user_id = ?
    )";
    $types   .= "i";
    $params[] = $currentUserId;
}


// filtro testo
if ($searchTerm !== '') {
    $conditions[] = "(n.title LIKE ? OR n.content LIKE ?)";
    $like = '%' . $searchTerm . '%';
    $types  .= "ss";
    $params[] = $like;
    $params[] = $like;
}

$whereClause = "";
if (!empty($conditions)) {
    $whereClause = "WHERE " . implode(" AND ", $conditions);
}

// sort
$orderBy = "note_date DESC";
if ($sort === 'votes') {
    $orderBy = "n.vote_count DESC, note_date DESC";
}

// -------------------------
// 3) Conteggio totale SOLO se sto cercando/filtrando
// -------------------------
$totalResults = 0;

if ($searchTerm !== '' || !is_null($courseFilter)) {
    $sqlCount = "
        SELECT COUNT(DISTINCT n.id) AS total
        FROM note n
        JOIN topic t ON n.topic_id = t.id
        JOIN course_offering co ON t.offering_id = co.id
        JOIN course c ON co.course_id = c.id
        $whereClause
    ";

    $stmtCount = mysqli_prepare($conn, $sqlCount);
    if ($stmtCount) {
        if (!empty($params)) {
            mysqli_stmt_bind_param($stmtCount, $types, ...$params);
        }
        mysqli_stmt_execute($stmtCount);
        $resCount = mysqli_stmt_get_result($stmtCount);
        if ($rowCount = mysqli_fetch_assoc($resCount)) {
            $totalResults = (int)$rowCount['total'];
        }
        mysqli_stmt_close($stmtCount);
    }
}

// -------------------------
// 4) Query effettiva per le note (con LIMIT)
// -------------------------
$recentNotes = [];

$sqlBase = "
    SELECT
        n.id,
        n.title,
        n.vote_count,
        COALESCE(n.published_at, n.created_at) AS note_date,
        c.name AS course_name,
        t.name AS topic_name,
        CONCAT(pt.name, ' ', pt.surname) AS teacher_name,
        CONCAT(po.name, ' ', po.surname) AS author_name
    FROM note n
    JOIN topic t ON n.topic_id = t.id
    JOIN course_offering co ON t.offering_id = co.id
    JOIN course c ON co.course_id = c.id
    LEFT JOIN course_offering_teacher cot ON co.id = cot.offering_id
    LEFT JOIN teacher th ON cot.teacher_id = th.person_id
    LEFT JOIN person pt ON th.person_id = pt.id              -- docente
    LEFT JOIN user uo ON n.owner_id = uo.person_id
    LEFT JOIN person po ON uo.person_id = po.id              -- autore
    $whereClause
    GROUP BY n.id
    ORDER BY $orderBy
    LIMIT ?
";

// aggiungo il LIMIT come ultimo parametro
$typesWithLimit      = $types . "i";
$paramsWithLimit     = $params;
$paramsWithLimit[]   = $limit;

$stmt = mysqli_prepare($conn, $sqlBase);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, $typesWithLimit, ...$paramsWithLimit);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) {

        $exam = $row['course_name'];
        if (!empty($row['teacher_name'])) {
            $exam .= ' - ' . $row['teacher_name'];
        }

        $recentNotes[] = [
            'id'=> $row['id'],
            "title"      => $row["title"],
            "course"     => $row["course_name"],
            "exam"       => $exam,
            "author"     => $row["author_name"] ?: "Anonimo",
            "date"       => $row["note_date"],
            "likes"      => (int)$row["vote_count"],
        ];
    }
    mysqli_stmt_close($stmt);
}

// -------------------------
// 5) Corsi popolari (sidebar / All courses)
// -------------------------
$popularCourses = [];

$sqlCourses = "
    SELECT c.id, c.name, COUNT(n.id) AS notes_count
    FROM course c
    JOIN course_offering co ON co.course_id = c.id
    JOIN topic t ON t.offering_id = co.id
    JOIN note n ON n.topic_id = t.id AND n.status = 'published'
    GROUP BY c.id
    ORDER BY notes_count DESC, c.name ASC
    LIMIT 10
";

$resultCourses = mysqli_query($conn, $sqlCourses);
if ($resultCourses) {
    while ($row = mysqli_fetch_assoc($resultCourses)) {
        $popularCourses[] = $row['name']; // non lo usi, ma lo lascio se ti serve altrove
    }
}
?>

<div class="container py-4">

    <!-- Header + barra di ricerca + filtri -->
    <div class="mb-4">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-3">
            <div class="d-flex align-items-center gap-2">
                <span class="fs-4">📘</span>
                <div>
                    <h1 class="h4 mb-1">Notes</h1>
                    <small class="text-muted">
                        <?php if ($searchTerm === '' && is_null($courseFilter)): ?>
                            Latest updated notes
                        <?php else: ?>
                            Filtered results 
                        <?php endif; ?>
                    </small>
                </div>
            </div>

            <?php if ($isAdmin): ?>
                <span class="badge bg-warning text-dark">
                    Admin
                </span>
            <?php endif; ?>
        </div>

        <form class="row g-2" method="get" action="index.php">
            <input type="hidden" name="page" value="home">
            <!-- conserva il numero di risultati già caricati -->
            <input type="hidden" name="limit" value="<?php echo (int)$limit; ?>">

            <div class="col-12 col-lg-6">
                <input
                    type="text"
                    class="form-control"
                    name="q"
                    placeholder="Search by title or content..."
                    value="<?php echo htmlspecialchars($searchTerm); ?>"
                >
            </div>

            <div class="col-6 col-lg-2">
                <select name="course_id" class="form-select">


                    <?php if ($currentUserId !== null): ?>
                        <option value="my" <?php echo $filterMyCourses ? 'selected' : ''; ?>>
                            My courses
                        </option>
                    <?php endif; ?>

                    <?php foreach ($filterCourseOptions as $course): ?>
                        <option value="<?php echo (int)$course['id']; ?>"
                            <?php echo (!$filterMyCourses && $courseFilter === (int)$course['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($course['name']); ?>
                        </option>
                    <?php endforeach; ?>

                </select>
            </div>


            <div class="col-6 col-lg-2">
                <select name="sort" class="form-select">
                    <option value="date" <?php echo $sort === 'date' ? 'selected' : ''; ?>>
                        Most recent
                    </option>
                    <option value="votes" <?php echo $sort === 'votes' ? 'selected' : ''; ?>>
                        Most liked
                    </option>
                </select>
            </div>

            <div class="col-12 col-lg-2 d-grid">
                <button class="btn btn-primary" type="submit">
                    Search
                </button>
            </div>
        </form>

        <?php if ($searchTerm !== '' || !is_null($courseFilter)): ?>
            <p class="text-muted small mt-2 mb-0">
                Found <?php echo $totalResults; ?> notes.
            </p>
        <?php endif; ?>
    </div>

    <div class="row">
        <!-- Colonna principale con le card degli appunti -->
        <div class="col-lg-12">

            <?php if (empty($recentNotes)): ?>
                <p class="text-muted">No notes found.</p>
            <?php else: ?>
                <?php foreach ($recentNotes as $note): ?>
                    <div class="card mb-3 border-0 shadow-sm rounded-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <a href="index.php?page=note_view&id=<?php echo htmlspecialchars($note['id']); ?>" class="small text-primary text-decoration-none">
                                        <h2 class="h5 mb-1">
                                            <?php echo htmlspecialchars($note["title"]); ?>
                                        </h2>
                                    </a>

                                    <p class="mb-0 text-muted">
                                        <?php echo htmlspecialchars($note["course"]); ?>
                                    </p>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                                <div class="text-muted small">
                                    <span class="me-3">
                                        👤 <?php echo htmlspecialchars($note["author"]); ?>
                                    </span>
                                    <span>
                                        📅 
                                        <?php
                                        $date = date("d M Y", strtotime($note["date"]));
                                        echo htmlspecialchars($date);
                                        ?>
                                    </span>
                                </div>

                                <div class="d-flex align-items-center gap-3">
                                    <span class="small text-muted">
                                        ⬆ <?php echo (int)$note["likes"]; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Sempre e solo "Load more" -->
                <div class="d-flex justify-content-center mt-3">
                    <?php
                        $loadMoreParams = [
                            'page'  => 'home',
                            'limit' => $limit + $loadMoreStep,
                        ];
                        if ($searchTerm !== '') {
                            $loadMoreParams['q'] = $searchTerm;
                        }
                        if ($filterMyCourses) {
                            $loadMoreParams['course_id'] = 'my';
                        } elseif (!is_null($courseFilter)) {
                            $loadMoreParams['course_id'] = $courseFilter;
                        }
                        if ($sort !== 'date') {
                            $loadMoreParams['sort'] = $sort;
                        }
                        $loadMoreUrl = 'index.php?' . http_build_query($loadMoreParams);
                    ?>
                    <a class="btn btn-outline-primary" href="<?php echo htmlspecialchars($loadMoreUrl); ?>">
                        Load more
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sezione All the courses -->
        <hr class="my-5">
        <div class="mb-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="fs-4">🎓</span>
                    <div>
                        <h2 class="h4 mb-1">All courses</h2>
                        <small class="text-muted">
                            Browse all courses that currently have published notes.
                        </small>
                    </div>
                </div>
            </div>

            <?php if (empty($allCourseOptions)): ?>
                <p class="text-muted">There are no courses with notes yet.</p>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($allCourseOptions as $course): ?>
                        <?php
                            $cid          = (int)$course['id'];
                            $alreadyAdded = in_array($cid, $followedCourseIds, true);
                        ?>
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="card border-0 shadow-sm rounded-4 h-100">
                                <div class="card-body d-flex flex-column">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h3 class="h6 mb-0">
                                            <?php echo htmlspecialchars($course['name']); ?>
                                        </h3>

                                        <?php if ($currentUserId !== null): ?>
                                        <form method="post" class="ms-2">
                                            <input type="hidden" name="page" value="home">
                                            <input type="hidden" name="course_id" value="<?php echo $cid; ?>">

                                            <?php if ($alreadyAdded): ?>
                                                <button
                                                    type="submit"
                                                    name="unfollow_course"
                                                    class="btn btn-sm btn-outline-danger"
                                                >
                                                    Remove course
                                                </button>
                                            <?php else: ?>
                                                <button
                                                    type="submit"
                                                    name="follow_course"
                                                    class="btn btn-sm btn-outline-success"
                                                >
                                                    Add course
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                    <?php endif; ?>
                                    
                                    </div>
                                    <p class="text-muted small mb-3">
                                        Study programme course
                                    </p>

                                    <div class="mt-auto d-flex justify-content-between align-items-center">
                                        <?php
                                            $courseUrlParams = [
                                                'page'      => 'home',
                                                'course_id' => $cid,
                                                'limit'     => $limit,
                                            ];
                                            if ($searchTerm !== '') {
                                                $courseUrlParams['q'] = $searchTerm;
                                            }
                                            if ($sort !== 'date') {
                                                $courseUrlParams['sort'] = $sort;
                                            }
                                            $courseUrl = 'index.php?' . http_build_query($courseUrlParams);
                                        ?>
                                        <a
                                            href="<?php echo htmlspecialchars($courseUrl); ?>"
                                            class="small text-primary text-decoration-none"
                                        >
                                            View notes → 
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
