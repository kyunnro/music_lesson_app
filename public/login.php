<?php
session_start();

// Check if user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    // Redirect to the appropriate dashboard based on user role
    if (isset($_SESSION['role'])) {
        switch ($_SESSION['role']) {
            case 'admin':
                header("Location: admin_dashboard.php");
                break;
            case 'mentor':
                header("Location: mentor_dashboard.php");
                break;
            case 'student':
                header("Location: dashboard.php");
                break;
            default:
                header("Location: dashboard.php");
                break;
        }
    } else {
        header("Location: dashboard.php");
    }
    exit();
}

// Handle potential login errors from auth.php
$loginError = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Music Lesson App</title>
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
            <a href="login.php" class="btn btn-login active">Login</a>
            <a href="register.php" class="btn btn-register">Register</a>
        </div>
    </header>

    <main class="login-container">
        <div class="login-form">
            <h2>Welcome Back!</h2>
            <p>Login to continue your musical journey.</p>

            <?php if ($loginError): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($loginError); ?>
                </div>
            <?php endif; ?>

            <form action="api/auth.php" method="POST">
                <div class="form-group">
                    <label for="username_email"><i class="fas fa-user"></i> Username or Email</label>
                    <input type="text" id="username_email" name="username_email" required>
                </div>
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn-submit">Login</button>
            </form>
            <p class="form-footer">Don't have an account? <a href="register.php">Register here</a></p>
        </div>
    </main>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> Music Lesson App. All Rights Reserved.</p>
    </footer>

</body>
</html>
