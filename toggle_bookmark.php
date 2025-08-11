<?php
include "config.php";
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['email'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

if (!isset($_GET['pid']) || !filter_var($_GET['pid'], FILTER_VALIDATE_INT)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid post ID']);
    exit();
}

$pid = (int) $_GET['pid'];

// Get current user ID
$email = $_SESSION['email'];
$userQuery = mysqli_query($conn, "SELECT user_id FROM credentials WHERE email='$email'");
$userData = mysqli_fetch_assoc($userQuery);
$user_id = $userData['user_id'];

// Check if already bookmarked
$check = mysqli_query($conn, "SELECT * FROM bookmarks WHERE user_id=$user_id AND post_id=$pid");

if (mysqli_num_rows($check) > 0) {
    // Remove bookmark
    mysqli_query($conn, "DELETE FROM bookmarks WHERE user_id=$user_id AND post_id=$pid");
    echo json_encode(['status' => 'unbookmarked']);
} else {
    // Add bookmark
    mysqli_query($conn, "INSERT INTO bookmarks (user_id, post_id) VALUES ($user_id, $pid)");
    echo json_encode(['status' => 'bookmarked']);
}
?>