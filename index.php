<?php
require_once("bootstrap.php");

if (isset($_SESSION['username'])) {
    if (isset($_SESSION['usertype']) && $_SESSION['usertype'] === 'admin') {
        header('Location: adminhome.php');
        exit();
    } else {
        header('Location: userhome.php');
        exit();
    }
} else {
    header('Location: login.php');
    exit();
}

?>
