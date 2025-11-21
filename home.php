<?php
// home.php

// Se qualcuno apre direttamente home.php, rimandalo al router (opzionale ma carino)
if (basename($_SERVER['SCRIPT_NAME']) === 'home.php') {
    header('Location: index.php?page=home');
    exit;
}

// Assumo che la sessione sia già partita in bootstrap.php
$isAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

// Per ora dati finti; poi li prenderai dal DB
$recentNotes = [];

// Prendiamo le ultime 3 note pubblicate, con:
// - titolo nota
// - nome corso
// - nome topic (per eventuale dettaglio)
// - docente (se presente)
// - autore (owner della nota)
// - una info su formato file (se esiste un file collegato)
// - data = published_at o created_at

$sql = "
    SELECT
        n.id,
        n.title,
        n.vote_count,
        COALESCE(n.published_at, n.created_at) AS note_date,
        c.name AS course_name,
        t.name AS topic_name,
        -- docente (se c'è un teacher associato all'offering)
        CONCAT(pt.name, ' ', pt.surname) AS teacher_name,
        -- autore (owner della nota)
        CONCAT(po.name, ' ', po.surname) AS author_name,
        -- prendiamo il primo file associato (se c'è) per sapere il tipo
        f.file_type
    FROM note n
    JOIN topic t ON n.topic_id = t.id
    JOIN course_offering co ON t.offering_id = co.id
    JOIN course c ON co.course_id = c.id
    LEFT JOIN course_offering_teacher cot ON co.id = cot.offering_id
    LEFT JOIN teacher th ON cot.teacher_id = th.person_id
    LEFT JOIN person pt ON th.person_id = pt.id               -- docente
    LEFT JOIN user uo ON n.owner_id = uo.person_id
    LEFT JOIN person po ON uo.person_id = po.id               -- autore
    LEFT JOIN file f ON f.note_id = n.id
    WHERE n.status = 'published'
    GROUP BY n.id
    ORDER BY note_date DESC
    LIMIT 3
";
$result = mysqli_query($conn, $sql);

// TODO: ricontrolla
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        // costruisco la stringa "exam": Corso - Docente (se disponibile)
        $exam = $row['course_name'];
        if (!empty($row['teacher_name'])) {
            $exam .= ' - ' . $row['teacher_name'];
        }

        // format: uso file_type se presente, altrimenti TEXT
        $format = !empty($row['file_type']) ? strtoupper($row['file_type']) : 'TEXT';

        $recentNotes[] = [
            "title"      => $row["title"],
            "course"     => $row["course_name"],
            "exam"       => $exam,
            "author"     => $row["author_name"] ?: "Anonimo",
            "date"       => $row["note_date"],
            "format"     => $format,
            // per ora views/downloads sono placeholder;
            // più avanti potrai aggiungere colonne dedicate
            "views"      => (int)$row["vote_count"],   // ad es. usiamo i voti come "popolarità"
            "downloads"  => 0,
        ];
    }
}

// TODO: metti un qualche messaggio...
if (empty($recentNotes)) {
    $recentNotes = [];
}

// Corsi popolari per il sidebar
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
        // per semplicità, nel template usiamo solo il nome del corso
        $popularCourses[] = $row['name'];
    }
}

?>
<div class="container py-4">

    <!-- Header: titolo + eventuale badge Admin -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center gap-2">
            <span class="fs-4">📘</span>
            <div>
                <h1 class="h4 mb-0">Appunti Recenti</h1>
                <small class="text-muted">Ultimi materiali caricati sulla piattaforma</small>
            </div>
        </div>

        <?php if ($isAdmin): ?>
            <span class="badge bg-warning text-dark">
                Admin
            </span>
        <?php endif; ?>
    </div>

    <div class="row">
        <!-- Colonna principale con le card degli appunti -->
        <div class="col-lg-9">

            <?php if (empty($recentNotes)): ?>
                <p class="text-muted">Non ci sono ancora appunti pubblicati.</p>
            <?php else: ?>
                <?php foreach ($recentNotes as $note): ?>
                    <div class="card mb-3 border-0 shadow-sm rounded-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h2 class="h5 mb-1">
                                        <?php echo htmlspecialchars($note["title"]); ?>
                                    </h2>
                                    <p class="mb-0 text-muted">
                                        <?php echo htmlspecialchars($note["course"]); ?>
                                    </p>
                                    <p class="mb-2 text-primary small">
                                        <?php echo htmlspecialchars($note["exam"]); ?>
                                    </p>
                                </div>

                                <!-- Badge formato (PDF / TEXT / ecc.) -->
                                <span class="badge rounded-pill bg-light text-primary fw-semibold">
                                    <?php echo htmlspecialchars($note["format"]); ?>
                                </span>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                                <!-- Autore + Data -->
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

                                <!-- Azioni -->
                                <div class="d-flex align-items-center gap-3">
                                    <a href="#" class="text-decoration-none small">
                                        👁 Visualizza
                                    </a>
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
            <?php endif; ?>

        </div>

        <!-- Colonna laterale (corsi popolari) -->
        <div class="col-lg-3 mt-4 mt-lg-0">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body">
                    <h2 class="h6 mb-3">Corsi popolari</h2>
                    <?php if (empty($popularCourses)): ?>
                        <p class="text-muted small mb-0">Non ci sono ancora corsi con appunti.</p>
                    <?php else: ?>
                        <ul class="list-unstyled mb-0">
                            <?php foreach ($popularCourses as $course): ?>
                                <li class="mb-2">
                                    <a href="#" class="text-decoration-none">
                                        <?php echo htmlspecialchars($course); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Se sei admin puoi avere un pulsante extra -->
            <?php if ($isAdmin): ?>
                <div class="mt-3 d-grid">
                    <a href="index.php?page=adminaccount" class="btn btn-outline-primary btn-sm">
                        Vai al pannello Admin
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>