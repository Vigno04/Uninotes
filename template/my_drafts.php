<div class="container py-4 py-lg-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 mb-1">My Drafts</h1>
            <p class="text-muted small mb-0">
                Draft notes that you haven't published yet.
            </p>
        </div>
        <a class="btn btn-outline-secondary btn-sm" href="index.php?page=account">
            <i class="bi bi-arrow-left me-1"></i> Back to Profile
        </a>
    </div>

    <?php if (empty($drafts)): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-file-earmark-text text-muted" style="font-size: 3rem;"></i>
                <p class="text-muted mt-3 mb-0">You don't have any drafts yet.</p>
                <a href="index.php?page=note_edit" class="btn btn-primary mt-3">
                    <i class="bi bi-plus-lg me-1"></i> Create Note
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($drafts as $draft): ?>
                <div class="col-12">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="badge bg-secondary me-2">
                                            <i class="bi bi-file-earmark me-1"></i>Draft
                                        </span>
                                        <small class="text-muted">
                                            Last modified: <?php echo date('d M Y', strtotime($draft['note_date'])); ?>
                                        </small>
                                    </div>
                                    <h2 class="h5 mb-2">
                                        <?php echo htmlspecialchars($draft['title']); ?>
                                    </h2>
                                    <div class="small text-muted mb-3">
                                        <i class="bi bi-book me-1"></i>
                                        <?php echo htmlspecialchars($draft['course_name']); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="index.php?page=note_edit&id=<?php echo (int)$draft['id']; ?>" 
                                   class="btn btn-primary btn-sm">
                                    <i class="bi bi-pencil me-1"></i> Edit
                                </a>
                                <a href="index.php?page=note_view&id=<?php echo (int)$draft['id']; ?>" 
                                   class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-eye me-1"></i> Preview
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
