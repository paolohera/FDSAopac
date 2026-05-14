<?php
require_once 'config/database.php';

$id = $_GET['id'] ?? 0;

$sql = "SELECT * FROM books WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$book = $result->fetch_assoc();

$stmt->close();
$conn->close();

header('Content-Type: application/json');
echo json_encode($book);
?>