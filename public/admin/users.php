<?php
session_start();
require_once '../../includes/db_connect.php';
require_once '../../includes/functions.php';

// Security checks for admin role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php?error=Access Denied");
    exit();
}

$error_message = $_GET['error'] ?? '';
$success_message = $_GET['message'] ?? '';

// Fetch all users
$users = [];
try {
    $stmt = $pdo->query("SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching users: " . $e->getMessage());
    $error_message = "Error loading users.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <!-- Google Fonts, Icons, and Custom Stylesheet -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="dashboard-body">

    <div class="sidebar">
        <a href="../index.php" class="logo">Music<span>App</span></a>
        <nav class="sidebar-nav">
            <a href="../admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="users.php" class="active"><i class="fas fa-users-cog"></i> Manage Users</a>
            <a href="instruments.php"><i class="fas fa-guitar"></i> Manage Instruments</a>
            <a href="bookings.php"><i class="fas fa-calendar-check"></i> All Bookings</a>
            <a href="courses.php"><i class="fas fa-book"></i> All Courses</a>
            <a href="../profile.php"><i class="fas fa-user"></i> Profile</a>
            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </div>

    <div class="main-content">
        <header class="dashboard-header">
            <h1>Manage Users</h1>
            <div class="header-actions">
                <a href="add_user.php" class="btn-primary"><i class="fas fa-plus"></i> Add New User</a>
            </div>
        </header>

        <main>
            <?php if ($error_message): ?>
                <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            <?php if ($success_message): ?>
                <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>

            <div class="table-container">
                <table class="management-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Joined On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="6">No users found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td data-label="ID"><?php echo htmlspecialchars($user['id']); ?></td>
                                    <td data-label="Username"><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td data-label="Email"><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td data-label="Role"><?php echo htmlspecialchars(ucfirst($user['role'])); ?></td>
                                    <td data-label="Joined On"><?php echo (new DateTime($user['created_at']))->format('M j, Y'); ?></td>
                                    <td data-label="Actions" class="actions-cell">
                                        <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn-action btn-edit" title="Edit User"><i class="fas fa-edit"></i></a>
                                        <?php // Prevent admin from deleting their own account ?>
                                        <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                            <a href="../api/admin/delete_user.php?id=<?php echo $user['id']; ?>" class="btn-action btn-delete" title="Delete User" onclick="return confirm('Are you sure you want to permanently delete this user? This action cannot be undone.');"><i class="fas fa-trash-alt"></i></a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

</body>
</html>
