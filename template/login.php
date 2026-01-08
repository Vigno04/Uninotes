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