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
        $message = '<div class="alert alert-danger">Inserisci email e password.</div>';
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
            // Failed login
            $_SESSION['login_attempts']++;
            $_SESSION['last_attempt'] = $currentTime;
            $message = '<div class="alert alert-danger">Wrong email or password.</div>';
        }
    }
}

include "template/login.php";