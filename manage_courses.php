<?php
// admin-manage-courses.php
require_once("bootstrap.php");

if (!isset($_SESSION['person_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: login.php');
    exit;
}

// Query: corsi + numero offerte + numero note
$sql = "
    SELECT 
        c.id,
        c.name,
        c.description,
        COUNT(DISTINCT co.id) AS offering_count,
        COUNT(DISTINCT n.id)  AS note_count
    FROM course c
    LEFT JOIN course_offering co ON co.course_id = c.id
    LEFT JOIN topic t            ON t.offering_id = co.id
    LEFT JOIN note n             ON n.topic_id = t.id
    GROUP BY c.id, c.name, c.description
    ORDER BY c.name
";

$result = mysqli_query($conn, $sql);
$courses = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $courses[] = $row;
    }
}
?>

<div class="container py-4 py-lg-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10">

            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4 p-lg-5">
                    <h1 class="h4 mb-3">Manage courses</h1>
                    <p class="text-muted mb-4">
                        Overview of all courses in UniNotes. Later you’ll be able to edit and create new ones.
                    </p>

                    <?php if (empty($courses)): ?>
                        <p class="text-muted">No courses found.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Course</th>
                                        <th>Description</th>
                                        <th>Offerings</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($courses as $c): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($c['name']); ?></td>
                                            <td class="small text-muted" style="max-width: 360px;">
                                                <?php echo htmlspecialchars($c['description'] ?? ''); ?>
                                            </td>
                                            <td><?php echo (int)$c['offering_count']; ?></td>
                                            <td><?php echo (int)$c['note_count']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <div class="mt-4">
                        <a href="index.php?page=admindashboard" class="btn btn-outline-secondary btn-sm">
                            ← Back to dashboard
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
