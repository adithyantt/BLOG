<?php
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

$email = $_SESSION['email'];
$res = mysqli_query($conn, "SELECT user_id FROM credentials WHERE email='$email'");
$user = mysqli_fetch_assoc($res);
$user_id = $user['user_id'];

// toggle likes button
$check = mysqli_query($conn, "SELECT * FROM likes WHERE post_id=$post_id AND user_id=$user_id");
if (mysqli_num_rows($check) > 0) {
    mysqli_query($conn, "DELETE FROM likes WHERE post_id=$post_id AND user_id=$user_id");
    $liked = false;
} else {
    mysqli_query($conn, "INSERT INTO likes (post_id,user_id) VALUES ($post_id,$user_id)");
    $liked = true;
}

// new count
$count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM likes WHERE post_id=$post_id"))['c'];

echo json_encode(["success"=>true,"liked"=>$liked,"like_count"=>$count]);
