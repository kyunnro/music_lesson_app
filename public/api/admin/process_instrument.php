<?php
session_start();
require_once '../../../includes/db_connect.php';
require_once '../../../includes/functions.php';

// Ensure this is a POST request
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../../admin/instruments.php?error=Invalid request method.");
    exit();
}

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php?error=You must be logged in to manage instruments.");
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
$instrument_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT); // Only for edit
$name = trim($_POST['name'] ?? '');
$icon_class = trim($_POST['icon_class'] ?? '');

// Construct redirect URL base
$redirect_base = "../../admin/instruments.php?";
if ($action === 'edit' && $instrument_id) {
    $redirect_base = "../../admin/edit_instrument.php?id={$instrument_id}&";
} else if ($action === 'add') {
    $redirect_base = "../../admin/add_instrument.php?";
}

// Validation
if (empty($name) || empty($icon_class)) {
    header("Location: {$redirect_base}error=Instrument Name and Icon Class are required.");
    exit();
}

try {
    // Check for duplicate name (excluding current instrument if editing)
    $check_duplicate_sql = "SELECT COUNT(*) FROM instruments WHERE name = ?";
    $check_duplicate_params = [$name];

    if ($action === 'edit' && $instrument_id) {
        $check_duplicate_sql .= " AND id != ?";
        $check_duplicate_params[] = $instrument_id;
    }

    $stmt_duplicate = $pdo->prepare($check_duplicate_sql);
    $stmt_duplicate->execute($check_duplicate_params);
    if ($stmt_duplicate->fetchColumn() > 0) {
        header("Location: {$redirect_base}error=Instrument name already exists.");
        exit();
    }

    if ($action === 'add') {
        $stmt_add = $pdo->prepare("INSERT INTO instruments (name, icon_class) VALUES (?, ?)");
        $stmt_add->execute([$name, $icon_class]);
        header("Location: ../../admin/instruments.php?message=Instrument '{$name}' added successfully!");
        exit();

    } elseif ($action === 'edit') {
        if (!$instrument_id) {
            header("Location: ../../admin/instruments.php?error=Instrument ID is missing for editing.");
            exit();
        }

        $stmt_update = $pdo->prepare("UPDATE instruments SET name = ?, icon_class = ? WHERE id = ?");
        $stmt_update->execute([$name, $icon_class, $instrument_id]);
        header("Location: ../../admin/instruments.php?message=Instrument '{$name}' updated successfully!");
        exit();
    } else {
        header("Location: ../../admin/instruments.php?error=Invalid action specified.");
        exit();
    }
} catch (PDOException $e) {
    error_log("Instrument Processing Error: " . $e->getMessage());
    header("Location: {$redirect_base}error=An unexpected database error occurred. Please try again.");
    exit();
}
