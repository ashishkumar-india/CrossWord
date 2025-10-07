<?php
require_once '../config.php';
require_once '../functions/helpers.php';

// Check if logged in
if (!isLoggedIn('teacher')) {
    redirect('auth/teacher_login.php');
}

$teacher = getCurrentUser('teacher');
$teacher_id = $teacher['id'];

// Handle unlock request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unlock_attempt'])) {
    $attempt_id = intval($_POST['attempt_id']);
    
    if ($attempt_id > 0) {
        try {
            $unlock_stmt = $conn->prepare("UPDATE attempts SET locked = 0 WHERE id = ?");
            $unlock_stmt->bind_param("i", $attempt_id);
            
            if ($unlock_stmt->execute()) {
                $_SESSION['message'] = "Puzzle unlocked successfully! Student can now retry.";
            } else {
                $_SESSION['error'] = "Failed to unlock puzzle";
            }
            $unlock_stmt->close();
        } catch (Exception $e) {
            $_SESSION['error'] = "Error unlocking puzzle: " . $e->getMessage();
        }
    }
    
    header("Location: manage_students.php");
    exit();
}

// Handle publish result
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['publish_attempt'])) {
    $attempt_id = intval($_POST['attempt_id']);
    
    if ($attempt_id > 0) {
        try {
            $publish_stmt = $conn->prepare("UPDATE attempts SET result_published = 1 WHERE id = ?");
            $publish_stmt->bind_param("i", $attempt_id);
            
            if ($publish_stmt->execute()) {
                $_SESSION['message'] = "Result published successfully!";
            } else {
                $_SESSION['error'] = "Failed to publish result";
            }
            $publish_stmt->close();
        } catch (Exception $e) {
            $_SESSION['error'] = "Error publishing result: " . $e->getMessage();
        }
    }
    
    header("Location: manage_students.php");
    exit();
}

// Handle unpublish result
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unpublish_attempt'])) {
    $attempt_id = intval($_POST['attempt_id']);
    
    if ($attempt_id > 0) {
        try {
            $unpublish_stmt = $conn->prepare("UPDATE attempts SET result_published = 0 WHERE id = ?");
            $unpublish_stmt->bind_param("i", $attempt_id);
            
            if ($unpublish_stmt->execute()) {
                $_SESSION['message'] = "Result hidden successfully!";
            } else {
                $_SESSION['error'] = "Failed to hide result";
            }
            $unpublish_stmt->close();
        } catch (Exception $e) {
            $_SESSION['error'] = "Error hiding result: " . $e->getMessage();
        }
    }
    
    header("Location: manage_students.php");
    exit();
}

// Get filter values
$selected_student = isset($_GET['student']) ? intval($_GET['student']) : 0;
$selected_puzzle = isset($_GET['puzzle']) ? intval($_GET['puzzle']) : 0;
$selected_status = isset($_GET['status']) ? $_GET['status'] : 'all';

