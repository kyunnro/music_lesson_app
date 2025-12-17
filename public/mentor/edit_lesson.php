<?php
session_start();
require_once '../../includes/db_connect.php';
require_once '../../includes/functions.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];

// Redirect if not a mentor
if ($role !== 'mentor') {
    header("Location: ../dashboard.php?error=Access denied. You are not a mentor.");
    exit();
}

$mentor_id = null;
try {
    $stmt = $pdo->prepare("SELECT id FROM mentors WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $mentor_data = $stmt->fetch();
    if ($mentor_data) {
        $mentor_id = $mentor_data['id'];
    } else {
        header("Location: ../dashboard.php?error=Mentor profile not found.");
        exit();
    }
} catch (PDOException $e) {
    error_log("Error fetching mentor ID: " . $e->getMessage());
    header("Location: ../dashboard.php?error=Database error.");
    exit();
}

$lesson_id = filter_input(INPUT_GET, 'lesson_id', FILTER_VALIDATE_INT);
if (!$lesson_id) {
    header("Location: courses.php?error=No lesson specified for editing."); // Fallback to courses page
    exit();
}

$lesson = null;
$course = null;
try {
    // Fetch lesson details, ensuring its course belongs to this mentor
    $stmt_lesson = $pdo->prepare("
        SELECT l.id, l.course_id, l.title, l.description, l.video_url, l.materials_url, l.quiz_data, l.lesson_order, c.title AS course_title
        FROM lessons l
        JOIN courses c ON l.course_id = c.id
        WHERE l.id = ? AND l.mentor_id = ?
    ");
    $stmt_lesson->execute([$lesson_id, $mentor_id]);
    $lesson = $stmt_lesson->fetch(PDO::FETCH_ASSOC);

    if (!$lesson) {
        header("Location: courses.php?error=Lesson not found or you do not own its course.");
        exit();
    }
    $course = ['id' => $lesson['course_id'], 'title' => $lesson['course_title']];

} catch (PDOException $e) {
    error_log("Error fetching lesson for editing: " . $e->getMessage());
    header("Location: courses.php?error=Database error loading lesson.");
    exit();
}

// Handle messages from lesson actions
$message = $_GET['message'] ?? '';
$error = $_GET['error'] ?? '';

$quiz_example = json_encode([
    [
        "question" => "What is the capital of France?",
        "options" => ["London", "Paris", "Rome", "Berlin"],
        "answer" => "Paris"
    ],
    [
        "question" => "Which instrument has 6 strings?",
        "options" => ["Piano", "Drums", "Guitar", "Violin"],
        "answer" => "Guitar"
    ]
], JSON_PRETTY_PRINT);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Lesson - Mentor Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</head>
<body>
    <div class="container wide-container">
        <div class="logout-button">
            <a href="../logout.php" class="btn"><i class="fas fa-sign-out-alt icon"></i>Logout</a>
        </div>

        <h1><i class="fas fa-edit icon"></i>Edit Lesson: <?php echo htmlspecialchars($lesson['title']); ?> (Course: <?php echo htmlspecialchars($course['title']); ?>)</h1>
        <p>Modify the details of your lesson below.</p>

        <?php if ($error): ?>
            <div class="message error">
                <i class="fas fa-exclamation-circle icon"></i><?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="message success">
                <i class="fas fa-check-circle icon"></i><?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form action="../api/mentor/process_lesson.php" method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($course['id']); ?>">
            <input type="hidden" name="lesson_id" value="<?php echo htmlspecialchars($lesson['id']); ?>">
            <div class="form-group">
                <label for="title"><i class="fas fa-heading icon"></i>Lesson Title</label>
                <input type="text" id="title" name="title" class="form-control" value="<?php echo htmlspecialchars($lesson['title']); ?>" required>
            </div>
            <div class="form-group">
                <label for="description"><i class="fas fa-align-left icon"></i>Description</label>
                <textarea id="description" name="description" class="form-control" rows="5" required><?php echo htmlspecialchars($lesson['description']); ?></textarea>
            </div>
            <div class="form-group">
                <label for="video_url"><i class="fas fa-video icon"></i>Video URL</label>
                <input type="url" id="video_url" name="video_url" class="form-control" value="<?php echo htmlspecialchars($lesson['video_url']); ?>" placeholder="e.g., https://youtube.com/watch?v=..." required>
            </div>
            <div class="form-group">
                <label for="materials_url"><i class="fas fa-file-alt icon"></i>Materials URL (Optional)</label>
                <input type="url" id="materials_url" name="materials_url" class="form-control" value="<?php echo htmlspecialchars($lesson['materials_url']); ?>" placeholder="e.g., https://example.com/materials.pdf">
            </div>
            <div class="form-group">
                <label for="quiz_data"><i class="fas fa-question-circle icon"></i>Quiz Data (JSON Format, Optional)</label>
                <textarea id="quiz_data" name="quiz_data" class="form-control" rows="8" placeholder='<?php echo htmlspecialchars($quiz_example); ?>'><?php echo htmlspecialchars($lesson['quiz_data']); ?></textarea>
                <small class="form-text text-muted">Provide quiz questions in JSON format. Example shown in placeholder.</small>
            </div>
            <div class="form-group">
                <label for="lesson_order"><i class="fas fa-sort-numeric-up icon"></i>Lesson Order</label>
                <input type="number" id="lesson_order" name="lesson_order" class="form-control" min="1" value="<?php echo htmlspecialchars($lesson['lesson_order']); ?>" required>
            </div>
            <button type="submit" class="btn btn-block"><i class="fas fa-save icon"></i>Update Lesson</button>
        </form>

        <p style="margin-top: 20px;"><a href="lessons.php?course_id=<?php echo htmlspecialchars($course['id']); ?>"><i class="fas fa-arrow-left icon"></i>Back to Lessons</a></p>
    </div>
</body>
</html>
