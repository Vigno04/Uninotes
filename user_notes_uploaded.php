<?php
// user_notes_uploaded.php

require_once 'bootstrap.php';

if (!isset($_SESSION['person_id'])) {
    header('Location: login.php');
    exit;
}

$personId = (int)$_SESSION['person_id'];

$noteModel = new NoteModel();
$notes = $noteModel->getNotesUploadedByUser($personId);
?>

<div class="container py-4 py-lg-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 mb-1">My Notes</h1>
            <p class="text-muted small mb-0">
                All your notes: drafts and published.
            </p>
        </div>
        <a class="btn btn-outline-secondary btn-sm" href="index.php?page=account">
            <i class="bi bi-arrow-left me-1"></i> Back to Profile
        </a>
    </div>

    <?php if (empty($notes)): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-file-earmark-text text-muted" style="font-size: 3rem;"></i>
                <p class="text-muted mt-3 mb-0">You haven't uploaded any notes yet.</p>
                <a href="index.php?page=note_edit" class="btn btn-primary mt-3">
                    <i class="bi bi-plus-lg me-1"></i> Create Note
                </a>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($notes as $note): ?>
            <div class="card mb-3 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <?php if ($note['status'] === 'draft'): ?>
                                    <span class="badge bg-secondary">
                                        <i class="bi bi-file-earmark me-1"></i>Draft
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-success">
                                        <i class="bi bi-check-circle me-1"></i>Published
                                    </span>
                                <?php endif; ?>
                                <small class="text-muted">
                                    <?php echo date('d M Y', strtotime($note['note_date'])); ?>
                                </small>
                            </div>
                            <a href="index.php?page=note_view&id=<?php echo (int)$note['id']; ?>"
                               class="text-decoration-none">
                                <h2 class="h5 mb-1">
                                    <?php echo htmlspecialchars($note['title']); ?>
                                </h2>
                            </a>
                            <div class="small text-muted">
                                <i class="bi bi-book me-1"></i>
                                <?php echo htmlspecialchars($note['course_name']); ?>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <div class="text-center">
                                <i class="bi bi-hand-thumbs-up text-primary"></i>
                                <div class="small text-muted"><?php echo (int)$note['vote_count']; ?></div>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="index.php?page=note_edit&id=<?php echo (int)$note['id']; ?>" 
                                   class="btn btn-outline-primary btn-sm"
                                   title="Edit note">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                                <a href="index.php?page=note_view&id=<?php echo (int)$note['id']; ?>" 
                                   class="btn btn-outline-secondary btn-sm"
                                   title="View note">
                                    <i class="bi bi-eye"></i> View
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
