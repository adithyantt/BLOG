<?php
include "config.php";
session_start();

$query = "SELECT p.*, c.u_name, COUNT(l.like_id) AS like_count 
          FROM posts p
          JOIN credentials c ON p.user_id = c.user_id
          LEFT JOIN likes l ON p.post_id = l.post_id
          GROUP BY p.post_id
          ORDER BY like_count DESC
          LIMIT 10";

$result = mysqli_query($conn, $query);

echo "<h2>🔥 Trending Posts</h2>";

while ($row = mysqli_fetch_assoc($result)) {
    echo "<div class='post'>";
    echo "<h3>" . $row['title'] . "</h3>";
    echo "<p><strong>By:</strong> " . $row['u_name'] . "</p>";
    echo "<p><strong>Likes:</strong> " . $row['like_count'] . "</p>";
    if (!empty($row['img_url'])) {
        echo "<img src='" . $row['img_url'] . "' width='200'>";
    }
    echo "<p>" . substr($row['content'], 0, 150) . "...</p>";
    echo "</div><hr>";
}
?>
