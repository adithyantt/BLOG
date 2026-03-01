<?php
//this is my edit file for editing this blog
include "config.php";
session_start();

// Check if user is logged in or not
if (!isset($_SESSION['email'])) {
    header("Location: login.html");
    exit();
}

// Check if post ID is passed
if (!isset($_GET['pid'])) {
    echo "Invalid access.";
    exit();
}

$pid = $_GET['pid'];
$email = $_SESSION['email'];

// Validate post IDs
if (!filter_var($pid, FILTER_VALIDATE_INT)) {
    echo "Invalid post ID.";
    exit();
}

// Fetch post data that belongs to the logged-in user
$get = "SELECT p.* FROM posts p JOIN credentials c ON p.user_id = c.user_id WHERE p.post_id = '$pid' AND c.email = '$email'";
$res = mysqli_query($conn, $get);
$post = mysqli_fetch_assoc($res);

if (!$post) {
    echo "Post not found or unauthorized access.";
    exit();
}

// Handle update form submission
if (isset($_POST['update'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $content = mysqli_real_escape_string($conn, $_POST['content']);

    $update = "UPDATE posts SET title='$title', content='$content' WHERE post_id='$pid'";
    if (mysqli_query($conn, $update)) {
        header("Location: view.php?pid=$pid");
        exit();
    } else {
        echo "Error updating post.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Blog</title>
    <link rel="stylesheet" href="edit.css">
</head>
<body>

<h2>Edit Blog</h2>
<form method="post">
    Title:<br>
    <input type="text" name="title" value="<?php echo htmlspecialchars($post['title']); ?>"><br><br>

    Content:<br>
    <textarea name="content" rows="6" cols="50"><?php echo htmlspecialchars($post['content']); ?></textarea><br><br>

    <input type="submit" name="update" value="Update">
</form>

</body>
</html>

