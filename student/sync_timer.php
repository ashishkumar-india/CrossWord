<?php
require_once '../config.php';
require_once '../functions/helpers.php';

header('Content-Type: application/json');

// Check authentication
if (!isLoggedIn('student')) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$student = getCurrentUser('student');
$student_id = $student['id'];
$puzzle_id = isset($_GET['puzzle_id']) ? intval($_GET['puzzle_id']) : 0;

if ($puzzle_id === 0) {
    echo json_encode(['error' => 'Invalid puzzle']);
    exit;
}

// Get stored start time from session
$session_key = "puzzle_start_time_{$puzzle_id}_{$student_id}";
if (!isset($_SESSION[$session_key])) {
    echo json_encode(['error' => 'No active session']);
    exit;
}

// Get puzzle time limit
$stmt = $conn->prepare("SELECT time_limit FROM puzzles WHERE id = ?");
$stmt->bind_param("i", $puzzle_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['error' => 'Puzzle not found']);
    exit;
}

$puzzle = $result->fetch_assoc();

$start_time = $_SESSION[$session_key];
$time_limit = $puzzle['time_limit'] * 60;
$current_time = time();
$elapsed_time = $current_time - $start_time;
$remaining_time = max(0, $time_limit - $elapsed_time);

echo json_encode([
    'success' => true,
    'remaining_time' => $remaining_time,
    'server_time' => $current_time,
    'elapsed_time' => $elapsed_time
]);
?>
