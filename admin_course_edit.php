<?php
// admin-course-edit.php
require_once("bootstrap.php");

if (!isset($_SESSION['person_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: login.php');
    exit;
}

$currentAdminId = (int)$_SESSION['person_id'];

// id corso (se presente → modalità "edit", altrimenti "create")
$courseId = isset($_GET['id']) ? (int)$_GET['id'] : null;

$errors  = [];
$success = "";

// ============================
// 1) Lookup per select (programmes, teachers)
// ============================

$courseModel = new CourseModel();
$programmes = $courseModel->getAllProgrammes();
$teachers = $courseModel->getTeachersList();

// ============================
// 2) Handle POST actions
// ============================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Sanitize common
    if (isset($_POST['course_id'])) {
        $courseId = (int)$_POST['course_id']; // sovrascrive se veniva da GET
    }

    if ($action === 'create_course') {

        $name        = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $programmeId = isset($_POST['programme_id']) && $_POST['programme_id'] !== '' 
                        ? (int)$_POST['programme_id'] 
                        : null;

        if ($name === '') {
            $errors[] = "Course name is required.";
        }

        if (empty($errors)) {
            $newId = $courseModel->createCourseWithAdmin($name, $description, $currentAdminId, $programmeId);
            
            if ($newId) {
                $courseId = $newId;
                $success = "Course created successfully.";
                // redirect alla pagina di edit per evitare resubmit
                header("Location: index.php?page=admin-course-edit&id=" . $courseId);
                exit;
            } else {
                $errors[] = "Error while creating course (maybe duplicate name).";
            }
        }

    } elseif ($action === 'add_offering' && $courseId) {

        $year     = (int)($_POST['year'] ?? date('Y'));
        $semester = $_POST['semester'] ?? '1';

        if ($year < 2000 || $year > 2100) {
            $errors[] = "Please enter a valid year.";
        }
        if (!in_array($semester, ['1', '2'], true)) {
            $errors[] = "Invalid semester.";
        }

        if (empty($errors)) {
            $success = $courseModel->addCourseOffering($year, $semester, $courseId);
            
            if ($success) {
                $success = "Course offering added.";
            } else {
                // Probabile violazione unique (course_id, year, semester)
                $errors[] = "Could not add offering. Maybe this year/semester already exists for this course.";
            }
        }

    } elseif ($action === 'add_teacher' && $courseId) {

        $offeringId = (int)($_POST['offering_id'] ?? 0);
        $teacherId  = (int)($_POST['teacher_id'] ?? 0);

        if ($offeringId <= 0 || $teacherId <= 0) {
            $errors[] = "Please select both offering and teacher.";
        }

        if (empty($errors)) {
            $success = $courseModel->linkTeacherToOffering($offeringId, $teacherId);
            
            if ($success) {
                $success = "Teacher linked to the offering.";
            } else {
                $errors[] = "Could not link teacher to offering.";
            }
        }

    }
}

// ============================
// 3) Carico dati del corso (se $courseId esiste)
// ============================

$course = null;
$offerings = [];

if ($courseId) {
    $course = $courseModel->getCourseDetails($courseId);

    if ($course) {
        // Offerte del corso + docenti + numero follower
        $offerings = $courseModel->getCourseOfferingsWithDetails($courseId);
    }
}

