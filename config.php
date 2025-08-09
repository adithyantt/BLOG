<?php 
$conn=mysqli_connect("localhost","root","");
$result=mysqli_select_db($conn,"blog");
if(!$result){
   die("connection failed!"); 
}


?>
