<?php
// admin-teacher-create.php
require_once("bootstrap.php");

if (!isset($_SESSION['person_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: login.php');
    exit;
}

$teacherModel = new TeacherModel();
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
        // Check if email already exists in person
        $existingPerson = $teacherModel->personExistsByEmail($email);

        if ($existingPerson) {
            $personId = (int)$existingPerson['id'];

            // Check if already a teacher
            if ($teacherModel->isTeacher($personId)) {
                $errors[] = "This email already belongs to an existing teacher.";
            } else {
                // Turn this existing person into a teacher
                if ($teacherModel->addExistingAsTeacher($personId)) {
                    $success = "Teacher created from existing person.";
                } else {
                    $errors[] = "Error while inserting teacher record.";
                }
            }
        } else {
            // Insert new person, then teacher
            $personId = $teacherModel->createTeacher($name, $surname, $email);
            
            if ($personId) {
                $success = "Teacher created successfully.";
                // Clear form fields after success
                $name = $surname = $email = $department = $unibo_site = $phone_number = $personal_site = "";
            } else {
                $errors[] = "Error creating teacher.";
            }
        }
    }
}

// ============================
// 2) List existing teachers
// ============================

$teachers = $teacherModel->getAllTeachers();

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
                        <div class="alert alert-danger" role="alert">
                            <ul class="mb-0">
                                <?php foreach ($errors as $e): ?>
                                    <li><?php echo htmlspecialchars($e); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success" role="status">
                            <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>

                    <!-- FORM -->
                    <form method="post" class="mb-4">
                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <label for="teacher-name" class="form-label">First name *</label>
                                <input
                                    id="teacher-name"
                                    type="text"
                                    name="name"
                                    class="form-control"
                                    required aria-required="true"
                                    value="<?php echo htmlspecialchars($name ?? ''); ?>"
                                >
                            </div>
                            <div class="col-12 col-md-4">
                                <label for="teacher-surname" class="form-label">Surname *</label>
                                <input
                                    id="teacher-surname"
                                    type="text"
                                    name="surname"
                                    class="form-control"
                                    required aria-required="true"
                                    value="<?php echo htmlspecialchars($surname ?? ''); ?>"
                                >
                            </div>
                            <div class="col-12 col-md-4">
                                <label for="teacher-email" class="form-label">Email *</label>
                                <input
                                    id="teacher-email"
                                    type="email"
                                    name="email"
                                    class="form-control"
                                    required aria-required="true"
                                    value="<?php echo htmlspecialchars($email ?? ''); ?>"
                                >
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="teacher-department" class="form-label">Department</label>
                                <input
                                    id="teacher-department"
                                    type="text"
                                    name="department"
                                    class="form-control"
                                    placeholder="e.g. Computer Science and Engineering"
                                    value="<?php echo htmlspecialchars($department ?? ''); ?>"
                                >
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="teacher-phone" class="form-label">Phone number</label>
                                <input
                                    id="teacher-phone"
                                    type="text"
                                    name="phone_number"
                                    class="form-control"
                                    value="<?php echo htmlspecialchars($phone_number ?? ''); ?>"
                                >
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="teacher-unibo" class="form-label">Unibo site</label>
                                <input id="teacher-unibo"
                                    type="url"
                                    name="unibo_site"
                                    class="form-control"
                                    placeholder="https://www.unibo.it/..."
                                    value="<?php echo htmlspecialchars($unibo_site ?? ''); ?>"
                                >
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="teacher-personal" class="form-label">Personal site</label>
                                <input id="teacher-personal"
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
                                <caption class="visually-hidden">List of all teachers with name, email, status and actions</caption>
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
                                                    <a href="<?php echo htmlspecialchars($t['unibo_site']); ?>" target="_blank" rel="noopener noreferrer">
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
