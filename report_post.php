<?php
// report_post.php
header('Content-Type: application/json');
include "config.php";
session_start();

// Clean output buffer
ob_clean();

// Check login
if (!isset($_SESSION['email'])) {
    echo json_encode(["success" => false, "msg" => "Not logged in"]);
    exit;
}

// Read JSON request
$data = json_decode(file_get_contents("php://input"), true);
if (!$data || !isset($data['post_id'])) {
    echo json_encode(["success" => false, "msg" => "Invalid request"]);
    exit;
}

$post_id = (int)$data['post_id'];
$reason  = trim($data['reason'] ?? "");
$details = trim($data['details'] ?? "");

if ($reason === "") {
    echo json_encode(["success" => false, "msg" => "Reason required"]);
    exit;
}

// Reporter ID
$email = $_SESSION['email'];
$userRes = mysqli_query($conn, "SELECT user_id FROM credentials WHERE email='" . mysqli_real_escape_string($conn, $email) . "' LIMIT 1");
if (!$userRes || mysqli_num_rows($userRes) === 0) {
    echo json_encode(["success" => false, "msg" => "User not found"]);
    exit;
}
$reporter = mysqli_fetch_assoc($userRes);
$reporter_id = (int)$reporter['user_id'];

// Post author
$postRes = mysqli_query($conn, "SELECT user_id FROM posts WHERE post_id=$post_id LIMIT 1");
if (!$postRes || mysqli_num_rows($postRes) === 0) {
    echo json_encode(["success" => false, "msg" => "Post not found"]);
    exit;
}
$postData = mysqli_fetch_assoc($postRes);
$reported_user_id = (int)$postData['user_id'];

// Final reason (reason + optional details)
$finalReason = $reason . ($details ? " - " . $details : "");

// Insert report
$stmt = $conn->prepare("INSERT INTO reports 
    (reporter_id, reported_user_id, reported_post_id, reason, report_type, status, created_at) 
    VALUES (?, ?, ?, ?, 'post', 'pending', NOW())");
$stmt->bind_param("iiis", $reporter_id, $reported_user_id, $post_id, $finalReason);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "msg" => "Report submitted"]);
} else {
    echo json_encode(["success" => false, "msg" => "Database error: " . $stmt->error]);
}

$stmt->close();
$conn->close();
exit;
?>
