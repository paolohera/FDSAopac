<?php
// search_suggestions.php - Returns suggestions as you type
require_once 'config/database.php';

$query = isset($_GET['q']) ? $_GET['q'] : '';
$search_field = isset($_GET['field']) ? $_GET['field'] : 'all';

$suggestions = [];

if (strlen($query) >= 2) {
    $search_param = "%$query%";
    
    if ($search_field == 'title') {
        $sql = "SELECT DISTINCT title, call_number FROM books WHERE title LIKE ? LIMIT 10";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $search_param);
    } elseif ($search_field == 'author') {
        $sql = "SELECT DISTINCT author as title, call_number FROM books WHERE author LIKE ? LIMIT 10";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $search_param);
    } elseif ($search_field == 'call_number') {
        $sql = "SELECT DISTINCT call_number as title, call_number FROM books WHERE call_number LIKE ? LIMIT 10";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $search_param);
    } else {
        $sql = "SELECT DISTINCT title, call_number FROM books WHERE title LIKE ? 
                UNION 
                SELECT DISTINCT author as title, call_number FROM books WHERE author LIKE ? 
                UNION 
                SELECT DISTINCT call_number as title, call_number FROM books WHERE call_number LIKE ? 
                LIMIT 10";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $search_param, $search_param, $search_param);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $suggestions[] = [
            'title' => $row['title'],
            'call_number' => $row['call_number']
        ];
    }
    $stmt->close();
}

header('Content-Type: application/json');
echo json_encode(['suggestions' => $suggestions]);

$conn->close();
?>