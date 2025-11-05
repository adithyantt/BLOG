<?php
include "config.php";
session_start();
header("Content-Type: application/json");

if(!isset($_SESSION['email'])){
    echo json_encode(["success"=>false,"msg"=>"Not logged in"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$comment_id = (int)($data['comment_id'] ?? 0);
if(!$comment_id){
    echo json_encode(["success"=>false,"msg"=>"Invalid comment"]);
    exit;
}

$email = $_SESSION['email'];
$userRes = mysqli_query($conn, "SELECT user_id FROM credentials WHERE email='".mysqli_real_escape_string($conn,$email)."'");
$user = mysqli_fetch_assoc($userRes);
$current_user_id = (int)$user['user_id'];

// Get comment & post author
$commentRes = mysqli_query($conn, "SELECT user_id, post_id FROM comments WHERE comment_id=$comment_id");
if(!$commentRes || mysqli_num_rows($commentRes)==0){
    echo json_encode(["success"=>false,"msg"=>"Comment not found"]);
    exit;
}
$comment = mysqli_fetch_assoc($commentRes);
$comment_user_id = (int)$comment['user_id'];

// Get post author
$postRes = mysqli_query($conn, "SELECT user_id FROM posts WHERE post_id=".intval($comment['post_id']));
$post = mysqli_fetch_assoc($postRes);
$post_author_id = (int)$post['user_id'];

if($current_user_id != $comment_user_id && $current_user_id != $post_author_id){
    echo json_encode(["success"=>false,"msg"=>"You cannot delete this comment"]);
    exit;
}

// Delete comment
if(mysqli_query($conn, "DELETE FROM comments WHERE comment_id=$comment_id")){
    echo json_encode(["success"=>true]);
}else{
    echo json_encode(["success"=>false,"msg"=>"Database error"]);
}
?>
