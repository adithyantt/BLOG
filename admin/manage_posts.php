<?php
include "../config.php";
session_start();

// Only admin can access
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Handle delete action
if (isset($_GET['action']) && isset($_GET['pid'])) {
    $pid = (int)$_GET['pid'];
    $action = $_GET['action'];

    if ($action === 'delete') {
        mysqli_query($conn, "DELETE FROM posts WHERE post_id=$pid");
        // Also delete any reports related to this post
        mysqli_query($conn, "DELETE FROM reports WHERE reported_post_id=$pid");
    }

    header("Location: manage_posts.php");
    exit();
}

// Fetch all posts with author name
$posts = mysqli_query($conn, "SELECT p.*, c.uname FROM posts p JOIN credentials c ON p.user_id = c.user_id ORDER BY p.created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Posts | NoCapPress Admin</title>
<link rel="stylesheet" href="admin_style.css">
<style>
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { border-bottom: 1px solid #ddd; padding: 8px; text-align: left; }
    .action-btn { padding: 4px 8px; border-radius: 4px; cursor: pointer; margin-right: 5px; color: #fff; text-decoration: none; }
    .edit { background-color: #3b82f6; }
    .delete { background-color: #ef4444; }
</style>
</head>
<body>
<div class="admin-container">
    <aside class="sidebar">
        <h2>NoCapPress Admin</h2>
        <ul>
            <li><a href="admin_dashboard.php">📊 Dashboard</a></li>
            <li><a href="manage_users.php">👤 Manage Users</a></li>
            <li><a href="manage_posts.php" class="active">📝 Manage Posts</a></li>
            <li><a href="reports.php">⚠️ Reports</a></li>
            <li><a href="../logout.php">🚪 Logout</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <header><h1>Manage Posts</h1></header>

        <table>
            <tr>
                <th>Title</th>
                <th>Author</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>
            <?php while ($post = mysqli_fetch_assoc($posts)) { ?>
            <tr>
                <td><?php echo $post['title']; ?></td>
                <td><?php echo $post['uname']; ?></td>
                <td><?php echo $post['created_at']; ?></td>
                <td>
                    <a href="edit_post.php?pid=<?php echo $post['post_id']; ?>" class="action-btn edit">Edit</a>
                    <a href="?action=delete&pid=<?php echo $post['post_id']; ?>" class="action-btn delete" onclick="return confirm('Are you sure you want to delete this post?')">Delete</a>
                </td>
            </tr>
            <?php } ?>
        </table>
    </main>
</div>
</body>
</html>
