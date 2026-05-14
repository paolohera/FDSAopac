<?php
// download_template.php - Download import templates
require_once 'config/database.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$type = isset($_GET['type']) ? $_GET['type'] : 'csv';

if ($type == 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="book_import_template.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Title', 'Author', 'Call Number', 'Copies']);
    fputcsv($output, ['INTRODUCTION TO COMPUTER CONCEPTS', 'LA PUTT, JUNY PILAPIL', 'FIL. 001.64 L319 2005', '1']);
    fputcsv($output, ['AN INTRODUCTION TO COMPUTER FUNDAMENTALS', 'BUENDIA, MARLYN P.', 'FIL 004 B928 2007', '1']);
    fputcsv($output, ['', '', '', '']);
    fputcsv($output, ['Instructions:', '', '', '']);
    fputcsv($output, ['1. Replace sample data with your books', '', '', '']);
    fputcsv($output, ['2. Do not change the column order', '', '', '']);
    fputcsv($output, ['3. Save as CSV and import', '', '', '']);
    fclose($output);
    
} elseif ($type == 'txt') {
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="book_import_template.txt"');
    
    echo "=== BOOK IMPORT TEMPLATE ===\n";
    echo "Format: Title | Author | Call Number | Copies\n";
    echo "Use pipe symbol (|) to separate fields\n\n";
    echo "Example:\n";
    echo "INTRODUCTION TO COMPUTER CONCEPTS|LA PUTT, JUNY PILAPIL|FIL. 001.64 L319 2005|1\n";
    echo "AN INTRODUCTION TO COMPUTER FUNDAMENTALS|BUENDIA, MARLYN P.|FIL 004 B928 2007|1\n";
    echo "A QUICK AND PRACTICAL GUIDE TO THE INTERNET|KING, DAVID|FIL 004.6 K581 2002|2\n\n";
    echo "Instructions:\n";
    echo "1. Replace the example lines with your book data\n";
    echo "2. Keep the same format\n";
    echo "3. Copy all lines and paste in the import page\n";
    
} elseif ($type == 'docx') {
    // Create a simple DOCX template
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="book_import_template.docx"');
    
    // Simple XML for DOCX
    $content = '<?xml version="1.0" encoding="UTF-8"?>
    <w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
        <w:body>
            <w:p><w:r><w:t>BOOK IMPORT TEMPLATE</w:t></w:r></w:p>
            <w:p><w:r><w:t>=====================</w:t></w:r></w:p>
            <w:p><w:r><w:t> </w:t></w:r></w:p>
            <w:p><w:r><w:t>Instructions:</w:t></w:r></w:p>
            <w:p><w:r><w:t>1. Replace the sample data below with your books</w:t></w:r></w:p>
            <w:p><w:r><w:t>2. Keep the format similar to the examples</w:t></w:r></w:p>
            <w:p><w:r><w:t>3. Save and upload the file</w:t></w:r></w:p>
            <w:p><w:r><w:t> </w:t></w:r></w:p>
            <w:p><w:r><w:t>Sample Books:</w:t></w:r></w:p>
            <w:p><w:r><w:t>------------------------</w:t></w:r></w:p>
            <w:p><w:r><w:t>Call Number: FIL. 001.64 L319 2005</w:t></w:r></w:p>
            <w:p><w:r><w:t>Title: INTRODUCTION TO COMPUTER CONCEPTS</w:t></w:r></w:p>
            <w:p><w:r><w:t>Author: LA PUTT, JUNY PILAPIL</w:t></w:r></w:p>
            <w:p><w:r><w:t>Copies: 1</w:t></w:r></w:p>
            <w:p><w:r><w:t> </w:t></w:r></w:p>
            <w:p><w:r><w:t>Call Number: FIL 004 B928 2007</w:t></w:r></w:p>
            <w:p><w:r><w:t>Title: AN INTRODUCTION TO COMPUTER FUNDAMENTALS AND WORD PROCESSING</w:t></w:r></w:p>
            <w:p><w:r><w:t>Author: BUENDIA, MARLYN P.</w:t></w:r></w:p>
            <w:p><w:r><w:t>Copies: 1</w:t></w:r></w:p>
        </w:body>
    </w:document>';
    
    echo $content;
}

exit();
?>