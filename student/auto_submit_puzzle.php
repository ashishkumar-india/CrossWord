<?php
require_once '../config.php';
require_once '../functions/helpers.php';
require_once '../functions/calculate_answer.php';

header('Content-Type: application/json');

// Check if logged in
if (!isLoggedIn('student')) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['puzzle_id']) || !isset($input['attempt_id']) || !isset($input['answers'])) {
    echo json_encode(['success' => false, 'error' => 'Missing data']);
    exit;
}

$student = getCurrentUser('student');
$student_id = $student['id'];
$puzzle_id = intval($input['puzzle_id']);
$attempt_id = intval($input['attempt_id']);
$answers = $input['answers'];
$time_taken = intval($input['time_taken']);

// Verify attempt belongs to this student
$verify_stmt = $conn->prepare("SELECT id FROM attempts WHERE id = ? AND student_id = ? AND puzzle_id = ?");
$verify_stmt->bind_param("iii", $attempt_id, $student_id, $puzzle_id);
$verify_stmt->execute();
$verify_result = $verify_stmt->get_result();

if ($verify_result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid attempt']);
    exit;
}

// Get puzzle data
$puzzle_stmt = $conn->prepare("SELECT grid_data, correct_answers FROM puzzles WHERE id = ?");
$puzzle_stmt->bind_param("i", $puzzle_id);
$puzzle_stmt->execute();
$puzzle_result = $puzzle_stmt->get_result();

if ($puzzle_result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Puzzle not found']);
    exit;
}

$puzzle = $puzzle_result->fetch_assoc();
$correct_answers = json_decode($puzzle['correct_answers'], true);

// Calculate score
$correct_count = 0;
$wrong_count = 0;
$total = count($correct_answers);

foreach ($correct_answers as $key => $correct_value) {
    if (isset($answers[$key]) && strtoupper(trim($answers[$key])) === strtoupper(trim($correct_value))) {
        $correct_count++;
    } else {
        $wrong_count++;
    }
}

$score = $total > 0 ? round(($correct_count / $total) * 100, 2) : 0;

// Update attempt
$answers_json = json_encode($answers);
$update_stmt = $conn->prepare("UPDATE attempts SET 
                                answers = ?, 
                                time_taken = ?, 
                                score = ?, 
                                correct_answers = ?, 
                                wrong_answers = ?, 
                                attempt_status = 'completed', 
                                completed_at = NOW(),
                                locked = 1
                                WHERE id = ?");
$update_stmt->bind_param("sdiiii", $answers_json, $time_taken, $score, $correct_count, $wrong_count, $attempt_id);

if ($update_stmt->execute()) {
    // Clear timer session
    $session_key = "puzzle_start_time_{$puzzle_id}_{$student_id}";
    unset($_SESSION[$session_key]);
    
    echo json_encode([
        'success' => true,
        'score' => $score,
        'correct' => $correct_count,
        'wrong' => $wrong_count,
        'total' => $total
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to save']);
}
?>
