<?php
// note_view.php - Controller for note viewing

// If someone opens note_view.php directly, redirect to router
if (basename($_SERVER['SCRIPT_NAME']) === 'note_view.php') {
    header('Location: index.php?page=note_view');
    exit;
}

require_once 'bootstrap.php';
require_once 'db/NoteModel.php';
require_once 'utils/MarkdownRenderer.php';

$id = $_GET['id'] ?? null;

if ($id !== null && is_numeric($id)) {
    $model = new NoteModel();
    $note  = $model->getById((int)$id);

    if ($note) {
        // -------- Files --------
        $files = $model->getFilesByNoteId($note['id']);

        // Build filename => file details map
        $fileMap = [];
        foreach ($files as $file) {
            $fileMap[$file['filename']] = [
                'id'   => $file['id'],
                'mime' => $file['mime_type'],
            ];
        }

        // Render Markdown to HTML using MarkdownRenderer
        $renderer    = new MarkdownRenderer();
        $htmlContent = $renderer->renderWithFiles($note['content'], $fileMap);

        // Add file list at the end if there are files
        $htmlContent .= MarkdownRenderer::generateFileList($files);

        // -------- Corrections --------
        $correctionModel = new CorrectionModel();
        $corrections = $correctionModel->getCorrectionsByNote($note['id']);

        // -------- Handle POST requests (corrections + voting) --------
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // New correction
            if (isset($_POST['submit_correction']) && isset($_SESSION['person_id'])) {
                $message = trim($_POST['message']);
                $file_id = !empty($_POST['file_id']) ? (int)$_POST['file_id'] : null;

                if (!empty($message)) {
                    $correctionModel->createCorrection($_SESSION['person_id'], $note['id'], $file_id, $message);
                    header("Location: " . $_SERVER['REQUEST_URI']);
                    exit;
                }
            }
            // Resolve correction
            elseif (
                isset($_POST['resolve_correction']) &&
                isset($_SESSION['person_id']) &&
                $_SESSION['person_id'] == $note['owner_id']
            ) {
                $correction_id = (int)$_POST['correction_id'];
                $correctionModel->resolveCorrection($correction_id, $note['id']);
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit;
            }
            // Reddit-style voting
            elseif (isset($_POST['vote']) && isset($_SESSION['person_id'])) {
                $voteModel = new VoteModel();
                $direction = $_POST['vote'];             // 'up' or 'down'
                $userId    = (int)$_SESSION['person_id'];
                $noteId    = (int)$note['id'];

                // vote: 1 = upvote, -1 = downvote
                $newValue = ($direction === 'up') ? 1 : -1;

                // Get existing vote, if any
                $currentVote = $voteModel->getUserVote($noteId, $userId);

                if ($currentVote === null) {
                    // First time voting → insert
                    $voteModel->vote($noteId, $userId, $newValue);
                } elseif ($currentVote === $newValue) {
                    // Same vote clicked again → undo
                    $voteModel->removeVote($noteId, $userId);
                } else {
                    // Switch up <-> down
                    $voteModel->vote($noteId, $userId, $newValue);
                }

                $redirectAnchor = strtok($_SERVER['REQUEST_URI'], '#') . '#note-vote';
                header("Location: " . $redirectAnchor);
                exit;
            }
        }

        // -------- Voting state for rendering --------
        $noteScore       = (int)($note['vote_count'] ?? 0);
        $currentUserVote = 0; // 1 = upvote, -1 = downvote, 0 = nothing

        if (isset($_SESSION['person_id'])) {
            $voteModel = new VoteModel();
            $userId = (int)$_SESSION['person_id'];
            $noteId = (int)$note['id'];

            $userVote = $voteModel->getUserVote($noteId, $userId);
            if ($userVote !== null) {
                $currentUserVote = $userVote; // 1 or -1
            }
        }

        $templateParams = ["title" => "Nota"];
    } else {
        echo "<div class='container mt-5'><h1>Nota non trovata</h1></div>";
        return;
    }
} else {
    echo "<div class='container mt-5'><h1>No note selected</h1></div>";
    return;
}
?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h1 class="mb-2">
                <?php echo htmlspecialchars($note['title'] ?? 'Nota'); ?>
            </h1>
            <?php if ($note['status'] === 'draft'): ?>
                <span class="badge bg-secondary">
                    <i class="bi bi-file-earmark me-1"></i>Draft
                </span>
            <?php endif; ?>
        </div>
        <?php if (isset($_SESSION['person_id']) && $_SESSION['person_id'] == $note['owner_id']): ?>
            <a href="index.php?page=note_edit&id=<?php echo (int)$note['id']; ?>" 
               class="btn btn-primary">
                <i class="bi bi-pencil me-1"></i> Edit
            </a>
        <?php endif; ?>
    </div>

    <div class="row">
        <!-- Note Content Column -->
        <div class="col-12 col-lg-8 mb-4">
            <div class="card note-card">
                <div class="card-body" style="position:relative;">
                    <div class="markdown-body"><?php echo $htmlContent; ?></div>

                    <!-- Voting buttons at the bottom-right, Reddit-style -->
                    <?php if (isset($_SESSION['person_id'])): ?>
                        <div id="note-vote" class="note-vote-fab">
                            <form method="post" class="d-inline">
                                <button
                                    type="submit"
                                    name="vote"
                                    value="up"
                                    class="btn btn-sm <?php echo ($currentUserVote === 1) ? 'btn-success' : 'btn-outline-secondary'; ?>"
                                    aria-pressed="<?php echo ($currentUserVote === 1) ? 'true' : 'false'; ?>"
                                    title="Upvote"
                                >
                                    ▲
                                </button>
                            </form>

                            <div class="mx-2 align-self-center fw-bold note-score">
                                <?php echo $noteScore; ?>
                            </div>

                            <form method="post" class="d-inline">
                                <button
                                    type="submit"
                                    name="vote"
                                    value="down"
                                    class="btn btn-sm <?php echo ($currentUserVote === -1) ? 'btn-danger' : 'btn-outline-secondary'; ?>"
                                    aria-pressed="<?php echo ($currentUserVote === -1) ? 'true' : 'false'; ?>"
                                    title="Downvote"
                                >
                                    ▼
                                </button>
                            </form>
                        </div>
                    <?php else: ?>
                        <div id="note-vote" class="note-vote-fab">
                            <p class="text-muted mb-0 small">Log in to vote</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Corrections Column -->
        <div class="col-12 col-lg-4">
            <div class="corrections-sticky-wrapper">
                <div class="card shadow-sm rounded-4 mb-4">
                    <div class="card-body p-4">
                        <h3 class="mb-3">Corrections</h3>
                        <?php if (!empty($corrections)): ?>
                            <div class="corrections-scrollable">
                                <?php foreach ($corrections as $corr): ?>
                                    <div class="card mb-3 border-0">
                                        <div class="card-body p-3">
                                            <p class="mb-1">
                                                <?php echo nl2br(htmlspecialchars($corr['message'])); ?>
                                            </p>
                                            <?php if ($corr['file_id']): ?>
                                                <small class="text-muted">
                                                    File: <?php echo htmlspecialchars($corr['filename']); ?>
                                                </small><br>
                                            <?php endif; ?>
                                            <small class="text-muted">
                                                By: <?php echo htmlspecialchars($corr['name'] . ' ' . $corr['surname']); ?>
                                                on <?php echo date('d/m/Y H:i', strtotime($corr['created_at'])); ?>
                                            </small>
                                            <?php if ($corr['resolved']): ?>
                                                <span class="badge bg-success ms-2">Resolved</span>
                                            <?php else: ?>
                                                <?php if (isset($_SESSION['person_id']) && $_SESSION['person_id'] == $note['owner_id']): ?>
                                                    <form method="post" class="d-inline ms-2">
                                                        <input type="hidden" name="correction_id" value="<?php echo $corr['id']; ?>">
                                                        <button type="submit" name="resolve_correction" class="btn btn-sm btn-success">Resolve</button>
                                                    </form>
                                                <?php endif; ?>
                                                <span class="badge bg-warning ms-2">Open</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No corrections yet.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (isset($_SESSION['person_id'])): ?>
                    <div class="card shadow-sm rounded-4">
                        <div class="card-body p-4">
                            <h3 class="mb-3">Report a correction</h3>
                            <form method="post">
                                <div class="mb-3">
                                    <label for="message" class="form-label">Message</label>
                                    <textarea class="form-control" id="message" name="message" rows="3" required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="file_id" class="form-label">Related file (optional)</label>
                                    <select class="form-select" id="file_id" name="file_id">
                                        <option value="">Note content</option>
                                        <?php foreach ($files as $file): ?>
                                            <option value="<?php echo $file['id']; ?>">
                                                <?php echo htmlspecialchars($file['filename']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" name="submit_correction" class="btn btn-primary">
                                    Submit Correction
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    window.MathJax = {
        tex: { inlineMath: [['$', '$'], ['\\(', '\\)']] },
        svg: { fontCache: 'global' }
    };
</script>
<script id="MathJax-script" async src="vendor/mathjax/tex-svg.js"></script>

<style>
/* Desktop corrections - sticky and scrollable */
@media (min-width: 992px) {
    .corrections-sticky-wrapper {
        position: sticky;
        top: 1rem;
    }
    .corrections-scrollable {
        max-height: calc(100vh - 300px);
        overflow-y: auto;
    }
}

/* Mobile view - stack vertically */
@media (max-width: 991.98px) {
    .corrections-sticky-wrapper {
        position: static;
    }
    .corrections-scrollable {
        max-height: none;
        overflow-y: visible;
    }
}
</style>

<style>
/* Voting FAB placed bottom-right inside the note card */
.note-card { position: relative; }
.note-vote-fab {
    position: absolute;
    right: 1rem;
    bottom: 1rem;
    display: flex;
    align-items: center;
    gap: .5rem;
    background: rgba(255,255,255,0.0);
}
.note-vote-fab .btn { padding: .25rem .5rem; font-size: 1rem; }
.note-score { font-size: 1rem; }

/* On small screens keep it inline below content (avoid overlapping) */
@media (max-width: 767.98px) {
    .note-vote-fab { position: static; margin-top: 1rem; justify-content: flex-end; }
    .note-card .card-body { padding-bottom: 3.5rem; }
}
</style>
