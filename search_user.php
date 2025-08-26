<?php
include "config.php";
session_start();

if (!isset($_SESSION['email'])) {
    header("Location: login.html");
    exit();
}

$q = isset($_GET['q']) ? mysqli_real_escape_string($conn, $_GET['q']) : '';

echo "<h2>🔍 Search Results for User: " . htmlspecialchars($q) . "</h2><hr>";

if (!empty($q)) {
    $sql = "SELECT user_id, uname, email, bio, profile_img 
            FROM credentials 
            WHERE uname LIKE '%$q%' OR email LIKE '%$q%'";
    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) > 0) {
        while ($user = mysqli_fetch_assoc($result)) {
            echo "<div class='post-card'>";
            echo "<img src='" . (!empty($user['profile_img']) ? htmlspecialchars($user['profile_img']) : 'uploads/default_profile.png') . "' 
                    width='60' height='60' style='border-radius:50%; vertical-align:middle; margin-right:10px;'>";
            echo "<strong>" . htmlspecialchars($user['uname']) . "</strong> (" . htmlspecialchars($user['email']) . ")<br>";
            echo "<p>" . (!empty($user['bio']) ? htmlspecialchars($user['bio']) : 'No bio yet.') . "</p>";
            echo "<a href='profile_view.php?uid=" . $user['user_id'] . "'>View Profile</a>";
            echo "</div>";
        }
    } else {
        echo "<p>No users found.</p>";
    }
} else {
    echo "<p>Please enter a search query.</p>";
}
?>
