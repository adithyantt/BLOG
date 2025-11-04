//review once again this post

<?php
include "../config.php";
session_start();

// Check if admin is logged in
if (!isset($_SESSION['email'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit();
}

if (isset($_POST['id']) && isset($_POST['type'])) {
    $id = (int)$_POST['id'];
    $type = $_POST['type']; // 'post' or 'comment'

    if ($type === "post") {
        $table = "posts";
        $id_col = "post_id";
    } elseif ($type === "comment") {
        $table = "comments";
        $id_col = "comment_id";
    } else {
        echo json_encode(["success" => false, "message" => "Invalid type"]);
        exit();
    }

    // Get current suspend status
    $result = mysqli_query($conn, "SELECT suspended FROM $table WHERE $id_col = $id LIMIT 1");
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $new_status = ($row['suspended'] == 1) ? 0 : 1;

        $update = mysqli_query($conn, "UPDATE $table SET suspended = $new_status WHERE $id_col = $id");

        if ($update) {
            echo json_encode(["success" => true, "new_status" => $new_status]);
        } else {
            echo json_encode(["success" => false, "message" => "Update failed"]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Record not found"]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Missing parameters"]);
}
