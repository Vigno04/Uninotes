<?php
require_once("bootstrap.php");
require_once("db/UserModel.php");

$message = "";

// Simple rate-limiting: session-based
$maxAttempts = 5;
$lockoutTime = 300; // 5 minutes

if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt'] = 0;
}

$currentTime = time();
if ($_SESSION['login_attempts'] >= $maxAttempts && ($currentTime - $_SESSION['last_attempt']) < $lockoutTime) {
    $remaining = $lockoutTime - ($currentTime - $_SESSION['last_attempt']);
    $message = '<div class="alert alert-danger">Troppi tentativi falliti. Riprova tra ' . ceil($remaining / 60) . ' minuti.</div>';
} elseif ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $message = '<div id="serverMessage" class="alert alert-danger" role="alert" aria-live="polite">Inserisci email e password.</div>';
    } else {
        $userModel = new UserModel();
        $user = $userModel->authenticate($email, $password);

        if ($user) {
            // Login OK
            $_SESSION["person_id"] = $user["person_id"];
            $_SESSION["name"]      = $user["name"];
            $_SESSION["surname"]   = $user["surname"];
            $_SESSION["email"]     = $user["email"];
            $_SESSION["role"]      = $user["role"];

            // Update last_login
            $userModel->updateLastLogin($user["person_id"]);

            // Reset attempts on success
            $_SESSION['login_attempts'] = 0;

            header("Location: index.php?page=home");
            exit();
        } else {
            // Se qualcosa va storto
            $message = '<div id="serverMessage" class="alert alert-danger" role="alert" aria-live="polite">Wrong email or password.</div>';
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
    <link rel="icon" href="assets/favicon.ico" type="image/x-icon">
</head>
<body class="auth-page">
    <a class="skip-link sr-only-focusable" href="#maincontent">Skip to content</a>
    <main id="maincontent" role="main" tabindex="-1">
    <div class="auth-card-wrapper px-3 px-sm-0">
        <div class="auth-card mx-auto">
            <div class="text-center mb-4">
                <div class="brand-icon mb-2">
                    <img src="assets/favicon-blueBG.ico" alt="UniNotes" class="img-fluid">
                </div>
                <div class="brand-title">UniNotes</div>
                <div class="brand-subtitle">Share knowledge, ace together</div>
            </div>

            <div class="mb-4">
                <h2 class="auth-heading mb-1">Welcome back</h2>
                <p class="auth-subheading mb-0">Enter your credentials to access your notes</p>
            </div>
            <?= $message ?>
            <form action="" method="POST" class="mb-3"> <!-- TODO: Forse si deve aggiungere un'action, magari authenticate.php -->
                <div class="mb-3">
                    <label for="login-email" class="form-label small mb-1">Email</label>
                    <input id="login-email" type="email" name="email" class="form-control" placeholder="student@university.edu" required autofocus aria-describedby="serverMessage">
                </div>

                <div class="mb-2">
                    <label for="login-password" class="form-label small mb-1">Password</label>
                    <input id="login-password" type="password" name="password" class="form-control" placeholder="●●●●●●●●" required aria-describedby="serverMessage">
                </div>


                <div class="d-flex justify-content-end mb-3">
                    <button type="button" class="btn btn-link p-0 auth-muted-link text-decoration-none" aria-label="Reset your password">Forgot password?</button>
                </div>

                <button type="submit" class="btn btn-primary auth-submit w-100">Sign In</button>
            </form>

            <div class="auth-divider"><span>or</span></div>
            <a href="register.php" class="btn btn-outline-primary auth-secondary w-100 mb-1">
                Create New Account
            </a>
            <p class="auth-footer-text text-center mb-0 mt-3">
                By continuing, you agree to UniNotes' Terms of Service and Privacy Policy
            </p>

        </div>
    </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Da aggiungere un altro scriptino... una funzione js -->
</body>
</html>
