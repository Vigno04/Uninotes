<?php
// admindashboard.php

// Qui assumo che bootstrap.php sia già stato incluso da index.php
// e che i modelli e $_SESSION siano disponibili.

// 1) Controllo: solo admin
if (!isset($_SESSION['person_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: login.php');
    exit;
}

$personId = $_SESSION['person_id'];
$personModel = new PersonModel();
$adminModel = new AdminModel();

// -----------------------------------------------------
// Dati dell'admin (per l'header in alto)
// -----------------------------------------------------
$admin = $personModel->getPersonById($personId);

if (!$admin) {
    header('Location: logout.php');
    exit;
}

$fullName  = $admin['name'] . ' ' . $admin['surname'];
$email     = $admin['email'];
$created   = $admin['created_at'] ?? '';
$lastLogin = $admin['last_login'] ?? '';

$profilePicture = $admin['profile_picture'];
if (empty($profilePicture)) {
    $profilePicture = 'https://via.placeholder.com/96?text=Admin';
}

$initials = strtoupper(
    mb_substr($admin['name'], 0, 1) . mb_substr($admin['surname'], 0, 1)
);

// -----------------------------------------------------
// 2) Statistiche generali
// -----------------------------------------------------
$stats = $adminModel->getStats();

// -----------------------------------------------------
// 3) Ultimi appunti caricati
// -----------------------------------------------------
$recentNotes = $adminModel->getRecentNotes(10);

// -----------------------------------------------------
// 4) Ultime segnalazioni (correction)
// -----------------------------------------------------
$recentReports = $adminModel->getRecentCorrections(10);

// -----------------------------------------------------
// 5) Note per corso (per il grafico) - top 5 corsi
// -----------------------------------------------------
$notesByCourse = $adminModel->getNotesByCourse();

?>

<div class="container py-4 py-lg-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10 col-xl-9">

            <!-- HEADER ADMIN -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body d-flex flex-column flex-md-row align-items-md-center gap-3">
                    <div class="d-flex align-items-center gap-3 flex-grow-1">
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:80px;height:80px;
                                    background:linear-gradient(135deg,#4e54c8,#8f94fb);
                                    color:#fff;font-size:1.6rem;font-weight:600;">
                            <?php echo htmlspecialchars($initials); ?>
                        </div>

                        <div>
                            <div class="fw-semibold mb-1">
                                <?php echo htmlspecialchars($email); ?>
                            </div>
                            <div class="d-flex flex-wrap align-items-center gap-2 small text-muted">
                                <span class="badge rounded-pill bg-warning text-dark">Admin</span>
                                <span>📅 Joined <?php echo htmlspecialchars(date('F Y', strtotime($created))); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="ms-md-auto">
                        <a href="logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
                    </div>
                </div>
            </div>

            <!-- DASHBOARD CARD -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4 p-lg-5">
                    <h1 class="h4 mb-4">Admin Dashboard</h1>

                    <!-- Statistiche -->
                    <div class="row g-3 mb-4">
                        <div class="col-6 col-md-3">
                            <div class="text-center p-3 rounded-3 bg-light">
                                <div class="small text-muted">Users</div>
                                <div class="fs-4 fw-semibold"><?php echo $stats['users']; ?></div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-center p-3 rounded-3 bg-light">
                                <div class="small text-muted">Courses</div>
                                <div class="fs-4 fw-semibold"><?php echo $stats['courses']; ?></div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-center p-3 rounded-3 bg-light">
                                <div class="small text-muted">Notes</div>
                                <div class="fs-4 fw-semibold"><?php echo $stats['notes']; ?></div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-center p-3 rounded-3 bg-light">
                                <div class="small text-muted">Open reports</div>
                                <div class="fs-4 fw-semibold"><?php echo $stats['corrections']; ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4 small text-muted">
                        <p class="mb-1">
                            <strong>Member since:</strong>
                            <?php echo htmlspecialchars(date('d/m/Y', strtotime($created))); ?>
                        </p>
                        <p class="mb-0">
                            <strong>Last login:</strong>
                            <?php echo $lastLogin
                                ? htmlspecialchars(date('d/m/Y H:i', strtotime($lastLogin)))
                                : 'First login'; ?>
                        </p>
                    </div>

                    <div class="d-grid gap-2 d-md-flex mb-4">
                        <a href="index.php?page=manage_users" class="btn btn-primary">
                            Manage users
                        </a>
                        <a href="index.php?page=manage_courses" class="btn btn-outline-primary">
                            Manage courses
                        </a>
                        <a href="index.php?page=admin_teacher_create" class="btn btn-outline-primary">
                            Manage teachers
                        </a>

                        <a href="index.php?page=view_reports" class="btn btn-outline-secondary">
                            View reports
                        </a>
                    </div>

                    <!-- Grafico: note per corso -->
                    <h2 class="h6 mb-2">Notes by course</h2>

                    <?php if (empty($notesByCourse)): ?>
                        <p class="text-muted small mb-4">No data available for chart.</p>
                    <?php else: ?>
                        <div class="mb-4">
                            <canvas id="notesByCourseChart" height="120"></canvas>
                        </div>
                    <?php endif; ?>


                    <!-- Ultimi appunti -->
                    <h2 class="h6 mb-2">Recent notes</h2>
                    <div class="table-responsive mb-4">
                        <table class="table table-sm align-middle mb-0">                            <caption class="visually-hidden">List of recent correction reports with note title, reporter, message and date</caption>                            <caption class="visually-hidden">List of recently uploaded notes with title, author, status and date</caption>
                            <thead class="table-light">
                                <tr>
                                    <th>Title</th>
                                    <th>Author</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($recentNotes)): ?>
                                <tr><td colspan="4" class="text-muted small">No notes yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($recentNotes as $note): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($note['title']); ?></td>
                                        <td>
                                            <?php
                                            $author = trim(($note['name'] ?? '') . ' ' . ($note['surname'] ?? ''));
                                            echo htmlspecialchars($author ?: 'Unknown');
                                            ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark">
                                                <?php echo htmlspecialchars($note['status']); ?>
                                            </span>
                                        </td>
                                        <td class="small text-muted">
                                            <?php echo htmlspecialchars(date('d/m/Y', strtotime($note['created_at']))); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Ultime segnalazioni -->
                    <h2 class="h6 mb-2">Recent reports</h2>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Note</th>
                                    <th>Message</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($recentReports)): ?>
                                <tr><td colspan="4" class="text-muted small">No reports yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($recentReports as $rep): ?>
                                    <tr>
                                        <td class="small">
                                            <?php echo htmlspecialchars($rep['note_title']); ?>
                                        </td>
                                        <td class="small">
                                            <?php
                                            $snippet = mb_substr($rep['message'], 0, 60);
                                            if (mb_strlen($rep['message']) > 60) $snippet .= '…';
                                            echo htmlspecialchars($snippet);
                                            ?>
                                        </td>
                                        <td>
                                            <?php if ($rep['resolved']): ?>
                                                <span class="badge bg-success">Resolved</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">Open</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="small text-muted">
                                            <?php echo htmlspecialchars(date('d/m/Y', strtotime($rep['created_at']))); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

<?php if (!empty($notesByCourse)): ?>
<script>
    (function() {
        const ctx = document.getElementById('notesByCourseChart');
        if (!ctx) return;

        const labels = <?php echo json_encode(array_column($notesByCourse, 'course_name')); ?>;
        const data   = <?php echo json_encode(array_map('intval', array_column($notesByCourse, 'note_count'))); ?>;

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Notes by course',
                    data: data,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0   // solo interi
                        }
                    }
                }
            }
        });
    })();
</script>
<?php endif; ?>
