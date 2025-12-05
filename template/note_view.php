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
        $corrections = [];

        $sql = "SELECT c.id, c.message, c.file_id, c.resolved, c.created_at,
                       f.filename, p.name, p.surname
                FROM correction c
                LEFT JOIN file f ON c.file_id = f.id
                LEFT JOIN user u ON c.reported_by = u.person_id
                LEFT JOIN person p ON u.person_id = p.id
                WHERE c.note_id = ?
                ORDER BY c.created_at DESC";

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $note['id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $corrections[] = $row;
        }
        mysqli_stmt_close($stmt);

        // -------- Handle POST requests (corrections + voting) --------
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // New correction
            if (isset($_POST['submit_correction']) && isset($_SESSION['person_id'])) {
                $message = trim($_POST['message']);
                $file_id = !empty($_POST['file_id']) ? (int)$_POST['file_id'] : null;

                if (!empty($message)) {
                    $stmt = mysqli_prepare(
                        $conn,
                        "INSERT INTO correction (reported_by, note_id, file_id, message)
                         VALUES (?, ?, ?, ?)"
                    );
                    mysqli_stmt_bind_param(
                        $stmt,
                        "iiis",
                        $_SESSION['person_id'],
                        $note['id'],
                        $file_id,
                        $message
                    );
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);

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
                $stmt = mysqli_prepare(
                    $conn,
                    "UPDATE correction
                     SET resolved = 1, resolved_at = NOW()
                     WHERE id = ? AND note_id = ?"
                );
                mysqli_stmt_bind_param($stmt, "ii", $correction_id, $note['id']);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                header("Location: " . $_SERVER['REQUEST_URI']);
                exit;
            }
            // Reddit-style voting
            elseif (isset($_POST['vote']) && isset($_SESSION['person_id'])) {
                $direction = $_POST['vote'];             // 'up' or 'down'
                $userId    = (int)$_SESSION['person_id'];
                $noteId    = (int)$note['id'];

                // vote column: 1 = upvote, 0 = downvote
                $newValue = ($direction === 'up') ? 1 : 0;

                // Existing vote, if any
                $stmt = mysqli_prepare($conn, "SELECT vote FROM vote WHERE note_id = ? AND user_id = ?");
                mysqli_stmt_bind_param($stmt, "ii", $noteId, $userId);
                mysqli_stmt_execute($stmt);
                $res     = mysqli_stmt_get_result($stmt);
                $hasRow  = false;
                $oldValue = null;
                if ($row = mysqli_fetch_assoc($res)) {
                    $hasRow  = true;
                    $oldValue = (int)$row['vote'];  // 1 or 0
                }
                mysqli_stmt_close($stmt);

                $delta = 0; // how much to change note.vote_count

                if (!$hasRow) {
                    // First time voting → insert
                    $stmt = mysqli_prepare(
                        $conn,
                        "INSERT INTO vote (note_id, user_id, vote) VALUES (?, ?, ?)"
                    );
                    mysqli_stmt_bind_param($stmt, "iii", $noteId, $userId, $newValue);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);

                    // score: +1 for up, -1 for down
                    $delta = ($newValue === 1) ? 1 : -1;

                } elseif ($oldValue === $newValue) {
                    // Same vote clicked again → undo
                    $stmt = mysqli_prepare($conn, "DELETE FROM vote WHERE note_id = ? AND user_id = ?");
                    mysqli_stmt_bind_param($stmt, "ii", $noteId, $userId);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);

                    // undo previous value
                    $delta = ($oldValue === 1) ? -1 : 1;

                } else {
                    // Switch up <-> down
                    $stmt = mysqli_prepare(
                        $conn,
                        "UPDATE vote SET vote = ? WHERE note_id = ? AND user_id = ?"
                    );
                    mysqli_stmt_bind_param($stmt, "iii", $newValue, $noteId, $userId);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);

                    // up(1)->down(0): -2, down(0)->up(1): +2
                    $delta = ($newValue === 1) ? 2 : -2;
                }

                if ($delta !== 0) {
                    $stmt = mysqli_prepare(
                        $conn,
                        "UPDATE note SET vote_count = vote_count + ? WHERE id = ?"
                    );
                    mysqli_stmt_bind_param($stmt, "ii", $delta, $noteId);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                }

                header("Location: " . $_SERVER['REQUEST_URI']);
                exit;
            }
        }

        // -------- Voting state for rendering --------
        $noteScore       = (int)($note['vote_count'] ?? 0);
        $currentUserVote = 0; // 1 = upvote, -1 = downvote, 0 = nothing

        if (isset($_SESSION['person_id'])) {
            $userId = (int)$_SESSION['person_id'];
            $noteId = (int)$note['id'];

            $stmt = mysqli_prepare($conn, "SELECT vote FROM vote WHERE note_id = ? AND user_id = ?");
            mysqli_stmt_bind_param($stmt, "ii", $noteId, $userId);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            if ($row = mysqli_fetch_assoc($res)) {
                $currentUserVote = ((int)$row['vote'] === 1) ? 1 : -1;
            }
            mysqli_stmt_close($stmt);
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
    <h1 class="mb-2"><?php echo htmlspecialchars($note['title'] ?? 'Nota'); ?></h1>

    <?php if (isset($_SESSION['person_id'])): ?>
        <form method="post" class="d-inline-flex align-items-center gap-2 mb-5">
            <button
                type="submit"
                name="vote"
                value="up"
                class="btn btn-sm <?php echo ($currentUserVote === 1) ? 'btn-success' : 'btn-outline-secondary'; ?>"
            >
                ⬆
            </button>

            <span class="fw-semibold">
                <?php echo $noteScore; ?>
            </span>

            <button
                type="submit"
                name="vote"
                value="down"
                class="btn btn-sm <?php echo ($currentUserVote === -1) ? 'btn-danger' : 'btn-outline-secondary'; ?>"
            >
                ⬇
            </button>
        </form>
    <?php else: ?>
        <p class="text-muted mb-5">Log in to vote on this note.</p>
    <?php endif; ?>

    <div class="row">
        <!-- Note Content Column -->
        <div class="col-12 col-lg-8 mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="markdown-body"><?php echo $htmlContent; ?></div>
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
