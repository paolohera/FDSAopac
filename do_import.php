<?php
// do_import.php - Perform the actual import
require_once 'config/database.php';

$htmlContent = file_get_contents('OPAC.html');

preg_match_all('/<tr[^>]*>\s*<td[^>]*>(.*?)<\/td>\s*<td[^>]*>(.*?)<\/td>\s*<td[^>]*>(.*?)<\/td>\s*<td[^>]*>(.*?)<\/td>\s*<\/tr>/is', $htmlContent, $matches, PREG_SET_ORDER);

$imported = 0;
$skipped = 0;
$errors = 0;

foreach ($matches as $match) {
    $title = trim(strip_tags($match[1]));
    $author = trim(strip_tags($match[2]));
    $callNumberHtml = trim($match[3]);
    $callNumber = trim(preg_replace('/<br\s*\/?>/i', ' ', $callNumberHtml));
    $callNumber = trim(strip_tags($callNumber));
    $copiesText = trim(strip_tags($match[4]));
    $copies = is_numeric($copiesText) ? (int)$copiesText : 1;
    
    if (empty($title) || $title == 'TITLE OF THE BOOK' || $title == 'TOTAL NO. OF COPIES' || strlen($title) < 3) {
        continue;
    }
    
    // Determine book type
    $bookType = 'FIL';
    if (strpos($callNumber, 'SRB') !== false) $bookType = 'SRB';
    elseif (strpos($callNumber, 'MSRB') !== false) $bookType = 'MSRB';
    elseif (strpos($callNumber, 'GRB') !== false) $bookType = 'GRB';
    elseif (strpos($callNumber, 'SHB') !== false) $bookType = 'SHB';
    
    // Extract year
    $yearPublished = '';
    if (preg_match('/\b(19|20)\d{2}\b/', $callNumber, $yearMatch)) {
        $yearPublished = $yearMatch[0];
    }
    
    // Check for duplicate
    $checkSql = "SELECT id FROM books WHERE title = ? AND call_number = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("ss", $title, $callNumber);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        $skipped++;
    } else {
        $sql = "INSERT INTO books (title, author, call_number, copies, book_type, year_published) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssiss", $title, $author, $callNumber, $copies, $bookType, $yearPublished);
        
        if ($stmt->execute()) {
            $imported++;
        } else {
            $errors++;
        }
        $stmt->close();
    }
    $checkStmt->close();
}

$conn->close();

echo json_encode(['imported' => $imported, 'skipped' => $skipped, 'errors' => $errors]);
?>