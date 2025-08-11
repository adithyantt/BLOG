<?php
include "config.php";
session_start();

if (!isset($_SESSION['email']) || !isset($_GET['author_id'])) {
    echo json_encode(["status" => "error", "message" => "Not logged in or missing author ID"]);
    exit;
}

$current_user_email = $_SESSION['email'];
$author_id = (int) $_GET['author_id'];

// Get current user ID
$result = mysqli_query($conn, "SELECT user_id FROM credentials WHERE email = '$current_user_email'");
$user = mysqli_fetch_assoc($result);
$current_user_id = $user['user_id'];

if ($current_user_id == $author_id) {
    echo json_encode(["status" => "error", "message" => "You cannot follow yourself"]);
    exit;
}

// Check if already following
$check = mysqli_query($conn, "SELECT 1 FROM follows WHERE follower_id=$current_user_id AND following_id=$author_id");

if (mysqli_num_rows($check) > 0) {
    // Unfollow
    mysqli_query($conn, "DELETE FROM follows WHERE follower_id=$current_user_id AND following_id=$author_id");
    echo json_encode(["status" => "unfollowed"]);
} else {
    // Follow
    mysqli_query($conn, "INSERT INTO follows (follower_id, following_id) VALUES ($current_user_id, $author_id)");
    echo json_encode(["status" => "following"]);
}
?>
