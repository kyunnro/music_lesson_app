<?php
session_start();
require_once '../../includes/db_connect.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $mentor_id = filter_input(INPUT_POST, 'mentor_id', FILTER_VALIDATE_INT);
    $duration_minutes = filter_input(INPUT_POST, 'duration_minutes', FILTER_VALIDATE_INT);

    if (!$mentor_id || !$duration_minutes) {
        echo json_encode(['success' => false, 'message' => 'Invalid input parameters.']);
        exit();
    }

    try {
        $stmt = $pdo->prepare("SELECT hourly_rate FROM mentors WHERE id = ?");
        $stmt->execute([$mentor_id]);
        $mentor = $stmt->fetch();

        if ($mentor) {
            $hourly_rate = (float) $mentor['hourly_rate'];
            $price = ($hourly_rate / 60) * $duration_minutes;
            echo json_encode(['success' => true, 'price' => number_format($price, 2)]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Mentor not found.']);
        }
    } catch (PDOException $e) {
        error_log("Price Calculation Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error during price calculation.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
