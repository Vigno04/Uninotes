<?php
require_once("bootstrap.php");

if (!isset($_SESSION['person_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit;
}

$userId = (int)($_GET['id'] ?? 0);

// Load person info
$sql = "SELECT * FROM person WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$person = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

// Load teacher row
$sql2 = "SELECT * FROM teacher WHERE person_id = ?";
$stmt2 = mysqli_prepare($conn, $sql2);
mysqli_stmt_bind_param($stmt2, "i", $userId);
mysqli_stmt_execute($stmt2);
$teacher = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt2));
mysqli_stmt_close($stmt2);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $department = $_POST['department'] ?? null;
    $phone = $_POST['phone'] ?? null;
    $site = $_POST['site'] ?? null;

    $sqlU = "UPDATE teacher SET department=?, phone_number=?, personal_site=? WHERE person_id=?";
    $stmtU = mysqli_prepare($conn, $sqlU);
    mysqli_stmt_bind_param($stmtU, "sssi", $department, $phone, $site, $userId);
    mysqli_stmt_execute($stmtU);
    mysqli_stmt_close($stmtU);

    header("Location: index.php?page=admin_teacher_requests");
    exit;
}
?>

<div class="container py-4">
    <h1 class="h4 mb-3">Approve Teacher</h1>

    <p><strong>User:</strong> <?= htmlspecialchars($person['name']." ".$person['surname']) ?></p>

    <form method="post">
        <label class="form-label">Department</label>
        <input name="department" class="form-control mb-3">

        <label class="form-label">Phone number</label>
        <input name="phone" class="form-control mb-3">

        <label class="form-label">Personal site</label>
        <input name="site" class="form-control mb-3">

        <button class="btn btn-success">Save</button>
        <a href="index.php?page=admin_teacher_requests" class="btn btn-secondary">Cancel</a>
    </form>
</div>
