<?php
session_start();
require_once '../../../includes/db_connect.php';
require_once '../../../includes/functions.php';

// Ensure this is a POST request
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
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

// Retrieve form data
$action = trim($_POST['action'] ?? '');
$course_id = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
$lesson_id = filter_input(INPUT_POST, 'lesson_id', FILTER_VALIDATE_INT); // Only for edit
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$video_url = trim($_POST['video_url'] ?? '');
$materials_url = trim($_POST['materials_url'] ?? '');
$quiz_data = trim($_POST['quiz_data'] ?? '');
$lesson_order = filter_input(INPUT_POST, 'lesson_order', FILTER_VALIDATE_INT);

// Construct redirect URL base
$redirect_base = "../../mentor/lessons.php?course_id={$course_id}&";
if ($action === 'edit' && $lesson_id) {
    $redirect_base = "../../mentor/edit_lesson.php?course_id={$course_id}&lesson_id={$lesson_id}&";
} else if ($action === 'add') {
    $redirect_base = "../../mentor/add_lesson.php?course_id={$course_id}&";
}


// Validation
if (!$course_id) {
    header("Location: ../../mentor/courses.php?error=Course ID is missing.");
    exit();
}
if (empty($title) || empty($description) || empty($video_url) || $lesson_order === false) {
    header("Location: {$redirect_base}error=Title, Description, Video URL, and Lesson Order are required.");
    exit();
}
if (!filter_var($video_url, FILTER_VALIDATE_URL)) {
    header("Location: {$redirect_base}error=Invalid Video URL format.");
    exit();
}
if (!empty($materials_url) && !filter_var($materials_url, FILTER_VALIDATE_URL)) {
    header("Location: {$redirect_base}error=Invalid Materials URL format.");
    exit();
}
if ($lesson_order < 1) {
    header("Location: {$redirect_base}error=Lesson order must be a positive number.");
    exit();
}

// Validate quiz_data if provided
if (!empty($quiz_data)) {
    json_decode($quiz_data);
    if (json_last_error() !== JSON_ERROR_NONE) {
        header("Location: {$redirect_base}error=Invalid JSON format for Quiz Data: " . json_last_error_msg());
        exit();
    }
}

try {
    // Verify the course belongs to this mentor
    $stmt_check_course = $pdo->prepare("SELECT COUNT(*) FROM courses WHERE id = ? AND mentor_id = ?");
    $stmt_check_course->execute([$course_id, $mentor_id]);
    if ($stmt_check_course->fetchColumn() === 0) {
        header("Location: ../../mentor/courses.php?error=Access denied. Course not found or does not belong to you.");
        exit();
    }

    if ($action === 'add') {
        $stmt = $pdo->prepare("
            INSERT INTO lessons (course_id, mentor_id, title, description, video_url, materials_url, quiz_data, lesson_order)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$course_id, $mentor_id, $title, $description, $video_url, $materials_url, $quiz_data, $lesson_order]);
        header("Location: ../../mentor/lessons.php?course_id={$course_id}&message=Lesson '{$title}' added successfully!");
        exit();
    } elseif ($action === 'edit') {
        if (!$lesson_id) {
            header("Location: ../../mentor/lessons.php?course_id={$course_id}&error=Lesson ID is missing for editing.");
            exit();
        }

        // Verify the lesson also belongs to this mentor and course
        $stmt_check_lesson = $pdo->prepare("SELECT COUNT(*) FROM lessons WHERE id = ? AND course_id = ? AND mentor_id = ?");
        $stmt_check_lesson->execute([$lesson_id, $course_id, $mentor_id]);
        if ($stmt_check_lesson->fetchColumn() === 0) {
            header("Location: ../../mentor/lessons.php?course_id={$course_id}&error=Access denied. Lesson not found or does not belong to you.");
            exit();
        }

        $stmt = $pdo->prepare("
            UPDATE lessons
            SET title = ?, description = ?, video_url = ?, materials_url = ?, quiz_data = ?, lesson_order = ?
            WHERE id = ? AND course_id = ? AND mentor_id = ?
        ");
        $stmt->execute([$title, $description, $video_url, $materials_url, $quiz_data, $lesson_order, $lesson_id, $course_id, $mentor_id]);
        header("Location: ../../mentor/lessons.php?course_id={$course_id}&message=Lesson '{$title}' updated successfully!");
        exit();
    } else {
        header("Location: ../../mentor/lessons.php?course_id={$course_id}&error=Invalid action specified.");
        exit();
    }
} catch (PDOException $e) {
    error_log("Lesson Processing Error: " . $e->getMessage());
    header("Location: {$redirect_base}error=An unexpected database error occurred. Please try again.");
    exit();
}
