<?php
session_start();

if (isset($_SESSION['username'])) {
    if (isset($_SESSION['usertype']) && $_SESSION['usertype'] === 'admin') {
        header('Location: adminhome.php');
        exit();
    } else {
        header('Location: userhome.php');
        exit();
    }
} else {
    header('Location: login.php');
    exit();
}

?>

<!-- Fallback content for browsers without redirects -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Redirecting...</title>
</head>
<body>
    <p>If you are not redirected automatically, <a href="login.php">click here</a>.</p>
</body>
</html>
