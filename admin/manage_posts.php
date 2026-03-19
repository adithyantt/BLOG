<?php
include "../config.php";
session_start();

// Only admin can access
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$search = "";
if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search = trim($_GET['search']);
    $param = "%" . $search . "%";

    $stmt = $conn->prepare("SELECT p.*, c.uname 
                            FROM posts p 
                            JOIN credentials c ON p.user_id = c.user_id 
                            WHERE p.title LIKE ? 
                            ORDER BY p.created_at DESC");
    $stmt->bind_param("s", $param);
} else {
    $stmt = $conn->prepare("SELECT p.*, c.uname 
                            FROM posts p 
                            JOIN credentials c ON p.user_id = c.user_id 
                            ORDER BY p.created_at DESC");
}
$stmt->execute();
$posts = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Posts | NoCapPress Admin</title>
<link rel="stylesheet" href="admin_style.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<style>
/* ===== Layout ===== */
.admin-container {
    display: flex;
    height: 100vh;
    background-color: #f9fafb;
    font-family: "Poppins", sans-serif;
}

/* ===== Main Content ===== */
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
    transition: all 0.2s ease;
}

.action-container {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
}

.review { background-color: #2563eb; }
.suspend { background-color: #fbbf24; color: #111827; }
.activate { background-color: #22c55e; }
.delete { background-color: #dc2626; }

.action-btn:hover {
    opacity: 0.9;
    transform: scale(1.02);
}

/* ===== No Data Message ===== */
.no-data {
    text-align: center;
    color: #888;
    padding: 20px;
    font-style: italic;
}
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
            <li><a href="#" onclick="confirmLogout(event)">🚪 Logout</a></li>

        </ul>
    </aside>

    <main class="main-content">
        <header><h1>Manage Posts</h1></header>

        <!-- Search Bar -->
        <div class="search-box">
            <form method="get" action="manage_posts.php">
                <input type="text" name="search" placeholder="🔍 Search by post title..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit">Search</button>
                <?php if ($search) { ?>
                    <a href="manage_posts.php" 
                       style="background:#dc2626; color:white; border:none; padding:10px 16px; border-radius:8px; text-decoration:none;">
                       Clear
                    </a>
                <?php } ?>
            </form>
        </div>

        <!-- Posts Table -->
        <table>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Author</th>
                <th>Status</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>

            <?php if ($posts->num_rows > 0) { ?>
                <?php while ($post = $posts->fetch_assoc()) { ?>
                <tr id="post-<?php echo $post['post_id']; ?>">
                    <td><?php echo $post['post_id']; ?></td>
                    <td><?php echo htmlspecialchars($post['title']); ?></td>
                    <td><?php echo htmlspecialchars($post['uname']); ?></td>
                    <td class="status"><?php echo $post['status'] ?? 'active'; ?></td>
                    <td><?php echo $post['created_at']; ?></td>
                    <td>
                        <div class="action-container">
                            <a href="review_post.php?pid=<?php echo $post['post_id']; ?>" class="action-btn review">Review</a>

                            <button class="action-btn toggle-btn 
                                <?php echo ($post['status'] === 'active') ? 'suspend' : 'activate'; ?>" 
                                data-id="<?php echo $post['post_id']; ?>" 
                                data-type="post">
                                <?php echo ($post['status'] === 'active') ? 'Suspend' : 'Activate'; ?>
                            </button>

                            <button class="action-btn delete delete-btn" 
                                data-id="<?php echo $post['post_id']; ?>" 
                                data-type="post">
                                Delete
                            </button>
                        </div>
                    </td>
                </tr>
                <?php } ?>
            <?php } else { ?>
                <tr>
                    <td colspan="6" class="no-data">
                        No posts found <?php echo $search ? "for '<b>".htmlspecialchars($search)."</b>'" : ""; ?>.
                    </td>
                </tr>
            <?php } ?>
        </table>
    </main>
</div>

<script>
$(document).on("click", ".toggle-btn", function() {
    let btn = $(this);
    let pid = btn.data("id");

    $.post("toggle_status.php", { type: "post", id: pid }, function(response) {
        try {
            let res = JSON.parse(response);
            if (res.success) {
                let row = $("#post-" + pid);
                row.find(".status").text(res.new_status);

                if (res.new_status === "active") {
                    btn.text("Suspend").removeClass("activate").addClass("suspend");
                } else {
                    btn.text("Activate").removeClass("suspend").addClass("activate");
                }
            } else {
                alert(res.message || "Failed to update status");
            }
        } catch (e) {
            console.error("Invalid response:", response);
        }
    });
});

$(document).on("click", ".delete-btn", function() {
    let btn = $(this);
    let pid = btn.data("id");

    if (!confirm("Are you sure you want to permanently delete this post?")) return;

    $.post("delete_item.php", { type: "post", id: pid }, function(response) {
        try {
            let res = JSON.parse(response);
            if (res.success) {
                $("#post-" + pid).fadeOut();
            } else {
                alert(res.message || "Failed to delete post");
            }
        } catch (e) {
            console.error("Invalid response:", response);
        }
    });
});

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
