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

// Fetch all bookings with user and instrument details
$bookings = [];
try {
    $stmt = $pdo->query("
        SELECT
            b.id AS booking_id,
            s.username AS student_name,
            m_u.username AS mentor_name,
            i.name AS instrument_name,
            b.schedule_time,
            b.duration_minutes,
            b.status
        FROM bookings b
        JOIN users s ON b.student_id = s.id
        JOIN mentors m ON b.mentor_id = m.id
        JOIN users m_u ON m.user_id = m_u.id
        JOIN instruments i ON b.instrument_id = i.id
        ORDER BY b.schedule_time DESC
    ");
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching bookings for admin: " . $e->getMessage());
    $error_message = "Error loading bookings data.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage All Bookings</title>
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
            <a href="users.php"><i class="fas fa-users-cog"></i> Manage Users</a>
            <a href="instruments.php"><i class="fas fa-guitar"></i> Manage Instruments</a>
            <a href="bookings.php" class="active"><i class="fas fa-calendar-check"></i> All Bookings</a>
            <a href="courses.php"><i class="fas fa-book"></i> All Courses</a>
            <a href="../profile.php"><i class="fas fa-user"></i> Profile</a>
            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </div>

    <div class="main-content">
        <header class="dashboard-header">
            <h1>Manage All Bookings</h1>
            <p>Oversee and manage all lesson bookings in the system.</p>
        </header>

        <main>
            <?php if ($error_message): ?>
                <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            <?php if ($success_message): ?>
                <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>

            <div class="table-container">
                <table class="management-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Student</th>
                            <th>Mentor</th>
                            <th>Instrument</th>
                            <th>Scheduled Time</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($bookings)): ?>
                            <tr>
                                <td colspan="7">No bookings found in the system.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($bookings as $booking): ?>
                                <tr>
                                    <td data-label="ID"><?php echo htmlspecialchars($booking['booking_id']); ?></td>
                                    <td data-label="Student"><?php echo htmlspecialchars($booking['student_name']); ?></td>
                                    <td data-label="Mentor"><?php echo htmlspecialchars($booking['mentor_name']); ?></td>
                                    <td data-label="Instrument"><?php echo htmlspecialchars($booking['instrument_name']); ?></td>
                                    <td data-label="Scheduled Time"><?php echo (new DateTime($booking['schedule_time']))->format('M j, Y, g:i A'); ?></td>
                                    <td data-label="Status"><span class="status-pill status-<?php echo htmlspecialchars($booking['status']); ?>"><?php echo htmlspecialchars($booking['status']); ?></span></td>
                                    <td data-label="Actions" class="actions-cell">
                                        <form action="../api/admin/update_booking_status.php" method="POST" class="status-update-form">
                                            <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                            <select name="status" class="status-dropdown" onchange="this.form.submit()">
                                                <option value="pending" <?php echo ($booking['status'] === 'pending') ? 'selected' : ''; ?>>Pending</option>
                                                <option value="confirmed" <?php echo ($booking['status'] === 'confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                                                <option value="completed" <?php echo ($booking['status'] === 'completed') ? 'selected' : ''; ?>>Completed</option>
                                                <option value="cancelled" <?php echo ($booking['status'] === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                            </select>
                                        </form>
                                        <a href="../api/admin/delete_booking.php?id=<?php echo $booking['booking_id']; ?>" class="btn-action btn-delete" title="Delete Booking" onclick="return confirm('Are you sure you want to permanently delete this booking?');"><i class="fas fa-trash-alt"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

</body>
</html>
