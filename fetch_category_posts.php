<?php
include "config.php";
session_start();

$category = isset($_GET['category']) ? mysqli_real_escape_string($conn, $_GET['category']) : "All";

if ($category === "All") {
    $query = "SELECT p.*, c.email FROM posts p 
              JOIN credentials c ON p.user_id = c.user_id 
              ORDER BY p.created_at DESC";
} else {
    $query = "SELECT p.*, c.email FROM posts p 
              JOIN credentials c ON p.user_id = c.user_id 
              WHERE p.category='$category'
              ORDER BY p.created_at DESC";
}

$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<div class='post-card'>";
        echo "<div class='post-meta'><strong>" . htmlspecialchars($row['email']) . "</strong> | " 
             . htmlspecialchars($row['created_at']) . " | Category: " 
             . htmlspecialchars($row['category']) . "</div>";
        echo "<h3 class='post-title'>" . htmlspecialchars($row['title']) . "</h3>";
        if (!empty($row['img_url'])) {
            echo "<img src='" . htmlspecialchars($row['img_url']) . "' class='post-image'>";
        }
        echo "<p class='post-excerpt'>" . nl2br(substr(htmlspecialchars($row['content']), 0, 120)) . "...</p>";
        echo "<div class='actions'><a href='view.php?pid=" . $row['post_id'] . "'>View</a></div>";
        echo "</div>";
    }
} else {
    echo "<p>No posts found in this category.</p>";
}
