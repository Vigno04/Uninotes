<?php
// filepath: c:\xampp\htdocs\Uninotes\upload.php
require_once("bootstrap.php");
require_once 'db/db.php';

// ===========================
// Controllo autenticazione
// ===========================
if (!isset($_SESSION['person_id'])) {
    header("Location: index.php?page=login");
    exit;
}

$personId = $_SESSION['person_id'];
$uploadMessage = '';
$uploadStatus = ''; // 'success' o 'error'

// ===========================
// DIRECTORY UPLOAD
// ===========================
$uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'upload' . DIRECTORY_SEPARATOR . 'notes' . DIRECTORY_SEPARATOR;
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// ===========================
// POST HANDLER - Elabora upload
// ===========================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['titolo'] ?? '');
    $course_id = (int)($_POST['course_id'] ?? 0);
    $topic_id = (int)($_POST['topic_id'] ?? 0);
    $file_format = trim($_POST['tipo_file'] ?? '');
    $description = trim($_POST['descrizione'] ?? '');

    // Validazione campi obbligatori
    if (empty($title)) {
        $uploadMessage = 'Il titolo è obbligatorio.';
        $uploadStatus = 'error';
    } elseif ($course_id <= 0) {
        $uploadMessage = 'Seleziona un corso valido.';
        $uploadStatus = 'error';
    } elseif ($topic_id <= 0) {
        $uploadMessage = 'Seleziona un argomento valido.';
        $uploadStatus = 'error';
    } elseif (empty($file_format)) {
        $uploadMessage = 'Seleziona un formato file.';
        $uploadStatus = 'error';
    } elseif (empty($_FILES['file_appunto']) || $_FILES['file_appunto']['error'] !== UPLOAD_ERR_OK) {
        $uploadMessage = 'Errore nel caricamento del file.';
        $uploadStatus = 'error';
    } else {
        // ===========================
        // Validazione file
        // ===========================
        $file = $_FILES['file_appunto'];
        $maxSize = 10 * 1024 * 1024; // 10 MB
        $allowedMimes = [
            'pdf' => ['application/pdf'],
            'immagine' => ['image/jpeg', 'image/png', 'image/webp'],
            'testo' => ['text/plain', 'text/markdown', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']
        ];

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        // Verifica MIME type in base al formato selezionato
        if (!isset($allowedMimes[$file_format]) || !in_array($mime, $allowedMimes[$file_format])) {
            $uploadMessage = "MIME type non consentito per il formato {$file_format}. Tipo rilevato: {$mime}";
            $uploadStatus = 'error';
        } elseif ($file['size'] > $maxSize) {
            $uploadMessage = 'File troppo grande. Massimo 10MB.';
            $uploadStatus = 'error';
        } else {
            // ===========================
            // Salva file su disco
            // ===========================
            $originalName = basename($file['name']);
            $ext = pathinfo($originalName, PATHINFO_EXTENSION);
            $safeFilename = 'note_' . $personId . '_' . time() . '.' . $ext;
            $destination = $uploadDir . $safeFilename;

            if (move_uploaded_file($file['tmp_name'], $destination)) {
                // ===========================
                // Leggi contenuto file (per testo/markdown)
                // ===========================
                $fileContent = '';
                if (in_array($mime, ['text/plain', 'text/markdown'])) {
                    $fileContent = file_get_contents($destination);
                }

                // ===========================
                // Insert nel DB
                // ===========================
                $storagePath = 'upload/notes/' . $safeFilename;
                $status = 'published';

                try {
                    $pdo = Database::getInstance();

                    // 1) Inserisci nota
                    $noteStmt = $pdo->prepare("
                        INSERT INTO note (owner_id, topic_id, title, content, status, published_at, created_at)
                        VALUES (?, ?, ?, ?, ?, NOW(), NOW())
                    ");
                    $noteStmt->execute([$personId, $topic_id, $title, $fileContent, $status]);
                    $noteId = $pdo->lastInsertId();

                    // 2) Inserisci file
                    $fileStmt = $pdo->prepare("
                        INSERT INTO file (note_id, filename, storage_path, file_type, file_size, mime_type, uploaded_by)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    $fileStmt->execute([
                        $noteId,
                        $originalName,
                        $storagePath,
                        $file_format,
                        $file['size'],
                        $mime,
                        $personId
                    ]);

                    $uploadMessage = 'Appunti caricati con successo!';
                    $uploadStatus = 'success';

                } catch (Exception $e) {
                    $uploadMessage = 'Errore durante il salvataggio nel database: ' . $e->getMessage();
                    $uploadStatus = 'error';
                    // Elimina il file caricato
                    @unlink($destination);
                }
            } else {
                $uploadMessage = 'Errore nel salvataggio del file sul server.';
                $uploadStatus = 'error';
            }
        }
    }
}

// ===========================
// Carica dati per dropdown
// ===========================
$courses = [];
$topics = [];

try {
    $pdo = Database::getInstance();

    // Carica corsi disponibili
    $courseStmt = $pdo->query("
        SELECT DISTINCT c.id, c.name
        FROM course c
        JOIN course_offering co ON co.course_id = c.id
        JOIN topic t ON t.offering_id = co.id
        ORDER BY c.name
    ");
    $courses = $courseStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $uploadMessage = 'Errore nel caricamento dei dati: ' . $e->getMessage();
    $uploadStatus = 'error';
}

// Carica topic in base al corso selezionato (via AJAX, ma mostriamo logica)
$selectedCourseId = $_POST['course_id'] ?? ($_GET['course_id'] ?? 0);
if ($selectedCourseId > 0) {
    try {
        $pdo = Database::getInstance();
        $topicStmt = $pdo->prepare("
            SELECT DISTINCT t.id, t.name
            FROM topic t
            JOIN course_offering co ON t.offering_id = co.id
            WHERE co.course_id = ?
            ORDER BY t.name
        ");
        $topicStmt->execute([$selectedCourseId]);
        $topics = $topicStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // fallback
    }
}

// ===========================
// AJAX endpoint: get_topics
// ===========================
if (isset($_GET['action']) && $_GET['action'] === 'get_topics' && isset($_GET['course_id'])) {
    header('Content-Type: application/json');
    $courseId = (int)$_GET['course_id'];

    try {
        $pdo = Database::getInstance();
        // La query CORRETTA: course → course_offering → topic
        $stmt = $pdo->prepare("
            SELECT t.id, t.name
            FROM topic t
            INNER JOIN course_offering co ON t.offering_id = co.id
            WHERE co.course_id = ?
            ORDER BY t.order_index, t.name
        ");
        $stmt->execute([$courseId]);
        $topics = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($topics);
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}
?>

<!-- PAGINA CARICA APPUNTI (CONTENUTO CENTRATO) -->
<main class="upload-page py-4 py-md-5">
  <div class="container d-flex flex-column align-items-center">

    <!-- Titolo + sottotitolo, centrati -->
    <div class="upload-header text-center mb-4 mb-md-5">
      <h1 class="h4 mb-1">Carica Appunti</h1>
      <p class="text-muted small mb-0">
        Condividi i tuoi appunti con altri studenti.
      </p>
    </div>

    <!-- Messaggi feedback -->
    <?php if (!empty($uploadMessage)): ?>
        <div class="alert alert-<?php echo $uploadStatus === 'success' ? 'success' : 'danger'; ?> w-100 mb-3" role="alert" style="max-width: 600px;">
            <?php echo htmlspecialchars($uploadMessage); ?>
        </div>
    <?php endif; ?>

    <!-- Card centrale con il form -->
    <div class="upload-card p-4 p-lg-5">
      <form
        action="index.php?page=upload"
        method="POST"
        enctype="multipart/form-data"
        class="row g-3"
      >
        <!-- Titolo -->
        <div class="col-12">
          <label class="form-label">Titolo *</label>
          <input
            type="text"
            name="titolo"
            class="form-control"
            placeholder="Es. Appunti Lezione 3 - Algoritmi"
            required
          />
        </div>

        <!-- Corso (Esame) - Dinamico -->
        <div class="col-12 col-md-6">
          <label class="form-label">Corso (Esame) *</label>
          <select class="form-select" name="course_id" id="courseSelect" required>
            <option value="" disabled selected>Seleziona corso</option>
            <?php foreach ($courses as $c): ?>
                <option value="<?php echo (int)$c['id']; ?>" 
                    <?php if ($selectedCourseId === (int)$c['id']) echo 'selected'; ?>>
                    <?php echo htmlspecialchars($c['name']); ?>
                </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Argomento/Topic - HARDCODED per ora -->
        <div class="col-12 col-md-6">
          <label class="form-label">Argomento *</label>
          <select class="form-select" name="topic_id" id="topicSelect" required>
            <option value="" disabled selected>Seleziona argomento</option>
            <option value="17">Introduzione agli Algoritmi</option>
            <option value="18">Ricerca e Ordinamento</option>
            <option value="19">Grafi e Alberi</option>
            <option value="20">Modello Relazionale</option>
            <option value="21">SQL Base</option>
            <option value="22">Normalizzazione</option>
            <option value="23">Introduzione al Paradigma Funzionale</option>
            <option value="24">Ricorsione e Pattern Matching</option>
            <option value="25">Porte Logiche</option>
            <option value="26">Circuiti Combinatori</option>
          </select>
        </div>

        <!-- Tipo di File (3 bottoni) -->
        <div class="col-12">
          <label class="form-label d-block">Tipo di File *</label>
          <div class="btn-group w-100 filetype-toggle" role="group">
            <input
              type="radio"
              class="btn-check"
              name="tipo_file"
              id="filePdf"
              value="pdf"
              checked
            />
            <label
              class="btn btn-outline-primary d-flex flex-column align-items-center py-2"
              for="filePdf"
            >
              <i class="bi bi-file-earmark-pdf mb-1"></i>
              <span class="small">PDF</span>
            </label>

            <input
              type="radio"
              class="btn-check"
              name="tipo_file"
              id="fileImg"
              value="immagine"
            />
            <label
              class="btn btn-outline-primary d-flex flex-column align-items-center py-2"
              for="fileImg"
            >
              <i class="bi bi-image mb-1"></i>
              <span class="small">Immagine</span>
            </label>

            <input
              type="radio"
              class="btn-check"
              name="tipo_file"
              id="fileTxt"
              value="testo"
            />
            <label
              class="btn btn-outline-primary d-flex flex-column align-items-center py-2"
              for="fileTxt"
            >
              <i class="bi bi-file-earmark-text mb-1"></i>
              <span class="small">Testo</span>
            </label>
          </div>
        </div>

        <!-- Dropzone File -->
        <div class="col-12">
          <label class="form-label">File *</label>
          <div class="dropzone mb-2" id="dropzoneArea">
            <div class="dropzone-icon mb-2">
              <i class="bi bi-cloud-arrow-up"></i>
            </div>
            <p class="mb-1">Clicca per caricare o trascina il file qui</p>
            <p class="text-muted small mb-0">PDF fino a 10MB</p>
          </div>
          <!-- input reale -->
          <input
            type="file"
            class="form-control"
            name="file_appunto"
            id="fileInput"
            required
          />
        </div>

        <!-- Descrizione -->
        <div class="col-12">
          <label class="form-label">Descrizione (Opzionale)</label>
          <textarea
            class="form-control"
            name="descrizione"
            rows="4"
            placeholder="Aggiungi una breve descrizione degli appunti..."
          ></textarea>
        </div>

        <!-- Pulsante Carica -->
        <div class="col-12 mt-2">
          <button
            type="submit"
            class="btn btn-upload text-white w-100 py-2 d-flex justify-content-center align-items-center gap-2"
          >
            <i class="bi bi-upload"></i>
            Carica Appunti
          </button>
        </div>
      </form>
    </div>
  </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const courseSelect = document.getElementById('courseSelect');
    const topicSelect = document.getElementById('topicSelect');
    const fileInput = document.getElementById('fileInput');
    const dropzoneArea = document.getElementById('dropzoneArea');

    // ===========================
    // Carica topic dinamicamente quando cambio corso
    // ===========================
    if (courseSelect) {
        courseSelect.addEventListener('change', function() {
            const courseId = this.value;
            if (!courseId) {
                topicSelect.innerHTML = '<option value="" disabled selected>Seleziona argomento</option>';
                return;
            }

            // Fetch topics via AJAX
            fetch('index.php?page=upload&action=get_topics&course_id=' + courseId)
                .then(response => {
                    console.log('Response status:', response.status);
                    if (!response.ok) throw new Error('HTTP error, status = ' + response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('Topics ricevuti:', data);
                    if (data.error) {
                        console.error('Error dal server:', data.error);
                        return;
                    }
                    topicSelect.innerHTML = '<option value="" disabled selected>Seleziona argomento</option>';
                    data.forEach(topic => {
                        const option = document.createElement('option');
                        option.value = topic.id;
                        option.textContent = topic.name;
                        topicSelect.appendChild(option);
                    });
                })
                .catch(error => console.error('Fetch error:', error));
        });
    }

    // ===========================
    // Dropzone drag-and-drop
    // ===========================
    if (dropzoneArea && fileInput) {
        dropzoneArea.addEventListener('click', () => fileInput.click());

        dropzoneArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropzoneArea.style.backgroundColor = '#f0f0f0';
        });

        dropzoneArea.addEventListener('dragleave', () => {
            dropzoneArea.style.backgroundColor = 'transparent';
        });

        dropzoneArea.addEventListener('drop', (e) => {
            e.preventDefault();
            dropzoneArea.style.backgroundColor = 'transparent';
            if (e.dataTransfer.files.length > 0) {
                fileInput.files = e.dataTransfer.files;
                // mostra il nome del file
                const fileName = e.dataTransfer.files[0].name;
                dropzoneArea.querySelector('p').textContent = 'File: ' + fileName;
            }
        });

        fileInput.addEventListener('change', () => {
            if (fileInput.files.length > 0) {
                const fileName = fileInput.files[0].name;
                dropzoneArea.querySelector('p').textContent = 'File: ' + fileName;
            }
        });
    }
});
</script>
