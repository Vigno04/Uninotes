<?php
require_once("bootstrap.php");
require_once("db/UserModel.php");

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name     = trim($_POST["name"] ?? "");
    $surname  = trim($_POST["surname"] ?? "");
    $email    = trim($_POST["email"] ?? "");
    $pass1    = $_POST["password"] ?? "";
    $pass2    = $_POST["password_confirm"] ?? "";

    // Basic validation
        if ($name === "" || $surname === "" || $email === "" || $pass1 === "" || $pass2 === "") {
            $message = '<div id="serverMessage" class="alert alert-danger" role="alert" aria-live="polite">Compila tutti i campi.</div>';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = '<div id="serverMessage" class="alert alert-danger" role="alert" aria-live="polite">Inserisci una email valida.</div>';
        } elseif ($pass1 !== $pass2) {
            $message = '<div id="serverMessage" class="alert alert-danger" role="alert" aria-live="polite">Le password non coincidono.</div>';
        } else {
            $userModel = new UserModel();
            
            // Check if email already exists
            if ($userModel->emailExists($email)) {
                $message = '<div id="serverMessage" class="alert alert-danger" role="alert" aria-live="polite">Esiste già un account con questa email.</div>';
            } else {
                // Hash password and register user
                $hashedPassword = password_hash($pass1, PASSWORD_DEFAULT);
                $role = "user";
                
                // Register the user
                $personId = $userModel->createUser($name, $surname, $email, $hashedPassword, $role);
                
                if ($personId) {
                    // Auto-login after registration
                    $_SESSION["person_id"] = $personId;
                    $_SESSION["name"]      = $name;
                    $_SESSION["surname"]   = $surname;
                    $_SESSION["email"]     = $email;
                    $_SESSION["role"]      = $role;

                    header("Location: index.php?page=home");
                    exit();
                } else {
                    $message = '<div id="serverMessage" class="alert alert-danger" role="alert" aria-live="polite">Errore durante la registrazione.</div>';
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

    <a class="skip-link sr-only-focusable" href="#maincontent">Skip to content</a>
    <main id="maincontent" role="main" tabindex="-1">

<div class="auth-card-wrapper px-3 px-sm-0">
    <div class="auth-card mx-auto">

        <!-- Brand block -->
        <div class="text-center mb-4">
            <div class="brand-icon mb-2">
                <img src="assets/favicon-blueBG.ico"
                    alt="UniNotes"
                    class="img-fluid"
                    style="width: 80px; height: 80px;">
            </div>
            <div class="brand-title fw-semibold">UniNotes</div>
            <div class="brand-subtitle text-muted">Share knowledge, ace together</div>
        </div>


        <!-- Heading -->
        <div class="mb-4 text-center">
            <h2 class="auth-heading mb-1">Create your account</h2>
            <p class="auth-subheading mb-0">
                Join UniNotes and start sharing your notes
            </p>
        </div>

        <!-- Server messages -->
        <?php if (!empty($message)) { echo $message; } ?>

        <!-- Register form -->
        <form action="" method="POST" class="mb-3">

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="reg-name" class="form-label small mb-1">Name</label>
                    <input id="reg-name" type="text" name="name" class="form-control" required>
                </div>

                <div class="col-md-6">
                    <label for="reg-surname" class="form-label small mb-1">Surname</label>
                    <input id="reg-surname" type="text" name="surname" class="form-control" required>
                </div>
            </div>

            <div class="mt-3 mb-3">
                <label for="reg-email" class="form-label small mb-1">Email</label>
                <input id="reg-email" type="email"
                       name="email"
                       class="form-control"
                       placeholder="student@university.edu"
                       required aria-describedby="serverMessage">
            </div>

            <div class="mb-3">
                <label for="reg-password" class="form-label small mb-1">Password</label>
                <input id="reg-password" type="password"
                       name="password"
                       class="form-control"
                       placeholder="●●●●●●●●"
                       required aria-describedby="serverMessage">
            </div>

            <div class="mb-4">
                <label for="reg-password-confirm" class="form-label small mb-1">Confirm password</label>
                <input id="reg-password-confirm" type="password"
                       name="password_confirm"
                       class="form-control"
                       placeholder="●●●●●●●●"
                       required aria-describedby="serverMessage">
            </div>

            <button type="submit" class="btn btn-primary auth-submit w-100">
                Create account
            </button>
        </form>

        <div class="auth-divider"><span>or</span></div>

        <a href="login.php"
           class="btn btn-outline-primary auth-secondary w-100 mb-1">
            Already have an account? Sign in
        </a>

        <p class="auth-footer-text text-center mb-0 mt-3">
            By continuing, you agree to UniNotes' Terms of Service and Privacy Policy.
        </p>
    </div>
</div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
