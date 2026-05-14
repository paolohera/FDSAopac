<?php
require_once 'config/database.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please login first.']);
    exit();
}

$id = $_POST['id'] ?? 0;
$title = $_POST['title'] ?? '';
$author = $_POST['author'] ?? '';
$call_number = $_POST['call_number'] ?? '';
$copies = $_POST['copies'] ?? 1;
$book_type = $_POST['book_type'] ?? 'FIL';
$year_published = $_POST['year_published'] ?? '';

$sql = "UPDATE books SET title=?, author=?, call_number=?, copies=?, book_type=?, year_published=? WHERE id=?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sssissi", $title, $author, $call_number, $copies, $book_type, $year_published, $id);

$response = ['success' => false, 'message' => ''];

if ($stmt->execute()) {
    $response['success'] = true;
    $response['message'] = 'Book updated successfully';
} else {
    $response['message'] = $conn->error;
}

$stmt->close();
$conn->close();

header('Content-Type: application/json');
echo json_encode($response);
?>