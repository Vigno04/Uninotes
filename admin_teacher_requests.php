<?php
require_once("bootstrap.php");

if (!isset($_SESSION['person_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit;
}

$teacherModel = new TeacherModel();

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uid = (int)$_POST['user_id'];

    if (isset($_POST['reject'])) {
        $teacherModel->rejectRequest($uid);
    }

    header("Location: index.php?page=admin_teacher_requests");
    exit;
}

// Fetch pending teacher requests
$requests = $teacherModel->getPendingRequests();
?>

<div class="container py-4">
    <h1 class="h4">Teacher Requests</h1>

    <?php if (empty($requests)): ?>
        <p class="text-muted">No pending requests.</p>
    <?php else: ?>
        <table class="table table-sm">
            <caption class="visually-hidden">List of pending teacher requests with name, email, request date and actions</caption>
            <thead class="table-light">
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['name']." ".$r['surname']) ?></td>
                    <td><?= htmlspecialchars($r['email']) ?></td>
                    <td>
                        <form method="post" class="d-inline">
                            <input type="hidden" name="user_id" value="<?= $r['person_id'] ?>">
                            <a href="index.php?page=admin_edit_teacher&id=<?= $r['person_id'] ?>" class="btn btn-success btn-sm">Approve</a>
                            <button name="reject" class="btn btn-danger btn-sm">Reject</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
