<?php
// user_notes_upvoted.php

require_once 'bootstrap.php';

if (!isset($_SESSION['person_id'])) {
    header('Location: login.php');
    exit;
}

$personId = (int)$_SESSION['person_id'];

$voteModel = new VoteModel();
$notes = $voteModel->getNotesUpvotedByUser($personId);
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
