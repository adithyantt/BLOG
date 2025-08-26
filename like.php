<?php
include "config.php";
session_start();

if (!isset($_SESSION['email'])) {
    header("Location: login.html");
    exit();
}

if (!isset($_GET['pid']) || !filter_var($_GET['pid'], FILTER_VALIDATE_INT)) {
    header("Location: home.php");
    exit();
}

$post_id = (int)$_GET['pid'];

// get current user
$email = $_SESSION['email'];
$q = mysqli_query($conn, "SELECT user_id FROM credentials WHERE email='$email'");
$user = mysqli_fetch_assoc($q);
$user_id = $user['user_id'];

// toggle like
$check = mysqli_query($conn, "SELECT * FROM likes WHERE post_id=$post_id AND user_id=$user_id");
if (mysqli_num_rows($check) > 0) {
    mysqli_query($conn, "DELETE FROM likes WHERE post_id=$post_id AND user_id=$user_id");
} else {
    mysqli_query($conn, "INSERT INTO likes (post_id, user_id) VALUES ($post_id, $user_id)");
}

// redirect back
header("Location: view.php?pid=$post_id");
exit();
?>
