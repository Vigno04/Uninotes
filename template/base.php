<?php
// niente bootstrap qui: è già incluso in index.php
$currentPage = $_GET['page'] ?? 'home';
$isAdmin     = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
//$profilePage = $isAdmin ? 'adminaccount' : 'useraccount';
$profilePage = 'account';

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($templateParams["title"] ?? "UniNotes"); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="vendor/github-markdown/github-markdown.css">
    <link rel="icon" href="assets/favicon.ico" type="image/x-icon">
</head>
<body class="bg-light">

    <a class="skip-link sr-only-focusable" href="#maincontent">Salta al contenuto</a>

<nav class="navbar navbar-expand-lg navbar-dark" style="background:#004a6f;" role="navigation" aria-label="Main navigation">
    <div class="container-fluid px-3 px-lg-5">
        <a class="navbar-brand fw-semibold" href="index.php?page=home" style="display: flex; align-items: center;">
            <img src="assets/favicon.ico" alt="UniNotes Icon" style="height: 3.15rem; margin-right: 8px;">
            UniNotes
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'home' ? 'active' : ''; ?>" href="index.php?page=home" <?php echo $currentPage === 'home' ? 'aria-current="page"' : ''; ?>>
                        Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'courses' ? 'active' : ''; ?>" href="index.php?page=courses" <?php echo $currentPage === 'courses' ? 'aria-current="page"' : ''; ?>>
                        Courses
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'note_edit' ? 'active' : ''; ?>" href="index.php?page=note_edit" <?php echo $currentPage === 'note_edit' ? 'aria-current="page"' : ''; ?>>
                        Create Note
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'account' ? 'active' : ''; ?>" href="index.php?page=<?php echo $profilePage; ?>" <?php echo $currentPage === 'account' ? 'aria-current="page"' : ''; ?>>
                        Profile
                    </a>
                </li>

                <?php if ($isAdmin): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'admindashboard' ? 'active' : ''; ?>"
                           href="index.php?page=admindashboard">
                            Admin
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main id="maincontent" role="main" tabindex="-1" class="py-4">
    <div class="container">
        <?php
        if (!empty($templateParams["main-content"])) {
            if (file_exists($templateParams["main-content"])) {
                require $templateParams["main-content"];
            } else {
                echo $templateParams["main-content"];
            }
        } else {
            echo "<p>No content found.</p>";
        }
        ?>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Accessibility helper: when server-side message exists, focus the first form control to help screen reader users
document.addEventListener('DOMContentLoaded', function(){
    try {
        var msg = document.getElementById('serverMessage');
        if (msg) {
            var first = document.querySelector('.form-control[autofocus]') || document.querySelector('.form-control');
            if (first && typeof first.focus === 'function') {
                first.focus();
            }
            // make sure the message is reachable by keyboard/sr
            msg.setAttribute('tabindex', '-1');
        }
    } catch(e) {
        // fail silently — this is a progressive enhancement
        console.error(e);
    }
});
</script>

</body>
</html>

