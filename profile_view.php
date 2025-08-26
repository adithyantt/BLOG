<?php
include "config.php";
session_start();

if (!isset($_GET['uid'])) {
    die("No user selected.");
}

$uid = intval($_GET['uid']);
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM credentials WHERE user_id=$uid"));

if (!$user) {
    die("User not found.");
}

echo "<h2>" . htmlspecialchars($user['uname']) . "'s Profile</h2>";
echo "<img src='" . (!empty($user['profile_img']) ? htmlspecialchars($user['profile_img']) : 'uploads/default_profile.png') . "' 
        width='120' height='120' style='border-radius:50%;'><br>";
echo "<p><b>Email:</b> " . htmlspecialchars($user['email']) . "</p>";
echo "<p><b>Bio:</b> " . (!empty($user['bio']) ? htmlspecialchars($user['bio']) : 'No bio yet.') . "</p>";

// Show this user's posts
$res = mysqli_query($conn, "SELECT * FROM posts WHERE user_id=$uid ORDER BY created_at DESC");
if ($res && mysqli_num_rows($res) > 0) {
    echo "<h3>Posts:</h3>";
    while ($row = mysqli_fetch_assoc($res)) {
        echo "<div class='post-card'>";
        echo "<h4>" . htmlspecialchars($row['title']) . "</h4>";
        if (!empty($row['img_url'])) echo "<img src='" . htmlspecialchars($row['img_url']) . "' width='200'><br>";
        echo "<p>" . nl2br(substr(htmlspecialchars($row['content']), 0, 120)) . "...</p>";
        echo "<a href='view.php?pid=" . $row['post_id'] . "'>View</a>";
        echo "</div>";
    }
} else {
    echo "<p>No posts yet.</p>";
}
?>
