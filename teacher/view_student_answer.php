<?php
require_once '../config.php';
require_once '../functions/helpers.php';

// Check if logged in
if (!isLoggedIn('teacher')) {
    redirect('auth/teacher_login.php');
}

$teacher = getCurrentUser('teacher');
$teacher_id = $teacher['id'];

// Get attempt ID
if (!isset($_GET['attempt_id'])) {
    redirect('manage_students.php');
}

$attempt_id = intval($_GET['attempt_id']);

// Get attempt details
$stmt = $conn->prepare("
    SELECT 
        a.*,
        s.name as student_name,
        s.email as student_email,
        p.title as puzzle_title,
        p.grid_data,
        p.teacher_id
    FROM attempts a
    INNER JOIN students s ON a.student_id = s.id
    INNER JOIN puzzles p ON a.puzzle_id = p.id
    WHERE a.id = ? AND p.teacher_id = ?
");
$stmt->bind_param("ii", $attempt_id, $teacher_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = 'Attempt not found or access denied.';
    redirect('manage_students.php');
}

$attempt = $result->fetch_assoc();
$grid_data = json_decode($attempt['grid_data'], true);
$student_answers = json_decode($attempt['answers'], true);

// Function to extract and analyze words
function analyzeWords($grid_data, $student_answers) {
    $words = [
        'across' => [],
        'down' => []
    ];
    
    if (!is_array($grid_data)) return $words;
    
    $rows = count($grid_data);
    $cols = count($grid_data[0]);
    
    // Extract ACROSS words
    for ($i = 0; $i < $rows; $i++) {
        $currentWord = [];
        
        for ($j = 0; $j < $cols; $j++) {
            $cell = $grid_data[$i][$j];
            
            if (!isset($cell['isBlack']) || !$cell['isBlack']) {
                $currentWord[] = [
                    'row' => $i,
                    'col' => $j,
                    'correct' => $cell['letter'] ?? '',
                    'student' => $student_answers["$i-$j"] ?? '',
                    'number' => $cell['number'] ?? null
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
            $cell = $grid_data[$i][$j];
            
            if (!isset($cell['isBlack']) || !$cell['isBlack']) {
                $currentWord[] = [
                    'row' => $i,
                    'col' => $j,
                    'correct' => $cell['letter'] ?? '',
                    'student' => $student_answers["$i-$j"] ?? '',
                    'number' => $cell['number'] ?? null
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
    
    return $words;
}

function isWordCorrect($word) {
    foreach ($word as $cell) {
        if (empty($cell['student']) || 
            strtoupper($cell['student']) !== strtoupper($cell['correct'])) {
            return false;
        }
    }
    return true;
}

function getWordString($word, $type = 'correct') {
    $str = '';
    foreach ($word as $cell) {
        $str .= $type === 'correct' ? $cell['correct'] : $cell['student'];
    }
    return $str;
}

$words = analyzeWords($grid_data, $student_answers);
$correct_words = 0;
$wrong_words = 0;
$incomplete_words = 0;

foreach (array_merge($words['across'], $words['down']) as $word) {
    $hasEmpty = false;
    foreach ($word as $cell) {
        if (empty($cell['student'])) {
            $hasEmpty = true;
            break;
        }
    }
    
    if ($hasEmpty) {
        $incomplete_words++;
    } elseif (isWordCorrect($word)) {
        $correct_words++;
    } else {
        $wrong_words++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Student Answer</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <style>
        .answer-header {
            background: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }

        .stat-card {
            background: linear-gradient(135deg, #f9fafb, #ffffff);
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            border: 2px solid #e5e7eb;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 13px;
            color: #6b7280;
            text-transform: uppercase;
        }

        .grid-section {
            background: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .grid-wrapper {
            display: inline-block;
            border: 3px solid #6366f1;
            border-radius: 10px;
            overflow: hidden;
            margin: 20px 0;
        }

        .grid-row {
            display: flex;
            line-height: 0;
        }

        .grid-cell {
            width: 40px;
            height: 40px;
            border: 1px solid #d1d5db;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            font-weight: 600;
            font-size: 18px;
        }

        .grid-cell.black {
            background: #1f2937;
        }

        .grid-cell.correct {
            background: #d1fae5;
            color: #065f46;
            border: 2px solid #10b981;
        }

        .grid-cell.wrong {
            background: #fee2e2;
            color: #991b1b;
            border: 2px solid #ef4444;
        }

        .grid-cell.empty {
            background: #f3f4f6;
            color: #9ca3af;
        }

        .cell-number {
            position: absolute;
            top: 2px;
            left: 3px;
            font-size: 10px;
            color: #6366f1;
            font-weight: 700;
        }

        .correct-answer-corner {
            position: absolute;
            bottom: 2px;
            right: 2px;
            font-size: 10px;
            color: #059669;
            font-weight: 700;
            background: white;
            padding: 1px 3px;
            border-radius: 3px;
        }

        .words-section {
            background: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .word-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .word-item {
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid;
        }

        .word-item.correct {
            background: #d1fae5;
            border-color: #10b981;
        }

        .word-item.wrong {
            background: #fee2e2;
            border-color: #ef4444;
        }

        .word-item.incomplete {
            background: #fef3c7;
            border-color: #f59e0b;
        }

        .word-number {
            font-weight: 700;
            color: #6366f1;
            margin-right: 5px;
        }

        .word-text {
            font-size: 18px;
            font-weight: 700;
            margin: 5px 0;
        }

        .word-status {
            font-size: 12px;
            margin-top: 5px;
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

    <div class="container" style="padding: 30px 20px;">
        <div class="answer-header">
            <h2 style="color: #6366f1; margin-bottom: 10px;">📝 Student Answer Review</h2>
            <p><strong>Student:</strong> <?php echo htmlspecialchars($attempt['student_name']); ?> (<?php echo htmlspecialchars($attempt['student_email']); ?>)</p>
            <p><strong>Puzzle:</strong> <?php echo htmlspecialchars($attempt['puzzle_title']); ?></p>
            <p><strong>Completed:</strong> <?php echo date('M d, Y H:i', strtotime($attempt['completed_at'])); ?></p>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value" style="color: #6366f1;">
                        <?php echo number_format($attempt['score'], 1); ?>%
                    </div>
                    <div class="stat-label">Score</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" style="color: #10b981;">
                        <?php echo $correct_words; ?>
                    </div>
                    <div class="stat-label">Correct Words</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" style="color: #ef4444;">
                        <?php echo $wrong_words; ?>
                    </div>
                    <div class="stat-label">Wrong Words</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" style="color: #f59e0b;">
                        <?php echo $incomplete_words; ?>
                    </div>
                    <div class="stat-label">Incomplete</div>
                </div>
            </div>
        </div>

        <!-- Crossword Grid -->
        <div class="grid-section">
            <h3 style="color: #6366f1;">📊 Student's Crossword Grid</h3>
            <div style="text-align: center;">
                <div class="grid-wrapper" id="result-grid"></div>
            </div>
        </div>

        <!-- Word Analysis - Across -->
        <div class="words-section">
            <h3 style="color: #6366f1;">→ ACROSS Words</h3>
            <div class="word-list">
                <?php foreach ($words['across'] as $word): ?>
                    <?php
                    $hasEmpty = false;
                    foreach ($word as $cell) {
                        if (empty($cell['student'])) {
                            $hasEmpty = true;
                            break;
                        }
                    }
                    
                    if ($hasEmpty) {
                        $status = 'incomplete';
                        $statusText = '⏸️ Incomplete';
                    } elseif (isWordCorrect($word)) {
                        $status = 'correct';
                        $statusText = '✅ Correct';
                    } else {
                        $status = 'wrong';
                        $statusText = '❌ Wrong';
                    }
                    
                    $correctWord = getWordString($word, 'correct');
                    $studentWord = getWordString($word, 'student');
                    $wordNumber = $word[0]['number'] ?? '?';
                    ?>
                    <div class="word-item <?php echo $status; ?>">
                        <div>
                            <span class="word-number"><?php echo $wordNumber; ?>.</span>
                            <span class="word-status"><?php echo $statusText; ?></span>
                        </div>
                        <div class="word-text">
                            Student: <strong><?php echo !empty($studentWord) ? htmlspecialchars($studentWord) : '(empty)'; ?></strong>
                        </div>
                        <?php if ($status === 'wrong'): ?>
                            <div style="font-size: 14px; color: #059669; margin-top: 5px;">
                                Correct: <strong><?php echo htmlspecialchars($correctWord); ?></strong>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Word Analysis - Down -->
        <div class="words-section">
            <h3 style="color: #6366f1;">↓ DOWN Words</h3>
            <div class="word-list">
                <?php foreach ($words['down'] as $word): ?>
                    <?php
                    $hasEmpty = false;
                    foreach ($word as $cell) {
                        if (empty($cell['student'])) {
                            $hasEmpty = true;
                            break;
                        }
                    }
                    
                    if ($hasEmpty) {
                        $status = 'incomplete';
                        $statusText = '⏸️ Incomplete';
                    } elseif (isWordCorrect($word)) {
                        $status = 'correct';
                        $statusText = '✅ Correct';
                    } else {
                        $status = 'wrong';
                        $statusText = '❌ Wrong';
                    }
                    
                    $correctWord = getWordString($word, 'correct');
                    $studentWord = getWordString($word, 'student');
                    $wordNumber = $word[0]['number'] ?? '?';
                    ?>
                    <div class="word-item <?php echo $status; ?>">
                        <div>
                            <span class="word-number"><?php echo $wordNumber; ?>.</span>
                            <span class="word-status"><?php echo $statusText; ?></span>
                        </div>
                        <div class="word-text">
                            Student: <strong><?php echo !empty($studentWord) ? htmlspecialchars($studentWord) : '(empty)'; ?></strong>
                        </div>
                        <?php if ($status === 'wrong'): ?>
                            <div style="font-size: 14px; color: #059669; margin-top: 5px;">
                                Correct: <strong><?php echo htmlspecialchars($correctWord); ?></strong>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <a href="manage_students.php" class="btn btn-primary">← Back to Results</a>
        </div>
    </div>

    <script>
        const gridData = <?php echo json_encode($grid_data); ?>;
        const studentAnswers = <?php echo json_encode($student_answers); ?>;

        function renderGrid() {
            const gridContainer = document.getElementById('result-grid');
            gridContainer.innerHTML = '';

            gridData.forEach((row, i) => {
                const rowDiv = document.createElement('div');
                rowDiv.className = 'grid-row';

                row.forEach((cell, j) => {
                    const cellDiv = document.createElement('div');
                    cellDiv.className = 'grid-cell';

                    if (cell.isBlack) {
                        cellDiv.classList.add('black');
                    } else {
                        const key = `${i}-${j}`;
                        const studentAnswer = studentAnswers[key] || '';
                        const correctAnswer = cell.letter || '';

                        const span = document.createElement('span');
                        span.textContent = studentAnswer || '';

                        if (!studentAnswer) {
                            cellDiv.classList.add('empty');
                        } else if (studentAnswer.toUpperCase() === correctAnswer.toUpperCase()) {
                            cellDiv.classList.add('correct');
                        } else {
                            cellDiv.classList.add('wrong');
                            
                            const correctSpan = document.createElement('span');
                            correctSpan.className = 'correct-answer-corner';
                            correctSpan.textContent = correctAnswer;
                            cellDiv.appendChild(correctSpan);
                        }

                        cellDiv.appendChild(span);

                        if (cell.number) {
                            const numberSpan = document.createElement('span');
                            numberSpan.className = 'cell-number';
                            numberSpan.textContent = cell.number;
                            cellDiv.appendChild(numberSpan);
                        }
                    }

                    rowDiv.appendChild(cellDiv);
                });

                gridContainer.appendChild(rowDiv);
            });
        }

        renderGrid();
    </script>
</body>
</html>
<script src="../assets/js/responsive.js"></script>
