<?php
$host = "localhost";
$user = "root";
$password = "";
$db = "user";

session_start();

$data = mysqli_connect($host, $user, $password, $db);
if ($data === false) {
    die("Connection error");
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($data, $_POST['username']);
    $password = $_POST['password'];

    // Use prepared statement to prevent SQL injection
    $sql = "SELECT * FROM login WHERE username = ? LIMIT 1";
    $stmt = mysqli_prepare($data, $sql);
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_array($result);


    if ($row) {
        if ($row["usertype"] == "user") {
            $_SESSION["username"] = $username;
            $_SESSION["usertype"] = "user";
            header("location:userhome.php");
            exit();
        } elseif ($row["usertype"] == "admin") {
            $_SESSION["username"] = $username;
            $_SESSION["usertype"] = "admin";
            header("location:adminhome.php");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - MyApp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="d-flex align-items-center justify-content-center">
    <div class="login-card col-11 col-md-6 col-lg-4">
        <h2 class="text-center mb-4 fw-light">Welcome Back</h2>
        <?= $message ?>
        <form action="" method="POST">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control form-control-lg" required autofocus>
            </div>
            <div class="mb-4">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control form-control-lg" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 btn-lg">Login</button>
        </form>
        <p class="text-center text-muted mt-4 mb-0">© 2025 MyApp - All Rights Reserved</p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>