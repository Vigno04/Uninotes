<?php
session_start();

// Connessione al database UniNotes
$host = "localhost";
$user = "root";
$password = "";        // la tua password MySQL
$dbname = "uninotes";  // nome del database dove hai importato lo schema

$conn = mysqli_connect($host, $user, $password, $dbname);
if ($conn === false) {
    die("Connection error: " . mysqli_connect_error());
}

// opzionale ma consigliato: charset UTF-8
mysqli_set_charset($conn, "utf8mb4");

// opzionale: costante per la cartella upload
define("UPLOAD_DIR", "./upload/");
?>

<!-- TODO: forse togliere? -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">