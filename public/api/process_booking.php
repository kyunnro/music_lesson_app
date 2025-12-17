<?php
session_start();
require_once '../../includes/db_connect.php';
require_once '../../includes/functions.php';

// Ensure this is a POST request
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../booking.php?error=Invalid request method.");
    exit();
}

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php?error=You must be logged in to book a lesson.");
    exit();
}

$student_id = $_SESSION['user_id'];

// Retrieve and sanitize form data
$mentor_id = filter_input(INPUT_POST, 'mentor_id', FILTER_VALIDATE_INT);
$instrument_id = filter_input(INPUT_POST, 'instrument_id', FILTER_VALIDATE_INT);
$schedule_date = filter_input(INPUT_POST, 'schedule_date', FILTER_SANITIZE_STRING);
$schedule_time_str = filter_input(INPUT_POST, 'schedule_time', FILTER_SANITIZE_STRING);
$duration_minutes = filter_input(INPUT_POST, 'duration_minutes', FILTER_VALIDATE_INT);

// Validate inputs
if (!$mentor_id || !$instrument_id || empty($schedule_date) || empty($schedule_time_str) || !$duration_minutes) {
    header("Location: ../booking.php?error=All booking fields are required.");
    exit();
}

// Combine date and time into a single DATETIME string for MySQL
try {
    $schedule_datetime = new DateTime("{$schedule_date} {$schedule_time_str}");
    $schedule_time = $schedule_datetime->format('Y-m-d H:i:s');
} catch (Exception $e) {
    header("Location: ../booking.php?error=Invalid date or time format.");
    exit();
}

// Optional: Re-calculate price on server-side to prevent tampering (good practice)
$actual_price = 0;
try {
    $stmt_mentor_rate = $pdo->prepare("SELECT hourly_rate FROM mentors WHERE id = ?");
    $stmt_mentor_rate->execute([$mentor_id]);
    $mentor_data = $stmt_mentor_rate->fetch();

    if ($mentor_data) {
        $hourly_rate = (float) $mentor_data['hourly_rate'];
        $actual_price = ($hourly_rate / 60) * $duration_minutes;
    } else {
        header("Location: ../booking.php?error=Mentor not found.");
        exit();
    }
} catch (PDOException $e) {
    error_log("Booking Price Recalculation Error: " . $e->getMessage());
    header("Location: ../booking.php?error=Error verifying mentor rate.");
    exit();
}


try {
    // Insert the booking into the database
    // room_id is NULL initially as it might be assigned later or not applicable for all lesson types
    $stmt = $pdo->prepare("
        INSERT INTO bookings (student_id, mentor_id, instrument_id, schedule_time, duration_minutes, status)
        VALUES (?, ?, ?, ?, ?, 'pending')
    ");
    $stmt->execute([$student_id, $mentor_id, $instrument_id, $schedule_time, $duration_minutes]);

    // Redirect to a success page or dashboard with a success message
    header("Location: ../dashboard.php?message=Lesson booked successfully! Price: $" . number_format($actual_price, 2));
    exit();

} catch (PDOException $e) {
    error_log("Booking Insertion Error: " . $e->getMessage());
    header("Location: ../booking.php?error=An unexpected error occurred during booking. Please try again.");
    exit();
}
