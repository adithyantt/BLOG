<?php
include "../config.php";
session_start();

if ($_SESSION['role'] !== 'admin') {
    die("Unauthorized");
}

if (!isset($_GET['post_id'])) {
    die("Post ID required");
}

$post_id = (int)$_GET['post_id'];
$reason = isset($_GET['reason']) ? mysqli_real_escape_string($conn, $_GET['reason']) : "Violation of rules";

// 1. Find post author
$res = mysqli_query($conn, "SELECT user_id FROM posts WHERE post_id=$post_id LIMIT 1");
if ($row = mysqli_fetch_assoc($res)) {
    $author_id = $row['user_id'];

    // 2. Update post status
    mysqli_query($conn, "UPDATE posts SET status='blocked' WHERE post_id=$post_id");

    // 3. Insert notification
    $msg = "Your post (ID: $post_id) has been blocked by admin. Reason: $reason";
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, is_read) VALUES (?, ?, 0)");
    $stmt->bind_param("is", $author_id, $msg);
    $stmt->execute();
}

echo "<script>alert('Post blocked and user notified.'); window.location='admin_dashboard.php';</script>";
