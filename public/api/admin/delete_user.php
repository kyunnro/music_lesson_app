<?php
session_start();
require_once '../../../includes/db_connect.php';
require_once '../../../includes/functions.php';

// Ensure this is a GET request (for simplicity in direct link, POST would be more robust)
if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    header("Location: ../../admin/users.php?error=Invalid request method.");
    exit();
}

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php?error=You must be logged in to manage users.");
    exit();
}

$current_admin_user_id = $_SESSION['user_id'];
$current_admin_role = $_SESSION['role'];

// Redirect if not an admin
if ($current_admin_role !== 'admin') {
    header("Location: ../../dashboard.php?error=Access denied. You are not an administrator.");
    exit();
}

$user_to_delete_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$user_to_delete_id) {
    header("Location: ../../admin/users.php?error=No user specified for deletion.");
    exit();
}

// Prevent admin from deleting their own account
if ($user_to_delete_id === $current_admin_user_id) {
    header("Location: ../../admin/users.php?error=You cannot delete your own admin account.");
    exit();
}

try {
    $pdo->beginTransaction();

    // The foreign key constraints (ON DELETE CASCADE) in schema.sql should handle
    // deleting associated mentors, courses, and lessons when a user is deleted.
    // So, a single delete on the users table should be sufficient.

    $stmt_delete_user = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt_delete_user->execute([$user_to_delete_id]);

    if ($stmt_delete_user->rowCount() > 0) {
        $pdo->commit();
        header("Location: ../../admin/users.php?message=User deleted successfully!");
        exit();
    } else {
        $pdo->rollBack();
        header("Location: ../../admin/users.php?error=User not found or could not be deleted.");
        exit();
    }

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("User Deletion Error: " . $e->getMessage());
    header("Location: ../../admin/users.php?error=An unexpected database error occurred during user deletion. Please try again.");
    exit();
}
