<?php
session_start();
require_once '../../../includes/db_connect.php';
require_once '../../../includes/functions.php';

// Ensure this is a GET request (for simplicity in direct link, POST would be more robust)
if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    header("Location: ../../mentor/courses.php?error=Invalid request method."); // Redirect to courses page for generic error
    exit();
}

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php?error=You must be logged in to manage lessons.");
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

$lesson_id = filter_input(INPUT_GET, 'lesson_id', FILTER_VALIDATE_INT);

if (!$lesson_id) {
    header("Location: ../../mentor/courses.php?error=No lesson specified for deletion."); // Redirect to courses if lesson_id missing
    exit();
}

$course_id = null; // To store course_id for redirection
try {
    // Verify the lesson belongs to this mentor and get its course_id
    $stmt_check = $pdo->prepare("SELECT course_id FROM lessons WHERE id = ? AND mentor_id = ?");
    $stmt_check->execute([$lesson_id, $mentor_id]);
    $lesson_data = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if (!$lesson_data) {
        header("Location: ../../mentor/courses.php?error=Access denied. Lesson not found or you do not own this lesson.");
        exit();
    }
    $course_id = $lesson_data['course_id'];

    // Delete the lesson
    $stmt_delete_lesson = $pdo->prepare("DELETE FROM lessons WHERE id = ?");
    $stmt_delete_lesson->execute([$lesson_id]);

    header("Location: ../../mentor/lessons.php?course_id={$course_id}&message=Lesson deleted successfully!");
    exit();

} catch (PDOException $e) {
    error_log("Lesson Deletion Error: " . $e->getMessage());
    $redirect_url = ($course_id) ? "../../mentor/lessons.php?course_id={$course_id}&" : "../../mentor/courses.php?";
    header("Location: {$redirect_url}error=An unexpected database error occurred during lesson deletion. Please try again.");
    exit();
}
