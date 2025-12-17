<?php
session_start();
require_once '../../includes/db_connect.php';
require_once '../../includes/functions.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    $role = trim($_POST['role'] ?? '');

    // Basic validation
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password) || empty($role)) {
        header("Location: ../register.php?error=All fields are required.");
        exit();
    }
    
    // Role validation
    if (!in_array($role, ['student', 'mentor', 'admin'])) {
        header("Location: ../register.php?error=Invalid role selected.");
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: ../register.php?error=Invalid email format.");
        exit();
    }

    if ($password !== $confirm_password) {
        header("Location: ../register.php?error=Passwords do not match.");
        exit();
    }

    if (strlen($password) < 6) {
        header("Location: ../register.php?error=Password must be at least 6 characters long.");
        exit();
    }

    try {
        $pdo->beginTransaction();

        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetchColumn() > 0) {
            $pdo->rollBack();
            header("Location: ../register.php?error=Username or Email already taken.");
            exit();
        }

        // Hash the password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // Insert new user into the database
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $email, $password_hash, $role]);
        
        // If the user is a mentor, create an entry in the mentors table
        if ($role === 'mentor') {
            $user_id = $pdo->lastInsertId();
            $stmt = $pdo->prepare("INSERT INTO mentors (user_id) VALUES (?)");
            $stmt->execute([$user_id]);
        }

        $pdo->commit();

        header("Location: ../login.php?message=Registration successful! Please log in.");
        exit();

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Registration Error: " . $e->getMessage());
        header("Location: ../register.php?error=An unexpected error occurred during registration. Please try again.");
        exit();
    }
} else {
    // If someone tries to access register_process.php directly without POST request
    header("Location: ../register.php");
    exit();
}
