<?php
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "leave_portal_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

?>
