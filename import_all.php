<?php
// import_all.php - Simple one-click import (NO JavaScript needed)
require_once 'config/database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Import Books - FDSA OPAC</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #155724; background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: #721c24; background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .warning { color: #856404; background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: #0c5460; background: #d1ecf1; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .log { background: #1a1a2e; color: #00ff88; padding: 15px; border-radius: 8px; font-family: monospace; font-size: 12px; height: 400px; overflow-y: auto; margin: 20px 0; }
        .stats { display: flex; gap: 20px; margin: 20px 0; }
        .stat { flex: 1; text-align: center; padding: 15px; background: #f8f9fa; border-radius: 8px; }
        .stat-number { font-size: 28px; font-weight: bold; }
        .stat-label { color: #6c757d; margin-top: 5px; }
        button { padding: 12px 24px; background: #d62828; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; font-weight: bold; margin-right: 10px; }
        button:hover { background: #b91c1c; }
        .btn-secondary { background: #003049; }
        .btn-secondary:hover { background: #002040; }
        h1 { color: #003049; }
        hr { margin: 20px 0; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>📚 Import Books from HTML File</h1>
        <p>This script will import all books from <strong>OPAC.html</strong> into your database.</p>";
        
        // Check if import was triggered
        if (isset($_GET['action']) && $_GET['action'] == 'import') {
            echo "<div class='info'>⏳ Starting import process...</div>";
            
            // Read the HTML file
            $htmlFile = 'OPAC.html';
            if (!file_exists($htmlFile)) {
                echo "<div class='error'>❌ Error: OPAC.html file not found! Make sure the file is in the same directory.</div>";
            } else {
                $htmlContent = file_get_contents($htmlFile);
                
                // Extract all book rows
                preg_match_all('/<tr[^>]*>\s*<td[^>]*>(.*?)<\/td>\s*<td[^>]*>(.*?)<\/td>\s*<td[^>]*>(.*?)<\/td>\s*<td[^>]*>(.*?)<\/td>\s*<\/tr>/is', $htmlContent, $matches, PREG_SET_ORDER);
                
                $imported = 0;
                $skipped = 0;
                $errors = 0;
                $duplicates = [];
                
                echo "<div class='log' id='log'>";
                echo "<div>📖 Found " . count($matches) . " potential rows...</div>";
                echo "<div>🔄 Processing...</div><br>";
                
                foreach ($matches as $match) {
                    $title = trim(strip_tags($match[1]));
                    $author = trim(strip_tags($match[2]));
                    $callNumberHtml = trim($match[3]);
                    $callNumber = trim(preg_replace('/<br\s*\/?>/i', ' ', $callNumberHtml));
                    $callNumber = trim(strip_tags($callNumber));
                    $copiesText = trim(strip_tags($match[4]));
                    $copies = is_numeric($copiesText) ? (int)$copiesText : 1;
                    
                    // Skip header rows and empty rows
                    if (empty($title) || $title == 'TITLE OF THE BOOK' || $title == 'TOTAL NO. OF COPIES' || strlen($title) < 3) {
                        continue;
                    }
                    
                    // Determine book type from call number
                    $bookType = 'FIL';
                    if (strpos($callNumber, 'SRB') !== false) {
                        $bookType = 'SRB';
                    } elseif (strpos($callNumber, 'MSRB') !== false) {
                        $bookType = 'MSRB';
                    } elseif (strpos($callNumber, 'GRB') !== false) {
                        $bookType = 'GRB';
                    } elseif (strpos($callNumber, 'SHB') !== false) {
                        $bookType = 'SHB';
                    }
                    
                    // Extract year from call number
                    $yearPublished = '';
                    if (preg_match('/\b(19|20)\d{2}\b/', $callNumber, $yearMatch)) {
                        $yearPublished = $yearMatch[0];
                    }
                    
                    // Clean author (remove "et.al." and extra spaces)
                    $author = preg_replace('/\bet\.?\s*al\.?\b/i', '', $author);
                    $author = trim($author);
                    
                    // Check for duplicate before inserting
                    $checkSql = "SELECT id FROM books WHERE title = ? AND call_number = ?";
                    $checkStmt = $conn->prepare($checkSql);
                    $checkStmt->bind_param("ss", $title, $callNumber);
                    $checkStmt->execute();
                    $checkResult = $checkStmt->get_result();
                    
                    if ($checkResult->num_rows > 0) {
                        $skipped++;
                        $duplicates[] = $title;
                        echo "<div style='color: #ffaa44;'>⏭️ SKIPPED (duplicate): " . htmlspecialchars(substr($title, 0, 60)) . "...</div>";
                    } else {
                        // Insert new book
                        $sql = "INSERT INTO books (title, author, call_number, copies, book_type, year_published) 
                                VALUES (?, ?, ?, ?, ?, ?)";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("sssiss", $title, $author, $callNumber, $copies, $bookType, $yearPublished);
                        
                        if ($stmt->execute()) {
                            $imported++;
                            echo "<div style='color: #00ff88;'>✅ IMPORTED: " . htmlspecialchars(substr($title, 0, 60)) . "...</div>";
                        } else {
                            $errors++;
                            echo "<div style='color: #ff4444;'>❌ ERROR: " . htmlspecialchars(substr($title, 0, 60)) . " - " . $conn->error . "</div>";
                        }
                        $stmt->close();
                    }
                    $checkStmt->close();
                }
                
                echo "<br><hr>";
                echo "<div style='color: #00ff88; font-weight: bold;'>═══════════════════════════════════════</div>";
                echo "<div style='color: #00ff88;'>🎉 IMPORT COMPLETE!</div>";
                echo "<div>✅ Successfully Imported: <strong>$imported</strong> books</div>";
                echo "<div>⏭️ Skipped (Duplicates): <strong>$skipped</strong> books</div>";
                echo "<div>❌ Errors: <strong>$errors</strong></div>";
                echo "</div>";
                
                echo "<div class='stats'>
                        <div class='stat'>
                            <div class='stat-number' style='color: #10b981;'>$imported</div>
                            <div class='stat-label'>Imported</div>
                        </div>
                        <div class='stat'>
                            <div class='stat-number' style='color: #f59e0b;'>$skipped</div>
                            <div class='stat-label'>Skipped</div>
                        </div>
                        <div class='stat'>
                            <div class='stat-number' style='color: #ef4444;'>$errors</div>
                            <div class='stat-label'>Errors</div>
                        </div>
                    </div>";
            }
        } else {
            // Show the import button
            echo "<div class='warning'>⚠️ Click the button below to start importing books from OPAC.html</div>";
            echo "<div class='stats'>
                    <div class='stat'>
                        <div class='stat-number'>📖</div>
                        <div class='stat-label'>Source: OPAC.html</div>
                    </div>
                    <div class='stat'>
                        <div class='stat-number'>→</div>
                        <div class='stat-label'>Destination: Database</div>
                    </div>
                    <div class='stat'>
                        <div class='stat-number'>✓</div>
                        <div class='stat-label'>No Duplicates</div>
                    </div>
                  </div>";
            echo "<form method='get' action=''>
                    <input type='hidden' name='action' value='import'>
                    <button type='submit'>▶ Start Import Now</button>
                    <a href='index.php' class='btn-secondary' style='display: inline-block; padding: 12px 24px; background: #003049; color: white; text-decoration: none; border-radius: 8px; margin-left: 10px;'>🏠 Go to OPAC</a>
                  </form>";
        }
        
echo "</div>
</body>
</html>";
?>