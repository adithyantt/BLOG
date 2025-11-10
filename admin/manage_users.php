<?php
include "../config.php";
session_start();

// Only admin can access
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// ✅ Handle delete request
if (isset($_POST['delete_user'])) {
    $uid = intval($_POST['user_id']);
    mysqli_query($conn, "DELETE FROM posts WHERE user_id = $uid");
    $deleteUser = mysqli_query($conn, "DELETE FROM credentials WHERE user_id = $uid");

    if ($deleteUser) {
        echo "<script>alert('User and their posts deleted successfully!'); window.location.href='manage_users.php';</script>";
    } else {
        echo "<script>alert('Failed to delete user.');</script>";
    }
}

// Handle actions
if (isset($_GET['action']) && isset($_GET['uid'])) {
    $uid = (int)$_GET['uid'];
    $action = $_GET['action'];

    if ($action === 'block') {
        mysqli_query($conn, "UPDATE credentials SET status='blocked' WHERE user_id=$uid");
    } elseif ($action === 'unblock') {
        mysqli_query($conn, "UPDATE credentials SET status='active' WHERE user_id=$uid");
    } elseif ($action === 'delete') {
        mysqli_query($conn, "DELETE FROM credentials WHERE user_id=$uid");
    } elseif ($action === 'suspend') {
        mysqli_query($conn, "UPDATE credentials SET status='suspended' WHERE user_id=$uid");
        $msg = "Your account has been suspended by the admin.";
        mysqli_query($conn, "INSERT INTO notifications (user_id, message, created_at) 
                             VALUES ($uid, '$msg', NOW())");
    }

    header("Location: manage_users.php");
    exit();
}
// Search functionality
$search_condition = "";
if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search_term = mysqli_real_escape_string($conn, trim($_GET['search']));
    $search_condition = " AND (uname LIKE '%$search_term%' OR email LIKE '%$search_term%')";
}

// ✅ Correct query construction
$sql = "SELECT * FROM credentials WHERE role!='admin' $search_condition ORDER BY created_at DESC";
$users = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Users | NoCapPress Admin</title>
<link rel="stylesheet" href="admin_style.css">
<style>
/* ===== Layout ===== */
.admin-container {
    display: flex;
    height: 100vh;
    background-color: #f9fafb;
    font-family: "Poppins", sans-serif;
}

/* Main content layout */
.main-content {
    flex: 1;
    padding: 30px;
    overflow-y: auto;
}

/* ===== Header ===== */
header h1 {
    font-size: 26px;
    color: #111827;
    margin-bottom: 25px;
}

/* ===== Search Box ===== */
.search-box {
    margin-bottom: 25px;
    display: flex;
    justify-content: flex-end;
}

.search-box form {
    display: flex;
    gap: 10px;
}

.search-box input {
    padding: 10px 14px;
    width: 240px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    outline: none;
}

.search-box input:focus {
    border-color: #2563eb;
}

.search-box button {
    background-color: #2563eb;
    color: white;
    border: none;
    padding: 10px 16px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 500;
}

.search-box button:hover {
    background-color: #1d4ed8;
}

/* ===== Table ===== */
table {
    width: 100%;
    border-collapse: collapse;
    background-color: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

th {
    background-color: #2563eb;
    color: white;
    text-align: left;
    padding: 12px;
    font-weight: 600;
    font-size: 14px;
}

td {
    padding: 12px;
    border-bottom: 1px solid #e5e7eb;
    font-size: 14px;
}

/* ===== Action Buttons ===== */
.action-btn {
    padding: 6px 10px;
    margin: 2px;
    border-radius: 6px;
    font-size: 13px;
    border: none;
    text-decoration: none;
    color: white;
    display: inline-block;
}

.action-container {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
}

.review { background-color: #2563eb; }
.message { background-color: #3b82f6; }
.block { background-color: #f87171; }
.suspend { background-color: #fbbf24; color: #111827; }
.unblock { background-color: #34d399; }
.delete { background-color: #dc2626; }

.action-btn:hover {
    opacity: 0.9;
    transform: scale(1.02);
    transition: all 0.2s ease;
}
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
           <li><a href="#" onclick="confirmLogout(event)">🚪 Logout</a></li>

        </ul>
    </aside>

    <main class="main-content">
        <header><h1>Manage Users</h1></header>

        <!-- Search Bar -->
        <div class="search-box">
            <form method="GET" action="manage_users.php">
                <input type="text" name="search" placeholder="🔍 Search by name or email" value="<?php echo $_GET['search'] ?? ''; ?>">
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
                <td><?php echo htmlspecialchars($user['uname']); ?></td>
                <td><?php echo htmlspecialchars($user['email']); ?></td>
                <td><?php echo ucfirst($user['role']); ?></td>
                <td><?php echo $user['status'] ?? 'active'; ?></td>
                <td><?php echo $user['created_at']; ?></td>
                <td>
                    <div class="action-container">
                        <a href="review_user.php?uid=<?php echo $user['user_id']; ?>" class="action-btn review">Review</a>
                        <a href="admin_message.php?uid=<?php echo $user['user_id']; ?>" class="action-btn message">Message</a>

                        <?php if (($user['status'] ?? 'active') === 'active') { ?>
                            <a href="?action=block&uid=<?php echo $user['user_id']; ?>" class="action-btn block">Block</a>
                            <a href="?action=suspend&uid=<?php echo $user['user_id']; ?>" class="action-btn suspend">Suspend</a>
                        <?php } elseif (($user['status'] ?? '') === 'suspended') { ?>
                            <a href="?action=unblock&uid=<?php echo $user['user_id']; ?>" class="action-btn unblock">Activate</a>
                        <?php } else { ?>
                            <a href="?action=unblock&uid=<?php echo $user['user_id']; ?>" class="action-btn unblock">Unblock</a>
                        <?php } ?>

                        <a href="?action=delete&uid=<?php echo $user['user_id']; ?>" class="action-btn delete" onclick="return confirm('Are you sure you want to permanently delete this user?');">Delete</a>
                    </div>
                </td>
            </tr>
            <?php } ?>
        </table>
    </main>
</div>
<script>
function confirmLogout(event) {
    event.preventDefault(); // stop the default link action
    const confirmLogout = confirm("Are you sure you want to log out?");
    if (confirmLogout) {
        window.location.href = "../logout.php"; // perform logout
    }
    // else do nothing (stay on the current page)
}
</script>

</body>
</html>
