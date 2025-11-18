<?php
session_start();
if (!isset($_SESSION['person_id'])) {
    header('Location: login.php');
    exit;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>User Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="home-card mx-auto col-11 col-md-8 col-lg-6 text-center">
            <h1 class="welcome-text">Hello, <strong><?php echo htmlspecialchars($_SESSION["name"]); ?></strong>!</h1>
            <span class="role-badge badge bg-success text-white">User Account</span>
            <hr>
            <p class="lead">Welcome to your personal dashboard.</p>
            <a href="logout.php" class="btn btn-logout text-white mt-4">Logout</a>
        </div>
    </div>
</body>
</html>