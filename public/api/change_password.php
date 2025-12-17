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
    header("Location: ../index.php?error=You must be logged in to change your password.");
    exit();
}

$user_id = $_SESSION['user_id'];
$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_new_password = $_POST['confirm_new_password'] ?? '';

// Basic validation
if (empty($current_password) || empty($new_password) || empty($confirm_new_password)) {
    header("Location: ../profile.php?error=All password fields are required.");
    exit();
}

if ($new_password !== $confirm_new_password) {
    header("Location: ../profile.php?error=New password and confirmation do not match.");
    exit();
}

if (strlen($new_password) < 6) {
    header("Location: ../profile.php?error=New password must be at least 6 characters long.");
    exit();
}

try {
    // Fetch current password hash from the database
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($current_password, $user['password_hash'])) {
        header("Location: ../profile.php?error=Incorrect current password.");
        exit();
    }

    // Hash the new password
    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);

    // Update password in the database
    $stmt_update = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    $stmt_update->execute([$new_password_hash, $user_id]);

    header("Location: ../profile.php?message=Password changed successfully!");
    exit();

} catch (PDOException $e) {
    error_log("Password Change Error: " . $e->getMessage());
    header("Location: ../profile.php?error=An unexpected error occurred during password change. Please try again.");
    exit();
}

