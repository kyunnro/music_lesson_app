<?php
session_start();
require_once '../includes/db_connect.php'; // Add this line

// Check if user is already logged in, redirect to dashboard if true
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
                // Default redirect if role is not set or invalid
                header("Location: dashboard.php");
                break;
        }
    } else {
        // Default redirect if role is not set
        header("Location: dashboard.php");
    }
    exit();
}

// Fetch a few courses for display on the homepage
$homepage_courses = [];
try {
    $stmt = $pdo->prepare("
        SELECT
            c.id AS course_id,
            c.title AS course_title,
            c.description AS course_description,
            c.price,
            c.difficulty,
            c.image_url,
            u.username AS mentor_name,
            i.name AS instrument_name,
            i.icon_class
        FROM courses c
        JOIN mentors m ON c.mentor_id = m.id
        JOIN users u ON m.user_id = u.id
        JOIN instruments i ON c.instrument_id = i.id
        ORDER BY c.created_at DESC
        LIMIT 3
    ");
    $stmt->execute();
    $homepage_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching homepage courses: " . $e->getMessage());
    // Optionally set an error message to display on the page
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Music Lesson App</title>
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
                <li><a href="index.php" class="active">Home</a></li>
                <li><a href="#features">Features</a></li>
                <li><a href="#courses">Courses</a></li>
            </ul>
        </nav>
        <div class="auth-buttons">
            <a href="login.php" class="btn btn-login">Login</a>
            <a href="register.php" class="btn btn-register">Register</a>
        </div>
    </header>

    <main>
        <section class="hero">
            <div class="hero-content">
                <h1>Unlock Your Musical Potential</h1>
                <p>Find expert mentors, book lessons, and track your progress all in one place.</p>
                <a href="register.php" class="btn">Get Started for Free</a>
            </div>
        </section>

        <section id="features" class="features">
            <h2>Why Choose Us?</h2>
            <div class="features-grid">
                <div class="feature-item">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <h3>Expert Mentors</h3>
                    <p>Learn from a curated list of professional music instructors.</p>
                </div>
                <div class="feature-item">
                    <i class="fas fa-calendar-alt"></i>
                    <h3>Flexible Scheduling</h3>
                    <p>Book lessons at times that fit your schedule perfectly.</p>
                </div>
                <div class="feature-item">
                    <i class="fas fa-guitar"></i>
                    <h3>Wide Range of Instruments</h3>
                    <p>From piano to guitar, find a mentor for the instrument you love.</p>
                </div>
            </div>
        </section>

        <section id="courses" class="courses">
            <h2>Our Popular Courses</h2>
            <div class="course-grid">
                <?php if (!empty($homepage_courses)): ?>
                    <?php foreach ($homepage_courses as $course): ?>
                        <div class="course-card">
                            <div class="course-card-header" style="background-color: #007bff; color: #fff; padding: 1.2rem; border-top-left-radius: 10px; border-top-right-radius: 10px; display: flex; align-items: center; gap: 1rem;">
                                <?php if (!empty($course['image_url'])): ?>
                                    <img src="<?php echo htmlspecialchars($course['image_url']); ?>" alt="Course Image" style="width: 60px; height: 60px; object-fit: cover; border-radius: 5px;">
                                <?php else: ?>
                                    <i class="<?php echo htmlspecialchars($course['icon_class'] ?? 'fas fa-music'); ?>" style="font-size: 1.8rem;"></i>
                                <?php endif; ?>
                                <h3><?php echo htmlspecialchars($course['course_title']); ?></h3>
                            </div>
                            <div class="course-card-body">
                                <p><strong>Mentor:</strong> <?php echo htmlspecialchars($course['mentor_name']); ?></p>
                                <p><strong>Instrument:</strong> <?php echo htmlspecialchars($course['instrument_name']); ?></p>
                                <p class="course-description"><?php echo htmlspecialchars(substr($course['course_description'], 0, 120)); ?>...</p>
                            </div>
                            <div class="course-card-footer">
                                <a href="login.php" class="btn-view-course">
                                    <i class="fas fa-arrow-right"></i> Learn More
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="info-message">No courses available at the moment.</div>
                <?php endif; ?>
            </div>
            <div style="text-align: center; margin-top: 2rem;">
                <a href="#" class="btn-primary">View All Courses</a>
            </div>
        </section>
    </main>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> Music Lesson App. All Rights Reserved.</p>
    </footer>

</body>
</html>
