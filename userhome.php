<?php
session_start();
if (!isset($_SESSION['person_id'])) {
    header('Location: login.php');
    exit;
}

// Connessione al DB (stessa di login / register)
$host = "localhost";
$user = "root";
$password = "";
$dbname = "uninotes";

$conn = mysqli_connect($host, $user, $password, $dbname);
if ($conn === false) {
    die("Connection error: " . mysqli_connect_error());
}

$personId = $_SESSION['person_id'];

$sql = "
    SELECT 
        p.name,
        p.surname,
        p.email,
        p.profile_picture,
        p.created_at,
        u.role,
        u.last_login
    FROM person p
    JOIN user u ON p.id = u.person_id
    WHERE p.id = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $personId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$userData = mysqli_fetch_assoc($result);

if (!$userData) {
    // qualcosa non torna: meglio sloggare
    header('Location: logout.php');
    exit;
}

$fullName = $userData['name'] . ' ' . $userData['surname'];
$email    = $userData['email'];
$role     = $userData['role'];
$created  = $userData['created_at'];
$lastLogin = $userData['last_login'];

// se non c'è foto profilo, usa un placeholder
$profilePicture = $userData['profile_picture'];
if (empty($profilePicture)) {
    // puoi cambiare questo in un tuo file locale tipo 'img/default-avatar.png'
    $profilePicture = 'https://via.placeholder.com/120?text=User';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>User Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="home-card mx-auto col-12 col-md-8 col-lg-6 p-4 p-md-5 bg-white rounded-4 shadow-sm">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="d-flex align-items-center gap-3">
                    <img src="<?php echo htmlspecialchars($profilePicture); ?>"
                         alt="Profile picture"
                         class="profile-avatar rounded-circle">
                    <div class="text-start">
                        <h1 class="welcome-text h4 mb-1">
                            Hello, <strong><?php echo htmlspecialchars($userData['name']); ?></strong>!
                        </h1>
                        <span class="badge bg-success">
                            <?php echo htmlspecialchars(ucfirst($role)); ?> account
                        </span>
                    </div>
                </div>
                <a href="logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
            </div>

            <hr>

            <div class="text-start mb-4">
                <p class="mb-1"><strong>Full name:</strong> <?php echo htmlspecialchars($fullName); ?></p>
                <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></p>
                <p class="mb-1">
                    <strong>Member since:</strong>
                    <?php echo htmlspecialchars(date('d/m/Y', strtotime($created))); ?>
                </p>
                <p class="mb-0">
                    <strong>Last login:</strong>
                    <?php echo $lastLogin ? htmlspecialchars(date('d/m/Y H:i', strtotime($lastLogin))) : 'First login'; ?>
                </p>
            </div>

            <div class="d-grid gap-2 d-md-flex justify-content-md-start">
                <a href="#" class="btn btn-primary me-md-2 disabled" aria-disabled="true">
                    Browse courses (coming soon)
                </a>
                <a href="#" class="btn btn-outline-secondary disabled" aria-disabled="true">
                    Edit profile (coming soon)
                </a>
            </div>
        </div>
    </div>
</body>
</html>