// helper per stampare nome programma
function findProgrammeName($programmes, $id) {
    foreach ($programmes as $p) {
        if ((int)$p['id'] === (int)$id) return $p['name'];
    }
    return null;
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
                    <h1 class="h4 mb-3">
                        <?php echo $course ? 'Edit course' : 'Create new course'; ?>
                    </h1>
                    <p class="text-muted mb-4">
                        Define the base data of the course. After saving, you can add offerings, teachers and see followers.
                    </p>

                    <!-- Alert messages -->
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

                    <!-- FORM: Create / basic info course -->
                    <?php if (!$course): ?>
                        <form method="post">
                            <input type="hidden" name="action" value="create_course">

                            <div class="mb-3">
                                <label for="course-name" class="form-label">Course name *</label>
                                <input id="course-name" type="text" name="name" class="form-control" required aria-required="true">
                            </div>

                            <div class="mb-3">
                                <label for="course-programme" class="form-label">Programme (optional)</label>
                                <select id="course-programme" name="programme_id" class="form-select">
                                    <option value="">None</option>
                                    <?php foreach ($programmes as $p): ?>
                                        <option value="<?php echo (int)$p['id']; ?>">
                                            <?php echo htmlspecialchars($p['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="course-description" class="form-label">Description</label>
                                <textarea id="course-description" name="description" class="form-control" rows="3"></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                Create course
                            </button>
                        </form>
                    <?php else: ?>
                        <!-- Show basic info + (in future) editing fields -->
                        <div class="mb-4">
                            <h2 class="h5 mb-1">
                                <?php echo htmlspecialchars($course['name']); ?>
                            </h2>
                            <p class="text-muted small mb-1">
                                Programme:
                                <?php
                                    $pName = $course['programme_id'] 
                                        ? findProgrammeName($programmes, $course['programme_id']) 
                                        : null;
                                    echo $pName ? htmlspecialchars($pName) : '—';
                                ?>
                            </p>
                            <p class="text-muted small mb-0">
                                Created at: <?php echo htmlspecialchars($course['created_at']); ?>
                            </p>
                            <?php if (!empty($course['description'])): ?>
                                <hr>
                                <p><?php echo nl2br(htmlspecialchars($course['description'])); ?></p>
                            <?php endif; ?>
                        </div>

                        <hr class="my-4">

                        <!-- Add offering -->
                        <h2 class="h5 mb-3">Course offerings</h2>
                        <p class="text-muted small mb-3">
                            Add academic years and semesters for this course and see who follows each offering.
                        </p>

                        <form method="post" class="row g-2 align-items-end mb-4">
                            <input type="hidden" name="action" value="add_offering">
                            <input type="hidden" name="course_id" value="<?php echo (int)$courseId; ?>">

                            <div class="col-6 col-md-3">
                                <label for="offering-year" class="form-label">Year</label>
                                <input id="offering-year" type="number" name="year" class="form-control"
                                       value="<?php echo (int)date('Y'); ?>" min="2000" max="2100">
                            </div>

                            <div class="col-6 col-md-3">
                                <label for="offering-semester" class="form-label">Semester</label>
                                <select id="offering-semester" name="semester" class="form-select">
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                </select>
                            </div>

                            <div class="col-12 col-md-3">
                                <button type="submit" class="btn btn-outline-primary w-100">
                                    Add offering
                                </button>
                            </div>
                        </form>

                        <?php if (empty($offerings)): ?>
                            <p class="text-muted small">No offerings yet for this course.</p>
                        <?php else: ?>
                            <div class="table-responsive mb-4">
                                <table class="table table-sm align-middle">                                    <caption class="visually-hidden">List of course offerings with year, semester, teachers and actions</caption>                                    <thead class="table-light">
                                        <tr>
                                            <th>Year</th>
                                            <th>Semester</th>
                                            <th>Teachers</th>
                                            <th>Followers</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($offerings as $off): ?>
                                            <tr>
                                                <td><?php echo (int)$off['year']; ?></td>
                                                <td><?php echo htmlspecialchars($off['semester']); ?></td>
                                                <td class="small">
                                                    <?php echo $off['teacher_names']
                                                        ? htmlspecialchars($off['teacher_names'])
                                                        : '<span class="text-muted">None</span>'; ?>
                                                </td>
                                                <td class="small">
                                                    <?php echo (int)$off['follower_count']; ?>

                                                    <?php
                                                    // Lista dettagliata follower
                                                    $followers = $courseModel->getOfferingFollowers((int)$off['id']);
                                                    ?>

                                                    <?php if (!empty($followers)): ?>
                                                        <details class="mt-1">
                                                            <summary class="text-muted small" style="cursor:pointer;">
                                                                View followers
                                                            </summary>
                                                            <ul class="small mb-0 mt-1">
                                                                <?php foreach ($followers as $f): ?>
                                                                    <li>
                                                                        <?php echo htmlspecialchars($f['surname'] . ' ' . $f['name']); ?>
                                                                        (<?php echo htmlspecialchars($f['email']); ?>)
                                                                    </li>
                                                                <?php endforeach; ?>
                                                            </ul>
                                                        </details>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>

                        <!-- Add teacher to offering -->
                        <?php if (!empty($offerings) && !empty($teachers)): ?>
                            <hr class="my-4">
                            <h2 class="h5 mb-3">Assign teacher to an offering</h2>
                            <form method="post" class="row g-2 align-items-end">
                                <input type="hidden" name="action" value="add_teacher">
                                <input type="hidden" name="course_id" value="<?php echo (int)$courseId; ?>">

                                <div class="col-12 col-md-4">
                                    <label for="offering-id" class="form-label">Offering</label>
                                    <select id="offering-id" name="offering_id" class="form-select" required>
                                        <option value="">Select offering…</option>
                                        <?php foreach ($offerings as $off): ?>
                                            <option value="<?php echo (int)$off['id']; ?>">
                                                <?php
                                                    echo (int)$off['year'] . " - semester " .
                                                         htmlspecialchars($off['semester']);
                                                ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-12 col-md-4">
                                    <label for="teacher-select" class="form-label">Teacher</label>
                                    <select id="teacher-select" name="teacher_id" class="form-select" required>
                                        <option value="">Select teacher…</option>
                                        <?php foreach ($teachers as $t): ?>
                                            <option value="<?php echo (int)$t['person_id']; ?>">
                                                <?php echo htmlspecialchars($t['surname'] . ' ' . $t['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-12 col-md-4">
                                    <button type="submit" class="btn btn-outline-primary w-100">
                                        Add teacher to offering
                                    </button>
                                </div>
                            </form>
                        <?php elseif (!empty($offerings) && empty($teachers)): ?>
                            <p class="text-muted small mt-3">
                                There are no teachers in the system yet. Create them first in the admin area.
                            </p>
                        <?php endif; ?>

                    <?php endif; // end if course ?>
                </div>
            </div>

        </div>
    </div>
</div>
