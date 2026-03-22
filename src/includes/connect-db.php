<?php
$host = 'db'; // Change to localhost if you are using XAMPP(usually localhost)
$username = 'root';
$password = 'root'; // let this empty for xampp
$database = 'personal_student_tracker_db'; // Database name
// Create connection
$conn = mysqli_connect($host, $username, $password, $database);
// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

?>