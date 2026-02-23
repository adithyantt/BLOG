<?php
session_start();

// block if not logged in or not admin in the admin side
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: ../login.html");
    exit();
}
?>
