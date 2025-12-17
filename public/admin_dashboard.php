<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Security checks for admin role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php?error=Access Denied");
    exit();
}

$username = $_SESSION['username'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <!-- Google Fonts, Icons, and Custom Stylesheet -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dashboard-body">

    <div class="sidebar">
        <a href="index.php" class="logo">Music<span>App</span></a>
        <nav class="sidebar-nav">
            <a href="admin_dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="admin/users.php"><i class="fas fa-users-cog"></i> Manage Users</a>
            <a href="admin/instruments.php"><i class="fas fa-guitar"></i> Manage Instruments</a>
            <a href="admin/bookings.php"><i class="fas fa-calendar-check"></i> All Bookings</a>
            <a href="admin/courses.php"><i class="fas fa-book"></i> All Courses</a>
            <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </div>

    <div class="main-content">
        <header class="dashboard-header">
            <h1>Welcome, Administrator <?php echo htmlspecialchars($username); ?>!</h1>
            <p>Select an option below to manage the application.</p>
        </header>

        <main>
            <div class="admin-grid">
                <a href="admin/users.php" class="admin-card">
                    <i class="fas fa-users-cog"></i>
                    <h2>Manage Users</h2>
                    <p>Add, edit, or remove user accounts.</p>
                </a>
                <a href="admin/instruments.php" class="admin-card">
                    <i class="fas fa-guitar"></i>
                    <h2>Manage Instruments</h2>
                    <p>Add, edit, or remove instruments.</p>
                </a>
                <a href="admin/bookings.php" class="admin-card">
                    <i class="fas fa-calendar-check"></i>
                    <h2>View All Bookings</h2>
                    <p>Oversee all scheduled lessons.</p>
                </a>
                 <a href="admin/courses.php" class="admin-card">
                    <i class="fas fa-book"></i>
                    <h2>View All Courses</h2>
                    <p>Oversee all courses created by mentors.</p>
                </a>
                <a href="profile.php" class="admin-card">
                    <i class="fas fa-user"></i>
                    <h2>My Profile</h2>
                    <p>Update your personal admin information.</p>
                </a>
            </div>
        </main>
    </div>

</body>
</html>
