<?php
session_start();
require_once '../../../includes/db_connect.php';
require_once '../../../includes/functions.php';

// Ensure this is a POST request
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
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

// Retrieve form data
$booking_id = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
$new_status = trim($_POST['status'] ?? '');

// Validation
if (!$booking_id || empty($new_status)) {
    header("Location: ../../admin/bookings.php?error=Booking ID and Status are required.");
    exit();
}

$allowed_statuses = ['pending', 'confirmed', 'completed', 'cancelled'];
if (!in_array($new_status, $allowed_statuses)) {
    header("Location: ../../admin/bookings.php?error=Invalid status provided.");
    exit();
}

try {
    $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $booking_id]);

    if ($stmt->rowCount() > 0) {
        header("Location: ../../admin/bookings.php?message=Booking status updated successfully!");
        exit();
    } else {
        header("Location: ../../admin/bookings.php?error=Booking not found or status already set.");
        exit();
    }
} catch (PDOException $e) {
    error_log("Update Booking Status Error: " . $e->getMessage());
    header("Location: ../../admin/bookings.php?error=An unexpected database error occurred. Please try again.");
    exit();
}
