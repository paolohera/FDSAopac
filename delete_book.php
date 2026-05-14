<?php
require_once 'config/database.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Delete the book
$sql = "DELETE FROM books WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    // Success - redirect with success message
    header("Location: index.php?deleted=1");
} else {
    // Error - redirect with error message
    header("Location: index.php?deleted=0");
}

$stmt->close();
$conn->close();
exit();
?>