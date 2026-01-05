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
        $courseModel = new CourseModel();
        
        if (isset($_POST['follow_offering'])) {
            $courseModel->followCourseOffering($offeringId, $currentUserId);
        } elseif (isset($_POST['unfollow_offering'])) {
            $courseModel->unfollowCourseOffering($offeringId, $currentUserId);
        }
    }

    header('Location: index.php?page=course_offerings&course_id=' . $courseId);
    exit;
}

// Get course info
$courseModel = new CourseModel();
$course = $courseModel->getCourse($courseId);

if (!$course) {
    header('Location: index.php?page=courses');
    exit;
}

// Get course offerings with notes count and teachers
$offerings = $courseModel->getCourseOfferingsWithNotes($courseId);

// Get followed offerings for this user
$followedOfferingIds = $courseModel->getFollowedOfferingIds($currentUserId);
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
            <div class="alert alert-info" role="status" aria-live="polite">
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
