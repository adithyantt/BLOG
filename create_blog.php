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
    $email = $_SESSION['email'];

    // Get user ID
    $sql = "SELECT user_id AS user_id FROM credentials WHERE email = '$email'";
    $result = mysqli_query($conn, $sql);

    if ($row = mysqli_fetch_assoc($result)) {
        $uid = $row['user_id'];

        if (!empty($title) && !empty($content)) {
            $query = "INSERT INTO posts (user_id, title, content) VALUES ('$uid', '$title', '$content')";
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

