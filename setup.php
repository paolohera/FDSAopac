<?php
// setup.php - Run this file ONCE to create your database and tables

$host = 'localhost';
$username = 'root';
$password = '';

// Create connection without database
$conn = new mysqli($host, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS opac_db";
if ($conn->query($sql) === TRUE) {
    echo "✅ Database 'opac_db' created successfully<br>";
} else {
    echo "❌ Error creating database: " . $conn->error . "<br>";
}

// Select the database
$conn->select_db("opac_db");

// Create books table
$sql = "CREATE TABLE IF NOT EXISTS books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(500) NOT NULL,
    author VARCHAR(300) NOT NULL,
    call_number VARCHAR(200) NOT NULL,
    copies INT DEFAULT 1,
    book_type VARCHAR(20) DEFAULT 'FIL',
    year_published VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "✅ Books table created successfully<br>";
} else {
    echo "❌ Error creating table: " . $conn->error . "<br>";
}

// Insert sample data (first 10 books as example)
$sql = "INSERT IGNORE INTO books (title, author, call_number, copies, book_type, year_published) VALUES
    ('INTRODUCTION TO COMPUTER CONCEPTS', 'LA PUTT, JUNY PILAPIL', 'FIL. 001.64 L319 2005', 1, 'FIL', '2005'),
    ('AN INTRODUCTION TO COMPUTER FUNDAMENTALS AND WORD PROCESSING', 'BUENDIA, MARLYN P.', 'FIL 004 B928 2007', 1, 'FIL', '2007'),
    ('INTRODUCTION TO COMPUTER FUNDAMENTALS ( CONCEPTS AND APPLICATIONS )', 'PEPITO, COPERNICUS P.', 'FIL 004.07 P393 2002', 1, 'FIL', '2002'),
    ('A QUICK AND PRACTICAL GUIDE TO THE INTERNET', 'KING, DAVID', 'FIL 004.6 K581 2002', 2, 'FIL', '2002'),
    ('LIBRARY CLASSIFICATION FACETS AND ANALYSES', 'HUSAIN, SHABAHAT', 'FIL 025 H95 1993', 1, 'FIL', '1993'),
    ('THE MANAGEMENT OF SPECIAL LIBRARIES AND INFORMATION CENTERS', '', 'FIL 025.1 M311 1995', 1, 'FIL', '1995'),
    ('UNDERSTANDING THE SELF', 'ALATA, EDEN JOY PASTOR et.al.', 'FIL 126 A321 2018', 1, 'FIL', '2018'),
    ('PERSONALITY AND HUMAN RELATIONS', 'SFERRA, ADAM', 'FIL 126 S523 1977', 1, 'FIL', '1977'),
    ('PHILOSOPHY OF MAN', 'CRUZ, CORAZON L.', 'FIL 128 C889 1993', 1, 'FIL', '1993'),
    ('PSYCHOLOGY TOWARDS A NEW MILLENNIUM', 'KAHAYON, ALICIA HERNANDEZ', 'FIL 150 K12 2004', 1, 'FIL', '2004')";

if ($conn->query($sql) === TRUE) {
    echo "✅ Sample data inserted successfully<br>";
} else {
    echo "❌ Error inserting data: " . $conn->error . "<br>";
}

$conn->close();

echo "<br><hr>";
echo "<h3>Setup Complete!</h3>";
echo "<p>You can now:</p>";
echo "<ul>";
echo "<li><a href='index.php'>📚 Go to OPAC System</a></li>";
echo "<li><a href='add_book.php'>➕ Add new books</a></li>";
echo "</ul>";
?>