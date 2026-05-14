<?php
session_start(); // Start session for login management

// Database configuration
$host = 'localhost';
$username = 'root';      // Default XAMPP username
$password = '';          // Default XAMPP password (empty)
$database = 'opac_db';

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to UTF-8
$conn->set_charset("utf8");

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['admin_id']) && isset($_SESSION['admin_username']);
}

// Function to redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

// Function to get current admin info
function getCurrentAdmin($conn) {
    if (isLoggedIn()) {
        $stmt = $conn->prepare("SELECT id, username, full_name FROM admin WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['admin_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    return null;
}
?>