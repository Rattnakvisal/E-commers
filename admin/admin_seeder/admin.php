<?php
include '../../config/conn.php';

$admin_email = "admin@ecommerce.com";
$admin_password = password_hash("admin123", PASSWORD_DEFAULT);
$admin_name = "Admin";

$sql = "SELECT * FROM users WHERE email='$admin_email'";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    $sql = "INSERT INTO users (name, email, password) VALUES ('$admin_name', '$admin_email', '$admin_password')";
    if ($conn->query($sql) === TRUE) {
        echo "Admin user seeded successfully.";
    } else {
        echo "Error: " . $conn->error;
    }
} else {
    echo "Admin user already exists.";
}

$conn->close();
