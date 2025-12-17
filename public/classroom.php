<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// Redirect if not logged in or course_id not provided
if (!isset($_SESSION['user_id']) || !isset($_GET['course_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$course_id = filter_input(INPUT_GET, 'course_id', FILTER_VALIDATE_INT);

if (!$course_id) {
    header("Location: my_courses.php"); // Redirect if invalid course_id
    exit();
}

$course = null;
$lessons = [];
$error_message = '';

try {
    // Fetch course details
    $stmt_course = $pdo->prepare("
        SELECT c.title AS course_title, c.description AS course_description,
               u.username AS mentor_name, i.name AS instrument_name
        FROM courses c
        JOIN mentors m ON c.mentor_id = m.id
        JOIN users u ON m.user_id = u.id
        JOIN instruments i ON c.instrument_id = i.id
        WHERE c.id = ?
    ");
    $stmt_course->execute([$course_id]);
    $course = $stmt_course->fetch(PDO::FETCH_ASSOC);

    if (!$course) {
        $error_message = "Course not found.";
    } else {
        // Fetch lessons for the course
        // Note: For quiz_data, it's stored as TEXT/JSON in the DB.
        // In a real app, you might have a separate 'quizzes' table
        $stmt_lessons = $pdo->prepare("
            SELECT id, title, description, video_url, materials_url, quiz_data
            FROM lessons
            WHERE course_id = ?
            ORDER BY lesson_order ASC, id ASC
        ");
        $stmt_lessons->execute([$course_id]);
        $lessons = $stmt_lessons->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    error_log("Error fetching course/lesson details: " . $e->getMessage());
    $error_message = "An error occurred while loading the course.";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $course ? htmlspecialchars($course['course_title']) : 'Classroom'; ?> - Music Lesson App</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/output.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</head>
<body class="bg-gray-100 flex flex-col min-h-screen font-sans antialiased">
    <div class="relative w-full max-w-7xl mx-auto sm:px-6 lg:px-8 py-4">
        <div class="fixed top-4 right-4 z-10">
            <a href="logout.php" class="inline-flex items-center bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-md transition duration-200 ease-in-out">
                <i class="fas fa-sign-out-alt mr-2"></i>Logout
            </a>
        </div>
        <?php if ($error_message): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mt-4 mb-4" role="alert">
                <i class="fas fa-exclamation-circle inline-flex items-center mr-2"></i><?php echo htmlspecialchars($error_message); ?>
            </div>
            <p class="mt-4 text-center"><a href="my_courses.php" class="text-blue-500 hover:text-blue-700 hover:underline inline-flex items-center"><i class="fas fa-arrow-left mr-2"></i>Back to My Courses</a></p>
        <?php else: ?>
            <h1 class="text-3xl font-bold mb-2 text-gray-800"><i class="fas fa-book-reader mr-2"></i> <?php echo htmlspecialchars($course['course_title']); ?></h1>
            <p class="text-gray-700 mb-1"><strong>Mentor:</strong> <?php echo htmlspecialchars($course['mentor_name']); ?></p>
            <p class="text-gray-700 mb-4"><strong>Instrument:</strong> <?php echo htmlspecialchars($course['instrument_name']); ?></p>
            <p class="text-gray-600 leading-relaxed"><?php echo nl2br(htmlspecialchars($course['course_description'])); ?></p>

            <div class="mt-8 space-y-6">
                <?php if (empty($lessons)): ?>
                    <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative mt-4" role="alert">
                        <i class="fas fa-info-circle inline-flex items-center mr-2"></i>No lessons found for this course yet.
                    </div>
                <?php else: ?>
                    <?php foreach ($lessons as $lesson): ?>
                        <div class="bg-white p-6 rounded-lg shadow-md">
                            <h2 class="text-2xl font-semibold mb-4 text-gray-700 border-b-2 border-green-500 pb-2"><i class="fas fa-graduation-cap mr-2 text-green-500"></i><?php echo htmlspecialchars($lesson['title']); ?></h2>
                            <p class="text-gray-600 mb-4 leading-relaxed"><?php echo nl2br(htmlspecialchars($lesson['description'])); ?></p>

                            <?php if ($lesson['video_url']): ?>
                                <h3 class="text-xl font-semibold mb-3 text-gray-700"><i class="fas fa-video mr-2 text-yellow-500"></i>Video Lesson</h3>
                                <div class="relative w-full aspect-video bg-gray-900 flex items-center justify-center rounded-lg mb-4">
                                    <i class="fas fa-play-circle text-6xl text-gray-400 hover:text-yellow-400 cursor-pointer transition duration-300"></i>
                                    <span class="absolute text-gray-400 text-sm bottom-4">Video: <?php echo htmlspecialchars($lesson['video_url']); ?></span>
                                    <!-- In a real app, you would embed a video player here -->
                                </div>
                            <?php endif; ?>

                            <?php if ($lesson['quiz_data']): ?>
                                <h3 class="text-xl font-semibold mb-3 text-gray-700 mt-6"><i class="fas fa-question-circle mr-2 text-yellow-500"></i>Quiz</h3>
                                <form class="space-y-4 mt-4">
                                    <?php
                                    $quizzes = json_decode($lesson['quiz_data'], true);
                                    if (is_array($quizzes)):
                                        foreach ($quizzes as $q_index => $quiz_item):
                                            if (isset($quiz_item['question']) && isset($quiz_item['options'])):
                                    ?>
                                                <div class="bg-gray-50 p-4 rounded-md shadow-sm">
                                                    <p class="font-semibold text-gray-800 mb-2"><?php echo ($q_index + 1) . ". " . htmlspecialchars($quiz_item['question']); ?></p>
                                                    <div class="space-y-2">
                                                    <?php foreach ($quiz_item['options'] as $o_index => $option): ?>
                                                        <label class="flex items-center text-gray-700 cursor-pointer">
                                                            <input type="radio" name="lesson_<?php echo $lesson['id']; ?>_q_<?php echo $q_index; ?>" value="<?php echo htmlspecialchars($option); ?>" class="form-radio h-4 w-4 text-green-600 focus:ring-green-500 mr-2">
                                                            <?php echo htmlspecialchars($option); ?>
                                                        </label>
                                                    <?php endforeach; ?>
                                                    </div>
                                                </div>
                                    <?php
                                            endif;
                                        endforeach;
                                    else:
                                        echo "<p class=\"text-gray-500\">No quiz questions available or invalid quiz data.</p>";
                                    endif;
                                    ?>
                                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-md transition duration-200 ease-in-out inline-flex items-center" disabled><i class="fas fa-check mr-2"></i>Submit Quiz</button>
                                </form>
                            <?php endif; ?>

                            <?php if ($lesson['materials_url']): ?>
                                <p class="mt-4"><i class="fas fa-file-alt mr-2 text-blue-500"></i><a href="<?php echo htmlspecialchars($lesson['materials_url']); ?>" target="_blank" class="text-blue-500 hover:text-blue-700 hover:underline">Download Materials</a></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <p class="mt-8 text-center"><a href="my_courses.php" class="text-blue-500 hover:text-blue-700 hover:underline inline-flex items-center"><i class="fas fa-arrow-left mr-2"></i>Back to My Courses</a></p>
        <?php endif; ?>
    </div>
</body>
</html>
