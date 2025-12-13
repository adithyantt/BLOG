<?php
include "config.php";
session_start();

if (!isset($_SESSION['email'])) {
    header("Location: login.html");
    exit();
}

$email = $_SESSION['email'];
$userQuery = mysqli_query($conn, "SELECT * FROM credentials WHERE email='$email'");
$user = mysqli_fetch_assoc($userQuery);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $bio = mysqli_real_escape_string($conn, $_POST['bio']);

    // Handle profile picture upload
    $profile_img = $user['profile_img'];
    if (isset($_FILES['profile_img']) && $_FILES['profile_img']['error'] == 0) {
        $targetDir = "uploads/profile/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        $fileName = time() . "_" . basename($_FILES['profile_img']['name']);
        $targetFilePath = $targetDir . $fileName;

        $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
        $allowedTypes = ["jpg", "jpeg", "png", "gif"];
        if (in_array($fileType, $allowedTypes)) {
            if (move_uploaded_file($_FILES['profile_img']['tmp_name'], $targetFilePath)) {
                $profile_img = $targetFilePath;
            }
        }
    }

    // Update database
    $update = "UPDATE credentials SET bio='$bio', profile_img=" . ($profile_img ? "'$profile_img'" : "NULL") . " WHERE email='$email'";
    if (mysqli_query($conn, $update)) {
        header("Location: profile.php");
        exit();
    } else {
        echo "Error updating profile: " . mysqli_error($conn);
    }
}
//this is the edit profile 
?>

<h1>Edit Profile</h1>

<form method="POST" enctype="multipart/form-data">
    <textarea name="bio" placeholder="Enter your bio"><?php echo $user['bio']; ?></textarea><br><br>
    <input type="file" name="profile_img" accept="image/*"><br><br>
    <button type="submit">Update Profile</button>
</form>

<a href="profile.php">Back to Profile</a>
