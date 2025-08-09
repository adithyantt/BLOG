<?php
include "config.php";

if (isset($_POST['submit'])) {
    $uname = trim($_POST['uname']);
    $email = trim($_POST['email']);
      $phone = $_POST['phone'];
    $role = $_POST['role'];
    $pwd = $_POST['pwd'];
    $confirm_pwd = $_POST['confirm_password'];

    // ✅ Basic validations
    if (empty($uname) || empty($email) || empty($role)  || empty($pwd) || empty($confirm_pwd)) {
        echo "Please fill in all fields.";
        exit();
    }

    if ($pwd !== $confirm_pwd) {
        echo "Passwords do not match.";
        exit();
    }

    // ✅ Check if email already exists
 //   $check = mysqli_query($conn, "SELECT * FROM login WHERE email = '$email'");
 // if (mysqli_num_rows($check) > 0) {
   // echo "<script>
   //     alert('Email already registered. Please use a different one.');
  //      window.location.href = 'signup.html';
//</script>";
 //   exit();



    // ✅ Insert user (you can hash password if needed)
    $sql = "INSERT INTO credentials (uname, email,phone, role, pwd) VALUES ('$uname', '$email','$phone','$role', '$pwd')";
    if (mysqli_query($conn, $sql)) {
        header("Location: home.php");
        exit();
    } else {
        echo "Signup failed: " . mysqli_error($conn);
    }
}
?>
