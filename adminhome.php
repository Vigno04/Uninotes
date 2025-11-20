<?php
session_start();
if (!isset($_SESSION['person_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Connessione al DB (stessa di login / register / userhome)
$host = "localhost";
$user = "root";
$password = "";
$dbname = "uninotes";

$conn = mysqli_connect($host, $user, $password, $dbname);
if ($conn === false) {
    die("Connection error: " . mysqli_connect_error());
}

$personId = $_SESSION['person_id'];

// Dati dell'admin
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
$admin = mysqli_fetch_assoc($result);

if (!$admin) {
    header('Location: logout.php');
    exit;
}

$fullName  = $admin['name'] . ' ' . $admin['surname'];
$email     = $admin['email'];
$created   = $admin['created_at'];
$lastLogin = $admin['last_login'];

$profilePicture = $admin['profile_picture'];
if (empty($profilePicture)) {
    // metti qui un tuo avatar locale se vuoi (es. img/admin-avatar.png)
    $profilePicture = 'https://via.placeholder.com/96?text=Admin';
}

// Statistiche base
$stats = [
    'users'       => 0,
    'courses'     => 0,
    'notes'       => 0,
    'corrections' => 0,
];

$res = mysqli_query($conn, "SELECT COUNT(*) AS c FROM user");
if ($res) $stats['users'] = mysqli_fetch_assoc($res)['c'];

$res = mysqli_query($conn, "SELECT COUNT(*) AS c FROM course");
if ($res) $stats['courses'] = mysqli_fetch_assoc($res)['c'];

$res = mysqli_query($conn, "SELECT COUNT(*) AS c FROM note");
if ($res) $stats['notes'] = mysqli_fetch_assoc($res)['c'];

$res = mysqli_query($conn, "SELECT COUNT(*) AS c FROM correction WHERE resolved = 0");
if ($res) $stats['corrections'] = mysqli_fetch_assoc($res)['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="home-card mx-auto col-12 col-lg-8 bg-white rounded-4 shadow-sm p-4 p-md-5">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3 mb-3">
                <div class="d-flex align-items-center gap-3">
                    <img src="<?php echo htmlspecialchars($profilePicture); ?>"
                         alt="Admin avatar"
                         class="profile-avatar-lg rounded-circle">
                    <div>
                        <h1 class="h4 mb-1">
                            Welcome, <strong><?php echo htmlspecialchars($admin['name']); ?></strong>!
                        </h1>
                        <span class="badge bg-danger">Administrator</span>
                        <p class="mb-0 text-muted small">
                            <?php echo htmlspecialchars($email); ?>
                        </p>
                    </div>
                </div>
                <a href="logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
            </div>

            <hr>

            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="stat-card text-center p-3 rounded-3 bg-light">
                        <div class="small text-muted">Users</div>
                        <div class="fs-4 fw-semibold"><?php echo (int)$stats['users']; ?></div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card text-center p-3 rounded-3 bg-light">
                        <div class="small text-muted">Courses</div>
                        <div class="fs-4 fw-semibold"><?php echo (int)$stats['courses']; ?></div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card text-center p-3 rounded-3 bg-light">
                        <div class="small text-muted">Notes</div>
                        <div class="fs-4 fw-semibold"><?php echo (int)$stats['notes']; ?></div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card text-center p-3 rounded-3 bg-light">
                        <div class="small text-muted">Open reports</div>
                        <div class="fs-4 fw-semibold"><?php echo (int)$stats['corrections']; ?></div>
                    </div>
                </div>
            </div>

            <div class="mb-4 small text-muted">
                <p class="mb-1">
                    <strong>Member since:</strong>
                    <?php echo htmlspecialchars(date('d/m/Y', strtotime($created))); ?>
                </p>
                <p class="mb-0">
                    <strong>Last login:</strong>
                    <?php echo $lastLogin ? htmlspecialchars(date('d/m/Y H:i', strtotime($lastLogin))) : 'First login'; ?>
                </p>
            </div>

            <div class="d-grid gap-2 d-md-flex">
                <a href="#" class="btn btn-primary disabled" aria-disabled="true">
                    Manage users (coming soon)
                </a>
                <a href="#" class="btn btn-outline-primary disabled" aria-disabled="true">
                    Manage courses (coming soon)
                </a>
                <a href="#" class="btn btn-outline-secondary disabled" aria-disabled="true">
                    View reports (coming soon)
                </a>
            </div>
        </div>
    </div>
</body>
</html>
