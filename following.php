<?php
include "config.php";
session_start();

if (!isset($_SESSION['email'])) {
    header("Location: login.html");
    exit();
}

// Get current user IDs
$current_user_email = $_SESSION['email'];
$current_user_query = mysqli_query($conn, "SELECT user_id FROM credentials WHERE email = '$current_user_email'");
$current_user_data = mysqli_fetch_assoc($current_user_query);
$current_user_id = $current_user_data['user_id'];

// Fetch posts from people this user follows
$sql = "
    SELECT p.*, c.email 
    FROM posts p
    JOIN credentials c ON p.user_id = c.user_id
    WHERE p.user_id IN (
        SELECT following_id 
        FROM follows 
        WHERE follower_id = $current_user_id
    )
    ORDER BY p.created_at DESC
";
$following_posts = mysqli_query($conn, $sql);
?>

<section class="following-section">
    <h2>Following</h2>

    <?php if (mysqli_num_rows($following_posts) > 0): ?>
        <?php while ($post = mysqli_fetch_assoc($following_posts)): ?>
            <div class="post-card">
                <h3><?php echo htmlspecialchars($post['title']); ?></h3>
                <small>By <?php echo htmlspecialchars($post['email']); ?></small>
                <p><?php echo substr(htmlspecialchars($post['content']), 0, 150); ?>...</p>
                <a href="view.php?pid=<?php echo $post['post_id']; ?>">Read More</a>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>You are not following anyone yet, or no posts from followed users.</p>
    <?php endif; ?>
</section>

<style>
.following-section {
    background: #fff;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    box-shadow: 0 0 5px rgba(0,0,0,0.1);
}
.post-card {
    padding: 10px;
    border-bottom: 1px solid #ddd;
}
.post-card:last-child {
    border-bottom: none;
}
.post-card h3 {
    margin: 0;
    color: #333;
}
.post-card small {
    color: #777;
}
.post-card p {
    margin-top: 8px;
}
.post-card a {
    display: inline-block;
    margin-top: 8px;
    color: #007BFF;
    text-decoration: none;
}
.post-card a:hover {
    text-decoration: underline;
}
</style>
