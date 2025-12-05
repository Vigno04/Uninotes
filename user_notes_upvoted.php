<?php
// user_notes_upvoted.php

require_once 'bootstrap.php';

if (!isset($_SESSION['person_id'])) {
    header('Location: login.php');
    exit;
}

$personId = (int)$_SESSION['person_id'];

$sql = "
    SELECT
        n.id,
        n.title,
        COALESCE(n.published_at, n.created_at) AS note_date,
        c.name AS course_name,
        n.vote_count
    FROM vote v
    JOIN note n ON v.note_id = n.id
    JOIN topic t ON n.topic_id = t.id
    JOIN course_offering co ON t.offering_id = co.id
    JOIN course c ON co.course_id = c.id
    WHERE v.user_id = ?
      AND v.vote = 1
    GROUP BY n.id
    ORDER BY note_date DESC
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $personId);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

$notes = [];
while ($row = mysqli_fetch_assoc($res)) {
    $notes[] = $row;
}
mysqli_stmt_close($stmt);
?>

<div class="container py-4 py-lg-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 mb-1">Notes You Upvoted</h1>
            <p class="text-muted small mb-0">
                All notes you have given an upvote to.
            </p>
        </div>
        <a class="nav-link <?php echo $currentPage === 'account' ? 'active' : ''; ?>"
            href="index.php?page=<?php echo $profilePage; ?>">
            ← Back to profile
        </a>
    </div>

    <?php if (empty($notes)): ?>
        <p class="text-muted">You haven't upvoted any notes yet.</p>
    <?php else: ?>
        <?php foreach ($notes as $note): ?>
            <div class="card mb-3 border-0 shadow-sm rounded-4">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <a href="index.php?page=note_view&id=<?php echo (int)$note['id']; ?>"
                           class="text-decoration-none">
                            <h2 class="h6 mb-1">
                                <?php echo htmlspecialchars($note['title']); ?>
                            </h2>
                        </a>
                        <div class="small text-muted">
                            <?php echo htmlspecialchars($note['course_name']); ?> ·
                            <?php echo date('d M Y', strtotime($note['note_date'])); ?>
                        </div>
                    </div>
                    <div class="small text-muted">
                        👍 <?php echo (int)$note['vote_count']; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
