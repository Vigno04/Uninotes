<?php
require_once("bootstrap.php");

// Se non sei loggato → login
if (!isset($_SESSION['person_id'])) {
    header('Location: login.php');
    exit;
}

$personId = $_SESSION['person_id'];
$isAdmin  = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

$currentPage = $_GET['page'] ?? 'useraccount';

// Se sei admin e stai visitando ?page=adminaccount → modalità admin dashboard,
// altrimenti modalità profilo utente.
$mode = ($isAdmin && $currentPage === 'adminaccount') ? 'admin' : 'user';

// Per modificare le opzioni di profilo
$updateMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $mode === 'user') {
    // raccogli campi testuali
    $name = trim($_POST['name'] ??'');
    $surname = trim($_POST['surname'] ??'');
    $programme = trim($_POST['programme'] ?? '');
    $bio       = trim($_POST['bio'] ?? '');

    // --- GESTIONE UPLOAD FOTO ---
    $profilePictureUploaded = null;
    if (!empty($_FILES['profile_picture']) && ($_FILES['profile_picture']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        // assicurati che la cartella esista
        $profileDir = rtrim(UPLOAD_DIR, "/\\") . DIRECTORY_SEPARATOR . 'profile' . DIRECTORY_SEPARATOR;
        if (!is_dir($profileDir)) {
            mkdir($profileDir, 0755, true);
        }

        $file = $_FILES['profile_picture'];
        if ($file['error'] === UPLOAD_ERR_OK) {
            // validazioni
            $maxSize = 2 * 1024 * 1024; // 2 MB
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);

            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            if (!isset($allowed[$mime])) {
                $updateMessage = '<div id="serverMessage" class="alert alert-danger mb-3" role="alert" aria-live="polite">File type not allowed. Use JPG/PNG/WEBP.</div>';
            } elseif ($file['size'] > $maxSize) {
                $updateMessage = '<div id="serverMessage" class="alert alert-danger mb-3" role="alert" aria-live="polite">File too large. Max 2MB.</div>';
            } else {
                $ext = $allowed[$mime];
                $newName = 'profile_' . $personId . '_' . time() . '.' . $ext;
                $dest = $profileDir . $newName;
                if (move_uploaded_file($file['tmp_name'], $dest)) {
                    // salva solo il percorso (senza query) nel DB
                    $dbPath = 'uploads/profile/' . $newName;

                    $personModel = new PersonModel();
                    $personModel->updateProfilePicture($personId, $dbPath);

                    // imposta la variabile per il preview locale con bust di cache
                    $profilePicture = $dbPath . '?v=' . time();
                } else {
                    $updateMessage = '<div id="serverMessage" class="alert alert-danger mb-3" role="alert" aria-live="polite">Errore nel salvataggio del file.</div>';
                }
            }
        } else {
            $updateMessage = '<div id="serverMessage" class="alert alert-danger mb-3" role="alert" aria-live="polite">Errore upload file (code '.$file['error'].').</div>';
        }
    }

    // --- AGGIORNA CAMPI DI PROFILO (user) ---
    $userModel = new UserModel();
    $success = $userModel->updateUserProfile($personId, $programme, $bio);

    if ($success) {
        $updateMessage = ($updateMessage ?? '') . '<div id="serverMessage" class="alert alert-success mb-3" role="status" aria-live="polite">Profile updated successfully.</div>';
    } else {
        $updateMessage = ($updateMessage ?? '') . '<div id="serverMessage" class="alert alert-danger mb-3" role="alert" aria-live="polite">Error while updating profile.</div>';
    }
}



// -------------------------
// 1) Dati utente (comune ad admin e user)
// -------------------------
$personModel = new PersonModel();
$userModel = new UserModel();

$userData = $personModel->getPersonWithUserData($personId);

if (!$userData) {
    header('Location: logout.php');
    exit;
}

$fullName  = $userData['name'] . ' ' . $userData['surname'];
$name = $userData['name'];
$surname = $userData['surname'];
$email     = $userData['email'];
$role      = $userData['role'];     // dovrebbe coincidere con $_SESSION['role']
$programme  = $userData['programme'] ?? '';
$bio        = $userData['bio'] ?? '';
$created   = $userData['created_at'];
$lastLogin = $userData['last_login'];

