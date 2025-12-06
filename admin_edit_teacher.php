<?php
// admin-teacher-edit.php
require_once("bootstrap.php");

if (!isset($_SESSION['person_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: login.php');
    exit;
}

$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
if ($userId <= 0) {
    header('Location: index.php?page=manage_users');
    exit;
}

// Fetch basic user info + teacher row (if any)
$sql = "
    SELECT 
        p.id,
        p.name,
        p.surname,
        p.email,
        u.role,
        t.person_id      AS teacher_person_id,
        t.department,
        t.unibo_site,
        t.phone_number,
        t.personal_site
    FROM person p
    JOIN user u ON p.id = u.person_id
    LEFT JOIN teacher t ON t.person_id = p.id
    WHERE p.id = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$res  = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$user) {
    header('Location: index.php?page=manage_users');
    exit;
}

// Handle POST: update / create teacher
$successMessage = '';
$errorMessage   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $department   = trim($_POST['department'] ?? '');
    $unibo_site   = trim($_POST['unibo_site'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $personal_site= trim($_POST['personal_site'] ?? '');

    if (is_null($user['teacher_person_id'])) {
        // create new teacher row
        $sqlInsert = "
            INSERT INTO teacher (person_id, department, unibo_site, phone_number, personal_site)
            VALUES (?, ?, ?, ?, ?)
        ";
        $stmtIns = mysqli_prepare($conn, $sqlInsert);
        mysqli_stmt_bind_param($stmtIns, "issss", $userId, $department, $unibo_site, $phone_number, $personal_site);
        if (mysqli_stmt_execute($stmtIns)) {
            $successMessage = 'Teacher profile created successfully.';
            $user['teacher_person_id'] = $userId;
            $user['department']        = $department;
            $user['unibo_site']        = $unibo_site;
            $user['phone_number']      = $phone_number;
            $user['personal_site']     = $personal_site;
        } else {
            $errorMessage = 'Error creating teacher profile.';
        }
        mysqli_stmt_close($stmtIns);
    } else {
        // update existing teacher row
        $sqlUpdate = "
            UPDATE teacher
            SET department = ?, unibo_site = ?, phone_number = ?, personal_site = ?
            WHERE person_id = ?
        ";
        $stmtUp = mysqli_prepare($conn, $sqlUpdate);
        mysqli_stmt_bind_param($stmtUp, "ssssi", $department, $unibo_site, $phone_number, $personal_site, $userId);
        if (mysqli_stmt_execute($stmtUp)) {
            $successMessage = 'Teacher profile updated successfully.';
            $user['department']    = $department;
            $user['unibo_site']    = $unibo_site;
            $user['phone_number']  = $phone_number;
            $user['personal_site'] = $personal_site;
        } else {
            $errorMessage = 'Error updating teacher profile.';
        }
        mysqli_stmt_close($stmtUp);
    }
}
?>

<div class="container py-4 py-lg-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">

            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4 p-lg-5">
                    <h1 class="h4 mb-3">Teacher profile</h1>
                    <p class="text-muted mb-4">
                        Review and edit the teacher information for this user.
                    </p>

                    <div class="mb-3">
                        <strong>User:</strong>
                        <?php echo htmlspecialchars($user['name'] . ' ' . $user['surname']); ?>
                        <br>
                        <span class="text-muted small">
                            <?php echo htmlspecialchars($user['email']); ?>
                        </span>
                    </div>

                    <?php if ($successMessage): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
                    <?php endif; ?>

                    <?php if ($errorMessage): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
                    <?php endif; ?>

                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label">Department</label>
                            <input type="text"
                                   name="department"
                                   class="form-control"
                                   value="<?php echo htmlspecialchars($user['department'] ?? ''); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">University website (Unibo)</label>
                            <input type="url"
                                   name="unibo_site"
                                   class="form-control"
                                   value="<?php echo htmlspecialchars($user['unibo_site'] ?? ''); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Phone number</label>
                            <input type="text"
                                   name="phone_number"
                                   class="form-control"
                                   value="<?php echo htmlspecialchars($user['phone_number'] ?? ''); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Personal website</label>
                            <input type="url"
                                   name="personal_site"
                                   class="form-control"
                                   value="<?php echo htmlspecialchars($user['personal_site'] ?? ''); ?>">
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <a href="index.php?page=manage_users" class="btn btn-outline-secondary btn-sm">
                                ← Back to users
                            </a>
                            <button type="submit" class="btn btn-primary btn-sm">
                                Save teacher profile
                            </button>
                        </div>
                    </form>

                </div>
            </div>

        </div>
    </div>
</div>
