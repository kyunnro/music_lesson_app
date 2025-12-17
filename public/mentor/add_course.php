<?php
session_start();
require_once '../../includes/db_connect.php';
require_once '../../includes/functions.php';

// Security checks for mentor role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mentor') {
    header("Location: ../login.php?error=Access Denied");
    exit();
}

// Fetch instruments for the dropdown
$instruments = [];
$form_error = $_GET['error'] ?? '';
try {
    $stmt = $pdo->query("SELECT id, name FROM instruments ORDER BY name ASC");
    $instruments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching instruments: " . $e->getMessage());
    $form_error = "Error loading instruments list.";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Course</title>
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
            <h1>Add a New Course</h1>
            <p>Fill out the form below to create a new course for students.</p>
        </header>

        <main>
            <div class="profile-card" style="max-width: 800px; margin: auto;">
                <h3><i class="fas fa-plus-circle"></i> Course Details</h3>
                
                <?php if ($form_error): ?>
                    <div class="error-message"><?php echo htmlspecialchars($form_error); ?></div>
                <?php endif; ?>

                <form action="../api/mentor/process_course.php" method="POST" class="modern-form" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="form-group-profile">
                        <label for="title">Course Title</label>
                        <input type="text" id="title" name="title" required>
                    </div>
                    
                    <div class="form-group-profile">
                        <label for="description">Course Description</label>
                        <textarea id="description" name="description" rows="5" required></textarea>
                    </div>

                    <div class="form-row" style="grid-template-columns: 1fr 1fr 1fr; gap: 1rem; align-items: end;">
                        <div class="form-group-profile">
                            <label for="instrument_id">Instrument</label>
                            <select id="instrument_id" name="instrument_id" required>
                                <option value="">Select...</option>
                                <?php foreach ($instruments as $instrument): ?>
                                    <option value="<?php echo htmlspecialchars($instrument['id']); ?>">
                                        <?php echo htmlspecialchars($instrument['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group-profile">
                            <label for="difficulty">Difficulty</label>
                            <select id="difficulty" name="difficulty" required>
                                <option value="">Select...</option>
                                <option value="beginner">Beginner</option>
                                <option value="intermediate">Intermediate</option>
                                <option value="advanced">Advanced</option>
                            </select>
                        </div>

                        <div class="form-group-profile">
                            <label for="price">Price ($)</label>
                            <input type="number" step="0.01" min="0" id="price" name="price" required>
                        </div>
                    </div>

                    <div class="form-group-profile">
                        <label for="course_image">Course Image (Optional)</label>
                        <input type="file" id="course_image" name="course_image" accept="image/*">
                    </div>
                    
                    <div class="form-actions" style="text-align: right; margin-top: 2rem;">
                         <a href="courses.php" class="btn-secondary">Cancel</a>
                        <button type="submit" class="btn-submit">Save Course</button>
                    </div>
                </form>
            </div>
        </main>
    </div>

</body>
</html>