// Get all students for filter
$students_list = [];
$students_query = $conn->prepare("SELECT DISTINCT s.id, s.name FROM students s 
                                  INNER JOIN attempts a ON s.id = a.student_id 
                                  INNER JOIN puzzles p ON a.puzzle_id = p.id 
                                  WHERE p.teacher_id = ? 
                                  ORDER BY s.name");
$students_query->bind_param("i", $teacher_id);
$students_query->execute();
$students_result = $students_query->get_result();
while ($student = $students_result->fetch_assoc()) {
    $students_list[] = $student;
}

// Get all puzzles for filter
$puzzles_list = [];
$puzzles_query = $conn->prepare("SELECT id, title FROM puzzles WHERE teacher_id = ? ORDER BY created_at DESC");
$puzzles_query->bind_param("i", $teacher_id);
$puzzles_query->execute();
$puzzles_result = $puzzles_query->get_result();
while ($puzzle = $puzzles_result->fetch_assoc()) {
    $puzzles_list[] = $puzzle;
}

// Function to count words from grid
function countWordsInAttempt($grid_data, $student_answers) {
    if (!is_array($grid_data) || empty($grid_data)) {
        return ['correct' => 0, 'wrong' => 0, 'incomplete' => 0];
    }
    
    $words = ['across' => [], 'down' => []];
    $rows = count($grid_data);
    $cols = count($grid_data[0] ?? []);
    
    // Extract ACROSS words
    for ($i = 0; $i < $rows; $i++) {
        $currentWord = [];
        for ($j = 0; $j < $cols; $j++) {
            $cell = $grid_data[$i][$j] ?? [];
            if (!isset($cell['isBlack']) || !$cell['isBlack']) {
                $currentWord[] = [
                    'correct' => $cell['letter'] ?? '',
                    'student' => $student_answers["$i-$j"] ?? ''
                ];
            } else {
                if (count($currentWord) > 1) {
                    $words['across'][] = $currentWord;
                }
                $currentWord = [];
            }
        }
        if (count($currentWord) > 1) {
            $words['across'][] = $currentWord;
        }
    }
    
    // Extract DOWN words
    for ($j = 0; $j < $cols; $j++) {
        $currentWord = [];
        for ($i = 0; $i < $rows; $i++) {
            $cell = $grid_data[$i][$j] ?? [];
            if (!isset($cell['isBlack']) || !$cell['isBlack']) {
                $currentWord[] = [
                    'correct' => $cell['letter'] ?? '',
                    'student' => $student_answers["$i-$j"] ?? ''
                ];
            } else {
                if (count($currentWord) > 1) {
                    $words['down'][] = $currentWord;
                }
                $currentWord = [];
            }
        }
        if (count($currentWord) > 1) {
            $words['down'][] = $currentWord;
        }
    }
    
    $correct = 0;
    $wrong = 0;
    $incomplete = 0;
    
    foreach (array_merge($words['across'], $words['down']) as $word) {
        $hasEmpty = false;
        $allCorrect = true;
        
        foreach ($word as $cell) {
            if (empty($cell['student'])) {
                $hasEmpty = true;
            }
            if (strtoupper($cell['student']) !== strtoupper($cell['correct'])) {
                $allCorrect = false;
            }
        }
        
        if ($hasEmpty) {
            $incomplete++;
        } elseif ($allCorrect) {
            $correct++;
        } else {
            $wrong++;
        }
    }
    
    return ['correct' => $correct, 'wrong' => $wrong, 'incomplete' => $incomplete];
}

// Get filtered attempts with word counts
$attempts = [];
try {
    $query = "
        SELECT 
            a.id as attempt_id,
            a.student_id,
            a.puzzle_id,
            a.score,
            a.time_taken,
            a.answers,
            a.attempt_status,
            a.locked,
            a.result_published,
            a.completed_at,
            s.name as student_name,
            s.email as student_email,
            s.program,
            p.title as puzzle_title,
            p.grid_data,
            p.time_limit
        FROM attempts a
        INNER JOIN students s ON a.student_id = s.id
        INNER JOIN puzzles p ON a.puzzle_id = p.id
        WHERE p.teacher_id = ?
    ";

    $params = [$teacher_id];
    $types = "i";

    if ($selected_student > 0) {
        $query .= " AND a.student_id = ?";
        $params[] = $selected_student;
        $types .= "i";
    }

    if ($selected_puzzle > 0) {
        $query .= " AND a.puzzle_id = ?";
        $params[] = $selected_puzzle;
        $types .= "i";
    }

    if ($selected_status !== 'all') {
        $query .= " AND a.attempt_status = ?";
        $params[] = $selected_status;
        $types .= "s";
    }

    $query .= " ORDER BY a.completed_at DESC, s.name ASC";

    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        // Calculate word-based statistics
        $grid_data = json_decode($row['grid_data'], true);
        $student_answers = json_decode($row['answers'], true);
        $wordStats = countWordsInAttempt($grid_data, $student_answers);
        
        $row['correct_words'] = $wordStats['correct'];
        $row['wrong_words'] = $wordStats['wrong'];
        $row['incomplete_words'] = $wordStats['incomplete'];
        
        $attempts[] = $row;
    }
    
    $stmt->close();
} catch (Exception $e) {
    error_log("Database Error: " . $e->getMessage());
    $error_message = "An error occurred while retrieving attempt data.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Student Results</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <style>
        .filter-box {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .filter-group label {
            font-size: 14px;
            font-weight: 600;
            color: #374151;
        }

        .filter-group select {
            padding: 10px 14px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            background: white;
            cursor: pointer;
            transition: all 0.3s;
        }

        .filter-group select:focus {
            border-color: #6366f1;
            outline: none;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .filter-btn {
            padding: 10px 24px;
            background: linear-gradient(135deg, #6366f1, #4338ca);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .filter-btn:hover {
            background: linear-gradient(135deg, #4338ca, #3730a3);
            transform: translateY(-2px);
        }

        .reset-btn {
            padding: 10px 24px;
            background: #6b7280;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .reset-btn:hover {
            background: #4b5563;
            transform: translateY(-2px);
        }

        .success-message {
            background: #d1fae5;
            color: #065f46;
            padding: 12px 20px;
            border-radius: 8px;
            margin: 20px 0;
            border: 1px solid #10b981;
        }
        
        .error-message {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px 20px;
            border-radius: 8px;
            margin: 20px 0;
            border: 1px solid #ef4444;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-completed {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-abandoned {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .status-in_progress {
            background: #fef3c7;
            color: #92400e;
        }
        
        .action-btn {
            padding: 6px 14px;
            font-size: 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            white-space: nowrap;
            transition: all 0.3s;
            display: inline-block;
        }
        
        .view-btn {
            background: #8b5cf6;
            color: white;
            text-decoration: none;
        }

        .view-btn:hover {
            background: #7c3aed;
            transform: translateY(-2px);
        }
        
        .unlock-btn {
            background: #10b981;
            color: white;
        }
        
        .unlock-btn:hover {
            background: #059669;
            transform: translateY(-2px);
        }
        
        .publish-btn {
            background: #3b82f6;
            color: white;
        }
        
        .publish-btn:hover {
            background: #2563eb;
            transform: translateY(-2px);
        }
        
        .unpublish-btn {
            background: #6b7280;
            color: white;
        }
        
        .unpublish-btn:hover {
            background: #4b5563;
            transform: translateY(-2px);
        }
        
        .published-badge {
            background: #dbeafe;
            color: #1e40af;
            padding: 3px 10px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .locked-badge {
            background: #fee2e2;
            color: #991b1b;
            padding: 3px 10px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .no-data {
            text-align: center;
            color: #6b7280;
            padding: 40px;
        }
        
        .score-cell {
            font-size: 18px;
            font-weight: 700;
        }
        
        .score-excellent {
            color: #10b981;
        }
        
        .score-good {
            color: #3b82f6;
        }
        
        .score-average {
            color: #f59e0b;
        }
        
        .score-poor {
            color: #ef4444;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }
        
        table th {
            background: #6366f1;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            white-space: nowrap;
        }
        
        table td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: middle;
        }
        
        table tr:hover {
            background: #f9fafb;
        }

        .results-count {
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 15px;
        }

            .btns {
    display: inline-block;
    padding: 8px 16px;
    font-size: 13px;
    font-weight: 500;
    text-align: center;
    text-decoration: none;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
    min-width: 95px;
}
        
    </style>
</head>
<body>
    <div class="navbar">
        <div class="container">
            <h1>Crossword Game</h1>
            
            <div class="hamburger" id="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </div>
            
            <nav id="navMenu">
                <a href="teacher_dashboard.php">Dashboard</a>
                <a href="create_puzzle.php">Create Puzzle</a>
                <a href="manage_puzzles.php">Manage Puzzles</a>
                <a href="view_students.php">View Students</a>
                <a href="manage_students.php">Students Result</a>
                <a href="../logout.php">Logout</a>
            </nav>
        </div>
    </div>

    <div class="container dashboard">
        <div class="welcome-section">
            <h2>Manage Student Results</h2>
            <!-- <p>Filter and manage individual puzzle attempts</p> -->
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="success-message">
                <?php 
                echo htmlspecialchars($_SESSION['message']); 
                unset($_SESSION['message']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-message">
                <?php 
                echo htmlspecialchars($_SESSION['error']); 
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <!-- Filter Box -->
        <div class="filter-box">
            <form method="GET" action="">
                <div class="filter-grid">
                    <div class="filter-group">
                        <label for="student">Filter by Student</label>
                        <select name="student" id="student">
                            <option value="0">All Students</option>
                            <?php foreach ($students_list as $student): ?>
                                <option value="<?php echo $student['id']; ?>" 
                                        <?php echo $selected_student == $student['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($student['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="puzzle"> Filter by Puzzle</label>
                        <select name="puzzle" id="puzzle">
                            <option value="0">All Puzzles</option>
                            <?php foreach ($puzzles_list as $puzzle): ?>
                                <option value="<?php echo $puzzle['id']; ?>" 
                                        <?php echo $selected_puzzle == $puzzle['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($puzzle['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="status">Filter by Status</label>
                        <select name="status" id="status">
                            <option value="all" <?php echo $selected_status == 'all' ? 'selected' : ''; ?>>All Status</option>
                            <option value="completed" <?php echo $selected_status == 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="abandoned" <?php echo $selected_status == 'abandoned' ? 'selected' : ''; ?>>Abandoned</option>
                            <option value="in_progress" <?php echo $selected_status == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>&nbsp;</label>
                        <div style="display: flex; gap: 10px;">
                            <button type="submit" class="filter-btn">Search</button>
                            <a href="manage_students.php" class="reset-btn">Reset</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="content-box">
            <h3>Puzzle Attempts</h3>
            
            <?php if (!empty($attempts)): ?>
                <p class="results-count">Showing <strong><?php echo count($attempts); ?></strong> result(s)</p>
                
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Student</th>
                                <th>Email</th>
                                <th>Program</th>
                                <th>Puzzle</th>
                                <th>Score</th>
                                <th>Correct</th>
                                <th>Wrong</th>
                                <th>Incomplete</th>
                                <th>Time</th>
                                <th>Status</th>
                                <th>Completed</th>
                                <th>Published</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attempts as $attempt): ?>
                                <tr>
                                    <td><?php echo $attempt['attempt_id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($attempt['student_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($attempt['student_email']); ?></td>
                                    <td><?php echo htmlspecialchars($attempt['program']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($attempt['puzzle_title']); ?></strong></td>
                                    <td>
                                        <?php
                                            $score = floatval($attempt['score']);
                                            $scoreClass = 'score-poor';
                                            if ($score >= 85) $scoreClass = 'score-excellent';
                                            elseif ($score >= 70) $scoreClass = 'score-good';
                                            elseif ($score >= 50) $scoreClass = 'score-average';
                                        ?>
                                        <span class="score-cell <?php echo $scoreClass; ?>">
                                            <?php echo number_format($score, 1); ?>%
                                        </span>
                                    </td>
                                    <td style="color: #10b981; font-weight: 600; font-size: 16px;">
                                        <?php echo intval($attempt['correct_words']); ?>
                                    </td>
                                    <td style="color: #ef4444; font-weight: 600; font-size: 16px;">
                                        <?php echo intval($attempt['wrong_words']); ?>
                                    </td>
                                    <td style="color: #f59e0b; font-weight: 600; font-size: 16px;">
                                        <?php echo intval($attempt['incomplete_words']); ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $time = intval($attempt['time_taken']);
                                        $mins = floor($time / 60);
                                        $secs = $time % 60;
                                        echo sprintf('%d:%02d', $mins, $secs);
                                        ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $attempt['attempt_status']; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $attempt['attempt_status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                        echo $attempt['completed_at'] ? 
                                            date('M d, Y H:i', strtotime($attempt['completed_at'])) : 
                                            'In Progress'; 
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($attempt['result_published'] == 1): ?>
                                            <span class="published-badge">✓Published</span>
                                        <?php else: ?>
                                            <span class="locked-badge">🔒Hidden</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                            <!-- View Answer Button -->
                                            <a href="view_student_answer.php?attempt_id=<?php echo $attempt['attempt_id']; ?>" 
                                               class="btn action-btn view-btn">
                                                View Answer
                                            </a>
                                            
                                            <!-- Unlock Button -->
                                            <?php if ($attempt['locked'] == 1): ?>
                                                <form method="POST" style="margin: 0;">
                                                    <input type="hidden" name="attempt_id" value="<?php echo $attempt['attempt_id']; ?>">
                                                    <button type="submit" name="unlock_attempt" class="btns action-btn unlock-btn" 
                                                            onclick="return confirm('Unlock this puzzle for retry?');">
                                                        🔓 Unlock
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            <!-- Publish/Unpublish Button -->
                                            <?php if ($attempt['attempt_status'] === 'completed'): ?>
                                                <?php if ($attempt['result_published'] == 0): ?>
                                                    <form method="POST" style="margin: 0;">
                                                        <input type="hidden" name="attempt_id" value="<?php echo $attempt['attempt_id']; ?>">
                                                        <button type="submit" name="publish_attempt" class="btns action-btn publish-btn" 
                                                                onclick="return confirm('Publish this result?');">
                                                             Publish
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <form method="POST" style="margin: 0;">
                                                        <input type="hidden" name="attempt_id" value="<?php echo $attempt['attempt_id']; ?>">
                                                        <button type="submit" name="unpublish_attempt" class="btns action-btn unpublish-btn" 
                                                                onclick="return confirm('Hide this result?');">
                                                            🔒 Hide
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="no-data">
                    No attempts found with current filters.
                </p>
            <?php endif; ?>
        </div>
    </div>

    <script src="../assets/js/responsive.js"></script>
</body>
</html>
