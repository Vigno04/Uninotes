<?php
// admin-teacher-create.php
require_once("bootstrap.php");

if (!isset($_SESSION['person_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: login.php');
    exit;
}

$errors  = [];
$success = "";

// ============================
// 1) Handle POST (create teacher)
// ============================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name         = trim($_POST['name'] ?? '');
    $surname      = trim($_POST['surname'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $department   = trim($_POST['department'] ?? '');
    $unibo_site   = trim($_POST['unibo_site'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $personal_site= trim($_POST['personal_site'] ?? '');

    // Basic validation
    if ($name === '') {
        $errors[] = "First name is required.";
    }
    if ($surname === '') {
        $errors[] = "Surname is required.";
    }
    if ($email === '') {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    if (empty($errors)) {
        // 1) Check if email already exists in person
        $sqlCheck = "SELECT id FROM person WHERE email = ? LIMIT 1";
        $stmtCheck = mysqli_prepare($conn, $sqlCheck);
        if ($stmtCheck) {
            mysqli_stmt_bind_param($stmtCheck, "s", $email);
            mysqli_stmt_execute($stmtCheck);
            $resCheck = mysqli_stmt_get_result($stmtCheck);
            $existingPerson = mysqli_fetch_assoc($resCheck);
            mysqli_stmt_close($stmtCheck);
        } else {
            $existingPerson = null;
        }

        if ($existingPerson) {
            // If person exists, check if already a teacher
            $personId = (int)$existingPerson['id'];

            $sqlCheckTeacher = "SELECT person_id FROM teacher WHERE person_id = ? LIMIT 1";
            $stmtCT = mysqli_prepare($conn, $sqlCheckTeacher);
            if ($stmtCT) {
                mysqli_stmt_bind_param($stmtCT, "i", $personId);
                mysqli_stmt_execute($stmtCT);
                $resCT = mysqli_stmt_get_result($stmtCT);
                $existingTeacher = mysqli_fetch_assoc($resCT);
                mysqli_stmt_close($stmtCT);
            } else {
                $existingTeacher = null;
            }

            if ($existingTeacher) {
                $errors[] = "This email already belongs to an existing teacher.";
            } else {
                // Turn this existing person into a teacher
                $sqlInsertTeacher = "
                    INSERT INTO teacher (person_id, department, unibo_site, phone_number, personal_site)
                    VALUES (?, ?, ?, ?, ?)
                ";
                $stmtT = mysqli_prepare($conn, $sqlInsertTeacher);
                if ($stmtT) {
                    mysqli_stmt_bind_param($stmtT, "issss",
                        $personId,
                        $department,
                        $unibo_site,
                        $phone_number,
                        $personal_site
                    );
                    if (mysqli_stmt_execute($stmtT)) {
                        $success = "Teacher created from existing person.";
                    } else {
                        $errors[] = "Error while inserting teacher record.";
                    }
                    mysqli_stmt_close($stmtT);
                } else {
                    $errors[] = "Internal error while preparing teacher insert.";
                }
            }
        } else {
            // 2) Insert new person, then teacher
            mysqli_begin_transaction($conn);
            try {
                // Insert person
                $sqlPerson = "INSERT INTO person (name, surname, email) VALUES (?, ?, ?)";
                $stmtP = mysqli_prepare($conn, $sqlPerson);
                if (!$stmtP) {
                    throw new Exception("Error preparing person insert.");
                }
                mysqli_stmt_bind_param($stmtP, "sss", $name, $surname, $email);
                if (!mysqli_stmt_execute($stmtP)) {
                    throw new Exception("Error executing person insert (maybe email already in use).");
                }
                $personId = mysqli_insert_id($conn);
                mysqli_stmt_close($stmtP);

                // Insert teacher
                $sqlTeacher = "
                    INSERT INTO teacher (person_id, department, unibo_site, phone_number, personal_site)
                    VALUES (?, ?, ?, ?, ?)
                ";
                $stmtT = mysqli_prepare($conn, $sqlTeacher);
                if (!$stmtT) {
                    throw new Exception("Error preparing teacher insert.");
                }
                mysqli_stmt_bind_param($stmtT, "issss",
                    $personId,
                    $department,
                    $unibo_site,
                    $phone_number,
                    $personal_site
                );
                if (!mysqli_stmt_execute($stmtT)) {
                    throw new Exception("Error executing teacher insert.");
                }
                mysqli_stmt_close($stmtT);

                mysqli_commit($conn);
                $success = "Teacher created successfully.";
                // Clear form fields after success
                $name = $surname = $email = $department = $unibo_site = $phone_number = $personal_site = "";

            } catch (Exception $e) {
                mysqli_rollback($conn);
                $errors[] = $e->getMessage();
            }
        }
    }
}

// ============================
// 2) List existing teachers
// ============================

$teachers = [];
$sqlList = "
    SELECT 
        t.person_id,
        p.name,
        p.surname,
        p.email,
        t.department,
        t.unibo_site
    FROM teacher t
    JOIN person p ON p.id = t.person_id
    ORDER BY p.surname, p.name
";
$resList = mysqli_query($conn, $sqlList);
if ($resList) {
    while ($row = mysqli_fetch_assoc($resList)) {
        $teachers[] = $row;
    }
}

?>

<div class="container py-4 py-lg-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10">

            <div class="mb-3">
                <a href="index.php?page=admindashboard" class="btn btn-outline-secondary btn-sm">
                    ← Back to dashboard
                </a>
            </div>

            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4 p-lg-5">
                    <h1 class="h4 mb-3">Create teacher</h1>
                    <p class="text-muted mb-4">
                        Create a new teacher profile. If the email already exists as a person, it will be reused.
                    </p>

                    <!-- Alerts -->
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $e): ?>
                                    <li><?php echo htmlspecialchars($e); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>

                    <!-- FORM -->
                    <form method="post" class="mb-4">
                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <label class="form-label">First name *</label>
                                <input
                                    type="text"
                                    name="name"
                                    class="form-control"
                                    required
                                    value="<?php echo htmlspecialchars($name ?? ''); ?>"
                                >
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">Surname *</label>
                                <input
                                    type="text"
                                    name="surname"
                                    class="form-control"
                                    required
                                    value="<?php echo htmlspecialchars($surname ?? ''); ?>"
                                >
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">Email *</label>
                                <input
                                    type="email"
                                    name="email"
                                    class="form-control"
                                    required
                                    value="<?php echo htmlspecialchars($email ?? ''); ?>"
                                >
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Department</label>
                                <input
                                    type="text"
                                    name="department"
                                    class="form-control"
                                    placeholder="e.g. Computer Science and Engineering"
                                    value="<?php echo htmlspecialchars($department ?? ''); ?>"
                                >
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Phone number</label>
                                <input
                                    type="text"
                                    name="phone_number"
                                    class="form-control"
                                    value="<?php echo htmlspecialchars($phone_number ?? ''); ?>"
                                >
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Unibo site</label>
                                <input
                                    type="url"
                                    name="unibo_site"
                                    class="form-control"
                                    placeholder="https://www.unibo.it/..."
                                    value="<?php echo htmlspecialchars($unibo_site ?? ''); ?>"
                                >
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Personal site</label>
                                <input
                                    type="url"
                                    name="personal_site"
                                    class="form-control"
                                    placeholder="https://..."
                                    value="<?php echo htmlspecialchars($personal_site ?? ''); ?>"
                                >
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                Create teacher
                            </button>
                        </div>
                    </form>

                    <hr class="my-4">

                    <h2 class="h5 mb-3">Existing teachers</h2>
                    <?php if (empty($teachers)): ?>
                        <p class="text-muted small">No teachers created yet.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Department</th>
                                        <th>Unibo site</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($teachers as $t): ?>
                                        <tr>
                                            <td>
                                                <?php echo htmlspecialchars($t['surname'] . ' ' . $t['name']); ?>
                                            </td>
                                            <td class="small">
                                                <?php echo htmlspecialchars($t['email']); ?>
                                            </td>
                                            <td class="small text-muted">
                                                <?php echo htmlspecialchars($t['department'] ?? ''); ?>
                                            </td>
                                            <td class="small">
                                                <?php if (!empty($t['unibo_site'])): ?>
                                                    <a href="<?php echo htmlspecialchars($t['unibo_site']); ?>" target="_blank" rel="noopener">
                                                        Open
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">—</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                </div>
            </div>

        </div>
    </div>
</div>
