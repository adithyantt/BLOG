<?php
include "config.php";
session_start();

if (!isset($_SESSION['email'])) {
    header("Location: login.html");
    exit();
}

if (!isset($_GET['uid']) || !filter_var($_GET['uid'], FILTER_VALIDATE_INT)) {
    header("Location: home.php");
    exit();
}

$author_id = (int)$_GET['uid'];
$post_id = isset($_GET['pid']) ? (int)$_GET['pid'] : 0;

// get current users
$email = $_SESSION['email'];
$q = mysqli_query($conn, "SELECT user_id FROM credentials WHERE email='$email'");
$user = mysqli_fetch_assoc($q);
$user_id = $user['user_id'];

if ($user_id != $author_id) {
    // toggle follow
    $check = mysqli_query($conn, "SELECT * FROM follows WHERE follower_id=$user_id AND following_id=$author_id");
    if (mysqli_num_rows($check) > 0) {
        mysqli_query($conn, "DELETE FROM follows WHERE follower_id=$user_id AND following_id=$author_id");
    } else {
        mysqli_query($conn, "INSERT INTO follows (follower_id, following_id) VALUES ($user_id, $author_id)");
    }
}

// redirect back
if ($post_id > 0) {
    header("Location: view.php?pid=$post_id");
} else {
    header("Location: home.php");
}
exit();
?>
