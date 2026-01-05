<?php
require_once("bootstrap.php");

if (!isset($_SESSION['person_id'])) {
    header('Location: login.php');
    exit;
}

$currentUserId = (int)$_SESSION['person_id'];

// tutti i corsi con info su offerings
$courseModel = new CourseModel();
$allCourses = $courseModel->getAllCoursesWithStats();

// corsi con almeno un offering seguito + info sugli offerings seguiti
$followedCourseIds = [];
$followedOfferingsPerCourse = []; // [course_id => [offering_id, ...]]

$followedData = $courseModel->getFollowedCoursesWithOfferings($currentUserId);
foreach ($followedData as $rowF) {
    $courseId = (int)$rowF['course_id'];
    if (!in_array($courseId, $followedCourseIds, true)) {
        $followedCourseIds[] = $courseId;
    }
    if (!isset($followedOfferingsPerCourse[$courseId])) {
        $followedOfferingsPerCourse[$courseId] = [];
    }
    $followedOfferingsPerCourse[$courseId][] = [
        'id' => (int)$rowF['offering_id'],
        'year' => $rowF['year'],
        'semester' => $rowF['semester']
    ];
}
?>

<div class="container py-4">
    <div class="mb-4 d-flex justify-content-between align-items-center">
        <div>
            <h1 class="h4 mb-1">Courses</h1>
            <p class="text-muted small mb-0">
                Browse courses and view their offerings to follow them.
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
            <p class="text-muted small">You are not following any course offering yet.</p>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($myCourses as $course): ?>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card border-0 shadow-sm rounded-4 h-100">
                            <div class="card-body d-flex flex-column">
                                <h3 class="h6 mb-1">
                                    <?php echo htmlspecialchars($course['name']); ?>
                                </h3>
                                <?php 
                                $courseId = (int)$course['id'];
                                $followedOfferings = $followedOfferingsPerCourse[$courseId] ?? [];
                                $followedCount = count($followedOfferings);
                                
                                // Mostra info dell'offering se ne segui solo uno
                                if ($followedCount === 1): 
                                    $offering = $followedOfferings[0];
                                    $semLabel = $offering['semester'] === '1' ? '1st' : '2nd';
                                ?>
                                    <p class="text-primary small mb-2">
                                        <?php echo htmlspecialchars($offering['year']) . ' - ' . $semLabel . ' Semester'; ?>
                                    </p>
                                <?php elseif ($followedCount > 1): ?>
                                    <p class="text-success small mb-2">
                                        Following <?php echo $followedCount; ?> offerings
                                    </p>
                                <?php endif; ?>
                                <p class="text-muted small mb-2">
                                    <?php echo htmlspecialchars($course['description'] ?? 'Study programme course'); ?>
                                </p>
                                <p class="text-muted small mb-3">
                                    <?php echo (int)$course['offering_count']; ?> offerings • 
                                    <?php echo (int)$course['note_count']; ?> notes
                                </p>

                                <div class="mt-auto">
                                    <?php
                                    // Se segui solo un offering, vai direttamente lì
                                    if ($followedCount === 1) {
                                        $href = 'index.php?page=home&offering_id=' . (int)$followedOfferings[0]['id'];
                                        $btnText = 'View notes →';
                                        $ariaLabel = 'View notes for ' . htmlspecialchars($course['name']);
                                    } else {
                                        // Se segui più offerings, vai alla pagina degli offerings
                                        $href = 'index.php?page=course_offerings&course_id=' . $courseId;
                                        $btnText = 'View offerings →';
                                        $ariaLabel = 'View offerings for ' . htmlspecialchars($course['name']);
                                    }
                                    ?>
                                    <a href="<?php echo $href; ?>"
                                       class="btn btn-sm btn-primary w-100"
                                       aria-label="<?php echo $ariaLabel; ?>">
                                        <?php echo $btnText; ?>
                                    </a>
                                    
                                    <?php if ($followedCount === 1 && (int)$course['offering_count'] > 1): ?>
                                        <a href="index.php?page=course_offerings&course_id=<?php echo $courseId; ?>"
                                           class="btn btn-sm btn-outline-secondary w-100 mt-2">
                                            View all offerings
                                        </a>
                                    <?php endif; ?>
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
                <?php $isFollowed = in_array((int)$course['id'], $followedCourseIds, true); ?>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h3 class="h6 mb-1">
                                    <?php echo htmlspecialchars($course['name']); ?>
                                </h3>
                                <?php if ($isFollowed): ?>
                                    <span class="badge bg-success">Following</span>
                                <?php endif; ?>
                            </div>
                            <?php if ((int)$course['offering_count'] === 1 && $course['single_offering_year']): ?>
                                <p class="text-primary small mb-2">
                                    <?php 
                                    $semLabel = $course['single_offering_semester'] === '1' ? '1st' : '2nd';
                                    echo htmlspecialchars($course['single_offering_year']) . ' - ' . $semLabel . ' Semester';
                                    ?>
                                </p>
                            <?php endif; ?>
                            <p class="text-muted small mb-2">
                                <?php echo htmlspecialchars($course['description'] ?? 'Study programme course'); ?>
                            </p>
                            <p class="text-muted small mb-3">
                                <?php echo (int)$course['offering_count']; ?> offerings • 
                                <?php echo (int)$course['note_count']; ?> notes
                            </p>

                            <div class="mt-auto">
                                <?php
                                $offeringCount = (int)$course['offering_count'];
                                if ($offeringCount === 1 && $course['single_offering_id']) {
                                    $href = 'index.php?page=home&offering_id=' . (int)$course['single_offering_id'];
                                    $btnText = 'View notes →';
                                } else {
                                    $href = 'index.php?page=course_offerings&course_id=' . (int)$course['id'];
                                    $btnText = 'View offerings →';
                                }
                                ?>
                                <a href="<?php echo $href; ?>"
                                   class="btn btn-sm btn-outline-primary w-100">
                                    <?php echo $btnText; ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
