<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

// Handle potential registration messages
$registerMessage = $_GET['message'] ?? '';
$registerError = $_GET['error'] ?? '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Music Lesson App</title>
    <!-- Google Fonts: Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Custom Stylesheet -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <header>
        <a href="index.php" class="logo">Music<span>App</span></a>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="index.php#features">Features</a></li>
                <li><a href="courses.php">Courses</a></li>
            </ul>
        </nav>
        <div class="auth-buttons">
            <a href="login.php" class="btn btn-login">Login</a>
            <a href="register.php" class="btn btn-register active">Register</a>
        </div>
    </header>

    <main class="register-container">
        <div class="register-form">
            <h2>Create Your Account</h2>
            <p>Join our community and start learning today!</p>

            <?php if ($registerError): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($registerError); ?>
                </div>
            <?php endif; ?>

            <?php if ($registerMessage): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($registerMessage); ?>
                </div>
            <?php endif; ?>

            <form action="api/register_process.php" method="POST">
                <div class="form-group">
                    <label for="username"><i class="fas fa-user"></i> Username</label>
                    <input type="text" id="username" name="username" required minlength="3">
                </div>
                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Password</label>
                    <input type="password" id="password" name="password" required minlength="6">
                </div>
                <div class="form-group">
                    <label for="confirm_password"><i class="fas fa-lock"></i> Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-user-graduate"></i> Register as</label>
                    <div class="role-selection">
                        <input type="radio" id="role_student" name="role" value="student" checked>
                        <label for="role_student">Student</label>
                        <input type="radio" id="role_mentor" name="role" value="mentor">
                        <label for="role_mentor">Mentor</label>
                        <input type="radio" id="role_admin" name="role" value="admin">
                        <label for="role_admin">Admin</label>
                    </div>
                </div>

                <button type="submit" class="btn-submit">Register</button>
            </form>
            <p class="form-footer">Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </main>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> Music Lesson App. All Rights Reserved.</p>
    </footer>

</body>
</html>
