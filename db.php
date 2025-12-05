<?php
$host = 'localhost';
$db   = 'ashesi_lms';
$user = 'root'; // Default XAMPP user
$pass = '';     // Default XAMPP password (leave empty)

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

session_start();
?>