// profile picture: se esiste il percorso in DB, bustiamo la cache con filemtime
$rawPic = trim((string)($userData['profile_picture'] ?? ''));
if ($rawPic !== '') {
    $serverPath = __DIR__ . '/' . $rawPic;
    if (file_exists($serverPath)) {
        $profilePicture = $rawPic . '?v=' . filemtime($serverPath);
    } else {
        // DB contiene percorso ma file non è presente: aggiungi timestamp per forzare reload
        $profilePicture = $rawPic . '?v=' . time();
    }
} else {
    $profilePicture = null; // userà le iniziali nella testata
}

// preview nel form (fallback placeholder)
$previewSrc = $profilePicture ?? 'https://via.placeholder.com/120?text=User';

$initials = strtoupper(
    mb_substr($userData['name'], 0, 1) . mb_substr($userData['surname'], 0, 1)
);

// -------------------------
// 2) Statistiche solo per modalità admin
// -------------------------
$stats = [
    'users'       => 0,
    'courses'     => 0,
    'notes'       => 0,
    'corrections' => 0,
];

$activity = [
    'notes'   => 0,
    'upvoted' => 0,
];

// NOTE CARICATE (tutte: draft + pubblicate)
$noteModel = new NoteModel();
$activity["notes"] = $noteModel->countNotesByOwner($personId);

// NOTE UPVOTATE (upvote = 1 nella tabella vote)
$voteModel = new VoteModel();
$activity["upvoted"] = $voteModel->countUpvotedNotesByUser($personId);



if ($mode === 'admin') {
    $adminModel = new AdminModel();
    $stats['users'] = $adminModel->countUsers();
    $stats['courses'] = $adminModel->countCourses();
    $stats['notes'] = $adminModel->countNotes();
    $stats['corrections'] = $adminModel->countUnresolvedCorrections();
}



$userId = (int)$_SESSION['person_id'];

// Check teacher status (pending vs confirmed)
$teacherModel = new TeacherModel();
$row = $teacherModel->getTeacherByPersonId($userId);

$hasTeacherRow      = (bool)$row;
$isTeacherConfirmed = false; // default

if ($hasTeacherRow) {
    // pending if ALL these fields are NULL
    $allNull = (
        is_null($row['department']) &&
        is_null($row['unibo_site']) &&
        is_null($row['phone_number']) &&
        is_null($row['personal_site'])
    );

    $isTeacherConfirmed = !$allNull;
}

// for convenience: “request exists but still pending”
$hasTeacherRequest = $hasTeacherRow && !$isTeacherConfirmed;

// --- Handle request to become a teacher ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_teacher'])) {

    // Check if a teacher row already exists (prevent duplication)
    $teacherModel = new TeacherModel();
    $exists = $teacherModel->teacherExists($userId);

    if (!$exists) {
        // Insert an empty teacher row → marks as PENDING
        $teacherModel->createTeacherRequest($userId);
    }

    // PRG redirect
    header("Location: index.php?page=account"); // ← change accordingly
    exit;
}

?>

