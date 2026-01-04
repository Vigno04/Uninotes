<?php
// bootstrap.php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- DB CONFIG ---
define('DB_HOST', 'localhost');
define('DB_NAME', 'uninotes');
define('DB_USER', 'root');
define('DB_PASS', '');

// --- SINGLETON CONNECTION (una sola connessione per request) ---
function db(): mysqli {
    static $conn = null;

    if ($conn instanceof mysqli) {
        return $conn;
    }

    $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if (!$conn) {
        die("DB connection error: " . mysqli_connect_error());
    }

    mysqli_set_charset($conn, "utf8mb4");
    return $conn;
}

// Se vuoi usare sempre $conn come variabile già pronta:
$conn = db();
