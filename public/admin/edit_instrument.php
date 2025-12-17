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
$instrument_to_edit = null;
$edit_instrument_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$edit_instrument_id) {
    header("Location: instruments.php?error=No instrument specified.");
    exit();
}

// Fetch instrument data
try {
    $stmt = $pdo->prepare("SELECT id, name, icon_class FROM instruments WHERE id = ?");
    $stmt->execute([$edit_instrument_id]);
    $instrument_to_edit = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$instrument_to_edit) {
        header("Location: instruments.php?error=Instrument not found.");
        exit();
    }
} catch (PDOException $e) {
    error_log("Error fetching instrument for editing: " . $e->getMessage());
    header("Location: instruments.php?error=Database error loading instrument.");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Instrument</title>
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
            <a href="instruments.php" class="active"><i class="fas fa-guitar"></i> Manage Instruments</a>
            <a href="bookings.php"><i class="fas fa-calendar-check"></i> All Bookings</a>
            <a href="courses.php"><i class="fas fa-book"></i> All Courses</a>
            <a href="../profile.php"><i class="fas fa-user"></i> Profile</a>
            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </div>

    <div class="main-content">
        <header class="dashboard-header">
            <h1>Edit Instrument: <?php echo htmlspecialchars($instrument_to_edit['name']); ?></h1>
            <p>Modify the details of the instrument.</p>
        </header>

        <main>
            <div class="profile-card" style="max-width: 600px; margin: auto;">
                <h3><i class="fas fa-edit"></i> Instrument Details</h3>
                
                <?php if ($error_message): ?>
                    <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>
                <?php if ($success_message): ?>
                    <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
                <?php endif; ?>

                <form action="../api/admin/process_instrument.php" method="POST" class="modern-form">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($instrument_to_edit['id']); ?>">
                    
                    <div class="form-group-profile">
                        <label for="name">Instrument Name</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($instrument_to_edit['name']); ?>" required>
                    </div>
                    
                    <div class="form-group-profile">
                        <label for="icon_class">Font Awesome Icon Class</label>
                        <input type="text" id="icon_class" name="icon_class" value="<?php echo htmlspecialchars($instrument_to_edit['icon_class']); ?>" required>
                        <small>Find free icons at <a href="https://fontawesome.com/search?m=free" target="_blank">Font Awesome</a>. Example: `fas fa-drum`.</small>
                    </div>
                    
                    <div class="form-actions" style="text-align: right; margin-top: 2rem;">
                        <a href="instruments.php" class="btn-secondary">Cancel</a>
                        <button type="submit" class="btn-submit">Save Changes</button>
                    </div>
                </form>
            </div>
        </main>
    </div>

</body>
</html>
