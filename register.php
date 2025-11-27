<?php
require_once("bootstrap.php");

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name     = trim($_POST["name"] ?? "");
    $surname  = trim($_POST["surname"] ?? "");
    $email    = trim($_POST["email"] ?? "");
    $pass1    = $_POST["password"] ?? "";
    $pass2    = $_POST["password_confirm"] ?? "";

    // Basic validation
    if ($name === "" || $surname === "" || $email === "" || $pass1 === "" || $pass2 === "") {
        $message = '<div class="alert alert-danger">Compila tutti i campi.</div>';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = '<div class="alert alert-danger">Inserisci una email valida.</div>';
    } elseif ($pass1 !== $pass2) {
        $message = '<div class="alert alert-danger">Le password non coincidono.</div>';
    } else {
        // Check if email already exists
        $checkSql = "SELECT id FROM person WHERE email = ? LIMIT 1";
        $checkStmt = mysqli_prepare($conn, $checkSql);
        mysqli_stmt_bind_param($checkStmt, "s", $email);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);

        if (mysqli_fetch_assoc($checkResult)) {
            $message = '<div class="alert alert-danger">Esiste già un account con questa email.</div>';
        } else {
            // Insert into person
            $insertPersonSql = "INSERT INTO person (name, surname, email) VALUES (?, ?, ?)";
            $personStmt = mysqli_prepare($conn, $insertPersonSql);
            mysqli_stmt_bind_param($personStmt, "sss", $name, $surname, $email);

            if (!mysqli_stmt_execute($personStmt)) {
                $message = '<div class="alert alert-danger">Errore durante la registrazione (person).</div>';
            } else {
                $personId = mysqli_insert_id($conn);

                // For now store password in plain text (like your login.php)
                // In a real app: $hashed = password_hash($pass1, PASSWORD_DEFAULT);
                $plainPassword = $pass1;
                $role = "user";

                $insertUserSql = "INSERT INTO user (person_id, password, role) VALUES (?, ?, ?)";
                $userStmt = mysqli_prepare($conn, $insertUserSql);
                mysqli_stmt_bind_param($userStmt, "iss", $personId, $plainPassword, $role);

                if (!mysqli_stmt_execute($userStmt)) {
                    $message = '<div class="alert alert-danger">Errore durante la registrazione (user).</div>';
                } else {
                    // Auto-login after registration (optional)
                    $_SESSION["person_id"] = $personId;
                    $_SESSION["name"]      = $name;
                    $_SESSION["surname"]   = $surname;
                    $_SESSION["email"]     = $email;
                    $_SESSION["role"]      = $role;

                    header("Location: userhome.php");
                    exit();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register - UniNotes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" href="assets/favicon.ico" type="image/x-icon">
</head>
<body class="auth-page">
    <div class="auth-card-wrapper px-3 px-sm-0">
        <div class="auth-card mx-auto">
        <div class="text-center mb-4">
            <div class="brand-icon mb-2">
                <img src="assets/favicon-blueBG.ico" alt="UniNotes" class="img-fluid">
            </div>
            <div class="brand-title">UniNotes</div>
            <div class="brand-subtitle">Create your account</div>
        </div>
        <h2 class="text-center mb-4 fw-light">Create your account</h2>
        <?= $message ?>
        <form action="" method="POST">
            <div class="mb-3">
                <label class="form-label">Name</label>
                <input type="text" name="name" class="form-control form-control-lg" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Surname</label>
                <input type="text" name="surname" class="form-control form-control-lg" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control form-control-lg" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control form-control-lg" required>
            </div>
            <div class="mb-4">
                <label class="form-label">Confirm password</label>
                <input type="password" name="password_confirm" class="form-control form-control-lg" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 btn-lg">Register</button>

            <p class="text-center mt-3 mb-0">
                Already have an account?
                <a href="login.php">Login</a>
            </p>
        </form>
        <p class="text-center text-muted mt-4 mb-0">© 2025 UniNotes - All Rights Reserved</p>
    </div>
</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
