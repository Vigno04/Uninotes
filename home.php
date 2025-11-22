<?php
require_once("bootstrap.php");


$templateParams = [
    "title" => "UniNotes - Home",
    "content" => include("template/home.php"),
];

require("template/base.php");
// TODO: mettere altra roba

//require("template/base.php");
echo "<h1>Welcome Home</h1>";
?>