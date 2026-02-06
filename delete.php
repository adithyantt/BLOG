<?php
//this is the delete file for deleting the files
include "config.php";
session_start();

if (!isset($_SESSION['email'])) {
    header("Location: login.html");
    exit();
}

$pid = $_GET['pid'];
$email = $_SESSION['email'];

// Delete the post if it belongs to the logged-in user
$sql = "DELETE FROM posts 
        WHERE post_id = '$pid' 
        AND user_id = (SELECT user_id FROM credentials WHERE email = '$email')";

if (mysqli_query($conn, $sql)) {
    header("Location: home.php");
    exit();
} else {
    echo "Delete failed.";
}
?>

