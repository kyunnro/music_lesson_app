<?php
session_start();
require_once '../../../includes/db_connect.php';
require_once '../../../includes/functions.php';

// Ensure this is a GET request (for simplicity in direct link, POST would be more robust)
if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    header("Location: ../../mentor/courses.php?error=Invalid request method.");
    exit();
}

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php?error=You must be logged in to manage courses.");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Redirect if not a mentor
if ($role !== 'mentor') {
    header("Location: ../../dashboard.php?error=Access denied. You are not a mentor.");
    exit();
}

$mentor_id = null;
try {
    $stmt = $pdo->prepare("SELECT id FROM mentors WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $mentor_data = $stmt->fetch();
    if ($mentor_data) {
        $mentor_id = $mentor_data['id'];
    } else {
        header("Location: ../../dashboard.php?error=Mentor profile not found.");
        exit();
    }
} catch (PDOException $e) {
    error_log("Error fetching mentor ID: " . $e->getMessage());
    header("Location: ../../dashboard.php?error=Database error.");
    exit();
}

$course_id = filter_input(INPUT_GET, 'course_id', FILTER_VALIDATE_INT);

if (!$course_id) {
    header("Location: ../../mentor/courses.php?error=No course specified for deletion.");
    exit();
}

try {
    $pdo->beginTransaction();

    // Verify the course belongs to this mentor
    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM courses WHERE id = ? AND mentor_id = ?");
    $stmt_check->execute([$course_id, $mentor_id]);
    if ($stmt_check->fetchColumn() === 0) {
        $pdo->rollBack();
        header("Location: ../../mentor/courses.php?error=Access denied. You do not own this course.");
        exit();
    }

    // First, delete associated lessons (if any)
    $stmt_delete_lessons = $pdo->prepare("DELETE FROM lessons WHERE course_id = ?");
    $stmt_delete_lessons->execute([$course_id]);

    // Then, delete the course
    $stmt_delete_course = $pdo->prepare("DELETE FROM courses WHERE id = ?");
    $stmt_delete_course->execute([$course_id]);

    $pdo->commit();
    header("Location: ../../mentor/courses.php?message=Course and all associated lessons deleted successfully!");
    exit();

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Course Deletion Error: " . $e->getMessage());
    header("Location: ../../mentor/courses.php?error=An unexpected database error occurred during course deletion. Please try again.");
    exit();
}
