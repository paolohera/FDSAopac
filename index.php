<?php
require_once 'config/database.php';

// Get initial data for page load
$search = isset($_GET['search']) ? $_GET['search'] : '';
$search_field = isset($_GET['search_field']) ? $_GET['search_field'] : 'all';
$book_type = isset($_GET['book_type']) ? $_GET['book_type'] : 'all';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Build search query for initial load
$where_conditions = [];
$params = [];
$types = "";

if (!empty($search)) {
    $search_param = "%$search%";
    
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

// Count total records
$count_sql = "SELECT COUNT(*) as total FROM books $where_clause";
$stmt = $conn->prepare($count_sql);
if (count($params) > 0) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total_result = $stmt->get_result();
$total_rows = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

// Get books for current page
$sql = "SELECT * FROM books $where_clause ORDER BY title LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Get unique book types for stats
$type_stats = [];
$type_sql = "SELECT book_type, COUNT(*) as count FROM books GROUP BY book_type";
$type_result = $conn->query($type_sql);
while ($row = $type_result->fetch_assoc()) {
    $type_stats[$row['book_type']] = $row['count'];
}

$currentAdmin = getCurrentAdmin($conn);
$isLoggedIn = isLoggedIn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FDSA OPAC - Smart Search Catalog</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>

        @import url('https://fonts.cdnfonts.com/css/amoera');

        :root {
            --deep-space-blue: #003049ff;
            --flag-red: #d62828ff;
            --vivid-tangerine: #f77f00ff;
            --sunflower-gold: #fcbf49ff;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #f5f5f5;
            color: #1a1a2e;
            line-height: 1.5;
        }
        
        /* Toast Notification Container */
        .toast-container {
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .toast-notification {
            min-width: 300px;
            max-width: 400px;
            background: white;
            border-radius: 12px;
            padding: 1rem 1.25rem;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.02);
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideInRight 0.3s ease forwards;
            position: relative;
            overflow: hidden;
            border-left: 4px solid;
        }
        
        .toast-notification.success {
            border-left-color: #10b981;
        }
        
        .toast-notification.error {
            border-left-color: var(--flag-red);
        }
        
        .toast-notification.warning {
            border-left-color: var(--vivid-tangerine);
        }
        
        .toast-notification.info {
            border-left-color: var(--deep-space-blue);
        }
        
        .toast-icon {
            font-size: 1.25rem;
            flex-shrink: 0;
        }
        
        .toast-icon.success { color: #10b981; }
        .toast-icon.error { color: var(--flag-red); }
        .toast-icon.warning { color: var(--vivid-tangerine); }
        .toast-icon.info { color: var(--deep-space-blue); }
        
        .toast-content {
            flex: 1;
        }
        
        .toast-title {
            font-weight: 600;
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
            color: #1a1a2e;
        }
        
        .toast-message {
            font-size: 0.75rem;
            color: #64748b;
        }
        
        .toast-close {
            background: none;
            border: none;
            font-size: 1.125rem;
            cursor: pointer;
            color: #94a3b8;
            padding: 0;
            line-height: 1;
            transition: color 0.2s;
            flex-shrink: 0;
        }
        
        .toast-close:hover {
            color: #475569;
        }
        
        .toast-progress {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 3px;
            background: rgba(0,0,0,0.1);
            animation: progress 3s linear forwards;
        }
        
        .toast-notification.success .toast-progress {
            background: #10b981;
        }
        
        .toast-notification.error .toast-progress {
            background: var(--flag-red);
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
        
        @keyframes progress {
            from {
                width: 100%;
            }
            to {
                width: 0%;
            }
        }
        
        .toast-notification.hide {
            animation: slideOutRight 0.3s ease forwards;
        }
        
        /* Navbar - Deep Space Blue */
        .navbar {
            background: rgba(255,255,255,0.9) !important;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            font-weight: 400;
            font-size: 1.25rem;
            letter-spacing: -0.3px;
            color: white !important;
            font-family: 'amoera', sans-serif;
            color: rgba(30, 15, 66, 0.9) !important;
        }
        
        .navbar-brand i {
            color: white;
            margin-right: 8px;
        }
        
        .nav-link {
            font-weight: 500;
            color: rgba(30, 15, 66, 0.9) !important;
            transition: color 0.2s ease;
            text-decoration: none;
            cursor: pointer;
            text-transform: uppercase;
            font-size: 14px;
        }
        
        .nav-link:hover {
            color: var(--sunflower-gold) !important;
        }
        
        .login-btn {
            background: var(--deep-space-blue);
            color: white !important;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: all 0.2s;
        }
        
        .login-btn:hover {
            background: var(--vivid-tangerine);
            color: white !important;
        }
        
        .logout-btn {
            color: var(--flag-red) !important;
        }
        
        .logout-btn:hover {
            color: #b91c1c !important;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .log {
            height: 60px;
            width: 60px;
        }
        
        /* Hero - Deep Space Blue */
        .hero {
            background-image: url(./img/bg.jpg);
            background-repeat: no-repeat;
            background-size: cover;
            padding: 3rem 0;
            text-align: center;
        }
        
        .hero h1 {
            font-size: 2.2rem;
            font-weight: 700;
            letter-spacing: -0.5px;
            color: white;
            margin-bottom: 0.5rem;
            font-family: 'amoera', sans-serif;
        }
        
        .hero p {
            color: rgba(255,255,255,0.8);
            font-size: 1rem;
        }
        
        /* Search Section */
        .search-section {
            background: white;
            padding: 1.5rem 0;
            border-bottom: 1px solid #e2e8f0;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .search-card {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .search-options {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .search-option-btn {
            background: none;
            border: none;
            padding: 0.4rem 1rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 500;
            color: #64748b;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .search-option-btn.active {
            background: var(--deep-space-blue);
            color: white;
        }
        
        .search-option-btn:hover:not(.active) {
            background: #f1f5f9;
            color: var(--deep-space-blue);
        }
        
        .search-input-wrapper {
            position: relative;
         
        }
        
        .search-input-group {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }
        
        .search-input {
            flex: 1;
            padding-left: 15px;
            border: 2px solid #e2e8f0;
            border-radius: 14px;
            font-size: 0.95rem;
            font-family: 'Inter', sans-serif;
            transition: all 0.2s ease;
            width: 500px;
            height: 50px;
        }
        
        .search-input:focus {
            outline: none;
            border-color: var(--vivid-tangerine);
            box-shadow: 0 0 0 3px rgba(247,127,0,0.1);
        }
        
        .search-select {
            padding: 0.875rem 1.25rem;
            border: 2px solid #e2e8f0;
            border-radius: 14px;
            font-size: 0.95rem;
            font-family: 'Inter', sans-serif;
            background: white;
            cursor: pointer;
        }
        
        .search-btn {
            padding: 0.875rem 1.75rem;
            background: var(--flag-red);
            color: white;
            border: none;
            border-radius: 14px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .search-btn:hover {
            background: #b91c1c;
            transform: translateY(-1px);
        }
        
        /* Add Button */
        .add-book-btn {
            padding: 0.875rem 1.75rem;
            background: var(--vivid-tangerine);
            color: white;
            border: none;
            border-radius: 14px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .add-book-btn:hover {
            background: #e67300;
            transform: translateY(-1px);
        }
        
        /* Loading spinner */
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 2rem;
        }
        
        .loading-spinner .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid #e2e8f0;
            border-top-color: var(--vivid-tangerine);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 0 auto;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Stats Section */
        .stats-section {
            background: white;
            padding: 1rem 0;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .stats-grid {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .stats-info h2 {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--deep-space-blue);
            margin-bottom: 0.3rem;
        }
        
        .type-badges {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .type-badge {
            padding: 0.2rem 0.7rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 500;
        }
        
        .type-badge.FIL { background: #dbeafe; color: #1e40af; }
        .type-badge.SRB { background: #cffafe; color: #0e7490; }
        .type-badge.MSRB { background: #fef3c7; color: #b45309; }
        .type-badge.GRB { background: #fee2e2; color: #b91c1c; }
        .type-badge.SHB { background: #e0e7ff; color: #3730a3; }
        
        .stats-count {
            font-size: 0.8rem;
            color: #64748b;
        }
        
        /* Books Table */
        .books-container {
            padding: 1.5rem 0 2rem;
        }
        
        .books-table-wrapper {
            background: white;
            border-radius: 20px;
            overflow-x: auto;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
        }
        
        .books-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .books-table th {
            text-align: left;
            padding: 1rem 1.25rem;
            font-weight: 600;
            font-size: 0.8rem;
            color: white;
            background: var(--deep-space-blue);
            border-bottom: 1px solid #e2e8f0;
        }
        
        .books-table td {
            padding: 1rem 1.25rem;
            font-size: 0.875rem;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }
        
        .book-title {
            font-weight: 600;
            color: var(--deep-space-blue);
            margin-bottom: 0.25rem;
        }
        
        .book-year {
            font-size: 0.7rem;
            color: #94a3b8;
        }
        
        .book-author {
            color: #475569;
        }
        
        .call-number {
            font-family: 'Monaco', 'Courier New', monospace;
            font-size: 0.75rem;
            background: #f1f5f9;
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
            display: inline-block;
        }
        
        .book-type-badge {
            display: inline-block;
            padding: 0.25rem 0.7rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        
        .book-type-badge.FIL { background: #dbeafe; color: #1e40af; }
        .book-type-badge.SRB { background: #cffafe; color: #0e7490; }
        .book-type-badge.MSRB { background: #fef3c7; color: #b45309; }
        .book-type-badge.GRB { background: #fee2e2; color: #b91c1c; }
        .book-type-badge.SHB { background: #e0e7ff; color: #3730a3; }
        
        .copies-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 32px;
            padding: 0.25rem 0.5rem;
            background: #f1f5f9;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            color: #475569;
        }
        
        /* Action Buttons */
        .action-btn {
            padding: 0.3rem 0.7rem;
            border-radius: 8px;
            font-size: 0.7rem;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease;
            margin: 0 0.2rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            border: none;
        }
        
        .action-btn.edit {
            background: #f1f5f9;
            color: var(--deep-space-blue);
        }
        
        .action-btn.edit:hover {
            background: var(--sunflower-gold);
            color: var(--deep-space-blue);
        }
        
        .action-btn.delete {
            background: #fef2f2;
            color: var(--flag-red);
        }
        
        .action-btn.delete:hover {
            background: var(--flag-red);
            color: white;
        }
        
        /* Pagination */
        .pagination-container {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 1.5rem;
            flex-wrap: wrap;
        }
        
        .pagination-btn {
            padding: 0.4rem 0.9rem;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: 500;
            text-decoration: none;
            color: var(--deep-space-blue);
            background: white;
            border: 1px solid #e2e8f0;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        
        .pagination-btn:hover {
            background: var(--sunflower-gold);
            border-color: var(--sunflower-gold);
            color: var(--deep-space-blue);
        }
        
        .pagination-btn.active {
            background: var(--deep-space-blue);
            color: white;
            border-color: var(--deep-space-blue);
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
        }
        
        .empty-state i {
            font-size: 3rem;
            color: #cbd5e1;
            margin-bottom: 1rem;
        }
        
        .empty-state h5 {
            color: var(--deep-space-blue);
        }
        
        /* Footer */
        .footer {
            text-align: center;
            padding: 1.5rem;
            background: var(--deep-space-blue);
            color: rgba(255,255,255,0.7);
            font-size: 0.75rem;
            margin-top: 2rem;
        }
        
        /* Modal Styles - Two Column Layout */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .modal.show {
            display: flex;
            opacity: 1;
        }
        
        .modal-content {
            background: white;
            max-width: 800px;
            width: 90%;
            margin: auto;
            border-radius: 24px;
            transform: scale(0.9);
            transition: transform 0.3s ease;
            overflow: hidden;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal.show .modal-content {
            transform: scale(1);
        }
        
        .modal-header {
            padding: 1.5rem 1.5rem 0.5rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e2e8f0;
            position: sticky;
            top: 0;
            background: white;
            z-index: 10;
        }
        
        .modal-header h3 {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--deep-space-blue);
            margin: 0;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #94a3b8;
            transition: color 0.2s;
        }
        
        .modal-close:hover {
            color: var(--flag-red);
        }
        
        .modal-body {
            padding: 1.5rem;
        }
        
        .modal-footer {
            padding: 1rem 1.5rem 1.5rem 1.5rem;
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            border-top: 1px solid #e2e8f0;
            background: white;
            position: sticky;
            bottom: 0;
        }
        
        .modal-footer button {
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .modal-btn-cancel {
            background: #f1f5f9;
            border: none;
            color: #64748b;
        }
        
        .modal-btn-cancel:hover {
            background: #e2e8f0;
        }
        
        .modal-btn-submit {
            background: var(--flag-red);
            border: none;
            color: white;
        }
        
        .modal-btn-submit:hover {
            background: #b91c1c;
        }
        
        .modal-btn-edit {
            background: var(--vivid-tangerine);
            border: none;
            color: white;
        }
        
        .modal-btn-edit:hover {
            background: #e67300;
        }
        
        .modal-btn-delete {
            background: var(--flag-red);
            border: none;
            color: white;
        }
        
        .modal-btn-delete:hover {
            background: #b91c1c;
        }
        
        /* Two Column Form Layout */
        .form-two-column {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.25rem;
        }
        
        .full-width {
            grid-column: span 2;
        }
        
        .form-group {
            margin-bottom: 0;
        }
        
        .form-group label {
            display: block;
            font-weight: 500;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
            color: #475569;
        }
        
        .form-group label .optional {
            font-weight: 400;
            font-size: 0.7rem;
            color: #94a3b8;
            margin-left: 0.5rem;
        }
        
        .form-group label .required {
            color: var(--flag-red);
        }
        
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-family: 'Inter', sans-serif;
            transition: all 0.2s ease;
            font-size: 0.875rem;
        }
        
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: var(--vivid-tangerine);
            box-shadow: 0 0 0 3px rgba(247,127,0,0.1);
        }
        
        .current-book-info {
            background: #f8fafc;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            border-left: 3px solid var(--vivid-tangerine);
        }
        
        .current-book-info p {
            margin: 0.25rem 0;
            font-size: 0.875rem;
        }
        
        .current-book-info strong {
            color: var(--deep-space-blue);
        }
        
        .info-row {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .info-item {
            flex: 1;
            min-width: 150px;
        }
        
        /* Suggestions dropdown */
        .search-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
            margin-top: 8px;
            z-index: 1000;
            max-height: 300px;
            overflow-y: auto;
            display: none;
        }
        
        .suggestion-item {
            padding: 0.75rem 1.25rem;
            cursor: pointer;
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.2s ease;
        }
        
        .suggestion-item:hover {
            background: #fff8e7;
            border-left: 3px solid var(--vivid-tangerine);
        }
        
        .suggestion-title {
            font-weight: 500;
            font-size: 0.875rem;
            color: #1a1a2e;
        }
        
        .suggestion-call {
            font-size: 0.7rem;
            color: #94a3b8;
            font-family: monospace;
        }
        
        /* Responsive */
        @media (max-width: 640px) {
            .form-two-column {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .full-width {
                grid-column: span 1;
            }
            
            .modal-content {
                width: 95%;
            }
            
            .hero h1 { font-size: 1.5rem; }
            .search-input-group { flex-direction: column; }
            .stats-grid { flex-direction: column; text-align: center; }
            .type-badges { justify-content: center; }
            .books-table th, .books-table td { padding: 0.75rem; }
            .toast-notification {
                min-width: 280px;
                max-width: 350px;
            }
        }
    </style>
</head>
<body>

<!-- Toast Notification Container -->
<div id="toastContainer" class="toast-container"></div>

<!-- Navbar -->
<nav class="navbar">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <img src="./img/logo.png" class="log" alt=""> FDSA OPAC
        </a>
        <div class="d-flex align-items-center gap-3">
            <a class="nav-link d-inline-block" href="index.php">Catalog</a>
            <?php if ($isLoggedIn): ?>
    <div class="user-info">
        <span class="nav-link" style="cursor: default;">
            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['admin_fullname'] ?? $_SESSION['admin_username']); ?>
            <span class="admin-badge">Admin</span>
        </span>
        <button class="nav-link" style="background: none; border: none;" onclick="openAddModal()">
            <i class="bi bi-plus-circle"></i> Add Book
        </button>
        <a href="import_word.php" class="nav-link">
            <i class="bi bi-file-earmark-word"></i> Import Books
        </a>
        <button onclick="openLogoutModal()" class="nav-link logout-btn" style="background: none; border: none;">
            <i class="bi bi-box-arrow-right"></i> Logout
        </button>
    </div>
<?php else: ?>
    <a href="login.php" class="nav-link login-btn">
        <i class="bi bi-shield-lock"></i> Librarian Login
    </a>
<?php endif; ?>
        </div>
    </div>
</nav>

<!-- Hero -->
<div class="hero">
    <div class="container">
        <h1>Online Public Access Catalog</h1>
        <p>Start typing to search - instant results as you type</p>
    </div>
</div>

<!-- Search Section -->
<div class="search-section">
    <div class="container">
        <div class="search-card">
            <div class="search-options">
                <button type="button" class="search-option-btn active" data-field="all">All Fields</button>
                <button type="button" class="search-option-btn" data-field="title">Title</button>
                <button type="button" class="search-option-btn" data-field="author">Author</button>
                <button type="button" class="search-option-btn" data-field="call_number">Call Number</button>
            </div>
            <input type="hidden" id="search_field" value="all">
            <div class="search-input-group">
                <div class="search-input-wrapper" style="flex: 2; position: relative;">
                    <input type="text" id="smart_search" class="search-input" placeholder="Search Books" autocomplete="off">
                    <div id="suggestions" class="search-suggestions"></div>
                </div>
                <select id="book_type_filter" class="search-select">
                    <option value="all">All Collections</option>
                    <option value="FIL">Filipiniana</option>
                    <option value="SRB">Senior Reference</option>
                    <option value="MSRB">Main Senior Ref</option>
                    <option value="GRB">General Reference</option>
                    <option value="SHB">Senior High</option>
                </select>
                <button id="search_btn" class="search-btn">
                    <i class="bi bi-search"></i> Search
                </button>
                <?php if ($isLoggedIn): ?>
               
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Stats Section -->
<div id="stats_section" class="stats-section">
    <div class="container">
        <div class="stats-grid">
            <div class="stats-info">
                <h2 id="total_books"><?php echo number_format($total_rows); ?> books</h2>
                <div class="type-badges" id="type_badges">
                    <?php foreach ($type_stats as $type => $count): ?>
                    <span class="type-badge <?php echo $type; ?>"><?php echo $type; ?>: <?php echo $count; ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="stats-count" id="result_range">
                <?php echo $offset + 1; ?> - <?php echo min($offset + $limit, $total_rows); ?> of <?php echo $total_rows; ?>
            </div>
        </div>
    </div>
</div>

<!-- Books Table -->
<div class="books-container">
    <div class="container">
        <div id="loading" class="loading-spinner">
            <div class="spinner"></div>
            <p style="margin-top: 10px; color: #64748b;">Searching...</p>
        </div>
        
        <div id="results_container">
            <div class="books-table-wrapper">
                <table class="books-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Call Number</th>
                            <th>Type</th>
                            <th>Copies</th>
                            <?php if ($isLoggedIn): ?>
                            <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody id="books_tbody">
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div class="book-title"><?php echo htmlspecialchars($row['title']); ?></div>
                                    <?php if ($row['year_published']): ?>
                                        <div class="book-year">© <?php echo htmlspecialchars($row['year_published']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="book-author"><?php echo htmlspecialchars($row['author']) ?: '—'; ?></td>
                                <td><code class="call-number"><?php echo htmlspecialchars($row['call_number']); ?></code></td>
                                <td><span class="book-type-badge <?php echo $row['book_type']; ?>"><?php echo $row['book_type']; ?></span></td>
                                <td><span class="copies-count"><?php echo $row['copies']; ?></span></td>
                                <?php if ($isLoggedIn): ?>
                                <td>
                                    <button onclick="openEditModal(<?php echo $row['id']; ?>)" class="action-btn edit">
                                        <i class="bi bi-pencil"></i> Edit
                                    </button>
                                    <button onclick="openDeleteModal(<?php echo $row['id']; ?>)" class="action-btn delete">
                                        <i class="bi bi-trash"></i> Delete
                                    </button>
                                </tr>
                                <?php endif; ?>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?php echo $isLoggedIn ? '6' : '5'; ?>" class="empty-state">
                                    <i class="bi bi-search"></i>
                                    <h5>No books found</h5>
                                    <p class="text-muted">Try adjusting your search or add a new book</p>
                                    <?php if ($isLoggedIn): ?>
                                    <button onclick="openAddModal()" class="add-book-btn" style="margin-top: 1rem;">Add a Book</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div id="pagination_container" class="pagination-container">
                <?php if ($total_pages > 1): ?>
                    <?php if ($page > 1): ?>
                    <button onclick="goToPage(<?php echo $page-1; ?>)" class="pagination-btn">← Prev</button>
                    <?php endif; ?>
                    
                    <?php 
                    $start = max(1, $page - 2);
                    $end = min($total_pages, $page + 2);
                    for ($i = $start; $i <= $end; $i++): 
                    ?>
                    <button onclick="goToPage(<?php echo $i; ?>)" class="pagination-btn <?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></button>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                    <button onclick="goToPage(<?php echo $page+1; ?>)" class="pagination-btn">Next →</button>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ADD BOOK MODAL -->
<div id="addModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="bi bi-plus-circle" style="color: var(--vivid-tangerine);"></i> Add New Book</h3>
            <button class="modal-close" onclick="closeAddModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="addBookForm">
                <div class="form-two-column">
                    <div class="form-group full-width">
                        <label>Book Title <span class="required">*</span></label>
                        <input type="text" id="add_title" placeholder="Enter book title" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Author <span class="optional">(optional)</span></label>
                        <input type="text" id="add_author" placeholder="Author name">
                    </div>
                    
                    <div class="form-group">
                        <label>Call Number <span class="required">*</span></label>
                        <input type="text" id="add_call_number" placeholder="e.g., FIL 001.64 L319 2005" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Book Type</label>
                        <select id="add_book_type">
                            <option value="FIL">📚 Filipiniana (FIL)</option>
                            <option value="SRB">📖 Senior Reference (SRB)</option>
                            <option value="MSRB">📘 Main Senior Reference (MSRB)</option>
                            <option value="GRB">📕 General Reference (GRB)</option>
                            <option value="SHB">📗 Senior High Book (SHB)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Number of Copies</label>
                        <input type="number" id="add_copies" value="1" min="1">
                    </div>
                    
                    <div class="form-group">
                        <label>Year Published</label>
                        <input type="text" id="add_year" placeholder="YYYY">
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="modal-btn-cancel" onclick="closeAddModal()">Cancel</button>
            <button class="modal-btn-submit" onclick="submitAddBook()">Add Book</button>
        </div>
    </div>
</div>

<!-- EDIT BOOK MODAL -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="bi bi-pencil-square" style="color: var(--vivid-tangerine);"></i> Edit Book</h3>
            <button class="modal-close" onclick="closeEditModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="current-book-info" id="currentBookInfo"></div>
            <form id="editBookForm">
                <input type="hidden" id="edit_id">
                <div class="form-two-column">
                    <div class="form-group full-width">
                        <label>Book Title <span class="required">*</span></label>
                        <input type="text" id="edit_title" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Author <span class="optional">(optional)</span></label>
                        <input type="text" id="edit_author">
                    </div>
                    
                    <div class="form-group">
                        <label>Call Number <span class="required">*</span></label>
                        <input type="text" id="edit_call_number" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Book Type</label>
                        <select id="edit_book_type">
                            <option value="FIL">📚 Filipiniana (FIL)</option>
                            <option value="SRB">📖 Senior Reference (SRB)</option>
                            <option value="MSRB">📘 Main Senior Reference (MSRB)</option>
                            <option value="GRB">📕 General Reference (GRB)</option>
                            <option value="SHB">📗 Senior High Book (SHB)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Number of Copies</label>
                        <input type="number" id="edit_copies" min="1">
                    </div>
                    
                    <div class="form-group">
                        <label>Year Published</label>
                        <input type="text" id="edit_year" placeholder="YYYY">
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="modal-btn-cancel" onclick="closeEditModal()">Cancel</button>
            <button class="modal-btn-edit" onclick="submitEditBook()">Update Book</button>
        </div>
    </div>
</div>

<!-- DELETE BOOK MODAL -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="bi bi-trash" style="color: var(--flag-red);"></i> Delete Book</h3>
            <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p style="margin-bottom: 1rem;">Are you sure you want to delete this book? This action cannot be undone.</p>
            <div class="current-book-info" id="deleteBookInfo"></div>
            <input type="hidden" id="delete_id">
        </div>
        <div class="modal-footer">
            <button class="modal-btn-cancel" onclick="closeDeleteModal()">Cancel</button>
            <button class="modal-btn-delete" onclick="confirmDelete()">Delete Permanently</button>
        </div>
    </div>
</div>

<!-- LOGOUT CONFIRMATION MODAL -->
<div id="logoutModal" class="modal">
    <div class="modal-content" style="max-width: 400px;">
        <div class="modal-header">
            <h3><i class="bi bi-box-arrow-right" style="color: var(--flag-red);"></i> Confirm Logout</h3>
            <button class="modal-close" onclick="closeLogoutModal()">&times;</button>
        </div>
        <div class="modal-body" style="text-align: center;">
            <i class="bi bi-question-circle" style="font-size: 3rem; color: var(--vivid-tangerine); margin-bottom: 1rem; display: block;"></i>
            <p style="font-size: 1rem; margin-bottom: 0.5rem;">Are you sure you want to logout?</p>
            <p style="font-size: 0.875rem; color: #64748b;">You will need to login again to access admin features.</p>
        </div>
        <div class="modal-footer" style="justify-content: center;">
            <button class="modal-btn-cancel" onclick="closeLogoutModal()">Cancel</button>
            <button class="modal-btn-delete" onclick="confirmLogout()">Logout</button>
        </div>
    </div>
</div>

<!-- Footer -->
<div class="footer">
    <div class="container">
        <p>FDSA Online Public Access Catalog • Smart Search</p>
    </div>
</div>

<script>
    let searchTimeout;
    let currentSearchTerm = '';
    let currentSearchField = 'all';
    let currentBookType = 'all';
    let currentPage = 1;
    
    // DOM elements
    const searchInput = document.getElementById('smart_search');
    const suggestionsDiv = document.getElementById('suggestions');
    const loadingDiv = document.getElementById('loading');
    const resultsContainer = document.getElementById('results_container');
    const booksTbody = document.getElementById('books_tbody');
    const totalBooksSpan = document.getElementById('total_books');
    const resultRangeSpan = document.getElementById('result_range');
    const paginationContainer = document.getElementById('pagination_container');
    const toastContainer = document.getElementById('toastContainer');
    
    // Modal elements
    const addModal = document.getElementById('addModal');
    const editModal = document.getElementById('editModal');
    const deleteModal = document.getElementById('deleteModal');
    const logoutModal = document.getElementById('logoutModal');
    
    // Check if user is logged in (PHP variable passed to JS)
    const isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
    
    // Toast Notification System
    function showToast(title, message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `toast-notification ${type}`;
        
        let icon = '';
        if (type === 'success') icon = '✅';
        else if (type === 'error') icon = '❌';
        else if (type === 'warning') icon = '⚠️';
        else icon = 'ℹ️';
        
        toast.innerHTML = `
            <div class="toast-icon ${type}">${icon}</div>
            <div class="toast-content">
                <div class="toast-title">${title}</div>
                <div class="toast-message">${message}</div>
            </div>
            <button class="toast-close" onclick="this.parentElement.remove()">×</button>
            <div class="toast-progress"></div>
        `;
        
        toastContainer.appendChild(toast);
        
        setTimeout(() => {
            toast.classList.add('hide');
            setTimeout(() => {
                if (toast.parentElement) toast.remove();
            }, 300);
        }, 3000);
        
        const closeBtn = toast.querySelector('.toast-close');
        closeBtn.onclick = () => {
            toast.classList.add('hide');
            setTimeout(() => toast.remove(), 300);
        };
    }
    
    // Search option buttons
    document.querySelectorAll('.search-option-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.search-option-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentSearchField = btn.dataset.field;
            document.getElementById('search_field').value = currentSearchField;
            if (searchInput.value.trim().length > 0) {
                performSearch();
            }
        });
    });
    
    // Book type filter
    const bookTypeFilter = document.getElementById('book_type_filter');
    bookTypeFilter.addEventListener('change', () => {
        currentBookType = bookTypeFilter.value;
        if (searchInput.value.trim().length > 0 || currentBookType !== 'all') {
            performSearch();
        } else {
            window.location.href = 'index.php';
        }
    });
    
    document.getElementById('search_btn').addEventListener('click', () => {
        performSearch();
    });
    
    searchInput.addEventListener('input', (e) => {
        const query = e.target.value.trim();
        clearTimeout(searchTimeout);
        
        if (query.length > 0) {
            getSuggestions(query);
            searchTimeout = setTimeout(() => {
                performSearch();
            }, 500);
        } else if (query.length === 0 && currentBookType !== 'all') {
            performSearch();
        } else if (query.length === 0 && currentBookType === 'all') {
            window.location.href = 'index.php';
        } else {
            suggestionsDiv.style.display = 'none';
        }
    });
    
    function getSuggestions(query) {
        if (query.length < 2) {
            suggestionsDiv.style.display = 'none';
            return;
        }
        
        fetch(`search_suggestions.php?q=${encodeURIComponent(query)}&field=${currentSearchField}`)
            .then(response => response.json())
            .then(data => {
                if (data.suggestions && data.suggestions.length > 0) {
                    suggestionsDiv.innerHTML = data.suggestions.map(s => `
                        <div class="suggestion-item" onclick="selectSuggestion('${s.title.replace(/'/g, "\\'")}')">
                            <div class="suggestion-title">${escapeHtml(s.title)}</div>
                            <div class="suggestion-call">${escapeHtml(s.call_number)}</div>
                        </div>
                    `).join('');
                    suggestionsDiv.style.display = 'block';
                } else {
                    suggestionsDiv.style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                suggestionsDiv.style.display = 'none';
            });
    }
    
    function selectSuggestion(title) {
        searchInput.value = title;
        suggestionsDiv.style.display = 'none';
        performSearch();
    }
    
    function performSearch() {
        const query = searchInput.value.trim();
        currentSearchTerm = query;
        currentPage = 1;
        
        loadingDiv.style.display = 'block';
        resultsContainer.style.display = 'none';
        
        fetch(`search.php?q=${encodeURIComponent(query)}&field=${currentSearchField}&type=${currentBookType}&page=${currentPage}`)
            .then(response => response.json())
            .then(data => {
                renderResults(data);
                loadingDiv.style.display = 'none';
                resultsContainer.style.display = 'block';
            })
            .catch(error => {
                console.error('Error:', error);
                loadingDiv.style.display = 'none';
                resultsContainer.style.display = 'block';
                showToast('Search Error', 'Failed to search books', 'error');
            });
    }
    
    function renderResults(data) {
        totalBooksSpan.innerHTML = `${formatNumber(data.total)} books`;
        resultRangeSpan.innerHTML = `${data.start} - ${data.end} of ${formatNumber(data.total)}`;
        
        if (data.type_stats) {
            const badgesHtml = Object.entries(data.type_stats).map(([type, count]) => 
                `<span class="type-badge ${type}">${type}: ${count}</span>`
            ).join('');
            document.getElementById('type_badges').innerHTML = badgesHtml;
        }
        
        if (data.books && data.books.length > 0) {
            booksTbody.innerHTML = data.books.map(book => `
                <tr>
                    <td>
                        <div class="book-title">${escapeHtml(book.title)}</div>
                        ${book.year_published ? `<div class="book-year">© ${escapeHtml(book.year_published)}</div>` : ''}
                    </td>
                    <td class="book-author">${escapeHtml(book.author) || '—'}</td>
                    <td><code class="call-number">${escapeHtml(book.call_number)}</code></td>
                    <td><span class="book-type-badge ${book.book_type}">${book.book_type}</span></td>
                    <td><span class="copies-count">${book.copies}</span></td>
                    ${isLoggedIn ? `
                    <td>
                        <button onclick="openEditModal(${book.id})" class="action-btn edit">
                            <i class="bi bi-pencil"></i> Edit
                        </button>
                        <button onclick="openDeleteModal(${book.id})" class="action-btn delete">
                            <i class="bi bi-trash"></i> Delete
                        </button>
                    </td>
                    ` : ''}
                </tr>
            `).join('');
        } else {
            booksTbody.innerHTML = `
                <tr>
                    <td colspan="${isLoggedIn ? '6' : '5'}" class="empty-state">
                        <i class="bi bi-search"></i>
                        <h5>No books found</h5>
                        <p class="text-muted">Try different search terms or add a new book</p>
                        ${isLoggedIn ? '<button onclick="openAddModal()" class="add-book-btn" style="margin-top: 1rem;">Add a Book</button>' : ''}
                    </td>
                </tr>
            `;
        }
        
        if (data.total_pages > 1) {
            let paginationHtml = '';
            if (data.page > 1) {
                paginationHtml += `<button onclick="goToPage(${data.page - 1})" class="pagination-btn">← Prev</button>`;
            }
            const start = Math.max(1, data.page - 2);
            const end = Math.min(data.total_pages, data.page + 2);
            for (let i = start; i <= end; i++) {
                paginationHtml += `<button onclick="goToPage(${i})" class="pagination-btn ${i === data.page ? 'active' : ''}">${i}</button>`;
            }
            if (data.page < data.total_pages) {
                paginationHtml += `<button onclick="goToPage(${data.page + 1})" class="pagination-btn">Next →</button>`;
            }
            paginationContainer.innerHTML = paginationHtml;
        } else {
            paginationContainer.innerHTML = '';
        }
    }
    
    function goToPage(page) {
        currentPage = page;
        const query = searchInput.value.trim();
        loadingDiv.style.display = 'block';
        resultsContainer.style.display = 'none';
        fetch(`search.php?q=${encodeURIComponent(query)}&field=${currentSearchField}&type=${currentBookType}&page=${page}`)
            .then(response => response.json())
            .then(data => {
                renderResults(data);
                loadingDiv.style.display = 'none';
                resultsContainer.style.display = 'block';
            });
    }
    
    // ============ LOGOUT MODAL FUNCTIONS ============
    function openLogoutModal() {
        if (logoutModal) {
            logoutModal.classList.add('show');
        }
    }
    
    function closeLogoutModal() {
        if (logoutModal) {
            logoutModal.classList.remove('show');
        }
    }
    
    function confirmLogout() {
        window.location.href = 'logout.php';
    }
    
    // ============ ADD BOOK MODAL FUNCTIONS ============
    function openAddModal() {
        if (!isLoggedIn) {
            showToast('Access Denied', 'Please login as librarian to add books', 'warning');
            window.location.href = 'login.php';
            return;
        }
        addModal.classList.add('show');
        document.getElementById('add_title').value = '';
        document.getElementById('add_author').value = '';
        document.getElementById('add_call_number').value = '';
        document.getElementById('add_copies').value = '1';
        document.getElementById('add_year').value = '';
        document.getElementById('add_book_type').value = 'FIL';
    }
    
    function closeAddModal() {
        addModal.classList.remove('show');
    }
    
    function submitAddBook() {
        const title = document.getElementById('add_title').value.trim();
        const author = document.getElementById('add_author').value.trim();
        const callNumber = document.getElementById('add_call_number').value.trim();
        const copies = document.getElementById('add_copies').value;
        const bookType = document.getElementById('add_book_type').value;
        const year = document.getElementById('add_year').value.trim();
        
        if (!title) {
            showToast('Missing Field', 'Please enter the book title', 'error');
            return;
        }
        if (!callNumber) {
            showToast('Missing Field', 'Please enter the call number', 'error');
            return;
        }
        
        fetch('ajax_add_book.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `title=${encodeURIComponent(title)}&author=${encodeURIComponent(author)}&call_number=${encodeURIComponent(callNumber)}&copies=${copies}&book_type=${bookType}&year_published=${year}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Success!', 'Book added successfully', 'success');
                closeAddModal();
                performSearch();
            } else {
                showToast('Error', data.message || 'Error adding book', 'error');
            }
        })
        .catch(error => {
            showToast('Error', 'Failed to add book', 'error');
        });
    }
    
    // ============ EDIT BOOK MODAL FUNCTIONS ============
    function openEditModal(id) {
        if (!isLoggedIn) {
            showToast('Access Denied', 'Please login as librarian to edit books', 'warning');
            window.location.href = 'login.php';
            return;
        }
        fetch(`ajax_get_book.php?id=${id}`)
            .then(response => response.json())
            .then(book => {
                document.getElementById('edit_id').value = book.id;
                document.getElementById('edit_title').value = book.title;
                document.getElementById('edit_author').value = book.author || '';
                document.getElementById('edit_call_number').value = book.call_number;
                document.getElementById('edit_copies').value = book.copies;
                document.getElementById('edit_book_type').value = book.book_type;
                document.getElementById('edit_year').value = book.year_published || '';
                document.getElementById('currentBookInfo').innerHTML = `
                    <div class="info-row">
                        <div class="info-item"><strong>📖 Title:</strong> ${escapeHtml(book.title)}</div>
                        <div class="info-item"><strong>✍️ Author:</strong> ${escapeHtml(book.author) || 'Unknown'}</div>
                        <div class="info-item"><strong>🔢 Call Number:</strong> ${escapeHtml(book.call_number)}</div>
                    </div>
                `;
                editModal.classList.add('show');
            });
    }
    
    function closeEditModal() {
        editModal.classList.remove('show');
    }
    
    function submitEditBook() {
        const id = document.getElementById('edit_id').value;
        const title = document.getElementById('edit_title').value.trim();
        const author = document.getElementById('edit_author').value.trim();
        const callNumber = document.getElementById('edit_call_number').value.trim();
        const copies = document.getElementById('edit_copies').value;
        const bookType = document.getElementById('edit_book_type').value;
        const year = document.getElementById('edit_year').value.trim();
        
        if (!title || !callNumber) {
            showToast('Missing Field', 'Please fill in required fields', 'error');
            return;
        }
        
        fetch('ajax_edit_book.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `id=${id}&title=${encodeURIComponent(title)}&author=${encodeURIComponent(author)}&call_number=${encodeURIComponent(callNumber)}&copies=${copies}&book_type=${bookType}&year_published=${year}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Success!', 'Book updated successfully', 'success');
                closeEditModal();
                performSearch();
            } else {
                showToast('Error', data.message || 'Error updating book', 'error');
            }
        });
    }
    
    // ============ DELETE BOOK MODAL FUNCTIONS ============
    function openDeleteModal(id) {
        if (!isLoggedIn) {
            showToast('Access Denied', 'Please login as librarian to delete books', 'warning');
            window.location.href = 'login.php';
            return;
        }
        fetch(`ajax_get_book.php?id=${id}`)
            .then(response => response.json())
            .then(book => {
                document.getElementById('delete_id').value = book.id;
                document.getElementById('deleteBookInfo').innerHTML = `
                    <p><strong>📖 ${escapeHtml(book.title)}</strong></p>
                    <p>✍️ ${escapeHtml(book.author) || 'Unknown Author'}</p>
                    <p>🔢 ${escapeHtml(book.call_number)}</p>
                `;
                deleteModal.classList.add('show');
            });
    }
    
    function closeDeleteModal() {
        deleteModal.classList.remove('show');
    }
    
    function confirmDelete() {
        const id = document.getElementById('delete_id').value;
        
        fetch('ajax_delete_book.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `id=${id}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Deleted', 'Book has been removed', 'success');
                closeDeleteModal();
                performSearch();
            } else {
                showToast('Error', 'Failed to delete book', 'error');
            }
        });
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }
    
    // Close modals when clicking outside
    window.onclick = function(event) {
        if (event.target === addModal) closeAddModal();
        if (event.target === editModal) closeEditModal();
        if (event.target === deleteModal) closeDeleteModal();
        if (event.target === logoutModal) closeLogoutModal();
    }
    
    // Enter key to search
    searchInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            suggestionsDiv.style.display = 'none';
            performSearch();
        }
    });
</script>

</body>
</html>

<?php $conn->close(); ?>