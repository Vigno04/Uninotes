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


// -------------------------
// 0) Parametri da $_GET
// -------------------------
$searchTerm = trim($_GET['q'] ?? '');
$sort       = $_GET['sort'] ?? 'date'; // 'date' o 'votes'

$filterMyCourses = false;
$offeringFilter  = null;

if (isset($_GET['offering_id']) && $_GET['offering_id'] !== '') {
    if ($_GET['offering_id'] === 'my' && $currentUserId !== null) {
        $filterMyCourses = true;
    } else {
        $offeringFilter = (int)$_GET['offering_id'];
    }
}

// "Load more" logic: quanti appunti mostrare
$defaultLimit = 5;
$loadMoreStep = 5;
$limit        = isset($_GET['limit']) ? max($defaultLimit, (int)$_GET['limit']) : $defaultLimit;

// -------------------------
// Informazioni sull'offering specifico (se filtrato)
// -------------------------
$offeringInfo = null;
if (!is_null($offeringFilter)) {
    $sqlOfferingInfo = "
        SELECT 
            c.name AS course_name,
            co.year,
            co.semester
        FROM course_offering co
        JOIN course c ON co.course_id = c.id
        WHERE co.id = ?
    ";
    $stmtInfo = mysqli_prepare($conn, $sqlOfferingInfo);
    if ($stmtInfo) {
        mysqli_stmt_bind_param($stmtInfo, "i", $offeringFilter);
        mysqli_stmt_execute($stmtInfo);
        $resInfo = mysqli_stmt_get_result($stmtInfo);
        $offeringInfo = mysqli_fetch_assoc($resInfo);
        mysqli_stmt_close($stmtInfo);
    }
}

// -------------------------
// 1) Offering disponibili per filtro
// -------------------------
$allOfferingOptions = [];

if ($currentUserId !== null) {
    // Offerings seguiti dall'utente
    $sqlOfferings = "
        SELECT 
            co.id,
            c.name AS course_name,
            co.year,
            co.semester
        FROM course_offering_follow cof
        JOIN course_offering co ON cof.offering_id = co.id
        JOIN course c ON co.course_id = c.id
        WHERE cof.user_id = ?
        ORDER BY c.name, co.year DESC, co.semester DESC
    ";
    $stmtOff = mysqli_prepare($conn, $sqlOfferings);
    if ($stmtOff) {
        mysqli_stmt_bind_param($stmtOff, "i", $currentUserId);
        mysqli_stmt_execute($stmtOff);
        $resOff = mysqli_stmt_get_result($stmtOff);
        while ($row = mysqli_fetch_assoc($resOff)) {
            $semesterLabel = $row['semester'] === '1' ? '1st' : '2nd';
            $row['display_name'] = $row['course_name'] . ' (' . $row['year'] . ' - ' . $semesterLabel . ' Sem)';
            $allOfferingOptions[] = $row;
        }
        mysqli_stmt_close($stmtOff);
    }
}


// -------------------------
// 2) Costruisco la query base (senza LIMIT)
// -------------------------
$conditions = ["n.status = 'published'"];
$params = [];
$types  = "";

// filtro offering specifico
if (!is_null($offeringFilter)) {
    $conditions[] = "co.id = ?";
    $types       .= "i";
    $params[]     = $offeringFilter;
}

// filtro "My courses" → note dei course offerings seguiti
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

if ($searchTerm !== '' || !is_null($offeringFilter) || $filterMyCourses) {

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
                        <?php if ($offeringInfo): ?>
                            <?php 
                            $semesterLabel = $offeringInfo['semester'] === '1' ? '1st' : '2nd';
                            echo htmlspecialchars($offeringInfo['course_name']) . ' - ' . 
                                 htmlspecialchars($offeringInfo['year']) . ' (' . $semesterLabel . ' Semester)';
                            ?>
                        <?php elseif ($searchTerm === '' && is_null($offeringFilter) && !$filterMyCourses): ?>
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
                <select name="offering_id" class="form-select">
                    <option value="">All notes</option>

                    <?php if ($currentUserId !== null): ?>
                        <option value="my" <?php echo $filterMyCourses ? 'selected' : ''; ?>>
                            My offerings
                        </option>
                    <?php endif; ?>

                    <?php foreach ($allOfferingOptions as $offering): ?>
                        <option value="<?php echo (int)$offering['id']; ?>"
                            <?php echo (!$filterMyCourses && $offeringFilter === (int)$offering['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($offering['display_name']); ?>
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

        <?php if ($searchTerm !== '' || !is_null($offeringFilter) || $filterMyCourses): ?>
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
                <?php
                    // Se sto filtrando e ho il totale, capisco se ci sono ancora note
                    $hasMore = true;
                    if ($totalResults > 0 && $limit >= $totalResults) {
                        $hasMore = false;
                    }
                ?>

                <?php if ($hasMore): ?>
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
                                $loadMoreParams['offering_id'] = 'my';
                            } elseif (!is_null($offeringFilter)) {
                                $loadMoreParams['offering_id'] = $offeringFilter;
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



            <?php endif; ?>
        </div>
    </div>
</div>
