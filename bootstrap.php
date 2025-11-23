<?php
// Apriamo la sessione
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'db/databaseHelper.php';
require_once 'utils/functions.php'; 

// Connessione al database UniNotes
$host = "localhost";
$user = "root";
$password = "";        // la tua password MySQL
$dbname = "uninotes";  // nome del database dove hai importato lo schema

// TODO: rimuovere perche' di troppo
// In databaseHelper.php ci connettiamo al db con __construct(...)
// Creiamo la connessione al database
// TODO: decidere se usare QUESTA
$dbh = new DatabaseHelper($host, $user, $password, $dbname);
// TODO: O QUESTA
$conn = mysqli_connect($host, $user, $password, $dbname);
if ($conn === false) {
    die("Connection error: " . mysqli_connect_error());
}

// opzionale ma consigliato: charset UTF-8
mysqli_set_charset($conn, "utf8mb4");

// opzionale: costante per la cartella upload
define("UPLOAD_DIR", "./upload/");

// FIno a qui
?>

<!-- TODO: forse togliere? -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">