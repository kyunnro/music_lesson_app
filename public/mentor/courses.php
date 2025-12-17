<?php
session_start();
require_once '../../includes/db_connect.php';
require_once '../../includes/functions.php';

// Security: Check if user is logged in and is a mentor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mentor') {
    header("Location: ../login.php?error=Access Denied");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch mentor_id from user_id
try {
    $stmt = $pdo->prepare("SELECT id FROM mentors WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $mentor = $stmt->fetch();
    if (!$mentor) {
        throw new Exception("Mentor profile not found.");
    }
    $mentor_id = $mentor['id'];
} catch (Exception $e) {
    error_log("Mentor check failed: " . $e->getMessage());
    header("Location: ../dashboard.php?error=An internal error occurred.");
    exit();
}

// Fetch mentor's courses
$courses = [];
$error_message = $_GET['error'] ?? '';
$success_message = $_GET['message'] ?? '';

try {
    $stmt = $pdo->prepare("
        SELECT
            c.id AS course_id,
            c.title,
            c.price,
            c.difficulty,
            c.image_url,  -- Added image_url
            i.name AS instrument_name
        FROM courses c
        JOIN instruments i ON c.instrument_id = i.id
        WHERE c.mentor_id = ?
        ORDER BY c.title ASC
    ");
    $stmt->execute([$mentor_id]);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching mentor's courses: " . $e->getMessage());
    $error_message = "Error loading your courses.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Courses</title>
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
            <a href="../mentor_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="courses.php" class="active"><i class="fas fa-book-open"></i> My Courses</a>
            <a href="../profile.php"><i class="fas fa-user"></i> Profile</a>
            <a href="#" class="disabled"><i class="fas fa-calendar-alt"></i> Availability (Soon)</a>
            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </div>

    <div class="main-content">
        <header class="dashboard-header">
            <h1>Manage Your Courses</h1>
            <div class="header-actions">
                <a href="add_course.php" class="btn-primary"><i class="fas fa-plus"></i> Add New Course</a>
            </div>
        </header>

        <main>
            <?php if ($error_message): ?>
                <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            <?php if ($success_message): ?>
                <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>

            <div class="table-container">
                <?php if (empty($courses)): ?>
                    <div class="info-message">
                        You haven't created any courses yet. Click "Add New Course" to get started.
                    </div>
                <?php else: ?>
                    <table class="management-table">
                        <thead>
                            <tr>
                                <th>Course Title</th>
                                <th>Instrument</th>
                                <th>Difficulty</th>
                                <th>Price</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($courses as $course): ?>
                                <tr>
                                    <td data-label="Title" style="display: flex; align-items: center; gap: 10px;">
                                        <?php if (!empty($course['image_url'])): ?>
                                            <img src="../../<?php echo htmlspecialchars($course['image_url']); ?>" alt="Course Image" style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                        <?php else: ?>
                                            <i class="fas fa-music" style="font-size: 24px; color: #007bff;"></i>
                                        <?php endif; ?>
                                        <span><?php echo htmlspecialchars($course['title']); ?></span>
                                    </td>
                                    <td data-label="Instrument"><?php echo htmlspecialchars($course['instrument_name']); ?></td>
                                    <td data-label="Difficulty"><?php echo htmlspecialchars(ucfirst($course['difficulty'])); ?></td>
                                    <td data-label="Price">$<?php echo htmlspecialchars(number_format($course['price'], 2)); ?></td>
                                    <td data-label="Actions" class="actions-cell">
                                        <a href="lessons.php?course_id=<?php echo $course['course_id']; ?>" class="btn-action btn-view" title="View Lessons"><i class="fas fa-list-ul"></i></a>
                                        <a href="edit_course.php?course_id=<?php echo $course['course_id']; ?>" class="btn-action btn-edit" title="Edit Course"><i class="fas fa-edit"></i></a>
                                        <a href="../api/mentor/delete_course.php?course_id=<?php echo $course['course_id']; ?>" class="btn-action btn-delete" title="Delete Course" onclick="return confirm('Are you sure you want to delete this course and all its lessons? This action cannot be undone.');"><i class="fas fa-trash-alt"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </main>
    </div>

</body>
</html>
