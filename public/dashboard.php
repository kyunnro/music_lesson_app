<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Redirect if not logged in or not a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Handle messages
$message = $_GET['message'] ?? '';
$error = $_GET['error'] ?? '';

// Fetch upcoming lessons
$upcoming_lessons = [];
try {
    $stmt = $pdo->prepare("
        SELECT
            b.id AS booking_id,
            b.schedule_time,
            b.duration_minutes,
            u.username AS mentor_name,
            i.name AS instrument_name,
            i.icon_class
        FROM bookings b
        JOIN mentors m ON b.mentor_id = m.id
        JOIN users u ON m.user_id = u.id
        JOIN instruments i ON b.instrument_id = i.id
        WHERE b.student_id = ? AND b.schedule_time >= CURRENT_TIMESTAMP AND b.status = 'confirmed'
        ORDER BY b.schedule_time ASC
    ");
    $stmt->execute([$student_id]);
    $upcoming_lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching upcoming lessons: " . $e->getMessage());
    $error = "Error loading your upcoming lessons.";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <!-- Google Fonts: Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Custom Stylesheet -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dashboard-body">

    <div class="sidebar">
        <a href="index.php" class="logo">Music<span>App</span></a>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="booking.php"><i class="fas fa-calendar-plus"></i> Book a Lesson</a>
            <a href="my_courses.php"><i class="fas fa-graduation-cap"></i> My Courses</a>
            <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </div>

    <div class="main-content">
        <header class="dashboard-header">
            <h1>Welcome, <?php echo htmlspecialchars($username); ?>!</h1>
            <p>Here's what's happening today.</p>
        </header>

        <main>
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($message): ?>
                <div class="success-message"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <section class="dashboard-section">
                <h2><i class="fas fa-calendar-check"></i> My Upcoming Lessons</h2>
                <div class="upcoming-lessons-grid">
                    <?php if (!empty($upcoming_lessons)): ?>
                        <?php foreach ($upcoming_lessons as $lesson): ?>
                            <div class="lesson-card">
                                <div class="lesson-card-icon">
                                    <i class="<?php echo htmlspecialchars($lesson['icon_class'] ?? 'fas fa-music'); ?>"></i>
                                </div>
                                <div class="lesson-card-details">
                                    <h3><?php echo htmlspecialchars($lesson['instrument_name']); ?></h3>
                                    <p>With <strong><?php echo htmlspecialchars($lesson['mentor_name']); ?></strong></p>
                                    <p class="lesson-time">
                                        <i class="far fa-clock"></i>
                                        <?php echo (new DateTime($lesson['schedule_time']))->format('D, M j, Y @ g:i A'); ?>
                                    </p>
                                    <p class="lesson-duration">
                                        <i class="far fa-hourglass"></i>
                                        <?php echo htmlspecialchars($lesson['duration_minutes']); ?> minutes
                                    </p>
                                </div>
                                <div class="lesson-card-actions">
                                     <a href="classroom.php?booking_id=<?php echo $lesson['booking_id']; ?>" class="btn-join">Join Classroom</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="info-message">
                            <i class="fas fa-info-circle"></i>
                            You have no upcoming lessons. Why not <a href="booking.php">book one now</a>?
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>

</body>
</html>
