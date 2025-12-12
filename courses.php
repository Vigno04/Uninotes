<?php
require_once("bootstrap.php");

if (!isset($_SESSION['person_id'])) {
    header('Location: login.php');
    exit;
}

$currentUserId = (int)$_SESSION['person_id'];

// tutti i corsi con info su offerings
$allCourses = [];
$sqlCourses = "
    SELECT 
        c.id, 
        c.name, 
        c.description,
        COUNT(DISTINCT co.id) AS offering_count,
        COUNT(DISTINCT n.id) AS note_count,
        (SELECT co2.id FROM course_offering co2 WHERE co2.course_id = c.id ORDER BY co2.year DESC, co2.semester DESC LIMIT 1) AS single_offering_id,
        (SELECT co2.year FROM course_offering co2 WHERE co2.course_id = c.id ORDER BY co2.year DESC, co2.semester DESC LIMIT 1) AS single_offering_year,
        (SELECT co2.semester FROM course_offering co2 WHERE co2.course_id = c.id ORDER BY co2.year DESC, co2.semester DESC LIMIT 1) AS single_offering_semester
    FROM course c
    LEFT JOIN course_offering co ON co.course_id = c.id
    LEFT JOIN topic t ON t.offering_id = co.id
    LEFT JOIN note n ON n.topic_id = t.id AND n.status = 'published'
    GROUP BY c.id, c.name, c.description
    ORDER BY c.name
";
$resAll = mysqli_query($conn, $sqlCourses);
if ($resAll) {
    while ($row = mysqli_fetch_assoc($resAll)) {
        $allCourses[] = $row;
    }
}

// corsi con almeno un offering seguito + info sugli offerings seguiti
$followedCourseIds = [];
$followedOfferingsPerCourse = []; // [course_id => [offering_id, ...]]

$sqlFollowed = "
    SELECT 
        c.id AS course_id,
        co.id AS offering_id,
        co.year,
        co.semester
    FROM course_offering_follow cof
    JOIN course_offering co ON cof.offering_id = co.id
    JOIN course c ON co.course_id = c.id
    WHERE cof.user_id = ?
    ORDER BY c.id, co.year DESC, co.semester DESC
";
$stmtF = mysqli_prepare($conn, $sqlFollowed);
if ($stmtF) {
    mysqli_stmt_bind_param($stmtF, "i", $currentUserId);
    mysqli_stmt_execute($stmtF);
    $resF = mysqli_stmt_get_result($stmtF);
    while ($rowF = mysqli_fetch_assoc($resF)) {
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
    mysqli_stmt_close($stmtF);
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
                                    } else {
                                        // Se segui più offerings, vai alla pagina degli offerings
                                        $href = 'index.php?page=course_offerings&course_id=' . $courseId;
                                        $btnText = 'View offerings →';
                                    }
                                    ?>
                                    <a href="<?php echo $href; ?>"
                                       class="btn btn-sm btn-primary w-100">
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
