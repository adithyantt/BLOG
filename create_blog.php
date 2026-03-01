<?php
include "config.php";
session_start();

if (!isset($_SESSION['email'])) {
    header("Location: login.html");
    exit();  
}

$email = $_SESSION['email'];

/* -- Current user info -- */
$userRes = mysqli_query($conn, "SELECT user_id, uname, profile_img FROM credentials WHERE email='" . mysqli_real_escape_string($conn, $email) . "' LIMIT 1");
$currentUser = mysqli_fetch_assoc($userRes);
$current_user_id = $currentUser ? (int)$currentUser['user_id'] : 0;
$currentUserName = $currentUser['uname'];
$currentUserImg = !empty($currentUser['profile_img']) ? $currentUser['profile_img'] : "uploads/default_profile.png";

/* --- Initialize post variables --- */
$post_id = $_GET['post_id'] ?? null;
$title = "";
$content = "";
$category = "General";
$existing_img = "";

/* --- If editing an existing draft --- */
if ($post_id) {
    $post_q = mysqli_query($conn, "SELECT * FROM posts WHERE post_id=".(int)$post_id." AND user_id=$current_user_id LIMIT 1");
    $post = mysqli_fetch_assoc($post_q);
    if ($post) {
        $title = $post['title'];
        $content = $post['content'];
        $category = $post['category'];
        $existing_img = $post['img_url'];
    }
}

