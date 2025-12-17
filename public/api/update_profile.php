<?php
session_start();
require_once '../../includes/db_connect.php';
require_once '../../includes/functions.php';

// Ensure this is a POST request
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../profile.php?error=Invalid request method.");
    exit();
}

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php?error=You must be logged in to update your profile.");
    exit();
}

$user_id = $_SESSION['user_id'];
$current_role = $_SESSION['role'];

$new_username = trim($_POST['username'] ?? '');
$new_email = trim($_POST['email'] ?? '');
$remove_profile_picture = isset($_POST['remove_profile_picture']) && $_POST['remove_profile_picture'] == '1';

// Basic validation
if (empty($new_username) || empty($new_email)) {
    header("Location: ../profile.php?error=Username and Email cannot be empty.");
    exit();
}

if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
    header("Location: ../profile.php?error=Invalid email format.");
    exit();
}

try {
    $pdo->beginTransaction(); // Start transaction

    // Check for duplicate username (excluding current user)
    $stmt_username = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id != ?");
    $stmt_username->execute([$new_username, $user_id]);
    if ($stmt_username->fetchColumn() > 0) {
        $pdo->rollBack();
        header("Location: ../profile.php?error=Username already taken by another user.");
        exit();
    }

    // Check for duplicate email (excluding current user)
    $stmt_email = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
    $stmt_email->execute([$new_email, $user_id]);
    if ($stmt_email->fetchColumn() > 0) {
        $pdo->rollBack();
        header("Location: ../profile.php?error=Email already taken by another user.");
        exit();
    }

    // Update users table
    $stmt_update_user = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
    $stmt_update_user->execute([$new_username, $new_email, $user_id]);

    // Update session username if it changed
    if ($_SESSION['username'] !== $new_username) {
        $_SESSION['username'] = $new_username;
    }

    $success_message = "Profile updated successfully!";

    // Handle mentor-specific fields
    if ($current_role === 'mentor') {
        $bio = trim($_POST['bio'] ?? '');
        $hourly_rate = filter_input(INPUT_POST, 'hourly_rate', FILTER_VALIDATE_FLOAT);

        if ($hourly_rate === false) {
            $pdo->rollBack();
            header("Location: ../profile.php?error=Invalid hourly rate format.");
            exit();
        }

        $profile_picture_url = null; // Default to null
        $upload_dir = '../../uploads/avatars/';
        $max_file_size = 2 * 1024 * 1024; // 2MB

        // Fetch current profile picture from DB
        $stmt_get_old_pic = $pdo->prepare("SELECT profile_picture FROM mentors WHERE user_id = ?");
        $stmt_get_old_pic->execute([$user_id]);
        $old_profile_picture = $stmt_get_old_pic->fetchColumn();

        $file_uploaded = isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK;

        if ($file_uploaded) {
            $file_name = $_FILES['profile_picture']['name'];
            $file_tmp_name = $_FILES['profile_picture']['tmp_name'];
            $file_size = $_FILES['profile_picture']['size'];
            $file_error = $_FILES['profile_picture']['error'];
            $file_type = $_FILES['profile_picture']['type'];

            // Get file extension
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];

            if ($file_error !== 0) {
                $pdo->rollBack();
                header("Location: ../profile.php?error=Error uploading file.");
                exit();
            }
            if (!in_array($file_ext, $allowed_ext)) {
                $pdo->rollBack();
                header("Location: ../profile.php?error=Invalid file type. Only JPG, JPEG, PNG, GIF are allowed.");
                exit();
            }
            if ($file_size > $max_file_size) {
                $pdo->rollBack();
                header("Location: ../profile.php?error=File size exceeds limit (2MB).");
                exit();
            }

            // Generate a unique file name
            $new_file_name = 'uploads/avatars/' . uniqid('profile_', true) . '.' . $file_ext; // Store relative path
            $file_destination = '../../' . $new_file_name; // Absolute path for moving

            // Delete old profile picture if a new one is uploaded
            if ($old_profile_picture && file_exists('../../' . $old_profile_picture)) {
                unlink('../../' . $old_profile_picture);
            }

            if (move_uploaded_file($file_tmp_name, $file_destination)) {
                $profile_picture_url = $new_file_name;
            } else {
                $pdo->rollBack();
                error_log("Failed to move uploaded file: " . $file_tmp_name . " to " . $file_destination);
                header("Location: ../profile.php?error=Failed to move uploaded profile picture.");
                exit();
            }
        } elseif ($remove_profile_picture) {
            // If remove checkbox is checked, delete old picture and set to null
            if ($old_profile_picture && file_exists('../../' . $old_profile_picture)) {
                unlink('../../' . $old_profile_picture);
            }
            $profile_picture_url = null;
        } else {
            // No new file, no remove request, keep existing
            $profile_picture_url = $old_profile_picture;
        }

        $mentor_update_sql = "UPDATE mentors SET bio = ?, hourly_rate = ?, profile_picture = ? WHERE user_id = ?";
        $stmt_update_mentor = $pdo->prepare($mentor_update_sql);
        $stmt_update_mentor->execute([$bio, $hourly_rate, $profile_picture_url, $user_id]);
    }

    $pdo->commit(); // Commit transaction
    header("Location: ../profile.php?message=" . urlencode($success_message));
    exit();

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack(); // Rollback on error
    }
    error_log("Profile Update Error: " . $e->getMessage());
    header("Location: ../profile.php?error=An unexpected database error occurred during profile update. Please try again.");
    exit();
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack(); // Rollback on error
    }
    error_log("Profile Update File Error: " . $e->getMessage());
    header("Location: ../profile.php?error=" . urlencode($e->getMessage()));
    exit();
}


