<?php
include "config.php";
session_start();

// Check if user is logged in
if(!isset($_SESSION['user_id'])){
    echo json_encode(["success"=>false, "msg"=>"You must be logged in"]);
    exit;
}

$current_user_id = (int)$_SESSION['user_id'];

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
if(!$input || !isset($input['comment_id'], $input['reason'])){
    echo json_encode(["success"=>false, "msg"=>"Invalid input"]);
    exit;
}

$comment_id = (int)$input['comment_id'];
$reason = trim(mysqli_real_escape_string($conn, $input['reason']));
$details = isset($input['details']) ? trim(mysqli_real_escape_string($conn, $input['details'])) : "";

// Fetch comment info
$comment_res = mysqli_query($conn, "SELECT user_id, post_id FROM comments WHERE comment_id=$comment_id AND status='active'");
if(mysqli_num_rows($comment_res) == 0){
    echo json_encode(["success"=>false, "msg"=>"Comment not found or already deleted"]);
    exit;
}

$comment_data = mysqli_fetch_assoc($comment_res);
$reported_user_id = (int)$comment_data['user_id'];
$reported_post_id = (int)$comment_data['post_id'];

// Prevent self-report
if($reported_user_id == $current_user_id){
    echo json_encode(["success"=>false, "msg"=>"You cannot report your own comment"]);
    exit;
}

// Check if this user already reported this comment
$check = mysqli_query($conn, "SELECT report_id FROM reports WHERE reporter_id=$current_user_id AND reported_comment_id=$comment_id AND report_type='user' AND status='pending'");
if(mysqli_num_rows($check) > 0){
    echo json_encode(["success"=>false, "msg"=>"You have already reported this comment"]);
    exit;
}

// Insert report
$insert = mysqli_query($conn, "
    INSERT INTO reports 
    (reporter_id, reported_user_id, reported_post_id, reported_comment_id, reason, report_type, status, created_at)
    VALUES 
    ($current_user_id, $reported_user_id, $reported_post_id, $comment_id, '$reason', 'user', 'pending', NOW())
");

if($insert){
    echo json_encode(["success"=>true, "msg"=>"Comment reported successfully"]);
} else {
    echo json_encode(["success"=>false, "msg"=>"Failed to report comment"]);
}
?>
//this is code
