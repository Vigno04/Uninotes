<?php
require_once("bootstrap.php");

$message = "";


if ($_SERVER["REQUEST_METHOD"] === "POST") {                // Controllo del metodo HTTP (form inviato o no)
    // Lettura dati dal form
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validazione campi vuoti
    if ($email === '' || $password === '') {
        $message = '<div class="alert alert-danger">Inserisci email e password.</div>';
    } else {
        // Preparazione della query
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

        // Prepared statement con mysqli
        $stmt = mysqli_prepare($conn, $sql); // TODO: cambia conn forse? ricontrolla in bootstrap.php
        if ($stmt === false) {
            die("Query error: " . mysqli_error($conn));
        }

        // Bind del parametro ed esecuzione
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);

        // TODO: hasha password
        /* $row['password'] === $password -------------------> Confronto della password in chiaro */
        /* password_verify($password, $row['password']) ----->  Confronto della password hashata*/
        /* Nel DB salverei password_hash(...) */
        if ($row && $row['password'] === $password) {
            // Login OK: salvo dati in sessione
            // Uso la $_SESSION per tenere traccia dell’utente loggato.
            // Per sapere chi e' loggato posso sempre usare $_SESSION in ogni pagina
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
            // Redirect dopo il login
            header("Location: index.php?page=home");
            exit();
        } else {
            // Se qualcosa va storto
            $message = '<div class="alert alert-danger">Wrong email or password.</div>';
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
<body class="auth-page">
    <div class="auth-card-wrapper px-3 px-sm-0">
        <div class="auth-card mx-auto">
            <div class="text-center mb-4">
                <div class="brand-icon mb-2">
                    <img src="assets/book-open.svg" alt="UniNotes" class="img-fluid" style="max-width: 36px;">
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
                    <label class="form-label small mb-1">Email</label>
                    <input type="email" name="email" class="form-control" placeholder="student@university.edu" required autofocus>
                </div>

                <div class="mb-2">
                    <label class="form-label small mb-1">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="●●●●●●●●" required>
                </div>


                <div class="d-flex justify-content-end mb-3">
                    <a href="#" class="auth-muted-link text-decoration-none">Forgot password?</a>
                </div>

                <button type="submit" class="btn btn-primary auth-submit w-100">Sign In</button>
            </form>

            <div class="auth-divider"><span>or</span></div>
            <a href="register.php" class="btn btn-outline-primary auth-secondary w-100 mb-1" role="button">
                Create New Account
            </a>
            <p class="auth-footer-text text-center mb-0 mt-3">
                By continuing, you agree to UniNotes' Terms of Service and Privacy Policy
            </p>

        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Da aggiungere un altro scriptino... una funzione js -->
</body>
</html>