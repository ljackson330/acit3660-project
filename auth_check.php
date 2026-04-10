<?php
session_start();
require_once 'db_connect.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['usertype']) && $_SESSION['usertype'] === 'admin';
}

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}
?>