
<?php
//this file include the seperate bookmark

include "config.php";
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['email'])) {
    echo json_encode(["success" => false, "msg" => "Not logged in"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$post_id = isset($data['post_id']) ? (int)$data['post_id'] : 0;

if ($post_id <= 0) {
    echo json_encode(["success" => false, "msg" => "Invalid post"]);
    exit;
}

$email = mysqli_real_escape_string($conn, $_SESSION['email']);
$res = mysqli_query($conn, "SELECT user_id FROM credentials WHERE email='$email' LIMIT 1");
$user = mysqli_fetch_assoc($res);
$user_id = (int)$user['user_id'];

// Toggle bookmark
$check = mysqli_query($conn, "SELECT 1 FROM bookmarks WHERE post_id=$post_id AND user_id=$user_id LIMIT 1");
if (mysqli_num_rows($check) > 0) {
    mysqli_query($conn, "DELETE FROM bookmarks WHERE post_id=$post_id AND user_id=$user_id LIMIT 1");
    echo json_encode(["success" => true, "bookmarked" => false, "msg" => "Bookmark removed"]);
} else {
    mysqli_query($conn, "INSERT INTO bookmarks (post_id, user_id) VALUES ($post_id, $user_id)");
    echo json_encode(["success" => true, "bookmarked" => true, "msg" => "Bookmark added"]);
}
?>
