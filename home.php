<?php
// home.php

// Se qualcuno apre direttamente home.php, rimandalo al router (opzionale)
if (basename($_SERVER['SCRIPT_NAME']) === 'home.php') {
    header('Location: index.php?page=home');
    exit;
}

// Assumo che la sessione sia già partita in bootstrap.php
$isAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

// -------------------------
// 0) Parametri da $_GET
// -------------------------
$searchTerm = trim($_GET['q'] ?? '');
$courseFilter = isset($_GET['course_id']) && $_GET['course_id'] !== '' ? (int)$_GET['course_id'] : null;
$sort = $_GET['sort'] ?? 'date'; // 'date' o 'votes'
$currentPage = max(1, (int)($_GET['p'] ?? 1));
$perPage = 5; // risultati per pagina quando si cerca

// -------------------------
// 1) Corsi disponibili per il filtro (dropdown)
// -------------------------
$courseOptions = [];
$sqlCourseOptions = "
    SELECT id, name FROM course;
";
$resultCourseOptions = mysqli_query($conn, $sqlCourseOptions);
if ($resultCourseOptions) {
    while ($row = mysqli_fetch_assoc($resultCourseOptions)) {
        $courseOptions[] = $row;
    }
}

// -------------------------
// 2) Costruisco la query base (senza LIMIT)
// -------------------------
$conditions = ["n.status = 'published'"];
$params = [];
$types  = "";

// filtro corso
if (!is_null($courseFilter)) {
    $conditions[] = "c.id = ?";
    $types       .= "i";
    $params[]     = $courseFilter;
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
// 3) Se sto cercando → calcolo il totale per la paginazione
// -------------------------
$totalResults = 0;
$totalPages = 1;

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

    if ($totalResults > 0) {
        $totalPages = (int)ceil($totalResults / $perPage);
        if ($currentPage > $totalPages) {
            $currentPage = $totalPages;
        }
    }
}

// -------------------------
// 4) Query effettiva per le note (con LIMIT/OFFSET)
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
        CONCAT(po.name, ' ', po.surname) AS author_name,
        f.file_type
    FROM note n
    JOIN topic t ON n.topic_id = t.id
    JOIN course_offering co ON t.offering_id = co.id
    JOIN course c ON co.course_id = c.id
    LEFT JOIN course_offering_teacher cot ON co.id = cot.offering_id
    LEFT JOIN teacher th ON cot.teacher_id = th.person_id
    LEFT JOIN person pt ON th.person_id = pt.id              -- docente
    LEFT JOIN user uo ON n.owner_id = uo.person_id
    LEFT JOIN person po ON uo.person_id = po.id              -- autore
    LEFT JOIN file f ON f.note_id = n.id
    $whereClause
    GROUP BY n.id
    ORDER BY $orderBy
";

// se c'è ricerca o filtro corso → paginazione
// altrimenti → solo ultimi 3 (come "home" normale)
if ($searchTerm !== '' || !is_null($courseFilter)) {
    $offset = ($currentPage - 1) * $perPage;
    $sqlBase .= " LIMIT ? OFFSET ?";

    // aggiungo i parametri per limit/offset
    $typesWithLimit = $types . "ii";
    $paramsWithLimit = $params;
    $paramsWithLimit[] = $perPage;
    $paramsWithLimit[] = $offset;

    $stmt = mysqli_prepare($conn, $sqlBase);
    if ($stmt) {
        if (!empty($paramsWithLimit)) {
            mysqli_stmt_bind_param($stmt, $typesWithLimit, ...$paramsWithLimit);
        }
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res)) {

            $exam = $row['course_name'];
            if (!empty($row['teacher_name'])) {
                $exam .= ' - ' . $row['teacher_name'];
            }

            $format = !empty($row['file_type']) ? strtoupper($row['file_type']) : 'TEXT';

            $recentNotes[] = [
                "title"      => $row["title"],
                "course"     => $row["course_name"],
                "exam"       => $exam,
                "author"     => $row["author_name"] ?: "Anonimo",
                "date"       => $row["note_date"],
                "format"     => $format,
                "views"      => (int)$row["vote_count"],
                "downloads"  => 0,
            ];
        }
        mysqli_stmt_close($stmt);
    }
} else {
    // nessuna ricerca / nessun filtro → ultimi 3 appunti
    $sqlBase .= " LIMIT 3";

    $stmt = mysqli_prepare($conn, $sqlBase);
    if ($stmt) {
        if (!empty($params)) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res)) {

            $exam = $row['course_name'];
            if (!empty($row['teacher_name'])) {
                $exam .= ' - ' . $row['teacher_name'];
            }

            $format = !empty($row['file_type']) ? strtoupper($row['file_type']) : 'TEXT';

            $recentNotes[] = [
                "title"      => $row["title"],
                "course"     => $row["course_name"],
                "exam"       => $exam,
                "author"     => $row["author_name"] ?: "Anonimo",
                "date"       => $row["note_date"],
                "format"     => $format,
                "views"      => (int)$row["vote_count"],
                "downloads"  => 0,
            ];
        }
        mysqli_stmt_close($stmt);
    }
}

