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
    $name = trim($_POST['name'] ??'');
    $surname = trim($_POST['surname'] ??'');
    $programme = trim($_POST['programme'] ?? '');
    $bio       = trim($_POST['bio'] ?? '');

    $updateSql = "UPDATE user SET programme = ?, bio = ? WHERE person_id = ?";
    $updateStmt = mysqli_prepare($conn, $updateSql);
    mysqli_stmt_bind_param($updateStmt, "ssi", $programme, $bio, $personId);

    if (mysqli_stmt_execute($updateStmt)) {
        $updateMessage = '<div class="alert alert-success mb-3">Profile updated successfully.</div>';
    } else {
        $updateMessage = '<div class="alert alert-danger mb-3">Error while updating profile.</div>';
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

$profilePicture = $userData['profile_picture'];
if (empty($profilePicture)) {
    $profilePicture = 'https://via.placeholder.com/120?text=User';
}

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
    'downloaded' => 0,
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

/*
// NOTE SCARICATE (download) – qui assumo tabella note_downloads
// TODO: TUTTO DA RIFATE
// TODO: FARE PURE LA TABELLA!
$downloadSql = "SELECT COUNT(*) AS c FROM note_downloads WHERE person_id = ?";
$downloadStmt = mysqli_prepare($conn, $downloadSql);
mysqli_stmt_bind_param($downloadStmt, "i", $personId);
mysqli_stmt_execute($downloadStmt);
$downloadRes = mysqli_stmt_get_result($downloadStmt);
if ($downloadRow = mysqli_fetch_assoc($downloadRes)) {
    $activity['downloaded'] = (int)$downloadRow['c'];
}
mysqli_stmt_close($downloadStmt);
*/

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
?>

<div class="container py-4 py-lg-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10 col-xl-8">
            <?php if (!empty($updateMessage)) echo $updateMessage; ?>
            <!-- HEADER PROFILO (comune) -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body d-flex flex-column flex-md-row align-items-md-center gap-3">
                    <div class="d-flex align-items-center gap-3 flex-grow-1">

                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:80px;height:80px;
                                    background:linear-gradient(135deg,#4e54c8,#8f94fb);
                                    color:#fff;font-size:1.6rem;font-weight:600;">
                            <?php echo htmlspecialchars($initials); ?>
                        </div>

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

                    <div class="ms-md-auto">
                        <a href="logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
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

                    <form id="profileForm" method="POST">
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
                            <div class="border rounded-4 py-4 px-3 text-center bg-light">
                                <div class="fs-2 mb-2">📘</div>
                                <div class="fw-semibold">Notes Uploaded</div>
                                <div class="text-muted small">
                                    <?php echo (int)$activity['uploaded']; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="border rounded-4 py-4 px-3 text-center bg-light">
                                <div class="fs-2 mb-2">📥</div>
                                <div class="fw-semibold">Notes Downloaded</div>
                                <div class="text-muted small">0</div>
                            </div>
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

    if (!editBtn || !form || !saveBtn) {
        console.warn('Edit/profile elements not found');
        return;
    }

    const fields = form.querySelectorAll('input, textarea');
    let editing = false;

    editBtn.addEventListener('click', function () {
        editing = !editing;

        fields.forEach(function (el) {
            // quelli con data-lock="true" restano sempre disabilitati
            if (el.dataset.lock === 'true') return;
            el.disabled = !editing;
        });

        saveBtn.disabled = !editing;
        editBtn.textContent = editing ? 'Cancel' : 'Edit profile';

        // se premi "Cancel", ricarico i valori originali dal DB
        if (!editing) {
            window.location.reload();
        }
    });
});
</script>

