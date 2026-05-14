<?php
// ajax_bulk_import.php - Handle bulk import via AJAX
require_once 'config/database.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$books = $data['books'] ?? [];

$imported = 0;
$skipped = 0;
$errors = 0;

foreach ($books as $book) {
    $title = trim($book['title'] ?? '');
    $author = trim($book['author'] ?? '');
    $call_number = trim($book['call_number'] ?? '');
    $copies = (int)($book['copies'] ?? 1);
    
    if (empty($title) || empty($call_number)) {
        $errors++;
        continue;
    }
    
    // Extract year
    $year_published = '';
    if (preg_match('/\b(19|20)\d{2}\b/', $call_number, $year_match)) {
        $year_published = $year_match[0];
    }
    
    // Determine book type
    $book_type = 'FIL';
    if (strpos($call_number, 'SRB') !== false) $book_type = 'SRB';
    elseif (strpos($call_number, 'MSRB') !== false) $book_type = 'MSRB';
    elseif (strpos($call_number, 'GRB') !== false) $book_type = 'GRB';
    elseif (strpos($call_number, 'SHB') !== false) $book_type = 'SHB';
    
    // Clean author
    $author = preg_replace('/\bet\.?\s*al\.?\b/i', '', $author);
    $author = trim($author);
    
    // Check for duplicate
    $check_sql = "SELECT id FROM books WHERE title = ? AND call_number = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ss", $title, $call_number);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $skipped++;
        continue;
    }
    
    // Insert book
    $sql = "INSERT INTO books (title, author, call_number, copies, book_type, year_published) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssiss", $title, $author, $call_number, $copies, $book_type, $year_published);
    
    if ($stmt->execute()) {
        $imported++;
    } else {
        $errors++;
    }
    $stmt->close();
}

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'imported' => $imported,
    'skipped' => $skipped,
    'errors' => $errors
]);

$conn->close();
?>