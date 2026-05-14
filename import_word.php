<?php
require_once 'config/database.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$message = '';
$message_type = '';
$imported = 0;
$skipped = 0;
$errors = 0;
$preview_data = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Method 1: Upload Text/CSV File
    if (isset($_FILES['text_file']) && $_FILES['text_file']['error'] == 0) {
        $file = $_FILES['text_file'];
        $file_tmp = $file['tmp_name'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        $content = file_get_contents($file_tmp);
        
        // Parse CSV or text file
        $lines = explode("\n", $content);
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // Try CSV format (comma separated)
            if ($file_ext == 'csv') {
                $parts = str_getcsv($line);
            } else {
                // Try pipe separated or comma separated
                if (strpos($line, '|') !== false) {
                    $parts = explode('|', $line);
                } else {
                    $parts = explode(',', $line);
                }
            }
            
            if (count($parts) >= 3) {
                $title = trim($parts[0]);
                $author = isset($parts[1]) ? trim($parts[1]) : '';
                $call_number = trim($parts[2]);
                $copies = isset($parts[3]) ? (int)trim($parts[3]) : 1;
                
                if (!empty($title) && !empty($call_number)) {
                    processBook($title, $author, $call_number, $copies);
                }
            }
        }
        
        $message = "Import completed!";
        $message_type = "success";
    }
    
    // Method 2: Copy-Paste Text
    elseif (isset($_POST['text_data']) && !empty($_POST['text_data'])) {
        $text_data = $_POST['text_data'];
        $lines = explode("\n", $text_data);
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // Try different separators
            if (strpos($line, '|') !== false) {
                $parts = explode('|', $line);
            } elseif (strpos($line, ',') !== false) {
                $parts = explode(',', $line);
            } elseif (strpos($line, "\t") !== false) {
                $parts = explode("\t", $line);
            } else {
                continue;
            }
            
            if (count($parts) >= 3) {
                $title = trim($parts[0]);
                $author = isset($parts[1]) ? trim($parts[1]) : '';
                $call_number = trim($parts[2]);
                $copies = isset($parts[3]) ? (int)trim($parts[3]) : 1;
                
                if (!empty($title) && !empty($call_number)) {
                    processBook($title, $author, $call_number, $copies);
                }
            }
        }
        
        $message = "Import completed from text!";
        $message_type = "success";
    }
    
    // Method 3: Excel/CSV Import
    elseif (isset($_POST['excel_data']) && !empty($_POST['excel_data'])) {
        $rows = json_decode($_POST['excel_data'], true);
        
        foreach ($rows as $row) {
            $title = trim($row['title'] ?? '');
            $author = trim($row['author'] ?? '');
            $call_number = trim($row['call_number'] ?? '');
            $copies = (int)($row['copies'] ?? 1);
            
            if (!empty($title) && !empty($call_number)) {
                processBook($title, $author, $call_number, $copies);
            }
        }
        
        $message = "Import completed from Excel data!";
        $message_type = "success";
    }
}

function processBook($title, $author, $call_number, $copies, &$imported = null, &$skipped = null, &$errors = null) {
    global $conn, $imported, $skipped, $errors;
    
    // Extract year from call number
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
    elseif (preg_match('/^F\s/', $call_number)) $book_type = 'F';
    
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
        return false;
    }
    
    // Insert book
    $sql = "INSERT INTO books (title, author, call_number, copies, book_type, year_published) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssiss", $title, $author, $call_number, $copies, $book_type, $year_published);
    
    if ($stmt->execute()) {
        $imported++;
        return true;
    } else {
        $errors++;
        return false;
    }
}

