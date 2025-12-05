<?php
include "../config.php";
session_start();

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    // Update status
    mysqli_query($conn, "UPDATE credentials SET status='active' WHERE user_id=$id");

    // Insert notification
    mysqli_query($conn, "INSERT INTO notifications (user_id, message) VALUES ($id, 'Your account has been reactivated by admin.')");
}

header("Location: manage_users.php");
exit();
//this is unsuspend user file
?>
