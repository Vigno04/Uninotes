
<?php
require_once("bootstrap.php");

if (!isset($_SESSION['person_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['person_id'];
$teacherModel = new TeacherModel();

// Check if already teacher
$isTeacher = $teacherModel->isTeacher($userId);

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isTeacher) {
    if ($teacherModel->requestTeacher($userId)) {
        $message = "Your request has been sent!";
    } else {
        $message = "Error sending request or request already exists.";
    }
}
?>

<div class="container py-4">
    <h1 class="h4">Become a Teacher</h1>

    <?php if ($isTeacher): ?>
        <div class="alert alert-success" role="alert" aria-live="polite">You are already a teacher or your request is pending!</div>
    <?php else: ?>
        <form method="post">
            <button class="btn btn-primary">Request Teacher Status</button>
        </form>

        <?php if ($message): ?>
            <div class="alert alert-info mt-3"><?= $message ?></div>
        <?php endif; ?>
    <?php endif; ?>
</div>
