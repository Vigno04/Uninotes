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
    $noteModel = new NoteModel();
    $offeringInfo = $noteModel->getOfferingInfo($offeringFilter);
}

// -------------------------
// 1) Offering disponibili per filtro
// -------------------------
$allOfferingOptions = [];

if ($currentUserId !== null) {
    $courseModel = new CourseModel();
    $allOfferingOptions = $courseModel->getUserFollowedOfferingsForHome($currentUserId);
    
    // Add display_name to each offering
    foreach ($allOfferingOptions as &$row) {
        $semesterLabel = $row['semester'] === '1' ? '1st' : '2nd';
        $row['display_name'] = $row['course_name'] . ' (' . $row['year'] . ' - ' . $semesterLabel . ' Sem)';
    }
    unset($row);
}


// -------------------------
// 2) Costruisco la query base (senza LIMIT) e ottengo note filtrate
// -------------------------
if (!isset($noteModel)) {
    $noteModel = new NoteModel();
}

// -------------------------
// 3) Conteggio totale SOLO se sto cercando/filtrando
// -------------------------
$totalResults = 0;

if ($searchTerm !== '' || !is_null($offeringFilter) || $filterMyCourses) {
    $totalResults = $noteModel->countFilteredNotes($offeringFilter, $currentUserId, $filterMyCourses, $searchTerm);
}

// -------------------------
// 4) Query effettiva per le note (con LIMIT)
// -------------------------
$recentNotes = [];
$notesData = $noteModel->getFilteredNotes($offeringFilter, $currentUserId, $filterMyCourses, $searchTerm, $sort, $limit);

foreach ($notesData as $row) {
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

// -------------------------
// 5) Corsi popolari (sidebar / All courses)
// -------------------------
if (!isset($courseModel)) {
    $courseModel = new CourseModel();
}
$popularCoursesData = $courseModel->getPopularCourses(10);
$popularCourses = array_column($popularCoursesData, 'name');
?>

<div class="container py-4">

    <!-- Header + barra di ricerca + filtri -->
    <div class="mb-4">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-3">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-book" style="font-size: 1.5rem; color: #2563eb;" aria-hidden="true"></i>
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
                <label for="home-search" class="visually-hidden">Search notes</label>
                <input
                    id="home-search"
                    type="text"
                    class="form-control"
                    name="q"
                    placeholder="Search by title or content..."
                    value="<?php echo htmlspecialchars($searchTerm); ?>"
                >
            </div>

            <div class="col-6 col-lg-2">
                <label for="offering-select" class="visually-hidden">Filter by offering</label>
                <select id="offering-select" name="offering_id" class="form-select">
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
                <label for="sort-select" class="visually-hidden">Sort results</label>
                <select id="sort-select" name="sort" class="form-select">
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
                                    <a href="index.php?page=note_view&id=<?php echo htmlspecialchars($note['id']); ?>" 
                                       class="small text-primary text-decoration-none"
                                       aria-label="View note: <?php echo htmlspecialchars($note['title']); ?>">
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
                                        <i class="bi bi-person" aria-hidden="true"></i> <?php echo htmlspecialchars($note["author"]); ?>
                                    </span>
                                    <span>
                                        <i class="bi bi-calendar3" aria-hidden="true"></i> 
                                        <?php
                                        $date = date("d M Y", strtotime($note["date"]));
                                        echo htmlspecialchars($date);
                                        ?>
                                    </span>
                                </div>

                                <div class="d-flex align-items-center gap-3">
                                    <span class="small text-muted">
                                        <i class="bi bi-arrow-up-circle" aria-hidden="true"></i> <?php echo (int)$note["likes"]; ?>
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
                        <a class="btn btn-outline-primary" 
                           href="<?php echo htmlspecialchars($loadMoreUrl); ?>"
                           aria-label="Load more notes">
                            Load more
                        </a>
                    </div>
                <?php endif; ?>



            <?php endif; ?>
        </div>
    </div>
</div>
