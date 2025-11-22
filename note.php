<?php
require_once("bootstrap.php");
require_once 'db/NoteModel.php';
require_once 'vendor/parsedown/Parsedown.php';

$id = $_GET['id'] ?? null;

if ($id !== null && is_numeric($id)) {
    $model = new NoteModel();
    $note = $model->getById((int)$id);
    if ($note) {
        $Parsedown = new Parsedown();
        $htmlContent = $Parsedown->text($note['content']);
        $templateParams = ["title" => "Nota"];
        include("template/note.php");
    } else {
        $templateParams = ["title" => "Nota non trovata"];
        echo "<div class='container mt-5'><h1>Nota non trovata</h1></div>";
    }
} else {
    $templateParams = ["title" => "ID non valido"];
    echo "<div class='container mt-5'><h1>ID non valido</h1></div>";
}

require("template/base.php");
?>