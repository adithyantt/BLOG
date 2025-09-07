//for deleting an item in the post side

<?php
include "../config.php";
session_start();

if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit();
}

$type = $_POST['type'] ?? '';
$id   = $_POST['id'] ?? '';

if (!ctype_digit($id)) {
    echo json_encode(["success" => false, "message" => "Invalid ID"]);
    exit();
}

$table = "";
$pk    = "";
switch ($type) {
    case 'user':
        $table = "credentials";
        $pk = "user_id";
        break;
    case 'post':
        $table = "posts";
        $pk = "post_id";
        break;
    case 'comment':
        $table = "comments";
        $pk = "comment_id";
        break;
    default:
        echo json_encode(["success" => false, "message" => "Invalid type"]);
        exit();
}

$stmt = mysqli_prepare($conn, "DELETE FROM $table WHERE $pk = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
$ok = mysqli_stmt_execute($stmt);

if ($ok) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "Database error"]);
}
?>
