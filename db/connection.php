<?php
// db/connection.php
// Un'unica connessione al DB (singleton per request)

declare(strict_types=1);

// Permette di sovrascrivere i parametri via variabili d'ambiente
// (utile se consegnate il progetto senza credenziali hardcoded)
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'uninotes');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

/**
 * Restituisce sempre la stessa connessione mysqli per tutta la request.
 */
function db(): mysqli {
    static $conn = null;

    if ($conn instanceof mysqli) {
        return $conn;
    }

    // Alza eccezioni invece di warning silenziosi.
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $conn->set_charset('utf8mb4');

    return $conn;
}
