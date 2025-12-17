<?php
session_start();
require_once '../../includes/db_connect.php';
require_once '../../includes/functions.php'; // Placeholder for now

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username_email = trim($_POST['username_email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username_email) || empty($password)) {
        header("Location: ../index.php?error=Please fill in all fields.");
        exit();
    }

    try {
        // Check if input is an email
        if (filter_var($username_email, FILTER_VALIDATE_EMAIL)) {
            $stmt = $pdo->prepare("SELECT id, username, password_hash, role FROM users WHERE email = ?");
        } else {
            $stmt = $pdo->prepare("SELECT id, username, password_hash, role FROM users WHERE username = ?");
        }
        $stmt->execute([$username_email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Login successful
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Redirect based on role or to a general dashboard
            if ($user['role'] == 'admin') {
                header("Location: ../admin_dashboard.php"); // Assuming an admin dashboard
            } elseif ($user['role'] == 'mentor') {
                header("Location: ../mentor_dashboard.php"); // Assuming a mentor dashboard
            } else {
                header("Location: ../dashboard.php"); // General student/user dashboard
            }
            exit();
        } else {
            // Login failed
            header("Location: ../index.php?error=Invalid username/email or password.");
            exit();
        }
    } catch (PDOException $e) {
        error_log("Login Error: " . $e->getMessage());
        header("Location: ../index.php?error=An unexpected error occurred. Please try again.");
        exit();
    }
} else {
    // If someone tries to access auth.php directly without POST request
    header("Location: ../index.php");
    exit();
}
