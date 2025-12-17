<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Security checks
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}
if ($_SESSION['role'] !== 'mentor') {
    header("Location: dashboard.php?error=Access Denied");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Fetch mentor_id and profile_picture from user_id
$stmt = $pdo->prepare("SELECT id, profile_picture FROM mentors WHERE user_id = ?");
$stmt->execute([$user_id]);
$mentor = $stmt->fetch();
if (!$mentor) {
    // This state should ideally not happen if DB is consistent
    session_destroy();
    header("Location: login.php?error=Mentor profile not configured.");
    exit();
}
$mentor_id = $mentor['id'];
$mentor_profile_picture = $mentor['profile_picture'];


// Fetch upcoming bookings
$upcoming_bookings = [];
$error_message = '';
try {
    $stmt = $pdo->prepare("
        SELECT
            b.id AS booking_id,
            b.schedule_time,
            b.duration_minutes,
            u.username AS student_name,
            i.name AS instrument_name,
            i.icon_class
        FROM bookings b
        JOIN users u ON b.student_id = u.id
        JOIN instruments i ON b.instrument_id = i.id
        WHERE b.mentor_id = ? AND b.schedule_time >= CURRENT_TIMESTAMP AND b.status = 'confirmed'
        ORDER BY b.schedule_time ASC
    ");
    $stmt->execute([$mentor_id]);
    $upcoming_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching mentor's upcoming bookings: " . $e->getMessage());
    $error_message = "Error loading your upcoming bookings.";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mentor Dashboard</title>
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
            <a href="mentor_dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="mentor/courses.php"><i class="fas fa-book-open"></i> My Courses</a>
            <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
            <a href="#" class="disabled"><i class="fas fa-calendar-alt"></i> Availability (Soon)</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </div>

    <div class="main-content">
        <header class="dashboard-header">
            <div style="display: flex; align-items: center; gap: 15px;">
                <?php if (!empty($mentor_profile_picture) && file_exists("../" . $mentor_profile_picture)): ?>
                    <img src="../<?php echo htmlspecialchars($mentor_profile_picture); ?>" alt="Profile Picture" style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover;">
                <?php else: ?>
                    <i class="fas fa-user-circle" style="font-size: 60px; color: #007bff;"></i>
                <?php endif; ?>
                <div>
                    <h1>Welcome, Mentor <?php echo htmlspecialchars($username); ?>!</h1>
                    <p>Manage your courses and see your upcoming lessons.</p>
                </div>
            </div>
        </header>

        <main>
            <?php if ($error_message): ?>
                <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <section class="dashboard-section">
                <h2><i class="fas fa-calendar-check"></i> Your Upcoming Lessons</h2>
                <div class="upcoming-lessons-grid">
                    <?php if (!empty($upcoming_bookings)): ?>
                        <?php foreach ($upcoming_bookings as $booking): ?>
                            <div class="lesson-card">
                                <div class="lesson-card-icon">
                                    <i class="<?php echo htmlspecialchars($booking['icon_class'] ?? 'fas fa-music'); ?>"></i>
                                </div>
                                <div class="lesson-card-details">
                                    <h3><?php echo htmlspecialchars($booking['instrument_name']); ?></h3>
                                    <p>With <strong><?php echo htmlspecialchars($booking['student_name']); ?></strong></p>
                                    <p class="lesson-time">
                                        <i class="far fa-clock"></i>
                                        <?php echo (new DateTime($booking['schedule_time']))->format('D, M j, Y @ g:i A'); ?>
                                    </p>
                                    <p class="lesson-duration">
                                        <i class="far fa-hourglass"></i>
                                        <?php echo htmlspecialchars($booking['duration_minutes']); ?> minutes
                                    </p>
                                </div>
                                 <div class="lesson-card-actions">
                                     <a href="classroom.php?booking_id=<?php echo $booking['booking_id']; ?>" class="btn-join">Join Classroom</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="info-message">
                            <i class="fas fa-info-circle"></i>
                            You have no upcoming bookings at the moment.
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>

</body>
</html>
