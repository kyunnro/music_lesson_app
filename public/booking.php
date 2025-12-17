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

// Fetch instruments
$instruments = [];
try {
    $stmt = $pdo->query("SELECT id, name FROM instruments ORDER BY name");
    $instruments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching instruments: " . $e->getMessage());
    $form_error = "Could not load instruments. Please try again later.";
}

// Fetch mentors
$mentors = [];
try {
    $stmt = $pdo->query("SELECT m.id, u.username, m.hourly_rate, m.profile_picture FROM mentors m JOIN users u ON m.user_id = u.id ORDER BY u.username");
    $mentors = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching mentors: " . $e->getMessage());
    $form_error = "Could not load mentors. Please try again later.";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book a Lesson</title>
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
            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="booking.php" class="active"><i class="fas fa-calendar-plus"></i> Book a Lesson</a>
            <a href="my_courses.php"><i class="fas fa-graduation-cap"></i> My Courses</a>
            <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </div>

    <div class="main-content">
        <header class="dashboard-header">
            <h1>Book Your Next Lesson</h1>
            <p>Find the perfect time with your favorite mentor.</p>
        </header>

        <main>
            <div class="booking-container">
                <form id="bookingForm" class="booking-form" action="api/process_booking.php" method="POST">
                    <?php if (isset($form_error)): ?>
                        <div class="error-message"><?php echo $form_error; ?></div>
                    <?php else: ?>
                        <div class="form-row">
                            <div class="form-group-booking">
                                <label for="instrument"><i class="fas fa-guitar"></i> Select Instrument</label>
                                <select id="instrument" name="instrument_id" required>
                                    <option value="">-- Choose an Instrument --</option>
                                    <?php foreach ($instruments as $instrument): ?>
                                        <option value="<?php echo htmlspecialchars($instrument['id']); ?>">
                                            <?php echo htmlspecialchars($instrument['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group-booking">
                                <label for="mentor"><i class="fas fa-chalkboard-teacher"></i> Select Mentor</label>
                                <select id="mentor" name="mentor_id" required>
                                    <option value="">-- Choose a Mentor --</option>
                                    <?php foreach ($mentors as $mentor): ?>
                                        <option value="<?php echo htmlspecialchars($mentor['id']); ?>" data-hourly-rate="<?php echo htmlspecialchars($mentor['hourly_rate']); ?>" data-profile-picture="<?php echo htmlspecialchars($mentor['profile_picture']); ?>">
                                            <?php echo htmlspecialchars($mentor['username']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group-booking">
                                <label for="booking_date"><i class="fas fa-calendar-day"></i> Select Date</label>
                                <input type="date" id="booking_date" name="schedule_date" required>
                            </div>
                            <div class="form-group-booking">
                                <label for="booking_time"><i class="fas fa-clock"></i> Select Time</label>
                                <input type="time" id="booking_time" name="schedule_time" required>
                            </div>
                        </div>
                        <div class="form-row">
                             <div class="form-group-booking">
                                <label for="duration"><i class="fas fa-hourglass-half"></i> Duration</label>
                                <select id="duration" name="duration_minutes" required>
                                    <option value="">-- Select Duration --</option>
                                    <option value="30">30 Minutes</option>
                                    <option value="45">45 Minutes</option>
                                    <option value="60">60 Minutes</option>
                                    <option value="90">90 Minutes</option>
                                </select>
                            </div>
                            <div class="form-group-booking price-display">
                                <label><i class="fas fa-dollar-sign"></i> Estimated Price</label>
                                <span id="estimatedPrice">$0.00</span>
                            </div>
                        </div>
                        <div class="form-actions">
                             <button type="submit" id="bookNowBtn" class="btn-submit" disabled>Book Now</button>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </main>
    </div>

    <script src="assets/js/script.js"></script>
</body>
</html>
