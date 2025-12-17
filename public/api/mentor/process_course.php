<?php
session_start();
require_once '../../../includes/db_connect.php';
require_once '../../../includes/functions.php';

// Ensure this is a POST request
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
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

// Retrieve form data
$action = trim($_POST['action'] ?? '');
$course_id = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$instrument_id = filter_input(INPUT_POST, 'instrument_id', FILTER_VALIDATE_INT);
$price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
$difficulty = trim($_POST['difficulty'] ?? '');
$remove_image = isset($_POST['remove_image']) && $_POST['remove_image'] == '1';

// Construct redirect URL base
$redirect_page = ($action === 'edit' && $course_id) ? "../../mentor/edit_course.php?course_id={$course_id}&" : "../../mentor/add_course.php?";


// Validation
if (empty($title) || empty($description) || !$instrument_id || $price === false || empty($difficulty)) {
    header("Location: {$redirect_page}error=All fields are required.");
    exit();
}

if ($price < 0) {
    header("Location: {$redirect_page}error=Price cannot be negative.");
    exit();
}

$allowed_difficulties = ['beginner', 'intermediate', 'advanced'];
if (!in_array($difficulty, $allowed_difficulties)) {
    header("Location: {$redirect_page}error=Invalid difficulty selected.");
    exit();
}

$image_url = null; // Will store the path to the image if uploaded or existing
$upload_dir = '../../../uploads/courses/';
$max_file_size = 5 * 1024 * 1024; // 5MB

try {
    // Start transaction for atomicity
    $pdo->beginTransaction();

    // --- Image Upload Handling ---
    $file_uploaded = isset($_FILES['course_image']) && $_FILES['course_image']['error'] === UPLOAD_ERR_OK;
    $old_image_url = null;

    if ($action === 'edit') {
        // Fetch current image URL if editing
        $stmt_get_image = $pdo->prepare("SELECT image_url FROM courses WHERE id = ? AND mentor_id = ?");
        $stmt_get_image->execute([$course_id, $mentor_id]);
        $result = $stmt_get_image->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $old_image_url = $result['image_url'];
        }
        // If no new image is uploaded and 'remove_image' is not checked, keep the old image
        if (!$file_uploaded && !$remove_image) {
            $image_url = $old_image_url;
        }
    }


    if ($file_uploaded) {
        $file_name = $_FILES['course_image']['name'];
        $file_tmp_name = $_FILES['course_image']['tmp_name'];
        $file_size = $_FILES['course_image']['size'];
        $file_error = $_FILES['course_image']['error'];
        $file_type = $_FILES['course_image']['type'];

        // Get file extension
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];

        if ($file_error !== 0) {
            $pdo->rollBack();
            header("Location: {$redirect_page}error=Error uploading file.");
            exit();
        }
        if (!in_array($file_ext, $allowed_ext)) {
            $pdo->rollBack();
            header("Location: {$redirect_page}error=Invalid file type. Only JPG, JPEG, PNG, GIF are allowed.");
            exit();
        }
        if ($file_size > $max_file_size) {
            $pdo->rollBack();
            header("Location: {$redirect_page}error=File size exceeds limit (5MB).");
            exit();
        }

        // Generate a unique file name
        $new_file_name = uniqid('', true) . '.' . $file_ext;
        $file_destination = $upload_dir . $new_file_name;

        if (move_uploaded_file($file_tmp_name, $file_destination)) {
            // Delete old image if it exists and a new one is uploaded
            if ($action === 'edit' && $old_image_url && file_exists('../../../' . $old_image_url)) {
                unlink('../../../' . $old_image_url);
            }
            $image_url = 'uploads/courses/' . $new_file_name; // Store relative path
        } else {
            $pdo->rollBack();
            header("Location: {$redirect_page}error=Failed to move uploaded file.");
            exit();
        }
    } elseif ($remove_image) {
        // If remove_image is checked, delete old image and set image_url to null
        if ($old_image_url && file_exists('../../../' . $old_image_url)) {
            unlink('../../../' . $old_image_url);
        }
        $image_url = null; // Explicitly set to null
    }

    // --- Database Operations ---
    if ($action === 'add') {
        $stmt = $pdo->prepare("
            INSERT INTO courses (mentor_id, instrument_id, title, description, price, difficulty, image_url)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$mentor_id, $instrument_id, $title, $description, $price, $difficulty, $image_url]);
        $pdo->commit();
        header("Location: ../../mentor/courses.php?message=Course '{$title}' added successfully!");
        exit();
    } elseif ($action === 'edit') {
        if (!$course_id) {
            $pdo->rollBack();
            header("Location: ../../mentor/courses.php?error=Course ID is missing for editing.");
            exit();
        }

        // Verify the course belongs to this mentor
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM courses WHERE id = ? AND mentor_id = ?");
        $stmt_check->execute([$course_id, $mentor_id]);
        if ($stmt_check->fetchColumn() === 0) {
            $pdo->rollBack();
            header("Location: ../../mentor/courses.php?error=Access denied. You do not own this course.");
            exit();
        }

        $stmt = $pdo->prepare("
            UPDATE courses
            SET title = ?, description = ?, instrument_id = ?, price = ?, difficulty = ?, image_url = ?
            WHERE id = ? AND mentor_id = ?
        ");
        $stmt->execute([$title, $description, $instrument_id, $price, $difficulty, $image_url, $course_id, $mentor_id]);
        $pdo->commit();
        header("Location: ../../mentor/courses.php?message=Course '{$title}' updated successfully!");
        exit();
    } else {
        $pdo->rollBack();
        header("Location: ../../mentor/courses.php?error=Invalid action specified.");
        exit();
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Course Processing Error: " . $e->getMessage());
    header("Location: {$redirect_page}error=An unexpected database error occurred. Please try again.");
    exit();
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("File Upload Error: " . $e->getMessage());
    header("Location: {$redirect_page}error=" . urlencode($e->getMessage()));
    exit();
}

