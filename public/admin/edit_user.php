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
$user_to_edit = null;
$edit_user_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$edit_user_id) {
    header("Location: users.php?error=No user specified.");
    exit();
}

// Fetch user data
try {
    $stmt = $pdo->prepare("SELECT id, username, email, role FROM users WHERE id = ?");
    $stmt->execute([$edit_user_id]);
    $user_to_edit = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user_to_edit) {
        header("Location: users.php?error=User not found.");
        exit();
    }
} catch (PDOException $e) {
    error_log("Error fetching user for editing: " . $e->getMessage());
    header("Location: users.php?error=Database error while loading user.");
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User</title>
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
            <h1>Edit User: <?php echo htmlspecialchars($user_to_edit['username']); ?></h1>
            <p>Modify the details of the user account.</p>
        </header>

        <main>
            <div class="profile-card" style="max-width: 600px; margin: auto;">
                <h3><i class="fas fa-edit"></i> User Details</h3>
                
                <?php if ($error_message): ?>
                    <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>
                <?php if ($success_message): ?>
                    <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
                <?php endif; ?>

                <form action="../api/admin/process_user.php" method="POST" class="modern-form">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($user_to_edit['id']); ?>">
                    
                    <div class="form-group-profile">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user_to_edit['username']); ?>" required minlength="3">
                    </div>
                    
                    <div class="form-group-profile">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_to_edit['email']); ?>" required>
                    </div>

                    <div class="form-group-profile">
                        <label for="password">New Password (leave blank to keep current)</label>
                        <input type="password" id="password" name="password" minlength="6">
                    </div>

                    <div class="form-group-profile">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password">
                    </div>

                    <div class="form-group-profile">
                        <label for="role">Role</label>
                        <select id="role" name="role" required>
                            <option value="student" <?php echo ($user_to_edit['role'] === 'student') ? 'selected' : ''; ?>>Student</option>
                            <option value="mentor" <?php echo ($user_to_edit['role'] === 'mentor') ? 'selected' : ''; ?>>Mentor</option>
                            <option value="admin" <?php echo ($user_to_edit['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>
                    
                    <div class="form-actions" style="text-align: right; margin-top: 2rem;">
                        <a href="users.php" class="btn-secondary">Cancel</a>
                        <button type="submit" class="btn-submit">Update User</button>
                    </div>
                </form>
            </div>
        </main>
    </div>

</body>
</html>
