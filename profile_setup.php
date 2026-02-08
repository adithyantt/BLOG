<?php
session_start();
include "config.php";

// Only allow access if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user datas
$stmt = mysqli_prepare($conn, "SELECT * FROM credentials WHERE user_id=? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

$error = "";
$success = "";

// ✅ Default avatar image path (make sure this file exists)
$default_img = "uploads/profile/default_profile.png";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // 🟢 If user skips setup
    if (isset($_POST['skip'])) {
        // Assign default image if none exists
        if (empty($user['profile_img']) || !file_exists($user['profile_img'])) {
            $updateDefault = mysqli_prepare($conn, "UPDATE credentials SET profile_img=? WHERE user_id=?");
            mysqli_stmt_bind_param($updateDefault, "si", $default_img, $user_id);
            mysqli_stmt_execute($updateDefault);
        }
        header("Location: home.php");
        exit();
    }

    // 🟢 Otherwise, handle save action
    $bio = mysqli_real_escape_string($conn, trim($_POST['bio']));
    $profile_img = $user['profile_img']; // keep old image if not changed

    // Handle new image upload
    if (isset($_FILES['profile_img']) && $_FILES['profile_img']['error'] == 0) {
        $targetDir = "uploads/profile/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

        $fileTmp  = $_FILES['profile_img']['tmp_name'];
        $fileName = time() . "_" . basename($_FILES['profile_img']['name']);
        $targetFilePath = $targetDir . $fileName;

        $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
        $allowedTypes = ["jpg", "jpeg", "png", "gif"];
        $maxSize = 2 * 1024 * 1024; // 2MB

        if (!in_array($fileType, $allowedTypes)) {
            $error = "Invalid file type. Only JPG, JPEG, PNG, GIF allowed.";
        } elseif ($_FILES['profile_img']['size'] > $maxSize) {
            $error = "File too large. Max 2MB allowed.";
        } else {
            if (move_uploaded_file($fileTmp, $targetFilePath)) {
                $profile_img = $targetFilePath;
            } else {
                $error = "File upload failed. Please try again.";
            }
        }
    }

    // If user didn’t upload anything or no valid image exists, use default
    if (empty($profile_img) || !file_exists($profile_img)) {
        $profile_img = $default_img;
    }

    // Update database
    if (!$error) {
        $update = mysqli_prepare($conn, "UPDATE credentials SET bio=?, profile_img=? WHERE user_id=?");
        mysqli_stmt_bind_param($update, "ssi", $bio, $profile_img, $user_id);
        if (mysqli_stmt_execute($update)) {
            header("Location: home.php");
            exit();
        } else {
            $error = "Error updating profile: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Setup Profile - NoCapPress</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: #f2f2f2;
    padding: 20px;
}
.container {
    max-width: 480px;
    margin: 40px auto;
    background: #fff;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    text-align: center;
}
h1 {
    font-size: 22px;
    margin-bottom: 15px;
}
textarea {
    width: 100%;
    padding: 10px;
    border-radius: 6px;
    border: 1px solid #ccc;
}
input[type="file"] {
    margin: 10px 0;
}
button {
    padding: 10px 20px;
    background: #000;
    color: #fff;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    margin: 10px 5px;
}
button:hover {
    background: #333;
}
.error { color: red; margin-bottom: 10px; }
.success { color: green; margin-bottom: 10px; }
img.profile-preview {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    object-fit: cover;
    display: block;
    margin: 0 auto 15px;
}
</style>
</head>
<body>
<div class="container">
    <h1>Setup Your Profile</h1>

    <?php if($error) echo "<div class='error'>$error</div>"; ?>
    <?php if($success) echo "<div class='success'>$success</div>"; ?>

    <?php
        // Display uploaded image or default avatar
        $img_to_show = (!empty($user['profile_img']) && file_exists($user['profile_img']))
            ? $user['profile_img']
            : $default_img;
    ?>
    <img src="<?php echo $img_to_show; ?>" alt="Profile Image" class="profile-preview">

    <form method="POST" enctype="multipart/form-data">
        <label for="bio">Bio:</label><br>
        <textarea name="bio" id="bio" rows="4" placeholder="Tell us about yourself"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea><br><br>

        <label for="profile_img">Profile Picture:</label><br>
        <input type="file" name="profile_img" id="profile_img" accept="image/*"><br><br>

        <button type="submit" name="save">Save</button>
        <button type="submit" name="skip">Skip</button>
    </form>
</div>
</body>
</html>
