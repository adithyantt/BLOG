<?php
include "../config.php";
session_start();

// Ensure only admins can access
if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit();
}

// Validate user id
if (!isset($_GET['uid']) || !is_numeric($_GET['uid'])) {
    echo "Invalid user ID.";
    exit();
}

$uid = (int)$_GET['uid'];

// Fetch user details
$userQuery = mysqli_query($conn, "SELECT * FROM credentials WHERE user_id = $uid");
$user = mysqli_fetch_assoc($userQuery);

if (!$user) {
    echo "User not found.";
    exit();
}

// Count posts
$postCountQuery = mysqli_query($conn, "SELECT COUNT(*) as total_posts FROM posts WHERE user_id = $uid");
$postCount = mysqli_fetch_assoc($postCountQuery)['total_posts'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Review User</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .card { border: 1px solid #ddd; padding: 20px; border-radius: 8px; max-width: 600px; }
        .actions a { margin-right: 10px; text-decoration: none; padding: 8px 12px; border-radius: 5px; }
        .block { background: #ef4444; color: white; }
        .suspend { background: #f59e0b; color: white; }
        .activate { background: #22c55e; color: white; }
    </style>
</head>
<body>
    <h2>Review User: <?php echo htmlspecialchars($user['uname']); ?></h2>
    <div class="card">
        <p><strong>Email:</strong> <?php echo $user['email']; ?></p>
        <p><strong>Role:</strong> <?php echo ucfirst($user['role']); ?></p>
        <p><strong>Status:</strong> <?php echo $user['status'] ?? 'active'; ?></p>
        <p><strong>Joined:</strong> <?php echo $user['created_at']; ?></p>
        <p><strong>Total Posts:</strong> <?php echo $postCount; ?></p>
        
        <div class="actions">
            <?php if (($user['status'] ?? 'active') === 'active') { ?>
                <a href="manage_users.php?action=block&uid=<?php echo $user['user_id']; ?>" class="block">Block</a>
                <a href="manage_users.php?action=suspend&uid=<?php echo $user['user_id']; ?>" class="suspend">Suspend</a>
            <?php } elseif ($user['status'] === 'suspended') { ?>
                <a href="manage_users.php?action=unblock&uid=<?php echo $user['user_id']; ?>" class="activate">Activate</a>
            <?php } else { ?>
                <a href="manage_users.php?action=unblock&uid=<?php echo $user['user_id']; ?>" class="activate">Unblock</a>
            <?php } ?>
        </div>
    </div>
</body>
</html>
