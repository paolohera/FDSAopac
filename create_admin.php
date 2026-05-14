<?php
// create_admin.php - Run this file to create a working admin account
require_once 'config/database.php';

// Delete existing admin
$conn->query("DELETE FROM admin WHERE username = 'librarian'");

// Create new admin with password: admin123
$username = 'librarian';
$password = 'admin123';
$full_name = 'Head Librarian';

$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO admin (username, password, full_name) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $username, $hashed_password, $full_name);

if ($stmt->execute()) {
    echo "<h2 style='color: green;'>✅ Admin account created successfully!</h2>";
    echo "<p><strong>Username:</strong> librarian</p>";
    echo "<p><strong>Password:</strong> admin123</p>";
    echo "<p><strong>Full Name:</strong> Head Librarian</p>";
    echo "<hr>";
    echo "<a href='login.php' style='font-size: 18px;'>🔐 Go to Login Page</a>";
} else {
    echo "<h2 style='color: red;'>❌ Error: " . $conn->error . "</h2>";
}

$stmt->close();
$conn->close();
?>