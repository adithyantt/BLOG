<?php
include "config.php";
session_start();

if (!isset($_SESSION['email'])) {
    header("Location: login.html");
    exit();
}

if (isset($_POST['submit'])) {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $category = trim($_POST['category']);  // new field
    $email = $_SESSION['email'];

    // Get user ID
    $sql = "SELECT user_id FROM credentials WHERE email = '$email'";
    $result = mysqli_query($conn, $sql);

    if ($row = mysqli_fetch_assoc($result)) {
        $uid = $row['user_id'];

        if (!empty($title) && !empty($content) && !empty($category)) {
            // Handle image upload
            $img_url = NULL;
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $target_dir = "uploads/"; // Folder to store images
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }

                $file_name = time() . "_" . basename($_FILES["image"]["name"]);
                $target_file = $target_dir . $file_name;

                // Validate file type (only images)
                $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
                $allowed_types = ["jpg", "jpeg", "png", "gif"];
                if (in_array($file_type, $allowed_types)) {
                    if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                        $img_url = $target_file;
                    } else {
                        echo "Error uploading image.";
                    }
                } else {
                    echo "Only JPG, JPEG, PNG & GIF files are allowed.";
                }
            }

            // Insert post with category + image
            $query = "INSERT INTO posts (user_id, title, content, category, img_url) 
                      VALUES ('$uid', '$title', '$content', '$category', " . ($img_url ? "'$img_url'" : "NULL") . ")";
            
            if (mysqli_query($conn, $query)) {
                header("Location: home.php");
                exit();
            } else {
                echo "Error inserting post: " . mysqli_error($conn);
            }
        } else {
            echo "All fields are required.";
        }
    } else {
        echo "User not found.";
    }
}
?>
