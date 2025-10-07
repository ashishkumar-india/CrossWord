<?php
require_once 'config.php';
require_once 'functions/calculate_answer.php';

if (!isset($_SESSION['student_id'])) {
    die('Unauthorized');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $puzzle_id = intval($_POST['puzzle_id']);
    $attempt_id = intval($_POST['attempt_id']);
    $student_id = $_SESSION['student_id'];
    $answers = $_POST['answers'];
    $time_taken = intval($_POST['time_taken']);
    
    try {
        $conn->begin_transaction();
        
        // Get puzzle grid
        $puzzle_query = $conn->prepare("SELECT grid_data FROM puzzles WHERE id = ?");
        $puzzle_query->bind_param("i", $puzzle_id);
        $puzzle_query->execute();
        $puzzle_result = $puzzle_query->get_result();
        
        if ($puzzle_result->num_rows === 0) {
            throw new Exception("Puzzle not found");
        }
        
        $puzzle = $puzzle_result->fetch_assoc();
        
        // Calculate statistics (now includes both words and cells)
        $stats = calculateCrosswordAnswers($puzzle['grid_data'], $answers);
        
        // Update attempt with both word and cell statistics
        $update_stmt = $conn->prepare("UPDATE attempts 
                                       SET answers = ?, 
                                           time_taken = ?, 
                                           score = ?,
                                           correct_answers = ?,
                                           wrong_answers = ?,
                                           total_questions = ?,
                                           correct_cells = ?,
                                           wrong_cells = ?,
                                           total_cells = ?,
                                           attempt_status = 'completed',
                                           locked = 1,
                                           completed_at = NOW()
                                       WHERE id = ? AND student_id = ?");
        
        // FIXED: 11 parameters - s i d i i i i i i i i (added one more 'i')
        $update_stmt->bind_param("sidiiiiiiii", 
            $answers,                  // s - string
            $time_taken,               // i - integer
            $stats['score'],           // d - double
            $stats['correct'],         // i - correct words
            $stats['wrong'],           // i - wrong words
            $stats['total'],           // i - total words
            $stats['correct_cells'],   // i - correct cells
            $stats['wrong_cells'],     // i - wrong cells
            $stats['total_cells'],     // i - total cells
            $attempt_id,               // i - integer
            $student_id                // i - integer
        );
        
        if (!$update_stmt->execute()) {
            throw new Exception("Failed to save: " . $update_stmt->error);
        }
        
        // Clear timer session
        unset($_SESSION["puzzle_start_time_{$puzzle_id}_{$student_id}"]);
        
        $conn->commit();
        
        $_SESSION['message'] = "Puzzle submitted successfully! Score: {$stats['score']}%";
        header("Location: student/view_result.php?puzzle={$puzzle_id}");
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Submission Error: " . $e->getMessage());
        $_SESSION['error'] = "Failed to submit: " . $e->getMessage();
        header("Location: student/student_dashboard.php");
        exit();
    }
} else {
    header("Location: student/student_dashboard.php");
    exit();
}
?>
