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

// Fetch all courses with mentor and instrument details
$courses = [];
try {
    $stmt = $pdo->query("
        SELECT
            c.id AS course_id,
            c.title,
            c.image_url,  -- Added image_url
            u.username AS mentor_name,
            i.name AS instrument_name,
            c.price,
            c.difficulty
        FROM courses c
        JOIN mentors m ON c.mentor_id = m.id
        JOIN users u ON m.user_id = u.id
        JOIN instruments i ON c.instrument_id = i.id
        ORDER BY c.title ASC
    ");
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching courses for admin: " . $e->getMessage());
    $error_message = "Error loading course data.";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage All Courses</title>
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
            <a href="bookings.php"><i class="fas fa-calendar-check"></i> All Bookings</a>
            <a href="courses.php" class="active"><i class="fas fa-book"></i> All Courses</a>
            <a href="../profile.php"><i class="fas fa-user"></i> Profile</a>
            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </div>

    <div class="main-content">
        <header class="dashboard-header">
            <h1>Manage All Courses</h1>
            <p>Oversee all courses created by mentors in the system.</p>
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
                            <th>Title</th>
                            <th>Mentor</th>
                            <th>Instrument</th>
                            <th>Price</th>
                            <th>Difficulty</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($courses)): ?>
                            <tr>
                                <td colspan="7">No courses found in the system.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($courses as $course): ?>
                                <tr>
                                    <td data-label="ID"><?php echo htmlspecialchars($course['course_id']); ?></td>
                                    <td data-label="Title" style="display: flex; align-items: center; gap: 10px;">
                                        <?php if (!empty($course['image_url'])): ?>
                                            <img src="../../<?php echo htmlspecialchars($course['image_url']); ?>" alt="Course Image" style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                        <?php else: ?>
                                            <i class="fas fa-music" style="font-size: 24px; color: #007bff;"></i>
                                        <?php endif; ?>
                                        <span><?php echo htmlspecialchars($course['title']); ?></span>
                                    </td>
                                    <td data-label="Mentor"><?php echo htmlspecialchars($course['mentor_name']); ?></td>
                                    <td data-label="Instrument"><?php echo htmlspecialchars($course['instrument_name']); ?></td>
                                    <td data-label="Price">$<?php echo htmlspecialchars(number_format($course['price'], 2)); ?></td>
                                    <td data-label="Difficulty"><?php echo htmlspecialchars(ucfirst($course['difficulty'])); ?></td>
                                    <td data-label="Actions" class="actions-cell">
                                        <a href="../mentor/lessons.php?course_id=<?php echo $course['course_id']; ?>" class="btn-action btn-view" title="View Lessons"><i class="fas fa-list-ul"></i></a>
                                        <a href="../api/admin/delete_course.php?course_id=<?php echo $course['course_id']; ?>" class="btn-action btn-delete" title="Delete Course" onclick="return confirm('Are you sure you want to permanently delete this course and all its lessons?');"><i class="fas fa-trash-alt"></i></a>
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
