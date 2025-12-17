<?php
session_start();
require_once '../../../includes/db_connect.php';
require_once '../../../includes/functions.php';

// Ensure this is a GET request (for simplicity in direct link, POST would be more robust)
if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    header("Location: ../../admin/bookings.php?error=Invalid request method.");
    exit();
}

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php?error=You must be logged in to manage bookings.");
    exit();
}

$current_admin_user_id = $_SESSION['user_id'];
$current_admin_role = $_SESSION['role'];

// Redirect if not an admin
if ($current_admin_role !== 'admin') {
    header("Location: ../../dashboard.php?error=Access denied. You are not an administrator.");
    exit();
}

$booking_to_delete_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$booking_to_delete_id) {
    header("Location: ../../admin/bookings.php?error=No booking specified for deletion.");
    exit();
}

try {
    $stmt_delete_booking = $pdo->prepare("DELETE FROM bookings WHERE id = ?");
    $stmt_delete_booking->execute([$booking_to_delete_id]);

    if ($stmt_delete_booking->rowCount() > 0) {
        header("Location: ../../admin/bookings.php?message=Booking deleted successfully!");
        exit();
    } else {
        header("Location: ../../admin/bookings.php?error=Booking not found or could not be deleted.");
        exit();
    }

} catch (PDOException $e) {
    error_log("Booking Deletion Error: " . $e->getMessage());
    header("Location: ../../admin/bookings.php?error=An unexpected database error occurred during booking deletion. Please try again.");
    exit();
}
