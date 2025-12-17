<?php
session_start();
require_once '../../../includes/db_connect.php';
require_once '../../../includes/functions.php';

// Ensure this is a POST request
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
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

// Retrieve form data
$action = trim($_POST['action'] ?? '');
$user_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT); // Only for edit
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$role = trim($_POST['role'] ?? '');

// Construct redirect URL base
$redirect_base = "../../admin/users.php?";
if ($action === 'edit' && $user_id) {
    $redirect_base = "../../admin/edit_user.php?id={$user_id}&";
} else if ($action === 'add') {
    $redirect_base = "../../admin/add_user.php?";
}

// Validation
if (empty($username) || empty($email) || empty($role)) {
    header("Location: {$redirect_base}error=Username, Email, and Role are required.");
    exit();
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: {$redirect_base}error=Invalid email format.");
    exit();
}
$allowed_roles = ['student', 'mentor', 'admin'];
if (!in_array($role, $allowed_roles)) {
    header("Location: {$redirect_base}error=Invalid role selected.");
    exit();
}

if ($action === 'add' || (!empty($password) || !empty($confirm_password))) { // Password is required for add, or if provided for edit
    if ($password !== $confirm_password) {
        header("Location: {$redirect_base}error=Passwords do not match.");
        exit();
    }
    if (strlen($password) < 6) {
        header("Location: {$redirect_base}error=Password must be at least 6 characters long.");
        exit();
    }
}

try {
    // Check for duplicate username or email (excluding current user if editing)
    $check_duplicate_sql = "SELECT COUNT(*) FROM users WHERE (username = ? OR email = ?)";
    $check_duplicate_params = [$username, $email];

    if ($action === 'edit' && $user_id) {
        $check_duplicate_sql .= " AND id != ?";
        $check_duplicate_params[] = $user_id;
    }

    $stmt_duplicate = $pdo->prepare($check_duplicate_sql);
    $stmt_duplicate->execute($check_duplicate_params);
    if ($stmt_duplicate->fetchColumn() > 0) {
        header("Location: {$redirect_base}error=Username or Email already taken.");
        exit();
    }

    if ($action === 'add') {
        if (empty($password)) { // Password is mandatory for adding a user
            header("Location: {$redirect_base}error=Password is required for new users.");
            exit();
        }
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt_add = $pdo->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)");
        $stmt_add->execute([$username, $email, $password_hash, $role]);
        $new_user_id = $pdo->lastInsertId();

        if ($role === 'mentor') {
            $stmt_add_mentor = $pdo->prepare("INSERT INTO mentors (user_id, bio, profile_picture, hourly_rate) VALUES (?, '', '', 0.00)");
            $stmt_add_mentor->execute([$new_user_id]);
        }
        header("Location: ../../admin/users.php?message=User '{$username}' added successfully!");
        exit();

    } elseif ($action === 'edit') {
        if (!$user_id) {
            header("Location: ../../admin/users.php?error=User ID is missing for editing.");
            exit();
        }

        // Prepare update statement for users table
        $update_user_sql = "UPDATE users SET username = ?, email = ?, role = ?";
        $update_user_params = [$username, $email, $role];

        if (!empty($password)) { // Only update password if provided
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $update_user_sql .= ", password_hash = ?";
            $update_user_params[] = $password_hash;
        }

        $update_user_sql .= " WHERE id = ?";
        $update_user_params[] = $user_id;

        $stmt_update = $pdo->prepare($update_user_sql);
        $stmt_update->execute($update_user_params);

        // Handle mentor role change
        if ($role === 'mentor') {
            $stmt_check_mentor_entry = $pdo->prepare("SELECT COUNT(*) FROM mentors WHERE user_id = ?");
            $stmt_check_mentor_entry->execute([$user_id]);
            if ($stmt_check_mentor_entry->fetchColumn() === 0) {
                // If user is now a mentor but no entry exists in mentors table, create one
                $stmt_add_mentor = $pdo->prepare("INSERT INTO mentors (user_id, bio, profile_picture, hourly_rate) VALUES (?, '', '', 0.00)");
                $stmt_add_mentor->execute([$user_id]);
            }
        } else {
            // Optional: If role changed from mentor to something else, remove from mentors table
            // For now, we'll leave it in the mentors table. This might require manual cleanup by admin later.
            // If cascade delete is set up, deleting the user will remove mentor entry.
        }

        header("Location: ../../admin/users.php?message=User '{$username}' updated successfully!");
        exit();
    } else {
        header("Location: ../../admin/users.php?error=Invalid action specified.");
        exit();
    }
} catch (PDOException $e) {
    error_log("User Processing Error: " . $e->getMessage());
    header("Location: {$redirect_base}error=An unexpected database error occurred. Please try again.");
    exit();
}
