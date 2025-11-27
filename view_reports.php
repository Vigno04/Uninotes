<?php
// admin-view-reports.php
require_once("bootstrap.php");

if (!isset($_SESSION['person_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: login.php');
    exit;
}

// --- AZIONE POST: segna come risolto ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['report_id'])) {
    if ($_POST['action'] === 'resolve') {
        $reportId = (int)$_POST['report_id'];

        $stmt = mysqli_prepare($conn, "
            UPDATE correction
            SET resolved = 1,
                resolved_at = NOW()
            WHERE id = ?
        ");
        mysqli_stmt_bind_param($stmt, "i", $reportId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    // redirect per evitare il resubmit
    header('Location: index.php?page=view_reports');
    exit;
}

// --- LETTURA REPORT DAL DB ---
$sql = "
    SELECT 
        c.id,
        c.message,
        c.file_id,
        c.created_at,
        c.resolved,
        c.resolved_at,
        n.id    AS note_id,
        n.title AS note_title,
        f.filename,
        p.name,
        p.surname
    FROM correction c
    JOIN note n      ON c.note_id = n.id
    LEFT JOIN file f ON c.file_id = f.id
    LEFT JOIN user u ON c.reported_by = u.person_id
    LEFT JOIN person p ON p.id = u.person_id
    ORDER BY c.resolved ASC, c.created_at DESC
    LIMIT 20
";

$result = mysqli_query($conn, $sql);
$reports = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $reports[] = $row;
    }
}
?>

<div class="container py-4 py-lg-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10">

            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4 p-lg-5">
                    <h1 class="h4 mb-2">Reports & corrections</h1>
                    <p class="text-muted mb-4">
                        Review error reports sent by students and teachers.
                    </p>

                    <?php if (empty($reports)): ?>
                        <p class="text-muted">No reports found.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Note</th>
                                        <th>Message</th>
                                        <th>Reported by</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reports as $r): ?>
                                        <tr>
                                            <td>
                                                <?php echo htmlspecialchars($r['note_title']); ?>
                                            </td>
                                            <td style="max-width: 360px;">
                                                <div class="small">
                                                    <?php echo nl2br(htmlspecialchars($r['message'])); ?>
                                                </div>
                                                <?php if (!is_null($r['file_id'])): ?>
                                                    <div class="text-muted small mt-1">
                                                        <strong>File:</strong> <?php echo htmlspecialchars($r['filename']); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="small">
                                                <?php
                                                if ($r['name']) {
                                                    echo htmlspecialchars($r['name'] . ' ' . $r['surname']);
                                                } else {
                                                    echo '<span class="text-muted">Anonymous / deleted</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php if ($r['resolved']): ?>
                                                    <span class="badge bg-success">Resolved</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark">Open</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="small">
                                                <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($r['created_at']))); ?>
                                            </td>
                                            <td class="text-end">
                                                <?php if (!$r['resolved']): ?>
                                                    <form method="post" class="d-inline">
                                                        <input type="hidden" name="report_id" value="<?php echo (int)$r['id']; ?>">
                                                        <input type="hidden" name="action" value="resolve">
                                                        <button type="submit" class="btn btn-outline-success btn-sm">
                                                            Mark as resolved
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="text-muted small">Already resolved</span>
                                                <?php endif; ?>
                                            </td>
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
