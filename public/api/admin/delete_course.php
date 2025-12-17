<?php
session_start();
require_once '../../../includes/db_connect.php';
require_once '../../../includes/functions.php';

// Ensure this is a GET request (for simplicity in direct link, POST would be more robust)
if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    header("Location: ../../admin/courses.php?error=Invalid request method.");
    exit();
}

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php?error=You must be logged in to manage courses.");
    exit();
}

$current_admin_user_id = $_SESSION['user_id'];
$current_admin_role = $_SESSION['role'];

// Redirect if not an admin
if ($current_admin_role !== 'admin') {
    header("Location: ../../dashboard.php?error=Access denied. You are not an administrator.");
    exit();
}

$course_to_delete_id = filter_input(INPUT_GET, 'course_id', FILTER_VALIDATE_INT);

if (!$course_to_delete_id) {
    header("Location: ../../admin/courses.php?error=No course specified for deletion.");
    exit();
}

try {
    $pdo->beginTransaction();

    // Due to ON DELETE CASCADE in schema.sql for lessons.course_id,
    // deleting a course will automatically delete associated lessons.
    // This is a powerful action, confirmed via JavaScript before calling this script.

    $stmt_delete_course = $pdo->prepare("DELETE FROM courses WHERE id = ?");
    $stmt_delete_course->execute([$course_to_delete_id]);

    if ($stmt_delete_course->rowCount() > 0) {
        $pdo->commit();
        header("Location: ../../admin/courses.php?message=Course and associated lessons deleted successfully!");
        exit();
    } else {
        $pdo->rollBack();
        header("Location: ../../admin/courses.php?error=Course not found or could not be deleted.");
        exit();
    }

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Admin Course Deletion Error: " . $e->getMessage());
    header("Location: ../../admin/courses.php?error=An unexpected database error occurred during course deletion. Please try again.");
    exit();
}
