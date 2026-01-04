<?php
$servername = "localhost:3306"; // Adjust the port if necessary
$username = "root";
$password = "";
$dbname = "e-commerce";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
