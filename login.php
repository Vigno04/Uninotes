<?php
require_once("bootstrap.php");

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // In questo progetto usiamo l'email come "username"
    $email    = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $message = '<div class="alert alert-danger">Inserisci email e password.</div>';
    } else {
        // Cerco la persona per email e recupero anche i dati da user
        $sql = "
            SELECT 
                p.id AS person_id,
                p.name,
                p.surname,
                p.email,
                u.password,
                u.role
            FROM person p
            JOIN user u ON p.id = u.person_id
            WHERE p.email = ?
            LIMIT 1
        ";

        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt === false) {
            die("Query error: " . mysqli_error($conn));
        }

        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);

        // Per ora password in chiaro: confronto diretto
        // (se poi userai password_hash, sostituisci con password_verify)
        if ($row && $row['password'] === $password) {
            // Login OK → salvo dati in sessione
            $_SESSION["person_id"] = $row["person_id"];
            $_SESSION["name"]      = $row["name"];
            $_SESSION["surname"]   = $row["surname"];
            $_SESSION["email"]     = $row["email"];
            $_SESSION["role"]      = $row["role"];

            // aggiorno last_login (opzionale)
            $update = mysqli_prepare($conn, "UPDATE user SET last_login = NOW() WHERE person_id = ?");
            if ($update) {
                mysqli_stmt_bind_param($update, "i", $row["person_id"]);
                mysqli_stmt_execute($update);
            }

            // TODO: cambiare e mettere router
            
            header("Location: home.php");
            exit();
            
        } else {
            $message = '<div class="alert alert-danger">Email o password errate.</div>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - UniNotes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="d-flex align-items-center justify-content-center">
    <div class="login-card col-11 col-md-6 col-lg-4">
        <h2 class="text-center mb-4 fw-light">Welcome Back</h2>
        <?= $message ?>
        <form action="" method="POST">
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="username" class="form-control form-control-lg" required autofocus>
            </div>
            <div class="mb-4">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control form-control-lg" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 btn-lg">Login</button>
        </form>
        <p class="text-center mt-3 mb-0">
            Don't have an account?
            <a href="register.php">Register</a>
        </p>

        <p class="text-center text-muted mt-4 mb-0">© 2025 UniNotes - All Rights Reserved</p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
