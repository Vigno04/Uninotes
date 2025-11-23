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

    <!-- Card centrale con il form -->
    <div class="upload-card p-4 p-lg-5">
      <!-- Quando avrai il backend, metti action="upload.php" -->
      <form
        action="#"
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

        <!-- Facoltà -->
        <div class="col-12 col-md-6">
          <label class="form-label">Facoltà *</label>
          <select class="form-select" name="facolta" required>
            <option value="" disabled selected>Seleziona facoltà</option>
            <option>Ingegneria e Scienze Informatiche</option>
            <option>Informatica</option>
            <option>Ingegneria Informatica</option>
            <option>Fisica</option>
            <option>Giurisprudenza</option>
            <option>Economia</option>
          </select>
        </div>

        <!-- Corso (Esame) -->
        <div class="col-12 col-md-6">
          <label class="form-label">Corso (Esame) *</label>
          <select class="form-select" name="corso" required>
            <option value="" disabled selected>Seleziona corso</option>
            <option>Algoritmi e Strutture Dati</option>
            <option>Analisi Matematica I</option>
            <option>Basi di Dati</option>
            <option>Programmazione Funzionale</option>
            <option>Circuiti Digitali</option>
          </select>
        </div>

        <!-- Docente -->
        <div class="col-12 col-md-6">
          <label class="form-label">Docente *</label>
          <select class="form-select" name="docente" required>
            <option value="" disabled selected>Seleziona docente</option>
            <option>Prof. Rossi</option>
            <option>Prof. Bianchi</option>
            <option>Prof. Verdi</option>
            <option>Prof.ssa Neri</option>
          </select>
        </div>

        <!-- Tipo di File (3 bottoni) -->
        <div class="col-12 col-md-6">
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
          <div class="dropzone mb-2">
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
