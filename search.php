<?php
// search.php - AJAX search endpoint
require_once 'config/database.php';

$query = isset($_GET['q']) ? $_GET['q'] : '';
$search_field = isset($_GET['field']) ? $_GET['field'] : 'all';
$book_type = isset($_GET['type']) ? $_GET['type'] : 'all';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Build search conditions
$where_conditions = [];
$params = [];
$types = "";

if (!empty($query)) {
    $search_param = "%$query%";
    
    if ($search_field == 'title') {
        $where_conditions[] = "title LIKE ?";
        $params[] = $search_param;
        $types .= "s";
    } elseif ($search_field == 'author') {
        $where_conditions[] = "author LIKE ?";
        $params[] = $search_param;
        $types .= "s";
    } elseif ($search_field == 'call_number') {
        $where_conditions[] = "call_number LIKE ?";
        $params[] = $search_param;
        $types .= "s";
    } else {
        $where_conditions[] = "(title LIKE ? OR author LIKE ? OR call_number LIKE ?)";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= "sss";
    }
}

if ($book_type !== 'all') {
    $where_conditions[] = "book_type = ?";
    $params[] = $book_type;
    $types .= "s";
}

$where_clause = "";
if (count($where_conditions) > 0) {
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
}

// Count total
$count_sql = "SELECT COUNT(*) as total FROM books $where_clause";
$stmt = $conn->prepare($count_sql);
if (count($params) > 0) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total_result = $stmt->get_result();
$total_rows = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

// Get books
$sql = "SELECT id, title, author, call_number, copies, book_type, year_published 
        FROM books $where_clause 
        ORDER BY title 
        LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$books = [];
while ($row = $result->fetch_assoc()) {
    $books[] = $row;
}

// Get type stats
$type_stats = [];
$type_sql = "SELECT book_type, COUNT(*) as count FROM books GROUP BY book_type";
$type_result = $conn->query($type_sql);
while ($row = $type_result->fetch_assoc()) {
    $type_stats[$row['book_type']] = $row['count'];
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'books' => $books,
    'total' => $total_rows,
    'total_pages' => $total_pages,
    'page' => $page,
    'start' => $offset + 1,
    'end' => min($offset + $limit, $total_rows),
    'type_stats' => $type_stats
]);

$conn->close();
?>