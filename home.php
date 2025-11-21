<?php
// home.php

// Assumo che la sessione sia già partita in bootstrap.php
$isAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

// Per ora dati finti; poi li prenderai dal DB
$recentNotes = [
    [
        "title"      => "Introduzione agli Algoritmi",
        "course"     => "Ingegneria e Scienze Informatiche",
        "exam"       => "Algoritmi e Strutture Dati - Prof. Rossi",
        "author"     => "Mario Rossi",
        "date"       => "2025-11-10",
        "format"     => "PDF",
        "views"      => 45,
        "downloads"  => 12,
    ],
    [
        "title"      => "Circuiti Digitali - Lezione 5",
        "course"     => "Ingegneria e Scienze Informatiche",
        "exam"       => "Elettronica Digitale - Prof. Bianchi",
        "author"     => "Laura Bianchi",
        "date"       => "2025-11-09",
        "format"     => "PDF",
        "views"      => 32,
        "downloads"  => 8,
    ],
    [
        "title"      => "Programmazione Funzionale in Haskell",
        "course"     => "Ingegneria e Scienze Informatiche",
        "exam"       => "Programmazione Funzionale - Prof. Verdi",
        "author"     => "Giuseppe Verdi",
        "date"       => "2025-11-08",
        "format"     => "TEXT",
        "views"      => 28,
        "downloads"  => 5,
    ],
];

// opzionale: corsi popolari per la colonna a destra
$popularCourses = [
    "Algoritmi e Strutture Dati",
    "Analisi Matematica I",
    "Basi di Dati",
    "Fisica Generale I",
];
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

        </div>

        <!-- Colonna laterale (corsi popolari) -->
        <div class="col-lg-3 mt-4 mt-lg-0">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body">
                    <h2 class="h6 mb-3">Corsi popolari</h2>
                    <ul class="list-unstyled mb-0">
                        <?php foreach ($popularCourses as $course): ?>
                            <li class="mb-2">
                                <a href="#" class="text-decoration-none">
                                    <?php echo htmlspecialchars($course); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
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
