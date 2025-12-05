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

$programmes = [];
$resProg = mysqli_query($conn, "SELECT id, name FROM programme ORDER BY name");
if ($resProg) {
    while ($row = mysqli_fetch_assoc($resProg)) {
        $programmes[] = $row;
    }
}

$teachers = [];
$sqlTeachers = "
    SELECT t.person_id, p.name, p.surname
    FROM teacher t
    JOIN person p ON p.id = t.person_id
    ORDER BY p.surname, p.name
";
$resTeachers = mysqli_query($conn, $sqlTeachers);
if ($resTeachers) {
    while ($row = mysqli_fetch_assoc($resTeachers)) {
        $teachers[] = $row;
    }
}

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
            $sql = "INSERT INTO course (name, description, created_by, programme_id)
                    VALUES (?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            if ($stmt) {
                // programme_id può essere NULL
                if ($programmeId === null) {
                    mysqli_stmt_bind_param($stmt, "ssii", $name, $description, $currentAdminId, $programmeId);
                } else {
                    mysqli_stmt_bind_param($stmt, "ssii", $name, $description, $currentAdminId, $programmeId);
                }

                if (mysqli_stmt_execute($stmt)) {
                    $newId  = mysqli_insert_id($conn);
                    $courseId = $newId;
                    $success = "Course created successfully.";
                    // redirect alla pagina di edit per evitare resubmit
                    header("Location: index.php?page=admin-course-edit&id=" . $courseId);
                    exit;
                } else {
                    $errors[] = "Error while creating course (maybe duplicate name).";
                }
                mysqli_stmt_close($stmt);
            } else {
                $errors[] = "Internal error while preparing the query.";
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
            $sql = "INSERT INTO course_offering (year, semester, course_id) VALUES (?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "isi", $year, $semester, $courseId);
                if (mysqli_stmt_execute($stmt)) {
                    $success = "Course offering added.";
                } else {
                    // Probabile violazione unique (course_id, year, semester)
                    $errors[] = "Could not add offering. Maybe this year/semester already exists for this course.";
                }
                mysqli_stmt_close($stmt);
            } else {
                $errors[] = "Internal error while preparing the query.";
            }
        }

    } elseif ($action === 'add_teacher' && $courseId) {

        $offeringId = (int)($_POST['offering_id'] ?? 0);
        $teacherId  = (int)($_POST['teacher_id'] ?? 0);

        if ($offeringId <= 0 || $teacherId <= 0) {
            $errors[] = "Please select both offering and teacher.";
        }

        if (empty($errors)) {
            $sql = "INSERT IGNORE INTO course_offering_teacher (offering_id, teacher_id)
                    VALUES (?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "ii", $offeringId, $teacherId);
                if (mysqli_stmt_execute($stmt)) {
                    $success = "Teacher linked to the offering.";
                } else {
                    $errors[] = "Could not link teacher to offering.";
                }
                mysqli_stmt_close($stmt);
            } else {
                $errors[] = "Internal error while preparing the query.";
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
    $sql = "
        SELECT 
            c.id,
            c.name,
            c.description,
            c.programme_id,
            c.created_at
        FROM course c
        WHERE c.id = ?
        LIMIT 1
    ";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $courseId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $course = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);
    }

    if ($course) {
        // Offerte del corso + docenti + numero follower
        $sqlOff = "
            SELECT
                co.id,
                co.year,
                co.semester,
                GROUP_CONCAT(DISTINCT CONCAT(p_t.name, ' ', p_t.surname) ORDER BY p_t.surname SEPARATOR ', ') AS teacher_names,
                COUNT(DISTINCT cof.user_id) AS follower_count
            FROM course_offering co
            LEFT JOIN course_offering_teacher cot ON cot.offering_id = co.id
            LEFT JOIN teacher t ON t.person_id = cot.teacher_id
            LEFT JOIN person p_t ON p_t.id = t.person_id
            LEFT JOIN course_offering_follow cof ON cof.offering_id = co.id
            WHERE co.course_id = ?
            GROUP BY co.id, co.year, co.semester
            ORDER BY co.year DESC, co.semester DESC
        ";
        $stmtOff = mysqli_prepare($conn, $sqlOff);
        if ($stmtOff) {
            mysqli_stmt_bind_param($stmtOff, "i", $courseId);
            mysqli_stmt_execute($stmtOff);
            $resOff = mysqli_stmt_get_result($stmtOff);
            while ($row = mysqli_fetch_assoc($resOff)) {
                $offerings[] = $row;
            }
            mysqli_stmt_close($stmtOff);
        }
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

                    <!-- FORM: Create / basic info course -->
                    <?php if (!$course): ?>
                        <form method="post">
                            <input type="hidden" name="action" value="create_course">

                            <div class="mb-3">
                                <label class="form-label">Course name *</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Programme (optional)</label>
                                <select name="programme_id" class="form-select">
                                    <option value="">None</option>
                                    <?php foreach ($programmes as $p): ?>
                                        <option value="<?php echo (int)$p['id']; ?>">
                                            <?php echo htmlspecialchars($p['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3"></textarea>
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
                                <label class="form-label">Year</label>
                                <input type="number" name="year" class="form-control"
                                       value="<?php echo (int)date('Y'); ?>" min="2000" max="2100">
                            </div>

                            <div class="col-6 col-md-3">
                                <label class="form-label">Semester</label>
                                <select name="semester" class="form-select">
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
                                <table class="table table-sm align-middle">
                                    <thead class="table-light">
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
                                                    $followers = [];
                                                    $sqlFol = "
                                                        SELECT p.name, p.surname, p.email
                                                        FROM course_offering_follow cof
                                                        JOIN user u ON u.person_id = cof.user_id
                                                        JOIN person p ON p.id = u.person_id
                                                        WHERE cof.offering_id = ?
                                                        ORDER BY p.surname, p.name
                                                    ";
                                                    $stmtFol = mysqli_prepare($conn, $sqlFol);
                                                    if ($stmtFol) {
                                                        $oid = (int)$off['id'];
                                                        mysqli_stmt_bind_param($stmtFol, "i", $oid);
                                                        mysqli_stmt_execute($stmtFol);
                                                        $resFol = mysqli_stmt_get_result($stmtFol);
                                                        while ($rowF = mysqli_fetch_assoc($resFol)) {
                                                            $followers[] = $rowF;
                                                        }
                                                        mysqli_stmt_close($stmtFol);
                                                    }
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
                                    <label class="form-label">Offering</label>
                                    <select name="offering_id" class="form-select" required>
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
                                    <label class="form-label">Teacher</label>
                                    <select name="teacher_id" class="form-select" required>
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
