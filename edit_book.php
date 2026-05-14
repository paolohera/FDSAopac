<?php
require_once 'config/database.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = '';
$message_type = '';

$sql = "SELECT * FROM books WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$book = $result->fetch_assoc();

if (!$book) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $author = $_POST['author'];
    $call_number = $_POST['call_number'];
    $copies = $_POST['copies'];
    $book_type = $_POST['book_type'];
    $year_published = $_POST['year_published'];
    
    $sql = "UPDATE books SET title=?, author=?, call_number=?, copies=?, book_type=?, year_published=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssissi", $title, $author, $call_number, $copies, $book_type, $year_published, $id);
    
    if ($stmt->execute()) {
        $message = "Book updated successfully!";
        $message_type = "success";
        $stmt = $conn->prepare("SELECT * FROM books WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $book = $stmt->get_result()->fetch_assoc();
    } else {
        $message = "Error: " . $conn->error;
        $message_type = "danger";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Book - FDSA OPAC</title>
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
            border-top: 4px solid var(--sunflower-gold);
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
        
        .form-control, .form-select {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 0.75rem 1rem;
            font-family: 'Inter', sans-serif;
        }
        
        .form-control:focus, .form-select:focus {
            outline: none;
            border-color: var(--vivid-tangerine);
            box-shadow: 0 0 0 3px rgba(247,127,0,0.1);
        }
        
        /* Update Button - Vivid Tangerine */
        .btn-update {
            background: var(--vivid-tangerine);
            border: none;
            border-radius: 12px;
            padding: 0.875rem;
            font-weight: 600;
            color: white;
            transition: all 0.2s ease;
        }
        
        .btn-update:hover {
            background: #e67300;
            transform: translateY(-1px);
        }
        
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
        
        .alert {
            border-radius: 12px;
            border: none;
        }
    </style>
</head>
<body>

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
        <h2><i class="bi bi-pencil-square" style="color: var(--vivid-tangerine);"></i> Edit Book</h2>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> mb-4">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="mb-4">
                <label class="form-label">Book Title</label>
                <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($book['title']); ?>" required>
            </div>
            
            <div class="mb-4">
                <label class="form-label">Author</label>
                <input type="text" name="author" class="form-control" value="<?php echo htmlspecialchars($book['author']); ?>" required>
            </div>
            
            <div class="mb-4">
                <label class="form-label">Call Number</label>
                <input type="text" name="call_number" class="form-control" value="<?php echo htmlspecialchars($book['call_number']); ?>" required>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-4">
                    <label class="form-label">Book Type</label>
                    <select name="book_type" class="form-select">
                        <option value="FIL" <?php echo $book['book_type'] == 'FIL' ? 'selected' : ''; ?>>Filipiniana (FIL)</option>
                        <option value="SRB" <?php echo $book['book_type'] == 'SRB' ? 'selected' : ''; ?>>Senior Reference (SRB)</option>
                        <option value="MSRB" <?php echo $book['book_type'] == 'MSRB' ? 'selected' : ''; ?>>Main Senior Reference (MSRB)</option>
                        <option value="GRB" <?php echo $book['book_type'] == 'GRB' ? 'selected' : ''; ?>>General Reference (GRB)</option>
                        <option value="SHB" <?php echo $book['book_type'] == 'SHB' ? 'selected' : ''; ?>>Senior High Book (SHB)</option>
                    </select>
                </div>
                <div class="col-md-3 mb-4">
                    <label class="form-label">Copies</label>
                    <input type="number" name="copies" class="form-control" value="<?php echo $book['copies']; ?>" min="1">
                </div>
                <div class="col-md-3 mb-4">
                    <label class="form-label">Year</label>
                    <input type="text" name="year_published" class="form-control" value="<?php echo htmlspecialchars($book['year_published']); ?>">
                </div>
            </div>
            
            <button type="submit" class="btn btn-update w-100 mb-2">
                <i class="bi bi-save"></i> Update Book
            </button>
            <a href="index.php" class="btn btn-cancel w-100 text-center">Cancel</a>
        </form>
    </div>
</div>

</body>
</html>