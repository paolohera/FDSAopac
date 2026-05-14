<?php
require_once 'config/database.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please login first.']);
    exit();
}

$id = $_POST['id'] ?? 0;

$sql = "DELETE FROM books WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);

$response = ['success' => false];

if ($stmt->execute()) {
    $response['success'] = true;
}

$stmt->close();
$conn->close();

header('Content-Type: application/json');
echo json_encode($response);
?>