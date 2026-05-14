<?php
require_once 'config/database.php';

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $author = !empty($_POST['author']) ? $_POST['author'] : ''; // Allow empty author
    $call_number = $_POST['call_number'];
    $copies = $_POST['copies'];
    $book_type = $_POST['book_type'];
    $year_published = $_POST['year_published'];
    
    $sql = "INSERT INTO books (title, author, call_number, copies, book_type, year_published) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssiss", $title, $author, $call_number, $copies, $book_type, $year_published);
    
    if ($stmt->execute()) {
        $message = "Book added successfully!";
        $message_type = "success";
        // Clear form after successful submission
        echo '<script>setTimeout(function(){ window.location.href = "add_book.php?success=1"; }, 1000);</script>';
    } else {
        $message = "Error: " . $conn->error;
        $message_type = "danger";
    }
    $stmt->close();
}

// Check if just redirected from successful add
if (isset($_GET['success'])) {
    $message = "Book added successfully!";
    $message_type = "success";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Book - FDSA OPAC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --deep-space-blue: #003049ff;
            --flag-red: #d62828ff;
            --vivid-tangerine: #f77f00ff;
            --sunflower-gold: #fcbf49ff;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f5f5f5; }
        
        /* Navbar - Deep Space Blue */
        .navbar {
            background: var(--deep-space-blue) !important;
            padding: 1rem 0;
        }
        
        .navbar-brand {
            font-weight: 600;
            font-size: 1.25rem;
            color: white !important;
            text-decoration: none;
        }
        
        .navbar-brand i { color: white; margin-right: 8px; }
        
        .back-link {
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            transition: color 0.2s;
        }
        
        .back-link:hover {
            color: var(--sunflower-gold);
        }
        
        .form-container {
            max-width: 680px;
            margin: 2rem auto;
        }
        
        .form-card {
            background: white;
            border-radius: 24px;
            padding: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border-top: 4px solid var(--vivid-tangerine);
        }
        
        .form-card h2 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: var(--deep-space-blue);
        }
        
        .form-label {
            font-weight: 500;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
            color: #475569;
        }
        
        .form-label .optional {
            font-weight: 400;
            font-size: 0.7rem;
            color: #94a3b8;
            margin-left: 0.5rem;
        }
        
        .form-label .required {
            color: var(--flag-red);
            font-size: 0.7rem;
            margin-left: 0.25rem;
        }
        
        .form-control, .form-select {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 0.75rem 1rem;
            font-family: 'Inter', sans-serif;
            transition: all 0.2s ease;
        }
        
        .form-control:focus, .form-select:focus {
            outline: none;
            border-color: var(--vivid-tangerine);
            box-shadow: 0 0 0 3px rgba(247,127,0,0.1);
        }
        
        /* Submit Button - Flag Red */
        .btn-submit {
            background: var(--flag-red);
            border: none;
            border-radius: 12px;
            padding: 0.875rem;
            font-weight: 600;
            color: white;
            transition: all 0.2s ease;
        }
        
        .btn-submit:hover {
            background: #b91c1c;
            transform: translateY(-1px);
        }
        
        /* Cancel Button - Deep Space Blue outline */
        .btn-cancel {
            background: transparent;
            border: 2px solid var(--deep-space-blue);
            border-radius: 12px;
            padding: 0.875rem;
            font-weight: 500;
            color: var(--deep-space-blue);
            transition: all 0.2s ease;
        }
        
        .btn-cancel:hover {
            background: var(--deep-space-blue);
            color: white;
        }
        
        .call-number-hint {
            font-size: 0.75rem;
            color: var(--vivid-tangerine);
            margin-top: 0.5rem;
        }
        
        .alert {
            border-radius: 12px;
            border: none;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
        }
        
        /* Success animation */
        @keyframes fadeOut {
            0% { opacity: 1; }
            70% { opacity: 1; }
            100% { opacity: 0; display: none; }
        }
        
        .alert-success {
            animation: fadeOut 3s ease forwards;
        }
    </style>
</head>
<body>

<!-- Navbar - Deep Space Blue -->
<nav class="navbar">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="bi bi-book-half"></i> FDSA OPAC
        </a>
        <a href="index.php" class="back-link">← Back to Catalog</a>
    </div>
</nav>

<div class="form-container">
    <div class="form-card">
        <h2><i class="bi bi-plus-circle" style="color: var(--vivid-tangerine);"></i> Add New Book</h2>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> mb-4">
                <i class="bi bi-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" id="addBookForm">
            <div class="mb-4">
                <label class="form-label">
                    Book Title 
                    <span class="required">*</span>
                </label>
                <input type="text" name="title" class="form-control" required>
            </div>
            
            <div class="mb-4">
                <label class="form-label">
                    Author 
                    <span class="optional">(optional)</span>
                </label>
                <input type="text" name="author" class="form-control" placeholder="Leave empty if unknown">
                <div class="call-number-hint" style="color: #94a3b8;">
                    <i class="bi bi-person"></i> Author name is optional - you can add it later
                </div>
            </div>
            
            <div class="mb-4">
                <label class="form-label">
                    Call Number 
                    <span class="required">*</span>
                </label>
                <input type="text" name="call_number" class="form-control" placeholder="e.g., FIL 001.64 L319 2005" required>
                <div class="call-number-hint">
                    <i class="bi bi-info-circle"></i> Example: FIL 004 B928 2007
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-4">
                    <label class="form-label">Book Type</label>
                    <select name="book_type" class="form-select">
                        <option value="FIL">📚 Filipiniana (FIL)</option>
                        <option value="SRB">📖 Senior Reference (SRB)</option>
                        <option value="MSRB">📘 Main Senior Reference (MSRB)</option>
                        <option value="GRB">📕 General Reference (GRB)</option>
                        <option value="SHB">📗 Senior High Book (SHB)</option>
                    </select>
                </div>
                <div class="col-md-3 mb-4">
                    <label class="form-label">Copies</label>
                    <input type="number" name="copies" class="form-control" value="1" min="1">
                </div>
                <div class="col-md-3 mb-4">
                    <label class="form-label">Year</label>
                    <input type="text" name="year_published" class="form-control" placeholder="2024">
                </div>
            </div>
            
            <button type="submit" class="btn btn-submit w-100 mb-2">
                <i class="bi bi-save"></i> Add Book
            </button>
            <a href="index.php" class="btn btn-cancel w-100 text-center">Cancel</a>
        </form>
    </div>
</div>

<script>
    // Optional: Clear form after successful submission via AJAX
    // But the current PHP redirect works fine
    
    // Add client-side validation
    document.getElementById('addBookForm').addEventListener('submit', function(e) {
        const title = document.querySelector('input[name="title"]').value.trim();
        const callNumber = document.querySelector('input[name="call_number"]').value.trim();
        
        if (!title) {
            e.preventDefault();
            alert('Please enter the book title');
            return false;
        }
        
        if (!callNumber) {
            e.preventDefault();
            alert('Please enter the call number');
            return false;
        }
        
        return true;
    });
</script>

</body>
</html>