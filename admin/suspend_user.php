<?php
include "../config.php";
session_start();

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    // Update status
    mysqli_query($conn, "UPDATE credentials SET status='suspended' WHERE user_id=$id");

    // Insert notification
    mysqli_query($conn, "INSERT INTO notifications (user_id, message) VALUES ($id, 'Your account has been suspended by admin.')");
}

header("Location: manage_users.php");
exit();
?>
