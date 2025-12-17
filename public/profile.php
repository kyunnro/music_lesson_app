<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$username = '';
$email = '';
$bio = '';
$profile_picture = '';
$hourly_rate = '';
$profile_error = $_GET['error'] ?? '';
$profile_message = $_GET['message'] ?? '';

// Fetch user details
try {
    $stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $username = $user['username'];
        $email = $user['email'];
    } else {
        throw new Exception("User not found.");
    }

    if ($role === 'mentor') {
        $stmt_mentor = $pdo->prepare("SELECT bio, profile_picture, hourly_rate FROM mentors WHERE user_id = ?");
        $stmt_mentor->execute([$user_id]);
        $mentor_data = $stmt_mentor->fetch(PDO::FETCH_ASSOC);
        if ($mentor_data) {
            $bio = $mentor_data['bio'];
            $profile_picture = $mentor_data['profile_picture'];
            $hourly_rate = $mentor_data['hourly_rate'];
        }
    }
} catch (Exception $e) {
    error_log("Error fetching user profile: " . $e->getMessage());
    $profile_error = "Error loading profile data. Please try again.";
    // To prevent partial data from being shown
    $username = '';
    $email = '';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
    <!-- Google Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Custom Stylesheet -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dashboard-body">

    <div class="sidebar">
        <a href="index.php" class="logo">Music<span>App</span></a>
        <nav class="sidebar-nav">
             <?php if ($role === 'mentor'): ?>
                <a href="mentor_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="profile.php" class="active"><i class="fas fa-user"></i> Profile</a>
                 <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            <?php elseif ($role === 'admin'): ?>
                 <a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="profile.php" class="active"><i class="fas fa-user"></i> Profile</a>
                 <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            <?php else: ?>
                <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="booking.php"><i class="fas fa-calendar-plus"></i> Book a Lesson</a>
                <a href="my_courses.php"><i class="fas fa-graduation-cap"></i> My Courses</a>
                <a href="profile.php" class="active"><i class="fas fa-user"></i> Profile</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            <?php endif; ?>
        </nav>
    </div>

    <div class="main-content">
        <header class="dashboard-header">
            <h1>My Profile</h1>
            <p>Update your personal information and manage your account.</p>
        </header>

        <main>
            <?php if ($profile_error): ?>
                <div class="error-message"><?php echo htmlspecialchars($profile_error); ?></div>
            <?php endif; ?>
            <?php if ($profile_message): ?>
                <div class="success-message"><?php echo htmlspecialchars($profile_message); ?></div>
            <?php endif; ?>

            <div class="profile-container">
                <!-- Update Profile Section -->
                <div class="profile-card">
                    <h3><i class="fas fa-user-edit"></i> Edit Information</h3>
                    <form action="api/update_profile.php" method="POST" enctype="multipart/form-data">
                        <div class="form-group-profile">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
                        </div>
                        <div class="form-group-profile">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                        </div>

                        <?php if ($role === 'mentor'): ?>
                            <div class="form-group-profile">
                                <label for="bio">Biography</label>
                                <textarea id="bio" name="bio" rows="4"><?php echo htmlspecialchars($bio); ?></textarea>
                            </div>
                            <div class="form-group-profile">
                                <label for="hourly_rate">Hourly Rate ($)</label>
                                <input type="number" id="hourly_rate" name="hourly_rate" step="0.01" value="<?php echo htmlspecialchars($hourly_rate); ?>">
                            </div>
                            <div class="form-group-profile">
                                <label>Current Profile Picture</label>
                                <?php if ($profile_picture && file_exists("../" . $profile_picture)): ?>
                                    <div style="margin-bottom: 10px;">
                                        <img src="../<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile Picture" class="profile-picture-preview">
                                        <div style="margin-top: 5px;">
                                            <input type="checkbox" id="remove_profile_picture" name="remove_profile_picture" value="1">
                                            <label for="remove_profile_picture">Remove Current Picture</label>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted">No picture uploaded.</p>
                                <?php endif; ?>
                                <label for="profile_picture" class="mt-2">Upload New Picture</label>
                                <input type="file" id="profile_picture" name="profile_picture" accept="image/*">
                                <?php if (!empty($profile_picture)): ?>
                                    <small>Leave blank to keep current picture. Select a new image to replace it.</small>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <button type="submit" class="btn-submit">Save Changes</button>
                    </form>
                </div>

                <!-- Change Password Section -->
                <div class="profile-card">
                    <h3><i class="fas fa-key"></i> Change Password</h3>
                    <form action="api/change_password.php" method="POST">
                        <div class="form-group-profile">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" required>
                        </div>
                        <div class="form-group-profile">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" required minlength="6">
                        </div>
                        <div class="form-group-profile">
                            <label for="confirm_new_password">Confirm New Password</label>
                            <input type="password" id="confirm_new_password" name="confirm_new_password" required>
                        </div>
                        <button type="submit" class="btn-submit">Update Password</button>
                    </form>
                </div>
            </div>
        </main>
    </div>

</body>
</html>