<div class="container py-4 py-lg-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10 col-xl-8">
            <?php if (!empty($updateMessage)) { echo $updateMessage; } ?>
            <!-- HEADER PROFILO (comune) -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body d-flex flex-column flex-md-row align-items-md-center gap-3">
                    <div class="d-flex align-items-center gap-3 flex-grow-1">

                        <?php if (!empty($profilePicture)): ?>
                            <img src="<?php echo htmlspecialchars($profilePicture); ?>"
                                 alt="Profile of <?php echo htmlspecialchars($fullName); ?>" style="width:80px;height:80px;border-radius:50%;object-fit:cover;">
                        <?php else: ?>
                            <div class="rounded-circle d-flex align-items-center justify-content-center"
                                 style="width:80px;height:80px;
                                        background:linear-gradient(135deg,#4e54c8,#8f94fb);
                                        color:#fff;font-size:1.6rem;font-weight:600;">
                                <?php echo htmlspecialchars($initials); ?>
                            </div>
                        <?php endif; ?>

                        <div>
                            <div class="fw-semibold mb-1">
                                <?php echo htmlspecialchars($email); ?>
                            </div>
                            <div class="d-flex flex-wrap align-items-center gap-2 small text-muted">
                                <?php if ($role === 'admin'): ?>
                                    <span class="badge rounded-pill bg-warning text-dark">
                                        Admin
                                    </span>
                                <?php else: ?>
                                    <span class="badge rounded-pill bg-secondary text-light">
                                        User
                                    </span>
                                <?php endif; ?>

                                <span>
                                    📅 Joined <?php echo htmlspecialchars(date('F Y', strtotime($created))); ?>
                                </span>
                            </div>
                        </div>
                    </div>


<div class="ms-md-auto d-flex flex-wrap gap-2 align-items-center profile-actions">
    <?php if ($isTeacherConfirmed): ?>
        <!-- Confirmed teacher -->
        <button class="btn btn-success btn-sm d-flex align-items-center gap-1" disabled>
            <i class="bi bi-mortarboard-fill"></i>
            <span>Teacher</span>
        </button>

    <?php elseif (!$hasTeacherRow): ?>
        <!-- No request yet -->
        <form method="post" class="m-0">
            <button type="submit"
                    name="request_teacher"
                    class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1">
                <i class="bi bi-mortarboard"></i>
                <span>Become a teacher</span>
            </button>
        </form>

    <?php else: ?>
        <!-- Request sent, still pending -->
        <button class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1" disabled>
            <i class="bi bi-hourglass-split"></i>
            <span>Teacher request pending</span>
        </button>
    <?php endif; ?>

    <a href="logout.php"
       class="btn btn-outline-danger btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-box-arrow-right"></i>
        <span>Logout</span>
    </a>
</div>




                </div>
            </div>
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4 p-lg-5">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="h5 mb-1">Personal Information</h2>
                        <p class="text-muted small mb-0">View your account details — click "Edit profile" to modify.</p>
                    </div>

                    <button type="button"
                            id="editProfileBtn"
                            class="btn btn-outline-secondary btn-sm"
                            aria-expanded="false"
                            aria-controls="profileForm">
                        Edit profile
                    </button>
                </div>

                    <form id="profileForm" class="d-none" aria-hidden="true" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="profilePictureInput" class="form-label small text-muted">Profile picture</label>
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <img id="profilePreview" src="<?php echo htmlspecialchars($previewSrc); ?>"
                                     alt="Profile preview of <?php echo htmlspecialchars($fullName); ?>" style="width:80px;height:80px;border-radius:50%;object-fit:cover;">
                                <div>
                                    <input type="file" class="form-control" name="profile_picture" id="profilePictureInput" accept="image/*" disabled>
                                    <div class="small text-muted">JPG, PNG, WEBP - max 2MB</div>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="profile-name" class="form-label small text-muted">First Name</label>
                            <input id="profile-name" type="text"
                                    class="form-control"
                                    name="name"
                                    value="<?php echo htmlspecialchars($name); ?>"
                                    disabled
                                    aria-readonly="true"
                                    data-lock="true">
                        </div>
                        <div class="mb-3">
                            <label for="profile-surname" class="form-label small text-muted">Last Name</label>
                            <input id="profile-surname" type="text"
                                    class="form-control"
                                    name="surname"
                                    value="<?php echo htmlspecialchars($surname); ?>"
                                    disabled
                                    aria-readonly="true"
                                    data-lock="true">
                        </div>

                        <div class="mb-3">
                            <label for="profile-email" class="form-label small text-muted">Email</label>
                            <div class="input-group">
                                <span class="input-group-text">📧</span>
                                <input id="profile-email" type="email"
                                        class="form-control"
                                        name="email"
                                        value="<?php echo htmlspecialchars($email); ?>"
                                        disabled
                                        aria-readonly="true"
                                        data-lock="true">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="profile-programme" class="form-label small text-muted">Study Programme</label>
                            <input id="profile-programme" type="text"
                                    class="form-control"
                                    name="programme"
                                    value="<?php echo htmlspecialchars($programme); ?>"
                                    disabled
                                    aria-readonly="true">
                        </div>

                        <div class="mb-4">
                            <label for="profile-bio" class="form-label small text-muted">Bio</label>
                            <textarea id="profile-bio" class="form-control" 
                                rows="3" 
                                cols="1" 
                                name="bio"
                                disabled aria-readonly="true"><?php echo htmlspecialchars($bio); ?></textarea>
                        </div>

                        <button type="submit"
                                id="saveProfileBtn"
                                class="btn btn-primary w-100"
                                disabled>
                            Save Changes
                        </button>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4 p-lg-5">
                    <h2 class="h6 mb-2">Activity</h2>
                    <p class="text-muted small mb-4">Your contribution to UniNotes</p>

                    <!--
                    Prendiamo da note, owner_id che e' uguale a person_id da user,
                    e contiamo quante volte compare.
                    -->

                    
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <a href="index.php?page=user_notes_uploaded"
                            class="text-decoration-none text-reset">
                                <div class="border rounded-4 py-4 px-3 text-center bg-light hover-shadow">
                                    <div class="fs-2 mb-2">📘</div>
                                    <div class="fw-semibold">My Notes</div>
                                    <div class="text-muted small">
                                        <?php echo (int)$activity['notes']; ?>
                                    </div>
                                </div>
                            </a>
                        </div>

                        <div class="col-12 col-md-6">
                            <a href="index.php?page=user_notes_upvoted"
                            class="text-decoration-none text-reset">
                                <div class="border rounded-4 py-4 px-3 text-center bg-light hover-shadow">
                                    <div class="fs-2 mb-2">👍</div>
                                    <div class="fw-semibold">Notes Upvoted</div>
                                    <div class="text-muted small">
                                        <?php echo (int)$activity['upvoted']; ?>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>



                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Accessibility: show/hide the edit form, move focus appropriately, and restore values on cancel
document.addEventListener('DOMContentLoaded', function () {
    const editBtn = document.getElementById('editProfileBtn');
    const form    = document.getElementById('profileForm');
    const saveBtn = document.getElementById('saveProfileBtn');
    const fileInput = document.getElementById('profilePictureInput');
    const preview = document.getElementById('profilePreview');

    if (!editBtn || !form || !saveBtn) {
        console.warn('Edit/profile elements not found');
        return;
    }

    // track fields and initial values for reset
    const fields = Array.from(form.querySelectorAll('input, textarea'));
    const initial = {};
    fields.forEach(function (el) {
        initial[el.id || el.name] = {
            value: el.value,
            disabled: el.disabled
        };
    });
    const initialPreview = preview ? preview.src : null;

    let editing = false;

    function showForm() {
        form.classList.remove('d-none');
        form.removeAttribute('aria-hidden');
        editBtn.setAttribute('aria-expanded', 'true');

        fields.forEach(function (el) {
            if (el.dataset.lock === 'true') return; // these remain readonly
            el.disabled = false;
        });
        if (fileInput) fileInput.disabled = false;
        saveBtn.disabled = false;

        // focus the first editable control
        const first = fields.find(f => !f.disabled);
        if (first) {
            first.focus();
        } else if (fileInput) {
            fileInput.focus();
        }

        editBtn.textContent = 'Cancel';
        editing = true;

        // close on Escape
        document.addEventListener('keydown', escHandler);
    }

    function hideForm(restore = true) {
        // restore values if requested
        if (restore) {
            fields.forEach(function (el) {
                const key = el.id || el.name;
                if (initial[key]) el.value = initial[key].value;
            });
            if (preview && initialPreview !== null) preview.src = initialPreview;
            if (fileInput) fileInput.value = '';
        }

        fields.forEach(function (el) {
            el.disabled = true;
        });
        if (fileInput) fileInput.disabled = true;
        saveBtn.disabled = true;

        form.classList.add('d-none');
        form.setAttribute('aria-hidden', 'true');
        editBtn.setAttribute('aria-expanded', 'false');
        editBtn.textContent = 'Edit profile';
        editing = false;

        editBtn.focus();
        document.removeEventListener('keydown', escHandler);
    }

    function escHandler(e) {
        if (e.key === 'Escape' || e.key === 'Esc') {
            hideForm(true);
        }
    }

    // Initial state: keep form hidden (server-side already sets this), ensure fields are disabled
    fields.forEach(function (el) { el.disabled = true; });
    if (fileInput) fileInput.disabled = true;
    saveBtn.disabled = true;

    editBtn.addEventListener('click', function () {
        if (editing) {
            hideForm(true);
        } else {
            showForm();
        }
    });

    // Preview image on file change
    if (fileInput && preview) {
        fileInput.addEventListener('change', function (e) {
            const f = fileInput.files[0];
            if (!f) return;
            const url = URL.createObjectURL(f);
            preview.src = url;
        });
    }

});
</script>

