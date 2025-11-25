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
    <title><?php echo htmlspecialchars($templateParams["title"] ?? "UniNotes"); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark" style="background:#004a6f;">
    <div class="container">
        <a class="navbar-brand fw-semibold" href="index.php?page=home">UniNotes</a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#mainNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'home' ? 'active' : ''; ?>"
                       href="index.php?page=home">
                        Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'note_edit' ? 'active' : ''; ?>"
                       href="index.php?page=note_edit">
                        Create Note
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'account' ? 'active' : ''; ?>"
                       href="index.php?page=<?php echo $profilePage; ?>">
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

<main class="py-4">
    <div class="container">
        <?php
        if (!empty($templateParams["main-content"])) {
            if (file_exists($templateParams["main-content"])) {
                require $templateParams["main-content"];
            } else {
                echo $templateParams["main-content"];
            }
        } else {
            echo "<p>Nessun contenuto.</p>";
        }
        ?>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>


</body>
</html>

