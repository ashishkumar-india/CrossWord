<?php
require_once '../config.php';
require_once '../functions/helpers.php';

header('Content-Type: application/json');

// Check if logged in
if (!isLoggedIn('student')) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

$student = getCurrentUser('student');
$student_id = $student['id'];

// Get JSON data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || !isset($data['attempt_id']) || !isset($data['answers'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit();
}

$attempt_id = intval($data['attempt_id']);
$answers = $data['answers']; // This is an object/array

// Verify this attempt belongs to this student
$check_stmt = $conn->prepare("SELECT id FROM attempts WHERE id = ? AND student_id = ?");
$check_stmt->bind_param("ii", $attempt_id, $student_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid attempt']);
    exit();
}

// Update answers in database
$answers_json = json_encode($answers);
$update_stmt = $conn->prepare("UPDATE attempts SET answers = ? WHERE id = ?");
$update_stmt->bind_param("si", $answers_json, $attempt_id);

if ($update_stmt->execute()) {
    echo json_encode([
        'success' => true,
        'saved_count' => count((array)$answers),
        'timestamp' => time()
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database update failed']);
}
?>
