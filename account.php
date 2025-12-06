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
                $updateMessage = '<div class="alert alert-danger mb-3">File type not allowed. Use JPG/PNG/WEBP.</div>';
            } elseif ($file['size'] > $maxSize) {
                $updateMessage = '<div class="alert alert-danger mb-3">File too large. Max 2MB.</div>';
            } else {
                $ext = $allowed[$mime];
                $newName = 'profile_' . $personId . '_' . time() . '.' . $ext;
                $dest = $profileDir . $newName;
                if (move_uploaded_file($file['tmp_name'], $dest)) {
                    // salva solo il percorso (senza query) nel DB
                    $dbPath = 'uploads/profile/' . $newName;

                    $uStmt = mysqli_prepare($conn, "UPDATE person SET profile_picture = ? WHERE id = ?");
                    mysqli_stmt_bind_param($uStmt, "si", $dbPath, $personId);
                    mysqli_stmt_execute($uStmt);
                    mysqli_stmt_close($uStmt);

                    // imposta la variabile per il preview locale con bust di cache
                    $profilePicture = $dbPath . '?v=' . time();
                } else {
                    $updateMessage = '<div class="alert alert-danger mb-3">Errore nel salvataggio del file.</div>';
                }
            }
        } else {
            $updateMessage = '<div class="alert alert-danger mb-3">Errore upload file (code '.$file['error'].').</div>';
        }
    }

    // --- AGGIORNA CAMPI DI PROFILO (user) ---
    $updateSql = "UPDATE user SET programme = ?, bio = ? WHERE person_id = ?";
    $updateStmt = mysqli_prepare($conn, $updateSql);
    mysqli_stmt_bind_param($updateStmt, "ssi", $programme, $bio, $personId);

    if (mysqli_stmt_execute($updateStmt)) {
        $updateMessage = ($updateMessage ?? '') . '<div class="alert alert-success mb-3">Profile updated successfully.</div>';
    } else {
        $updateMessage = ($updateMessage ?? '') . '<div class="alert alert-danger mb-3">Error while updating profile.</div>';
    }

    mysqli_stmt_close($updateStmt);
}



// -------------------------
// 1) Dati utente (comune ad admin e user)
// -------------------------
$sql = "
    SELECT 
        p.name,
        p.surname,
        p.email,
        p.profile_picture,
        u.created_at,
        u.programme,
        u.bio,
        u.role,
        u.last_login
    FROM person p
    JOIN user u ON p.id = u.person_id
    WHERE p.id = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $personId);
mysqli_stmt_execute($stmt);
$result   = mysqli_stmt_get_result($stmt);
$userData = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

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
    'uploaded'   => 0,
    'upvoted' => 0,
];

// NOTE CARICATE (upload)
$uploadSql = "SELECT COUNT(*) AS c FROM note WHERE owner_id = ?";
$uploadStmt = mysqli_prepare($conn, $uploadSql);
mysqli_stmt_bind_param($uploadStmt, "i", $personId);
mysqli_stmt_execute($uploadStmt);
$uploadRes = mysqli_stmt_get_result($uploadStmt);
if ($uploadRow = mysqli_fetch_assoc($uploadRes)) {
    $activity["uploaded"] = (int)$uploadRow["c"];
}
mysqli_stmt_close($uploadStmt);


// NOTE UPVOTATE (upvote = 1 nella tabella vote)
$upvoteSql = "SELECT COUNT(DISTINCT note_id) AS c FROM vote WHERE user_id = ? AND vote = 1";
$upvoteStmt = mysqli_prepare($conn, $upvoteSql);
mysqli_stmt_bind_param($upvoteStmt, "i", $personId);
mysqli_stmt_execute($upvoteStmt);
$upvoteRes = mysqli_stmt_get_result($upvoteStmt);
if ($upvoteRow = mysqli_fetch_assoc($upvoteRes)) {
    $activity["upvoted"] = (int)$upvoteRow["c"];
}
mysqli_stmt_close($upvoteStmt);



if ($mode === 'admin') {
    $res = mysqli_query($conn, "SELECT COUNT(*) AS c FROM user");
    if ($res) $stats['users'] = (int)mysqli_fetch_assoc($res)['c'];

    $res = mysqli_query($conn, "SELECT COUNT(*) AS c FROM course");
    if ($res) $stats['courses'] = (int)mysqli_fetch_assoc($res)['c'];

    $res = mysqli_query($conn, "SELECT COUNT(*) AS c FROM note");
    if ($res) $stats['notes'] = (int)mysqli_fetch_assoc($res)['c'];

    $res = mysqli_query($conn, "SELECT COUNT(*) AS c FROM correction WHERE resolved = 0");
    if ($res) $stats['corrections'] = (int)mysqli_fetch_assoc($res)['c'];
}



