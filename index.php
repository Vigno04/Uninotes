<?php
require_once("bootstrap.php");
require_once("utils/router.php");

if (!isset($router)) {
    $router = new Router();
}
$route = $router->getRoute();

$templateParams["main-content"] = $route["file"];
$templateParams["title"] = $route["title"];

// qui potresti anche decidere contenuti diversi per admin/user
// $templateParams["nome"] = $_SESSION['usertype'] === 'admin' ? "home-admin.php" : "home-user.php";

require("login.php");
?>
