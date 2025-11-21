<?php
// admin-view-reports.php
require_once("bootstrap.php");

if (!isset($_SESSION['person_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: login.php');
    exit;
}

// Recupero ultimi 20 report (prima gli aperti, poi i risolti)
$sql = "
    SELECT 
        c.id,
        c.message,
        c.snippet,
        c.file_index,
        c.line_number,
        c.created_at,
        c.resolved,
        c.resolved_at,
        n.id    AS note_id,
        n.title AS note_title,
        p.name,
        p.surname
    FROM correction c
    JOIN note n      ON c.note_id = n.id
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
                        Review error reports sent by students and teachers. For now this page is read-only.
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
                                                <?php if (!empty($r['snippet'])): ?>
                                                    <div class="text-muted small mt-1">
                                                        <strong>Snippet:</strong>
                                                        <?php echo htmlspecialchars($r['snippet']); ?>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if (!is_null($r['file_index']) || !is_null($r['line_number'])): ?>
                                                    <div class="text-muted small">
                                                        <strong>Location:</strong>
                                                        file <?php echo (int)$r['file_index']; ?>,
                                                        line <?php echo (int)$r['line_number']; ?>
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