$userId = $_SESSION['person_id']; 

// Check if the user already has a teacher row
$sql = "SELECT person_id FROM teacher WHERE person_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$hasTeacherRequest = mysqli_fetch_assoc($res) ? true : false;
mysqli_stmt_close($stmt);

// --- Handle request to become a teacher ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_teacher'])) {

    // Check if a teacher row already exists (prevent duplication)
    $sqlCheck = "SELECT person_id FROM teacher WHERE person_id = ?";
    $stmtC = mysqli_prepare($conn, $sqlCheck);
    mysqli_stmt_bind_param($stmtC, "i", $userId);
    mysqli_stmt_execute($stmtC);
    $exists = mysqli_stmt_get_result($stmtC)->num_rows > 0;
    mysqli_stmt_close($stmtC);

    if (!$exists) {
        // Insert an empty teacher row → marks as PENDING
        $sqlInsert = "INSERT INTO teacher (person_id) VALUES (?)";
        $stmt = mysqli_prepare($conn, $sqlInsert);
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    // PRG redirect
    header("Location: index.php?page=account"); // ← change accordingly
    exit;
}

?>

<div class="container py-4 py-lg-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10 col-xl-8">
            <?php if (!empty($updateMessage)) echo $updateMessage; ?>
            <!-- HEADER PROFILO (comune) -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body d-flex flex-column flex-md-row align-items-md-center gap-3">
                    <div class="d-flex align-items-center gap-3 flex-grow-1">

                        <?php if (!empty($profilePicture)): ?>
                            <img src="<?php echo htmlspecialchars($profilePicture); ?>"
                                 alt="profile" style="width:80px;height:80px;border-radius:50%;object-fit:cover;">
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
    <?php if (!$hasTeacherRequest): ?>
        <form method="post" class="m-0">
            <button type="submit"
                    name="request_teacher"
                    class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1">
                <i class="bi bi-mortarboard"></i>
                <span>Become a teacher</span>
            </button>
        </form>
    <?php else: ?>
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
                        <p class="text-muted small mb-0">Update your account details</p>
                    </div>

                    <button type="button"
                            id="editProfileBtn"
                            class="btn btn-outline-secondary btn-sm">
                        Edit profile
                    </button>
                </div>

                    <form id="profileForm" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label small text-muted">Profile picture</label>
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <img id="profilePreview" src="<?php echo htmlspecialchars($previewSrc); ?>"
                                     alt="profile" style="width:80px;height:80px;border-radius:50%;object-fit:cover;">
                                <div>
                                    <input type="file" class="form-control" name="profile_picture" id="profilePictureInput" accept="image/*" disabled>
                                    <div class="small text-muted">JPG, PNG, WEBP - max 2MB</div>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small text-muted">First Name</label>
                            <input type="text"
                                    class="form-control"
                                    name="name"
                                    value="<?php echo htmlspecialchars($name); ?>"
                                    disabled
                                    data-lock="true">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small text-muted">Last Name</label>
                            <input type="text"
                                    class="form-control"
                                    name="surname"
                                    value="<?php echo htmlspecialchars($surname); ?>"
                                    disabled
                                    data-lock="true">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small text-muted">Email</label>
                            <div class="input-group">
                                <span class="input-group-text">📧</span>
                                <input type="email"
                                        class="form-control"
                                        name="email"
                                        value="<?php echo htmlspecialchars($email); ?>"
                                        disabled
                                        data-lock="true">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small text-muted">Study Programme</label>
                            <input type="text"
                                    class="form-control"
                                    name="programme"
                                    value="<?php echo htmlspecialchars($programme); ?>"
                                    disabled>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small text-muted">Bio</label>
                            <textarea class="form-control" 
                                rows="3" 
                                cols="1" 
                                name="bio"
                                disabled><?php echo htmlspecialchars($bio); ?></textarea>
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
                                    <div class="fw-semibold">Notes Uploaded</div>
                                    <div class="text-muted small">
                                        <?php echo (int)$activity['uploaded']; ?>
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

    const fields = form.querySelectorAll('input, textarea');
    let editing = false;

    editBtn.addEventListener('click', function () {
        editing = !editing;

        fields.forEach(function (el) {
            if (el.dataset.lock === 'true') return;
            el.disabled = !editing;
        });

        // abilita/disabilita campo file separatamente (fileInput non ha data-lock)
        if (fileInput) fileInput.disabled = !editing;

        saveBtn.disabled = !editing;
        editBtn.textContent = editing ? 'Cancel' : 'Edit profile';

        if (!editing) {
            window.location.reload();
        }
    });

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

