<?php
session_start();
require_once '../../../includes/db_connect.php';
require_once '../../../includes/functions.php';

// Ensure this is a GET request (for simplicity in direct link, POST would be more robust)
if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    header("Location: ../../admin/instruments.php?error=Invalid request method.");
    exit();
}

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php?error=You must be logged in to manage instruments.");
    exit();
}

$current_admin_user_id = $_SESSION['user_id'];
$current_admin_role = $_SESSION['role'];

// Redirect if not an admin
if ($current_admin_role !== 'admin') {
    header("Location: ../../dashboard.php?error=Access denied. You are not an administrator.");
    exit();
}

$instrument_to_delete_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$instrument_to_delete_id) {
    header("Location: ../../admin/instruments.php?error=No instrument specified for deletion.");
    exit();
}

try {
    $pdo->beginTransaction();

    // Due to ON DELETE CASCADE set in schema.sql for courses.instrument_id and bookings.instrument_id,
    // deleting an instrument will automatically delete associated courses and bookings.
    // This is a powerful action, confirmed via JavaScript before calling this script.

    $stmt_delete_instrument = $pdo->prepare("DELETE FROM instruments WHERE id = ?");
    $stmt_delete_instrument->execute([$instrument_to_delete_id]);

    if ($stmt_delete_instrument->rowCount() > 0) {
        $pdo->commit();
        header("Location: ../../admin/instruments.php?message=Instrument and associated data deleted successfully!");
        exit();
    } else {
        $pdo->rollBack();
        header("Location: ../../admin/instruments.php?error=Instrument not found or could not be deleted.");
        exit();
    }

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Instrument Deletion Error: " . $e->getMessage());
    header("Location: ../../admin/instruments.php?error=An unexpected database error occurred during instrument deletion. Please try again.");
    exit();
}
