<?php
include "config.php";
session_start();

if (!isset($_SESSION['email'])) {
    header("Location: login.html");
    exit();
}

$email = $_SESSION['email'];

// Fetch user info
$userQuery = mysqli_query($conn, "SELECT * FROM credentials WHERE email='$email'");
$user = mysqli_fetch_assoc($userQuery);

// Fetch user posts
$user_id = $user['user_id'];
$postQuery = mysqli_query($conn, "SELECT * FROM posts WHERE user_id='$user_id' ORDER BY created_at DESC");
?>

<h1>My Profile</h1>

<div>
    <!-- Profile Picture -->
    <?php if (!empty($user['profile_img'])): ?>
        <img src="<?php echo $user['profile_img']; ?>" width="120" height="120" style="border-radius:50%;">
    <?php else: ?>
        <img src="uploads/default_profile.png" width="120" height="120" style="border-radius:50%;">
    <?php endif; ?>
</div>

<p><b>Name:</b> <?php echo $user['uname']; ?></p>
<p><b>Email:</b> <?php echo $user['email']; ?></p>
<p><b>Bio:</b> <?php echo $user['bio'] ?? "No bio added yet."; ?></p>

<a href="edit_profile.php">Edit Profile</a>

<hr>
<h2>My Blog Posts</h2>
<?php while ($post = mysqli_fetch_assoc($postQuery)) { ?>
    <div>
        <h3><?php echo $post['title']; ?></h3>
        <?php if (!empty($post['img_url'])): ?>
            <img src="<?php echo $post['img_url']; ?>" width="200"><br>
        <?php endif; ?>
        <p><?php echo substr($post['content'], 0, 150) . "..."; ?></p>
        <a href="view.php?pid=<?php echo $post['post_id']; ?>">Read More</a>
    </div>
    <hr>
<?php } ?>
