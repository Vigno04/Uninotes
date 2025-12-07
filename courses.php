<?php
require_once("bootstrap.php");

if (!isset($_SESSION['person_id'])) {
    header('Location: login.php');
    exit;
}

$currentUserId = (int)$_SESSION['person_id'];

// POST follow / unfollow (copiato da home.php)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $courseId = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;

    if ($courseId > 0) {
        if (isset($_POST['follow_course'])) {
            $sqlOffering = "
                SELECT id
                FROM course_offering
                WHERE course_id = ?
                ORDER BY year DESC, semester DESC
                LIMIT 1
            ";
            $stmtOff = mysqli_prepare($conn, $sqlOffering);
            if ($stmtOff) {
                mysqli_stmt_bind_param($stmtOff, "i", $courseId);
                mysqli_stmt_execute($stmtOff);
                $resOff = mysqli_stmt_get_result($stmtOff);
                if ($rowOff = mysqli_fetch_assoc($resOff)) {
                    $offeringId = (int)$rowOff['id'];

                    $sqlFollow = "
                        INSERT IGNORE INTO course_offering_follow (offering_id, user_id)
                        VALUES (?, ?)
                    ";
                    $stmtFollow = mysqli_prepare($conn, $sqlFollow);
                    if ($stmtFollow) {
                        mysqli_stmt_bind_param($stmtFollow, "ii", $offeringId, $currentUserId);
                        mysqli_stmt_execute($stmtFollow);
                        mysqli_stmt_close($stmtFollow);
                    }
                }
                mysqli_stmt_close($stmtOff);
            }
        } elseif (isset($_POST['unfollow_course'])) {
            $sqlUnfollow = "
                DELETE cof
                FROM course_offering_follow cof
                JOIN course_offering co ON cof.offering_id = co.id
                WHERE co.course_id = ? AND cof.user_id = ?
            ";
            $stmtUnf = mysqli_prepare($conn, $sqlUnfollow);
            if ($stmtUnf) {
                mysqli_stmt_bind_param($stmtUnf, "ii", $courseId, $currentUserId);
                mysqli_stmt_execute($stmtUnf);
                mysqli_stmt_close($stmtUnf);
            }
        }
    }

    header('Location: index.php?page=courses');
    exit;
}

// tutti i corsi
$allCourses = [];
$resAll = mysqli_query($conn, "SELECT id, name, description FROM course ORDER BY name");
if ($resAll) {
    while ($row = mysqli_fetch_assoc($resAll)) {
        $allCourses[] = $row;
    }
}

// corsi seguiti
$followedCourseIds = [];
$sqlFollowed = "
    SELECT DISTINCT c.id AS course_id
    FROM course_offering_follow cof
    JOIN course_offering co ON cof.offering_id = co.id
    JOIN course c ON co.course_id = c.id
    WHERE cof.user_id = ?
";
$stmtF = mysqli_prepare($conn, $sqlFollowed);
if ($stmtF) {
    mysqli_stmt_bind_param($stmtF, "i", $currentUserId);
    mysqli_stmt_execute($stmtF);
    $resF = mysqli_stmt_get_result($stmtF);
    while ($rowF = mysqli_fetch_assoc($resF)) {
        $followedCourseIds[] = (int)$rowF['course_id'];
    }
    mysqli_stmt_close($stmtF);
}
?>

<div class="container py-4">
    <div class="mb-4 d-flex justify-content-between align-items-center">
        <div>
            <h1 class="h4 mb-1">Courses</h1>
            <p class="text-muted small mb-0">
                Follow courses to see their notes quickly in your home page.
            </p>
        </div>
    </div>

    <!-- My courses -->
    <div class="mb-4">
        <h2 class="h5 mb-3">My courses</h2>

        <?php
        $myCourses = array_filter($allCourses, fn($c) => in_array((int)$c['id'], $followedCourseIds, true));
        ?>

        <?php if (empty($myCourses)): ?>
            <p class="text-muted small">You are not following any course yet.</p>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($myCourses as $course): ?>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card border-0 shadow-sm rounded-4 h-100">
                            <div class="card-body d-flex flex-column">
                                <h3 class="h6 mb-1">
                                    <?php echo htmlspecialchars($course['name']); ?>
                                </h3>
                                <p class="text-muted small mb-3">
                                    <?php echo htmlspecialchars($course['description'] ?? 'Study programme course'); ?>
                                </p>

                                <div class="mt-auto d-flex justify-content-between align-items-center">
                                    <a href="index.php?page=home&course_id=<?php echo (int)$course['id']; ?>"
                                       class="small text-primary text-decoration-none">
                                        View notes →
                                    </a>

                                    <form method="post" class="ms-2">
                                        <input type="hidden" name="course_id" value="<?php echo (int)$course['id']; ?>">
                                        <button type="submit"
                                                name="unfollow_course"
                                                class="btn btn-sm btn-outline-danger">
                                            Unfollow
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <hr class="my-4">

    <!-- All courses -->
    <div class="mb-4">
        <h2 class="h5 mb-3">All courses</h2>

        <div class="row g-3">
            <?php foreach ($allCourses as $course): ?>
                <?php $alreadyAdded = in_array((int)$course['id'], $followedCourseIds, true); ?>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body d-flex flex-column">
                            <h3 class="h6 mb-1">
                                <?php echo htmlspecialchars($course['name']); ?>
                            </h3>
                            <p class="text-muted small mb-3">
                                <?php echo htmlspecialchars($course['description'] ?? 'Study programme course'); ?>
                            </p>

                            <div class="mt-auto d-flex justify-content-between align-items-center">
                                <a href="index.php?page=home&course_id=<?php echo (int)$course['id']; ?>"
                                   class="small text-primary text-decoration-none">
                                    View notes →
                                </a>

                                <form method="post" class="ms-2">
                                    <input type="hidden" name="course_id" value="<?php echo (int)$course['id']; ?>">

                                    <?php if ($alreadyAdded): ?>
                                        <button type="submit"
                                                name="unfollow_course"
                                                class="btn btn-sm btn-outline-danger">
                                            Unfollow
                                        </button>
                                    <?php else: ?>
                                        <button type="submit"
                                                name="follow_course"
                                                class="btn btn-sm btn-outline-success">
                                            Follow
                                        </button>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