// -------------------------
// 5) Corsi popolari (sidebar)
// -------------------------
$popularCourses = [];

$sqlCourses = "
    SELECT c.name, COUNT(n.id) AS notes_count
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
        $popularCourses[] = $row['name'];
    }
}

// helper per costruire URL di paginazione mantenendo i filtri
function buildPageUrl($page, $searchTerm, $courseFilter, $sort) {
    $params = [
        'page' => 'home',
        'p'    => $page,
    ];
    if ($searchTerm !== '') {
        $params['q'] = $searchTerm;
    }
    if (!is_null($courseFilter)) {
        $params['course_id'] = $courseFilter;
    }
    if ($sort !== '' && $sort !== 'date') {
        $params['sort'] = $sort;
    }
    return 'index.php?' . http_build_query($params);
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
                    <option value="">All courses</option>
                    <?php foreach ($courseOptions as $course): ?>
                        <option value="<?php echo (int)$course['id']; ?>"
                            <?php echo ($courseFilter === (int)$course['id']) ? 'selected' : ''; ?>>
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
                <?php if ($totalPages > 1): ?>
                    (Page <?php echo $currentPage; ?> di <?php echo $totalPages; ?>)
                <?php endif; ?>
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
                                    <a href="#" class="small text-primary text-decoration-none">
                                        <h2 class="h5 mb-1">
                                            <!--Note title -->
                                            <?php echo htmlspecialchars($note["title"]); ?>
                                        </h2>
                                    </a>

                                    <p class="mb-0 text-muted">
                                        <!-- Course of the note -->
                                        <?php echo htmlspecialchars($note["course"]); ?>
                                    </p>

                                </div>

                                <span class="badge rounded-pill bg-light text-primary fw-semibold">
                                    <?php echo htmlspecialchars($note["format"]); ?>
                                </span>
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
                                        ⬇ <?php echo (int)$note["downloads"]; ?>
                                    </span>
                                    <span class="small text-muted">
                                        👀 <?php echo (int)$note["views"]; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Paginazione -->
                <?php if ($searchTerm !== '' || !is_null($courseFilter)): ?>
                    <?php if ($totalPages > 1): ?>
                        <nav aria-label="Paginazione appunti">
                            <ul class="pagination mt-3">
                                <li class="page-item <?php echo $currentPage <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link"
                                       href="<?php echo $currentPage > 1 ? buildPageUrl($currentPage - 1, $searchTerm, $courseFilter, $sort) : '#'; ?>">
                                        « Previous
                                    </a>
                                </li>

                                <li class="page-item disabled">
                                    <span class="page-link">
                                        Page <?php echo $currentPage; ?> di <?php echo $totalPages; ?>
                                    </span>
                                </li>

                                <li class="page-item <?php echo $currentPage >= $totalPages ? 'disabled' : ''; ?>">
                                    <a class="page-link"
                                       href="<?php echo $currentPage < $totalPages ? buildPageUrl($currentPage + 1, $searchTerm, $courseFilter, $sort) : '#'; ?>">
                                        Next »
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
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

                <?php if (empty($courseOptions)): ?>
                    <p class="text-muted">There are no courses with notes yet.</p>
                <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($courseOptions as $course): ?>
                            <div class="col-12 col-md-6 col-lg-4">
                                <div class="card border-0 shadow-sm rounded-4 h-100">
                                    <div class="card-body d-flex flex-column">
                                        <h3 class="h6 mb-1">
                                            <?php echo htmlspecialchars($course['name']); ?>
                                        </h3>
                                        <p class="text-muted small mb-3">
                                            Study programme course
                                        </p>

                                        <div class="mt-auto d-flex justify-content-between align-items-center">
                                            <a
                                                href="<?php echo buildPageUrl(1, $searchTerm, (int)$course['id'], $sort); ?>"
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