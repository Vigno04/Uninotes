<?php
require_once("bootstrap.php");

if (!isset($_SESSION['person_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit;
}

// Handle approval = admin fills the teacher details manually
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uid = (int)$_POST['user_id'];

    if (isset($_POST['reject'])) {
        mysqli_query($conn, "DELETE FROM teacher WHERE person_id = $uid");
    }

    header("Location: index.php?page=admin_teacher_requests");
    exit;
}

// Fetch pending teacher rows (all fields null)
$sql = "
    SELECT 
        t.person_id,
        p.name,
        p.surname,
        p.email
    FROM teacher t
    JOIN person p ON p.id = t.person_id
    WHERE t.department IS NULL
      AND t.unibo_site IS NULL
      AND t.phone_number IS NULL
      AND t.personal_site IS NULL
";
$result = mysqli_query($conn, $sql);
$requests = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>

<div class="container py-4">
    <h1 class="h4">Teacher Requests</h1>

    <?php if (empty($requests)): ?>
        <p class="text-muted">No pending requests.</p>
    <?php else: ?>
        <table class="table table-sm">
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
