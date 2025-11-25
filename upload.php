
<?php

// PRogramme
$programmeFilter = isset($_GET['programme_id']) && $_GET['programme_id'] !== '' ? (int)$_GET['programme_id'] : null;
$programmeOptions = [];
$sqlProgrammeOptions = "
  SELECT DISTINCT p.id, p.name
    FROM programme p
    ORDER BY p.name
";

$resultProgrammeOptions = mysqli_query($conn, $sqlProgrammeOptions);
if ($resultProgrammeOptions) {
  while ($row = mysqli_fetch_assoc($resultProgrammeOptions)) {
    $programmeOptions[] = $row;
  }
}

// COurse
$courseFilter = isset($_GET['course_id']) && $_GET['course_id'] !== '' ? (int)$_GET['course_id'] : null;

$courseOptions = [];
$sqlCourseOptions = "
    SELECT DISTINCT c.id, c.name
    FROM course c
    JOIN course_offering co ON co.course_id = c.id
    JOIN topic t ON t.offering_id = co.id
    JOIN note n ON n.topic_id = t.id AND n.status = 'published'
    ORDER BY c.name
";
$resultCourseOptions = mysqli_query($conn, $sqlCourseOptions);
if ($resultCourseOptions) {
    while ($row = mysqli_fetch_assoc($resultCourseOptions)) {
        $courseOptions[] = $row;
    }
}

// Teacher
$teacherFilter = isset($_GET['person_id']) && $_GET['person_id'] !== '' ? (int)$_GET['person_id'] : null;


$teacherOptions = [];
$sqlTeacherOptions = "
    SELECT DISTINCT p.id,
           CONCAT(p.name, ' ', p.surname) AS full_name
    FROM person p
    JOIN teacher t ON t.person_id = p.id
    ORDER BY p.surname, p.name
";

$resultTeacherOptions = mysqli_query($conn, $sqlTeacherOptions);
if ($resultTeacherOptions) {
    while ($row = mysqli_fetch_assoc($resultTeacherOptions)) {
        $teacherOptions[] = $row;
    }
}

?>

<!-- PAGINA CARICA APPUNTI (CONTENUTO CENTRATO) -->
<main class="upload-page py-4 py-md-5">
  <div class="container d-flex flex-column align-items-center">

    <!-- Titolo + sottotitolo, centrati -->
    <div class="upload-header text-center mb-4 mb-md-5">
      <h1 class="h4 mb-1">Upload Notes</h1>
      <p class="text-muted small mb-0">
        Share your notes with other students.
      </p>
    </div>

    <!-- Card centrale con il form -->
    <div class="upload-card p-4 p-lg-4">
      <!-- Quando avrai il backend, metti action="upload.php" -->
      <form
        id="noteForm"
        action="upload.php"
        method="POST"
        enctype="multipart/form-data"
        class="row g-3"
      >
        <!-- Titolo -->
        <div class="col-12">
          <label class="form-label">Title *</label>
          <input
            type="text"
            name="title"
            class="form-control"
            placeholder="Eg. Appunti Lezione 3 - Algoritmi"
            required
          />
        </div>

        <!-- Facoltà -->
        <div class="col-12 col-md-6">
          <label class="form-label">Programme *</label>
          <select class="form-select" name="programme" required>
            <option value="" disabled selected>Select programme</option>
            <!-- TODO: prendili dal db -->
             <?php foreach ($programmeOptions as $programme): ?>
              <option value="<?php echo (int)$programme['id'] ?>">
                <?php echo ($programmeFilter === (int)$programme['id']) ? 'selected' : ''; ?>
                <?php echo htmlspecialchars($programme['name']) ?>
              </option>
              <?php endforeach; ?>
          </select>
        </div>

        <!-- Corso (Esame) -->
        <div class="col-12 col-md-6">
          <label class="form-label">Course (Exam) *</label>
          <select class="form-select" name="course" required>
            <option value="" disabled selected>Select course</option>
            <?php foreach ($courseOptions as $course): ?>
                <option value="<?php echo (int)$course['id']; ?>"
                    <?php echo ($courseFilter === (int)$course['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($course['name']); ?>
                </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Docente -->
        <div class="col-12 col-md-6">
          <label class="form-label">Teacher *</label>
          <select class="form-select" name="teacher" required>
            <option value="" disabled selected>Select teacher</option>
            <?php foreach ($teacherOptions as $teacher): ?>
                <option value="<?php echo (int)$teacher['id']; ?>"
                    <?php echo ($teacherFilter === (int)$teacher['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($teacher['full_name']); ?>
                </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Tipo di File (3 bottoni) -->
        <div class="col-12 col-md-6">
          <label class="form-label d-block">File type *</label>
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
              <span class="small">Photo</span>
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
              <span class="small">Text</span>
            </label>
          </div>
        </div>

        <!-- Dropzone File -->
        <div class="col-12">
          <label class="form-label">File *</label>
          <div class="dropzone mb-2">
            <div class="dropzone-icon mb-2">
              <i class="bi bi-cloud-arrow-up"></i>
            </div>
            <p class="mb-1">Click or drag file here to upload it.</p>
            <p class="text-muted small mb-0">PDF up to 10MB</p>
          </div>
          <!-- input reale -->
          <input
            type="file"
            class="form-control"
            name="file_appunto"
            required
          />
        </div>

        <!-- Descrizione -->
        <div class="col-12">
          <label class="form-label">Description (Optional)</label>
          <textarea
            class="form-control"
            name="descrizione"
            rows="4"
            placeholder="Add a short description of your note..."
          ></textarea>
        </div>

        <!-- Pulsante Carica -->
        <div class="col-12 mt-2">
          <button
            type="submit"
            id="uploadBtn"
            class="btn btn-primary text-white w-100 py-2 d-flex justify-content-center align-items-center gap-2">
            <i class="bi bi-upload"></i>
            Upload notes
          </button>
        </div>
      </form>
    </div>
  </div>
</main>
