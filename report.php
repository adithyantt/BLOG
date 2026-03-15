<?php
// report.php - User side reporting posts
include "config.php";
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['email'])) {
    echo json_encode(["success" => false, "msg" => "Not logged in"]);
    exit();
}

// Get POST body
$data = json_decode(file_get_contents("php://input"), true);
$post_id = isset($data['post_id']) ? (int)$data['post_id'] : 0;
$reason  = isset($data['reason']) ? trim($data['reason']) : "No reason provided";

if ($post_id <= 0) {
    echo json_encode(["success" => false, "msg" => "Invalid post"]);
    exit();
}

// Current users
$email = mysqli_real_escape_string($conn, $_SESSION['email']);
$res = mysqli_query($conn, "SELECT user_id FROM credentials WHERE email='$email' LIMIT 1");
$user = mysqli_fetch_assoc($res);
$user_id = (int)($user['user_id'] ?? 0);

if (!$user_id) {
    echo json_encode(["success" => false, "msg" => "User not found"]);
    exit();
}

// Insert into reports table
$reasonEsc = mysqli_real_escape_string($conn, $reason);
$sql = "INSERT INTO reports (post_id, user_id, reason, reported_at) 
        VALUES ($post_id, $user_id, '$reasonEsc', NOW())";

if (mysqli_query($conn, $sql)) {
    echo json_encode(["success" => true, "msg" => "Report submitted"]);
} else {
    echo json_encode(["success" => false, "msg" => "DB Error: " . mysqli_error($conn)]);
}
?>
