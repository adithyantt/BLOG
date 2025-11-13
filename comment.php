<?php
include "config.php";
session_start();
header('Content-Type: application/json');

// ✅ Check login
if (!isset($_SESSION['email'])) {
    echo json_encode(["success"=>false,"msg"=>"Not logged in"]);
    exit;
}

// ✅ Get JSON data
$data = json_decode(file_get_contents("php://input"), true);
$post_id = isset($data['post_id']) ? (int)$data['post_id'] : 0;
$comment = isset($data['comment']) ? trim($data['comment']) : "";

if ($post_id <= 0 || empty($comment)) {
    echo json_encode(["success"=>false,"msg"=>"Invalid data"]);
    exit;
}

// ✅ Get current user
$res = mysqli_query($conn, "SELECT user_id, uname FROM credentials WHERE email='".mysqli_real_escape_string($conn, $_SESSION['email'])."' LIMIT 1");
$user = mysqli_fetch_assoc($res);
$user_id = $user['user_id'];
$uname   = $user['uname'];

// ✅ Insert comment
$commentEscaped = mysqli_real_escape_string($conn, $comment);
mysqli_query($conn, "INSERT INTO comments (post_id, user_id, comment, created_at) 
                     VALUES ($post_id, $user_id, '$commentEscaped', NOW())");

// ✅ Fetch post owner
$postOwnerRes = mysqli_query($conn, "SELECT user_id FROM posts WHERE post_id=$post_id LIMIT 1");
$postOwnerId = ($postOwnerRes && mysqli_num_rows($postOwnerRes) > 0) ? mysqli_fetch_assoc($postOwnerRes)['user_id'] : 0;

// ✅ Notify post owner (only if not self)
if ($postOwnerId && $postOwnerId != $user_id) {
    $message = "$uname commented on your post.";
    $link    = "view.php?pid=$post_id";

    mysqli_query($conn, "INSERT INTO notifications (user_id, message, link, is_read, created_at) 
                         VALUES ($postOwnerId, '".mysqli_real_escape_string($conn, $message)."', '$link', 0, NOW())");
}

// ✅ Return JSON response for instant display
echo json_encode([
    "success"    => true,
    "uname"      => htmlspecialchars($uname),
    "comment"    => htmlspecialchars($comment),
    "created_at" => "just now"
]);
?>
