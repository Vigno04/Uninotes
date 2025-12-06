
<?php
require_once("bootstrap.php");

if (!isset($_SESSION['person_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['person_id'];

// Check if already teacher
$sql = "SELECT person_id FROM teacher WHERE person_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$isTeacher = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isTeacher) {
    // Insert teacher row with empty details → means pending
    $sqlInsert = "INSERT IGNORE INTO teacher (person_id) VALUES (?)";
    $stmt2 = mysqli_prepare($conn, $sqlInsert);
    mysqli_stmt_bind_param($stmt2, "i", $userId);

    if (mysqli_stmt_execute($stmt2)) {
        $message = "Your request has been sent!";
    } else {
        $message = "Error sending request.";
    }
    mysqli_stmt_close($stmt2);
}
?>

<div class="container py-4">
    <h1 class="h4">Become a Teacher</h1>

    <?php if ($isTeacher): ?>
        <div class="alert alert-success">You are already a teacher or your request is pending!</div>
    <?php else: ?>
        <form method="post">
            <button class="btn btn-primary">Request Teacher Status</button>
        </form>

        <?php if ($message): ?>
            <div class="alert alert-info mt-3"><?= $message ?></div>
        <?php endif; ?>
    <?php endif; ?>
</div>
