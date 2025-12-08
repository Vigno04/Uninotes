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
        $message = '<div class="alert alert-danger">Compila tutti i campi.</div>';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = '<div class="alert alert-danger">Inserisci una email valida.</div>';
    } elseif (strlen($pass1) < 8) {
        $message = '<div class="alert alert-danger">La password deve essere lunga almeno 8 caratteri.</div>';
    } elseif ($pass1 !== $pass2) {
        $message = '<div class="alert alert-danger">Le password non coincidono.</div>';
    } else {
        $userModel = new UserModel();

        if ($userModel->emailExists($email)) {
            $message = '<div class="alert alert-danger">Esiste già un account con questa email.</div>';
        } else {
            $hashedPassword = password_hash($pass1, PASSWORD_DEFAULT);
            $role = "user";

            try {
                $personId = $userModel->createUser($name, $surname, $email, $hashedPassword, $role);

                // Auto-login after registration
                $_SESSION["person_id"] = $personId;
                $_SESSION["name"]      = $name;
                $_SESSION["surname"]   = $surname;
                $_SESSION["email"]     = $email;
                $_SESSION["role"]      = $role;

                header("Location: home.php");
                exit();
            } catch (Exception $e) {
                $message = '<div class="alert alert-danger">Errore durante la registrazione.</div>';
            }
        }
    }
}

include "template/register.php";
