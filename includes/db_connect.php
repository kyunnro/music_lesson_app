<?php
// includes/db_connect.php

// Database connection parameters for SQLite
$db_path = __DIR__ . '/../music_lesson.db'; // Path to the SQLite database file

$dsn = "sqlite:" . $db_path;
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, null, null, $options);
    // You can uncomment the line below for debugging, but remove in production
    // echo "Connected successfully to SQLite database.";
} catch (PDOException $e) {
    // Log the error for debugging (e.g., to an error log file)
    error_log("Database Connection Error: " . $e->getMessage());

    // Display a user-friendly error message
    // In a production environment, you might redirect to an error page or show a generic message
    die("Error connecting to the database. Please try again later.");
}