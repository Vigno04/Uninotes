<?php
require_once("bootstrap.php");

if (!isset($_SESSION['person_id'])) {
    header('Location: login.php');
    exit;
}

$currentUserId = (int)$_SESSION['person_id'];
$courseId = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

if ($courseId <= 0) {
    header('Location: index.php?page=courses');
    exit;
}

// POST follow / unfollow per course offering
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $offeringId = isset($_POST['offering_id']) ? (int)$_POST['offering_id'] : 0;

    if ($offeringId > 0) {
        if (isset($_POST['follow_offering'])) {
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
        } elseif (isset($_POST['unfollow_offering'])) {
            $sqlUnfollow = "
                DELETE FROM course_offering_follow
                WHERE offering_id = ? AND user_id = ?
            ";
            $stmtUnf = mysqli_prepare($conn, $sqlUnfollow);
            if ($stmtUnf) {
                mysqli_stmt_bind_param($stmtUnf, "ii", $offeringId, $currentUserId);
                mysqli_stmt_execute($stmtUnf);
                mysqli_stmt_close($stmtUnf);
            }
        }
    }

    header('Location: index.php?page=course_offerings&course_id=' . $courseId);
    exit;
}

// Get course info
$course = null;
$sqlCourse = "SELECT id, name, description FROM course WHERE id = ?";
$stmtCourse = mysqli_prepare($conn, $sqlCourse);
if ($stmtCourse) {
    mysqli_stmt_bind_param($stmtCourse, "i", $courseId);
    mysqli_stmt_execute($stmtCourse);
    $resCourse = mysqli_stmt_get_result($stmtCourse);
    $course = mysqli_fetch_assoc($resCourse);
    mysqli_stmt_close($stmtCourse);
}

if (!$course) {
    header('Location: index.php?page=courses');
    exit;
}

// Get course offerings with notes count and teachers
$offerings = [];
$sqlOfferings = "
    SELECT 
        co.id,
        co.year,
        co.semester,
        COUNT(DISTINCT n.id) AS note_count,
        GROUP_CONCAT(DISTINCT CONCAT(p.name, ' ', p.surname) SEPARATOR ', ') AS teachers
    FROM course_offering co
    LEFT JOIN topic t ON t.offering_id = co.id
    LEFT JOIN note n ON n.topic_id = t.id AND n.status = 'published'
    LEFT JOIN course_offering_teacher cot ON cot.offering_id = co.id
    LEFT JOIN teacher te ON te.person_id = cot.teacher_id
    LEFT JOIN person p ON p.id = te.person_id
    WHERE co.course_id = ?
    GROUP BY co.id, co.year, co.semester
    ORDER BY co.year DESC, co.semester DESC
";

$stmtOff = mysqli_prepare($conn, $sqlOfferings);
if ($stmtOff) {
    mysqli_stmt_bind_param($stmtOff, "i", $courseId);
    mysqli_stmt_execute($stmtOff);
    $resOff = mysqli_stmt_get_result($stmtOff);
    while ($rowOff = mysqli_fetch_assoc($resOff)) {
        $offerings[] = $rowOff;
    }
    mysqli_stmt_close($stmtOff);
}

// Get followed offerings for this user
$followedOfferingIds = [];
$sqlFollowed = "
    SELECT offering_id
    FROM course_offering_follow
    WHERE user_id = ?
";
$stmtF = mysqli_prepare($conn, $sqlFollowed);
if ($stmtF) {
    mysqli_stmt_bind_param($stmtF, "i", $currentUserId);
    mysqli_stmt_execute($stmtF);
    $resF = mysqli_stmt_get_result($stmtF);
    while ($rowF = mysqli_fetch_assoc($resF)) {
        $followedOfferingIds[] = (int)$rowF['offering_id'];
    }
    mysqli_stmt_close($stmtF);
}
?>

<div class="container py-4">
    <div class="mb-4">
        <a href="index.php?page=courses" class="small text-muted text-decoration-none">
            ← Back to courses
        </a>
    </div>

    <div class="mb-4">
        <h1 class="h4 mb-1"><?php echo htmlspecialchars($course['name']); ?></h1>
        <?php if ($course['description']): ?>
            <p class="text-muted mb-0">
                <?php echo htmlspecialchars($course['description']); ?>
            </p>
        <?php endif; ?>
    </div>

    <div class="mb-4">
        <h2 class="h5 mb-3">Course Offerings</h2>
        <p class="text-muted small mb-3">
            Select the course offering(s) you want to follow. You'll see notes from followed offerings in your home page.
        </p>

        <?php if (empty($offerings)): ?>
            <div class="alert alert-info">
                No course offerings available for this course yet.
            </div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($offerings as $offering): ?>
                    <?php 
                    $isFollowed = in_array((int)$offering['id'], $followedOfferingIds, true);
                    $semesterLabel = $offering['semester'] === '1' ? '1st Semester' : '2nd Semester';
                    ?>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card border-0 shadow-sm rounded-4 h-100">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h3 class="h6 mb-0">
                                            <?php echo htmlspecialchars($offering['year']); ?> - <?php echo $semesterLabel; ?>
                                        </h3>
                                    </div>
                                    <?php if ($isFollowed): ?>
                                        <span class="badge bg-success">Following</span>
                                    <?php endif; ?>
                                </div>

                                <?php if ($offering['teachers']): ?>
                                    <p class="text-muted small mb-2">
                                        <strong>Teachers:</strong> <?php echo htmlspecialchars($offering['teachers']); ?>
                                    </p>
                                <?php endif; ?>

                                <p class="text-muted small mb-3">
                                    <?php echo (int)$offering['note_count']; ?> notes available
                                </p>

                                <div class="mt-auto d-flex justify-content-between align-items-center gap-2">
                                    <a href="index.php?page=home&offering_id=<?php echo (int)$offering['id']; ?>"
                                       class="small text-primary text-decoration-none">
                                        View notes →
                                    </a>

                                    <form method="post">
                                        <input type="hidden" name="offering_id" value="<?php echo (int)$offering['id']; ?>">
                                        
                                        <?php if ($isFollowed): ?>
                                            <button type="submit"
                                                    name="unfollow_offering"
                                                    class="btn btn-sm btn-outline-danger">
                                                Unfollow
                                            </button>
                                        <?php else: ?>
                                            <button type="submit"
                                                    name="follow_offering"
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
        <?php endif; ?>
    </div>
</div>
