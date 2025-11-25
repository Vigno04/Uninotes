<?php
require_once "bootstrap.php";

// For now this page only renders the layout (no DB logic)

// Detect if we are editing an existing note via GET parameter
$noteId   = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : null;
$mode     = $noteId ? 'edit' : 'create';

// Placeholder values for now (no DB)
$selectedCourse        = '';
$selectedCourseOffering = '';
$selectedTopic         = '';
$titleValue            = '';
$contentValue          = '';

$courseOfferings = [
    '1' => 'A.A. 2024/2025 - Primo semestre',
    '2' => 'A.A. 2024/2025 - Secondo semestre',
];

$topics = [
    '1' => 'Introduzione',
    '2' => 'Strutture Dati',
    '3' => 'Algoritmi',
];

?>

<div class="row justify-content-center mt-4">
    <div class="col-12 col-lg-10 col-xl-9">
        <h1 class="h4 mb-3">
            <?php echo $mode === 'edit' ? 'Modifica nota' : 'Crea nuova nota'; ?>
        </h1>
        <p class="text-muted small mb-4">
            Seleziona il corso e l'argomento, poi scrivi il contenuto in Markdown.
        </p>

        <div class="card">
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <!-- Se in futuro servirà distinguere create/edit -->
                    <?php if ($noteId): ?>
                        <input type="hidden" name="note_id" value="<?php echo htmlspecialchars((string)$noteId); ?>">
                    <?php endif; ?>

                    <!-- 2. Selezione erogazione del corso -->
                    <div class="mb-3">
                        <label for="course_offering" class="form-label">Corso *</label>
                        <select class="form-select" id="course_offering" name="course_offering" required>
                            <option value="" disabled <?php echo $selectedCourseOffering === '' ? 'selected' : ''; ?>>Seleziona erogazione</option>
                            <?php foreach ($courseOfferings as $id => $label): ?>
                                <option value="<?php echo htmlspecialchars($id); ?>" <?php echo ($selectedCourseOffering === $id) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- 3. Selezione topic -->
                    <div class="mb-3">
                        <label for="topic" class="form-label">Argomento *</label>
                        <select class="form-select" id="topic" name="topic" required>
                            <option value="" disabled <?php echo $selectedTopic === '' ? 'selected' : ''; ?>>Seleziona argomento</option>
                            <?php foreach ($topics as $id => $label): ?>
                                <option value="<?php echo htmlspecialchars($id); ?>" <?php echo ($selectedTopic === $id) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <hr class="my-4">

                    <!-- Titolo nota -->
                    <div class="mb-3">
                        <label for="title" class="form-label">Titolo *</label>
                        <input
                            type="text"
                            class="form-control"
                            id="title"
                            name="title"
                            value="<?php echo htmlspecialchars($titleValue); ?>"
                            placeholder="Es. Appunti Lezione 3 - Algoritmi"
                            required
                        >
                    </div>

                    <!-- Contenuto nota (Markdown) -->
                    <div class="mb-3">
                        <label for="content" class="form-label">Contenuto (Markdown) *</label>
                        <textarea
                            class="form-control"
                            id="content"
                            name="content"
                            rows="10"
                            placeholder="Scrivi qui i tuoi appunti in linguaggio Markdown..."
                            required
                        ><?php echo htmlspecialchars($contentValue); ?></textarea>
                    </div>

                    <!-- Upload file allegati -->
                    <div class="mb-3">
                        <label for="attachments" class="form-label">File allegati</label>
                        <input
                            type="file"
                            class="form-control"
                            id="attachments"
                            name="attachments[]"
                            multiple
                        >
                        <div class="form-text">
                            Dopo aver caricato i file, potrai riferirti ad essi nel testo Markdown usando il nome del file.
                            Ad esempio: <code>![Descrizione immagine](nomefile.png)</code> oppure <code>[Scarica file](documento.pdf)</code>.
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Piccola guida Markdown -->
                    <div class="mb-3">
                        <h2 class="h6 mb-2">Guida rapida Markdown</h2>
                        <div class="row small">
                            <div class="col-12 col-md-6 mb-2">
                                <strong>Titoli</strong><br>
                                <code># Titolo 1</code><br>
                                <code>## Titolo 2</code><br>
                                <code>### Titolo 3</code>
                            </div>
                            <div class="col-12 col-md-6 mb-2">
                                <strong>Testo</strong><br>
                                <code>**grassetto**</code><br>
                                <code>*corsivo*</code><br>
                                <code>~~barrato~~</code>
                            </div>
                            <div class="col-12 col-md-6 mb-2">
                                <strong>Liste</strong><br>
                                <code>- elemento</code><br>
                                <code>1. elemento numerato</code>
                            </div>
                            <div class="col-12 col-md-6 mb-2">
                                <strong>Link e immagini</strong><br>
                                <code>[testo link](https://esempio.com)</code><br>
                                <code>![alt immagine](immagine.png)</code>
                            </div>
                            <div class="col-12 col-md-6 mb-2">
                                <strong>Codice</strong><br>
                                <code>`inline`</code><br>
                                <code>```<br>blocco di codice<br>```</code>
                            </div>
                        </div>
                    </div>

                    <!-- Solo pulsanti visuali per ora (no submit reale) -->
                    <div class="d-flex justify-content-end gap-2 mt-3">
                        <a href="index.php?page=home" class="btn btn-outline-secondary">Annulla</a>
                        <button type="button" class="btn btn-primary" disabled>Tieni premuto per salvare (solo layout)</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
