<?php
include "config.php";
session_start();

if (!isset($_SESSION['email'])) {
    header("Location: login.html");
    exit();
}

if (!isset($_GET['pid']) || !filter_var($_GET['pid'], FILTER_VALIDATE_INT)) {
    echo "Invalid post.";
    exit();
}

$pid = (int)$_GET['pid'];

// Fetch post title
$post = $conn->query("SELECT title FROM posts WHERE post_id=$pid")->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Comments</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; padding:20px; }
        .comment-box { background: #fff; padding: 10px; border-radius: 5px; margin-bottom: 10px; }
    </style>
</head>
<body>
<h2>Comments on: <?php echo htmlspecialchars($post['title']); ?></h2>

<!-- Show existing comments -->
<?php
$comments = $conn->query("
    SELECT c.comment_text, u.uname 
    FROM comments c
    JOIN credentials u ON c.user_id = u.user_id
    WHERE c.post_id=$pid
    ORDER BY c.user_id DESC
");

if ($comments->num_rows > 0) {
    while ($row = $comments->fetch_assoc()) {
        echo "<div class='comment-box'><b>".htmlspecialchars($row['uname']).":</b> ".htmlspecialchars($row['comment_text'])."</div>";
    }
} else {
    echo "<p>No comments yet.</p>";
}
?>

<!-- Comment form -->
<form method="post" action="add_comment.php">
    <input type="hidden" name="post_id" value="<?php echo $pid; ?>">
    <textarea name="comment" rows="3" cols="50" required></textarea><br>
    <button type="submit">Add Comment</button>
</form>

<br>
<a href="view.php?pid=<?php echo $pid; ?>">Back to Post</a>
</body>
</html>
