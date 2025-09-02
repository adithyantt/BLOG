<?php
include "../config.php";
session_start();

if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Handle actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];

    if ($action === 'delete_post') {
        mysqli_query($conn, "DELETE FROM posts WHERE post_id=$id");
        mysqli_query($conn, "DELETE FROM reports WHERE reported_post_id=$id");
    } elseif ($action === 'block_user') {
        mysqli_query($conn, "UPDATE credentials SET status='blocked' WHERE user_id=$id");
        mysqli_query($conn, "DELETE FROM reports WHERE reported_user_id=$id");
    }

    header("Location: reports.php");
    exit();
}

// Fetch reports
$reports = mysqli_query($conn, "
    SELECT r.*, 
           ru.uname AS reporter_name, 
           u.uname AS reported_user_name,
           p.title AS post_title
    FROM reports r
    LEFT JOIN credentials ru ON r.reporter_id = ru.user_id
    LEFT JOIN credentials u ON r.reported_user_id = u.user_id
    LEFT JOIN posts p ON r.reported_post_id = p.post_id
    ORDER BY r.created_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Reports | NoCapPress Admin</title>
<link rel="stylesheet" href="admin_style.css">
<style>
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { border-bottom: 1px solid #ddd; padding: 8px; text-align: left; }
    .action-btn { padding: 4px 8px; border-radius: 4px; cursor: pointer; margin-right: 5px; color: #fff; text-decoration: none; }
    .delete { background-color: #ef4444; }
    .block { background-color: #f87171; }
</style>
</head>
<body>
<div class="admin-container">
    <aside class="sidebar">
        <h2>NoCapPress Admin</h2>
        <ul>
            <li><a href="admin_dashboard.php">📊 Dashboard</a></li>
            <li><a href="manage_users.php">👤 Manage Users</a></li>
            <li><a href="manage_posts.php">📝 Manage Posts</a></li>
            <li><a href="reports.php" class="active">⚠️ Reports</a></li>
            <li><a href="../logout.php">🚪 Logout</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <header><h1>Reports</h1></header>

        <table>
            <tr>
                <th>Type</th>
                <th>Reporter</th>
                <th>Reported User</th>
                <th>Post</th>
                <th>Reason</th>
                <th>Date</th>
                <th>Action</th>
            </tr>
            <?php while ($r = mysqli_fetch_assoc($reports)) { ?>
            <tr>
                <td><?php echo ucfirst($r['report_type']); ?></td>
                <td><?php echo $r['reporter_name']; ?></td>
                <td><?php echo $r['reported_user_name'] ?? '-'; ?></td>
                <td><?php echo $r['post_title'] ?? '-'; ?></td>
                <td><?php echo $r['reason']; ?></td>
                <td><?php echo $r['created_at']; ?></td>
                <td>
                    <?php if ($r['report_type'] === 'post') { ?>
                        <a href="?action=delete_post&id=<?php echo $r['reported_post_id']; ?>" class="action-btn delete" onclick="return confirm('Delete this post?')">Delete Post</a>
                    <?php } else { ?>
                        <a href="?action=block_user&id=<?php echo $r['reported_user_id']; ?>" class="action-btn block" onclick="return confirm('Block this user?')">Block User</a>
                    <?php } ?>
                </td>
            </tr>
            <?php } ?>
        </table>
    </main>
</div>
</body>
</html>
