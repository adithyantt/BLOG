<?php
session_start();

// block if not logged in or not admin
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: ../login.html");
    exit();
}
?>
