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

// Fetch all instruments
$instruments = [];
try {
    $stmt = $pdo->query("SELECT id, name, icon_class FROM instruments ORDER BY name ASC");
    $instruments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching instruments: " . $e->getMessage());
    $error_message = "Error loading instruments.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Instruments</title>
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
            <h1>Manage Instruments</h1>
            <div class="header-actions">
                <a href="add_instrument.php" class="btn-primary"><i class="fas fa-plus"></i> Add New Instrument</a>
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
                <table class="management-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Icon Class</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($instruments)): ?>
                            <tr>
                                <td colspan="4">No instruments found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($instruments as $instrument): ?>
                                <tr>
                                    <td data-label="ID"><?php echo htmlspecialchars($instrument['id']); ?></td>
                                    <td data-label="Name"><i class="<?php echo htmlspecialchars($instrument['icon_class']); ?> fa-fw"></i> <?php echo htmlspecialchars($instrument['name']); ?></td>
                                    <td data-label="Icon Class"><?php echo htmlspecialchars($instrument['icon_class']); ?></td>
                                    <td data-label="Actions" class="actions-cell">
                                        <a href="edit_instrument.php?id=<?php echo $instrument['id']; ?>" class="btn-action btn-edit" title="Edit Instrument"><i class="fas fa-edit"></i></a>
                                        <a href="../api/admin/delete_instrument.php?id=<?php echo $instrument['id']; ?>" class="btn-action btn-delete" title="Delete Instrument" onclick="return confirm('Are you sure you want to delete this instrument? This may affect existing courses and bookings.');"><i class="fas fa-trash-alt"></i></a>
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
