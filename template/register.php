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
        <?php if (!empty($message)) echo $message; ?>

        <!-- Register form -->
        <form action="" method="POST" class="mb-3">

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small mb-1">Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label small mb-1">Surname</label>
                    <input type="text" name="surname" class="form-control" required>
                </div>
            </div>

            <div class="mt-3 mb-3">
                <label class="form-label small mb-1">Email</label>
                <input type="email"
                       name="email"
                       class="form-control"
                       placeholder="student@university.edu"
                       required>
            </div>

            <div class="mb-3">
                <label class="form-label small mb-1">Password</label>
                <input type="password"
                       name="password"
                       class="form-control"
                       placeholder="●●●●●●●●"
                       required>
            </div>

            <div class="mb-4">
                <label class="form-label small mb-1">Confirm password</label>
                <input type="password"
                       name="password_confirm"
                       class="form-control"
                       placeholder="●●●●●●●●"
                       required>
            </div>

            <button type="submit" class="btn btn-primary auth-submit w-100">
                Create account
            </button>
        </form>

        <div class="auth-divider"><span>or</span></div>

        <a href="login.php"
           class="btn btn-outline-primary auth-secondary w-100 mb-1"
           role="button">
            Already have an account? Sign in
        </a>

        <p class="auth-footer-text text-center mb-0 mt-3">
            By continuing, you agree to UniNotes' Terms of Service and Privacy Policy.
        </p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>