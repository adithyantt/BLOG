<?php
include "config.php";
session_start();

if (!isset($_SESSION['email'])) {
    header("Location: login.html");
    exit();
}

// Get post ID from URL
if (!isset($_GET['pid'])) {
    echo "Invalid access.";
    exit();
}

$pid = $_GET['pid'];

// Validate pid as integer
if (!filter_var($pid, FILTER_VALIDATE_INT)) {
    echo "Invalid post ID.";
    exit();
}

// Fetch post details with author email
$query = "SELECT p.*, c.email FROM posts p JOIN credentials c ON p.user_id = c.user_id WHERE p.post_id = $pid";
$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) == 0) {
    echo "Post not found.";
    exit();
}

$row = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Blog</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            margin: 0;
            padding: 20px;
        }

        h2 {
            color: #333;
            margin-bottom: 5px;
        }

        small {
            color: #777;
        }

        p {
            background: #fff;
            padding: 15px;
            border-radius: 5px;
            line-height: 1.6;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
        }

        a {
            display: inline-block;
            margin-right: 15px;
            color: #007BFF;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        .actions {
            margin-top: 20px;
        }
    </style>
</head>
<body>

<h2><?php echo htmlspecialchars($row['title']); ?></h2>
<small>Author: <?php echo htmlspecialchars($row['email']); ?></small><br><br>

<p><?php echo nl2br(htmlspecialchars($row['content'])); ?></p>

<div class="actions">
<?php
if ($_SESSION['email'] === $row['email']) {
    echo "<a href='edit.php?pid=$pid'>Edit</a>";
    echo "<a href='delete.php?pid=$pid' onclick=\"return confirm('Delete this post?');\">Delete</a>";
}
?>
    <br><br>
    <a href='home.php'>Back to Home</a>
</div>

</body>
</html>
