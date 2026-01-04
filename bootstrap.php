<?php
// bootstrap.php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('BASE_PATH')) {
    define('BASE_PATH', __DIR__);
}
if (!defined('UPLOAD_DIR')) {
    define('UPLOAD_DIR', BASE_PATH . DIRECTORY_SEPARATOR . 'uploads');
}

// Connessione DB centralizzata (unica istanza per request)
require_once __DIR__ . '/db/connection.php';

// Se vuoi usare sempre $conn come variabile già pronta:
$conn = db();
