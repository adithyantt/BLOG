<?php
include "../config.php";
session_start();

// Only admin can access
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Handle block/unblock/delete actions
if (isset($_GET['action']) && isset($_GET['uid'])) {
    $uid = (int)$_GET['uid'];
    $action = $_GET['action'];

    if ($action === 'block') {
        mysqli_query($conn, "UPDATE credentials SET status='blocked' WHERE user_id=$uid");
    } elseif ($action === 'unblock') {
        mysqli_query($conn, "UPDATE credentials SET status='active' WHERE user_id=$uid");
    } elseif ($action === 'delete') {
        mysqli_query($conn, "DELETE FROM credentials WHERE user_id=$uid");
    }

    header("Location: manage_users.php");
    exit();
}

// Search functionality
$search_query = '';
if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search_term = mysqli_real_escape_string($conn, trim($_GET['search']));
    $search_query = "WHERE uname LIKE '%$search_term%' OR email LIKE '%$search_term%'";
}

// Fetch users
$users = mysqli_query($conn, "SELECT * FROM credentials $search_query ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Users | NoCapPress Admin</title>
<link rel="stylesheet" href="admin_style.css">
<style>
    .action-btn { margin-right: 5px; padding: 4px 8px; border: none; border-radius: 4px; cursor: pointer; }
    .block { background-color: #f87171; color: white; }
    .unblock { background-color: #34d399; color: white; }
    .delete { background-color: #ef4444; color: white; }
    .search-box { margin-bottom: 20px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border-bottom: 1px solid #ddd; padding: 10px; text-align: left; }
</style>
</head>
<body>
<div class="admin-container">
    <aside class="sidebar">
        <h2>NoCapPress Admin</h2>
        <ul>
            <li><a href="admin_dashboard.php">📊 Dashboard</a></li>
            <li><a href="manage_users.php" class="active">👤 Manage Users</a></li>
            <li><a href="manage_posts.php">📝 Manage Posts</a></li>
            <li><a href="reports.php">⚠️ Reports</a></li>
            <li><a href="../logout.php">🚪 Logout</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <header>
            <h1>Manage Users</h1>
        </header>

        <!-- Search Form -->
        <div class="search-box">
            <form method="GET" action="manage_users.php">
                <input type="text" name="search" placeholder="Search by name or email" value="<?php echo $_GET['search'] ?? ''; ?>" />
                <button type="submit">Search</button>
            </form>
        </div>

        <!-- Users Table -->
        <table>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Status</th>
                <th>Joined</th>
                <th>Actions</th>
            </tr>
            <?php while ($user = mysqli_fetch_assoc($users)) { ?>
            <tr>
                <td><?php echo $user['uname']; ?></td>
                <td><?php echo $user['email']; ?></td>
                <td><?php echo ucfirst($user['role']); ?></td>
                <td><?php echo $user['status'] ?? 'active'; ?></td>
                <td><?php echo $user['created_at']; ?></td>
                <td>
                    <?php if (($user['status'] ?? 'active') === 'active') { ?>
                        <a href="?action=block&uid=<?php echo $user['user_id']; ?>" class="action-btn block">Block</a>
                    <?php } else { ?>
                        <a href="?action=unblock&uid=<?php echo $user['user_id']; ?>" class="action-btn unblock">Unblock</a>
                    <?php } ?>
                    <a href="?action=delete&uid=<?php echo $user['user_id']; ?>" class="action-btn delete" onclick="return confirm('Are you sure?')">Delete</a>
                </td>
            </tr>
            <?php } ?>
        </table>

    </main>
</div>
</body>
</html>
