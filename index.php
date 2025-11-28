<?php
require_once "bootstrap.php";
require_once "utils/router.php";

$router = new Router();
$route  = $router->getRoute();

// Se la pagina è il login, NON usiamo il template base (così il form è centrato e senza navbar)
if ($route["file"] === "login.php") {
    require $route["file"];
    exit;
}

// If there's a separate template, load the controller first (contains PHP logic)
if (isset($route["template"])) {
    require $route["file"];
}

// Per tutte le altre pagine: preparo i parametri per il template
$templateParams = [];
// Use template file if specified, otherwise use the file itself as template
$templateParams["main-content"] = $route["template"] ?? $route["file"];
$templateParams["title"]        = $route["title"];

// Ora carico il layout comune (navbar + <main> con dentro il contenuto scelto sopra)
require "template/base.php";
?>