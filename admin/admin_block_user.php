<?php
include "../config.php";
session_start();

if ($_SESSION['role'] !== 'admin') {
    die("Unauthorized");
}

if (!isset($_GET['user_id'])) {
    die("User ID required");
}

$user_id = (int)$_GET['user_id'];
$reason = isset($_GET['reason']) ? mysqli_real_escape_string($conn, $_GET['reason']) : "Violation of rules";

// 1. Update user status
mysqli_query($conn, "UPDATE credentials SET status='blocked' WHERE user_id=$user_id");

// 2. Insert notification
$msg = "Your account has been suspended by admin. Reason: $reason";
$stmt = $conn->prepare("INSERT INTO notifications (user_id, message, is_read) VALUES (?, ?, 0)");
$stmt->bind_param("is", $user_id, $msg);
$stmt->execute();

echo "<script>alert('User blocked and notified.'); window.location='admin_dashboard.php';</script>";