/* --- Handle form submission --- */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = mysqli_real_escape_string($conn, $_POST['title'] ?? '');
    $content = mysqli_real_escape_string($conn, $_POST['content'] ?? '');
    $category = mysqli_real_escape_string($conn, $_POST['category'] ?? 'General');

    $action = $_POST['action'] ?? 'publish';
    $status = ($action === "draft") ? "draft" : "active";

    $img_url = $existing_img;
    if (!empty($_FILES['image']['name'])) {
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

        $fileTmpPath = $_FILES["image"]["tmp_name"];
        $fileName = time() . "_" . basename($_FILES["image"]["name"]);
        $targetFile = $targetDir . $fileName;

        $fileType = mime_content_type($fileTmpPath);
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($fileType, $allowedTypes)) {
            echo "<script>alert('Only images can be uploaded.'); window.history.back();</script>";
            exit;
        }

        if (move_uploaded_file($fileTmpPath, $targetFile)) $img_url = $targetFile;
    }

    if ($post_id) {
        $sql = "UPDATE posts 
                SET title='$title', content='$content', category='$category', img_url=" . 
                ($img_url ? "'$img_url'" : "NULL") . ", 
                status='$status', updated_at=NOW() 
                WHERE post_id=$post_id AND user_id=$current_user_id";
    } else {
        $sql = "INSERT INTO posts (user_id, title, content, img_url, category, status, created_at, updated_at) 
                VALUES ($current_user_id, '$title', '$content', " . 
                ($img_url ? "'$img_url'" : "NULL") . ", 
                '$category', '$status', NOW(), NOW())";
    }

    if (mysqli_query($conn, $sql)) {
        if ($status === "draft") header("Location: drafts.php");
        else header("Location: home.php");
        exit();
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $post_id ? "Edit Draft" : "Create Blog"; ?> - NoCap Press</title>
<link rel="stylesheet" href="home.css">
<style>
/* Sidebar */
.side-nav { width: 220px; position: fixed; top: 0; left: 0; bottom: 0; background:#f8f8f8; padding-top:60px; }
.side-nav .menu-item { display:block; padding:12px 20px; color:#070707; text-decoration:none; }
.side-nav .active-link { background:#ddd; }

/* Top header */
.top-header { position:fixed; left:0; right:0; height:60px; display:flex; justify-content:space-between; align-items:center; padding:0 20px; border-bottom:1px solid #ddd; background:#fff; z-index:10; }
.top-header .site-title { font-weight:700; font-size:20px; }
.top-header .profile { display:flex; align-items:center; gap:10px; }
.top-header .profile img { width:36px; height:36px; border-radius:50%; }

/* Main content */
.main-content { margin-left:220px; margin-top:60px; padding:20px; }

/* Create container */
.create-container { max-width:700px; margin:0 auto; padding:20px; background:#fff; border-radius:12px; box-shadow:0 2px 6px rgba(0,0,0,0.1); }
.create-container h2 { text-align:center; margin-bottom:20px; }
input, textarea, select { width:100%; margin-bottom:15px; padding:12px; border:1px solid #ddd; border-radius:8px; font-size:16px; resize:vertical; }
textarea { min-height:200px; }
textarea::placeholder { color:#aaa; opacity:0.7; }
.btn-group { display:flex; justify-content:space-between; gap:10px; }
button { flex:1; padding:12px; border:none; background:#070707ff; color:white; font-size:16px; border-radius:8px; cursor:pointer; }
button:hover { background:#282829ff; }
.draft-btn { background:#777; }
.draft-btn:hover { background:#555; }
.preview-img { margin:10px 0; text-align:center; }
.preview-img img { max-width:100%; border-radius:8px; }
</style>
</head>
<body>

<!-- Sidebar -->
<nav class="side-nav">
  <div class="brand-title">NoCapPress</div> <!-- same class as home.php -->

  <a href="home.php" class="menu-item">Home</a>
  <a href="search.php" class="menu-item">Search</a>
  <a href="create_blog.php" class="menu-item active-link">Create</a>
  <a href="bookmarks.php" class="menu-item">Bookmark</a>
  <a href="profile.php" class="menu-item">Profile</a>
</nav>

<!-- Top Header -->
<div class="top-header">
  <div class="site-title">NoCap Press</div>
  <div class="profile">
    <img src="<?= $currentUserImg ?>" alt="Profile">
    <span><?= htmlspecialchars($currentUserName) ?></span>
  </div>
</div>

<div class="main-content">
  <div class="create-container">
    <h2><?php echo $post_id ? "Edit Draft" : "Create a New Post"; ?></h2>
    <form method="post" enctype="multipart/form-data">
      <input type="text" name="title" placeholder="Enter post title" value="<?php echo htmlspecialchars($title); ?>" required>
      <textarea name="content" placeholder="Write your thoughts...." required><?php echo htmlspecialchars($content); ?></textarea>
      <select name="category">
        <option value="General" <?php if($category=="General") echo "selected"; ?>>General</option>
        <option value="Technology" <?php if($category=="Technology") echo "selected"; ?>>Technology</option>
        <option value="Science" <?php if($category=="Science") echo "selected"; ?>>Science</option>
        <option value="Lifestyle" <?php if($category=="Lifestyle") echo "selected"; ?>>Lifestyle</option>
        <option value="Education" <?php if($category=="Education") echo "selected"; ?>>Education</option>
      </select>
      <input type="file" name="image" accept="image/*">

      <div class="preview-img" id="preview-container" style="display:none;">
        <img id="preview-image" src="" alt="Preview">
      </div>

      <?php if ($existing_img): ?>
        <div class="preview-img">
          <img src="<?php echo htmlspecialchars($existing_img); ?>" alt="Current Image">
        </div>
      <?php endif; ?>

      <div class="btn-group">
        <button type="submit" name="action" value="publish">Publish</button>
        <button type="submit" name="action" value="draft" class="draft-btn">Save as Draft</button>
      </div>
    </form>
  </div>
</div>

<script>
document.querySelector('input[name="image"]').addEventListener("change", function() {
    const file = this.files[0];
    const previewContainer = document.getElementById("preview-container");
    const previewImage = document.getElementById("preview-image");

    if (file) {
        const validImageTypes = ["image/jpeg","image/png","image/gif","image/webp"];
        if (!validImageTypes.includes(file.type)) {
            alert("Only images can be uploaded.");
            this.value = "";
            previewContainer.style.display = "none";
            return;
        }
        const reader = new FileReader();
        reader.onload = function(e){
            previewImage.src = e.target.result;
            previewContainer.style.display = "block";
        }
        reader.readAsDataURL(file);
    } else {
        previewContainer.style.display = "none";
    }
});
</script>

</body>
</html>
