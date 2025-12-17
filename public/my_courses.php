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

$courses = [];
$error = '';

try {
    // Fetch distinct instrument IDs the user has booked lessons for
    $stmt_instruments = $pdo->prepare("
        SELECT DISTINCT b.instrument_id
        FROM bookings b
        WHERE b.student_id = ? AND b.status = 'confirmed'
    ");
    $stmt_instruments->execute([$user_id]);
    $instrument_ids = $stmt_instruments->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($instrument_ids)) {
        // Use a placeholder for each ID to create the IN clause
        $in_clause = implode(',', array_fill(0, count($instrument_ids), '?'));

        // Fetch courses linked to these instruments
        $stmt_courses = $pdo->prepare("
            SELECT
                c.id AS course_id,
                c.title AS course_title,
                c.description AS course_description,
                c.image_url, -- Added image_url
                u.username AS mentor_name,
                i.name AS instrument_name,
                i.icon_class
            FROM courses c
            JOIN mentors m ON c.mentor_id = m.id
            JOIN users u ON m.user_id = u.id
            JOIN instruments i ON c.instrument_id = i.id
            WHERE c.instrument_id IN ($in_clause)
            ORDER BY c.title
        ");
        $stmt_courses->execute($instrument_ids);
        $courses = $stmt_courses->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    error_log("Error fetching courses: " . $e->getMessage());
    $error = "There was an error fetching your courses. Please try again later.";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Courses</title>
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
            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="booking.php"><i class="fas fa-calendar-plus"></i> Book a Lesson</a>
            <a href="my_courses.php" class="active"><i class="fas fa-graduation-cap"></i> My Courses</a>
            <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </div>

    <div class="main-content">
        <header class="dashboard-header">
            <h1>My Learning Courses</h1>
            <p>Courses related to the instruments you've booked.</p>
        </header>

        <main>
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="course-grid">
                <?php if (empty($courses) && !$error): ?>
                    <div class="info-message">
                        You have no courses available. Courses appear here once you book a lesson for a specific instrument.
                        <a href="booking.php">Book a lesson now</a> to get started!
                    </div>
                <?php else: ?>
                    <?php foreach ($courses as $course): ?>
                        <div class="course-card">
                            <div class="course-card-header" style="background-color: #007bff; color: #fff; padding: 1.2rem; border-top-left-radius: 10px; border-top-right-radius: 10px; display: flex; align-items: center; gap: 1rem;">
                                <?php if (!empty($course['image_url'])): ?>
                                    <img src="../<?php echo htmlspecialchars($course['image_url']); ?>" alt="Course Image" style="width: 60px; height: 60px; object-fit: cover; border-radius: 5px;">
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
                                <a href="classroom.php?course_id=<?php echo htmlspecialchars($course['course_id']); ?>" class="btn-view-course">
                                    <i class="fas fa-play-circle"></i> View Course
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

</body>
</html>