// Get statistics
$total_books = $conn->query("SELECT COUNT(*) as count FROM books")->fetch_assoc()['count'];
$type_stats = [];
$type_result = $conn->query("SELECT book_type, COUNT(*) as count FROM books GROUP BY book_type");
while ($row = $type_result->fetch_assoc()) {
    $type_stats[$row['book_type']] = $row['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Books - FDSA OPAC</title>
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
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f5f5f5; }
        
        .navbar {
            background: rgba(255,255,255,0.98) !important;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            font-weight: 400;
            font-size: 1.25rem;
            font-family: 'amoera', sans-serif;
            color: rgba(30, 15, 66, 0.9) !important;
        }
        
        .log { height: 60px; width: 60px; }
        
        .page-header {
            background: linear-gradient(135deg, var(--deep-space-blue) 0%, #1a4a6e 100%);
            padding: 2rem 0;
            margin-top: 76px;
            color: white;
        }
        
        .container-custom {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .card {
            background: white;
            border-radius: 20px;
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            overflow: hidden;
        }
        
        .card-header {
            background: var(--deep-space-blue);
            color: white;
            padding: 1rem 1.5rem;
            font-weight: 600;
        }
        
        .upload-area {
            border: 2px dashed #cbd5e1;
            border-radius: 16px;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
            background: #f8fafc;
        }
        
        .upload-area:hover {
            border-color: var(--vivid-tangerine);
            background: #fef3e8;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 1rem;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--deep-space-blue);
        }
        
        .stat-label {
            font-size: 0.75rem;
            color: #64748b;
        }
        
        .btn-import {
            background: var(--flag-red);
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.2s;
        }
        
        .btn-import:hover {
            background: #b91c1c;
            transform: translateY(-1px);
        }
        
        .format-example {
            background: #f1f5f9;
            padding: 1rem;
            border-radius: 12px;
            font-size: 0.8rem;
            margin-top: 1rem;
        }
        
        .format-example code {
            background: #e2e8f0;
            padding: 0.2rem 0.4rem;
            border-radius: 4px;
            font-family: monospace;
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
        
        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .container-custom { padding: 1rem; }
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <img src="./img/logo.png" class="log" alt=""> FDSA OPAC
        </a>
        <div>
            <a class="nav-link d-inline-block me-3" href="index.php">Catalog</a>
            <a class="nav-link d-inline-block" href="add_book.php">Add Book</a>
        </div>
    </div>
</nav>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1><i class="bi bi-file-earmark-spreadsheet"></i> Import Books</h1>
        <p>Upload CSV/Text files or paste data to bulk import books</p>
    </div>
</div>

<div class="container-custom">
    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?php echo number_format($total_books); ?></div>
            <div class="stat-label">Total Books</div>
        </div>
        <?php foreach ($type_stats as $type => $count): ?>
        <div class="stat-card">
            <div class="stat-number"><?php echo $count; ?></div>
            <div class="stat-label"><?php echo $type; ?> Books</div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type == 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
        <i class="bi bi-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
        <?php echo $message; ?>
        <?php if ($imported > 0): ?> ✅ <?php echo $imported; ?> imported<?php endif; ?>
        <?php if ($skipped > 0): ?> ⏭️ <?php echo $skipped; ?> skipped<?php endif; ?>
        <?php if ($errors > 0): ?> ❌ <?php echo $errors; ?> errors<?php endif; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Method 1: Upload CSV/Text File -->
    <div class="card">
        <div class="card-header">
            <i class="bi bi-file-earmark-spreadsheet"></i> Method 1: Upload CSV or Text File
        </div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <div class="upload-area" onclick="document.getElementById('fileInput').click()">
                    <i class="bi bi-cloud-upload" style="font-size: 3rem; color: var(--vivid-tangerine);"></i>
                    <p class="mt-2">Click to upload CSV or text file</p>
                    <p class="text-muted small">Supports .csv, .txt files</p>
                    <input type="file" name="text_file" id="fileInput" class="d-none" accept=".csv,.txt">
                </div>
                <div class="text-center mt-4">
                    <button type="submit" class="btn-import">
                        <i class="bi bi-upload"></i> Import Books
                    </button>
                </div>
            </form>
            
            <div class="format-example">
                <strong><i class="bi bi-info-circle"></i> File Format (CSV or TXT):</strong><br>
                <code>Title, Author, Call Number, Copies</code><br>
                <strong>Example:</strong><br>
                <code>INTRODUCTION TO COMPUTER CONCEPTS, LA PUTT, JUNY PILAPIL, FIL. 001.64 L319 2005, 1</code><br>
                <code>AN INTRODUCTION TO COMPUTER FUNDAMENTALS, BUENDIA, MARLYN P., FIL 004 B928 2007, 1</code>
            </div>
        </div>
    </div>

    <!-- Method 2: Copy-Paste Text -->
    <div class="card">
        <div class="card-header">
            <i class="bi bi-file-text"></i> Method 2: Copy-Paste Text
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Paste your book data here</label>
                    <textarea name="text_data" class="form-control" rows="10" placeholder="Paste books in this format:
Title | Author | Call Number | Copies
Example:
INTRODUCTION TO COMPUTER CONCEPTS|LA PUTT, JUNY PILAPIL|FIL. 001.64 L319 2005|1
AN INTRODUCTION TO COMPUTER FUNDAMENTALS|BUENDIA, MARLYN P.|FIL 004 B928 2007|1"></textarea>
                    <div class="form-text mt-2">
                        <strong>Format:</strong> Each line should contain: <code>Title | Author | Call Number | Copies</code><br>
                        Use the pipe symbol (|) or comma (,) to separate fields.
                    </div>
                </div>
                <div class="text-center">
                    <button type="submit" class="btn-import">
                        <i class="bi bi-upload"></i> Import from Text
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Method 3: Manual Entry Grid -->
    <div class="card">
        <div class="card-header">
            <i class="bi bi-grid-3x3-gap-fill"></i> Method 3: Bulk Entry Grid
        </div>
        <div class="card-body">
            <div id="bulkEntries">
                <div class="row mb-2 entry-row">
                    <div class="col-md-5"><input type="text" class="form-control form-control-sm" placeholder="Title" id="title_0"></div>
                    <div class="col-md-3"><input type="text" class="form-control form-control-sm" placeholder="Author" id="author_0"></div>
                    <div class="col-md-3"><input type="text" class="form-control form-control-sm" placeholder="Call Number" id="call_0"></div>
                    <div class="col-md-1"><input type="number" class="form-control form-control-sm" placeholder="Copies" id="copies_0" value="1"></div>
                </div>
            </div>
            <div class="text-center mt-3">
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="addEntryRow()">
                    <i class="bi bi-plus-lg"></i> Add Another Book
                </button>
                <button type="button" class="btn-import ms-2" onclick="submitBulkEntries()">
                    <i class="bi bi-save"></i> Save All Books
                </button>
            </div>
        </div>
    </div>

    <!-- Method 4: Download Templates -->
    <div class="card">
        <div class="card-header">
            <i class="bi bi-download"></i> Method 4: Download Templates
        </div>
        <div class="card-body">
            <p>Download a template file to help you format your book data correctly.</p>
            <div class="row">
                <div class="col-md-6 mb-2">
                    <a href="download_template.php?type=csv" class="btn btn-outline-primary w-100">
                        <i class="bi bi-file-spreadsheet"></i> CSV Template
                    </a>
                </div>
                <div class="col-md-6 mb-2">
                    <a href="download_template.php?type=txt" class="btn btn-outline-primary w-100">
                        <i class="bi bi-file-text"></i> Text Template
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="text-center mt-4">
        <a href="index.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Catalog
        </a>
    </div>
</div>

<script>
    let entryCount = 1;
    
    function addEntryRow() {
        const container = document.getElementById('bulkEntries');
        const newRow = document.createElement('div');
        newRow.className = 'row mb-2 entry-row';
        newRow.innerHTML = `
            <div class="col-md-5"><input type="text" class="form-control form-control-sm" placeholder="Title" id="title_${entryCount}"></div>
            <div class="col-md-3"><input type="text" class="form-control form-control-sm" placeholder="Author" id="author_${entryCount}"></div>
            <div class="col-md-3"><input type="text" class="form-control form-control-sm" placeholder="Call Number" id="call_${entryCount}"></div>
            <div class="col-md-1"><input type="number" class="form-control form-control-sm" placeholder="Copies" id="copies_${entryCount}" value="1"></div>
        `;
        container.appendChild(newRow);
        entryCount++;
    }
    
    function submitBulkEntries() {
        const books = [];
        
        for (let i = 0; i < entryCount; i++) {
            const title = document.getElementById(`title_${i}`);
            const author = document.getElementById(`author_${i}`);
            const callNumber = document.getElementById(`call_${i}`);
            const copies = document.getElementById(`copies_${i}`);
            
            if (title && callNumber && title.value.trim() && callNumber.value.trim()) {
                books.push({
                    title: title.value.trim(),
                    author: author ? author.value.trim() : '',
                    call_number: callNumber.value.trim(),
                    copies: copies ? parseInt(copies.value) : 1
                });
            }
        }
        
        if (books.length === 0) {
            alert('Please add at least one book to import');
            return;
        }
        
        // Submit via AJAX
        fetch('ajax_bulk_import.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ books: books })
        })
        .then(response => response.json())
        .then(data => {
            alert(`Import complete!\nImported: ${data.imported}\nSkipped: ${data.skipped}\nErrors: ${data.errors}`);
            if (data.imported > 0) {
                window.location.href = 'index.php';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error importing books');
        });
    }
    
    // Auto-submit on file selection
    document.getElementById('fileInput')?.addEventListener('change', () => {
        if (document.getElementById('fileInput').files.length > 0) {
            document.getElementById('fileInput').closest('form').submit();
        }
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php $conn->close(); ?>