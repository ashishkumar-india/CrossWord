<?php
require_once '../config.php';
require_once '../functions/helpers.php';

// Check if logged in
if (!isLoggedIn('student')) {
    header("Location: ../auth/student_login.php");
    exit();
}

checkStudentActive($conn, $_SESSION['student_id']);

$student = getCurrentUser('student');
$student_id = $student['id'];

// Function to extract words from grid
function extractWords($grid_data, $student_answers) {
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
        $startCol = null;
        
        for ($j = 0; $j < $cols; $j++) {
            $cell = $grid_data[$i][$j];
            
            if (!isset($cell['isBlack']) || !$cell['isBlack']) {
                if ($startCol === null) {
                    $startCol = $j;
                }
                $currentWord[] = [
                    'row' => $i,
                    'col' => $j,
                    'correct' => $cell['letter'] ?? '',
                    'student' => $student_answers["$i-$j"] ?? ''
                ];
            } else {
                // Black cell - end of word
                if (count($currentWord) > 1) {
                    $words['across'][] = $currentWord;
                }
                $currentWord = [];
                $startCol = null;
            }
        }
        
        // End of row
        if (count($currentWord) > 1) {
            $words['across'][] = $currentWord;
        }
    }
    
    // Extract DOWN words
    for ($j = 0; $j < $cols; $j++) {
        $currentWord = [];
        $startRow = null;
        
        for ($i = 0; $i < $rows; $i++) {
            $cell = $grid_data[$i][$j];
            
            if (!isset($cell['isBlack']) || !$cell['isBlack']) {
                if ($startRow === null) {
                    $startRow = $i;
                }
                $currentWord[] = [
                    'row' => $i,
                    'col' => $j,
                    'correct' => $cell['letter'] ?? '',
                    'student' => $student_answers["$i-$j"] ?? ''
                ];
            } else {
                // Black cell - end of word
                if (count($currentWord) > 1) {
                    $words['down'][] = $currentWord;
                }
                $currentWord = [];
                $startRow = null;
            }
        }
        
        // End of column
        if (count($currentWord) > 1) {
            $words['down'][] = $currentWord;
        }
    }
    
    return $words;
}

// Function to check if a word is correct
function isWordCorrect($word) {
    foreach ($word as $cell) {
        if (empty($cell['student']) || 
            strtoupper($cell['student']) !== strtoupper($cell['correct'])) {
            return false;
        }
    }
    return true;
}

// If specific puzzle requested
if (isset($_GET['puzzle'])) {
    $puzzle_id = intval($_GET['puzzle']);
    
    $stmt = $conn->prepare("SELECT 
                                a.*, 
                                p.title, 
                                p.grid_data, 
                                p.is_published as puzzle_published, 
                                t.name as teacher_name
                            FROM attempts a 
                            INNER JOIN puzzles p ON a.puzzle_id = p.id 
                            INNER JOIN teachers t ON p.teacher_id = t.id
                            WHERE a.student_id = ? AND a.puzzle_id = ? 
                            ORDER BY a.id DESC LIMIT 1");
    $stmt->bind_param("ii", $student_id, $puzzle_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header("Location: student_dashboard.php");
        exit();
    }
    
    $attempt = $result->fetch_assoc();
    
    if ($attempt['result_published'] == 0) {
        $show_pending = true;
    } else {
        $show_pending = false;
    }
    
    $attempts = [$attempt];
} else {
    $stmt = $conn->prepare("SELECT 
                                a.*, 
                                p.title, 
                                p.is_published as puzzle_published, 
                                t.name as teacher_name
                            FROM attempts a 
                            INNER JOIN puzzles p ON a.puzzle_id = p.id 
                            INNER JOIN teachers t ON p.teacher_id = t.id
                            WHERE a.student_id = ? AND a.result_published = 1
                            ORDER BY a.completed_at DESC");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $attempts = [];
    while ($row = $result->fetch_assoc()) {
        $attempts[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Results</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .score-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            text-align: center;
        }

        .score-card h2 {
            color: white;
            margin-bottom: 15px;
            font-size: 32px;
        }

        .score-display {
            font-size: 64px;
            font-weight: bold;
            margin: 20px 0;
            text-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
        }

        .score-info {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .score-info-item {
            background: rgba(255, 255, 255, 0.2);
            padding: 15px 25px;
            border-radius: 10px;
            backdrop-filter: blur(10px);
        }

        .score-info-item p {
            margin: 0;
            font-size: 14px;
            opacity: 0.9;
        }

        .score-info-item h4 {
            margin: 5px 0 0 0;
            font-size: 24px;
            color: white;
        }

        .grid-legend {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            padding: 15px;
            background: #f9fafb;
            border-radius: 10px;
            flex-wrap: wrap;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .legend-box {
            width: 30px;
            height: 30px;
            border: 2px solid #d1d5db;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .legend-box.correct {
            background: #d1fae5;
            color: #065f46;
        }

        .legend-box.incorrect {
            background: #fee2e2;
            color: #991b1b;
        }

        .result-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .pending-message {
            text-align: center;
            padding: 60px 20px;
        }

        .pending-message .icon {
            font-size: 80px;
            margin-bottom: 20px;
        }

        .pending-message h3 {
            color: #f59e0b;
            margin-bottom: 15px;
        }

        .pending-message p {
            color: #6b7280;
            font-size: 16px;
        }

        .table-responsive {
            overflow-x: auto;
            margin-top: 20px;
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
            border-bottom: 2px solid #e5e7eb;
        }

        table td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
        }

        @media print {
            .navbar, .result-actions {
                display: none;
            }
        }
    </style>
    <link rel="stylesheet" href="../assets/css/responsive.css">
    
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
                <a href="student_dashboard.php">Dashboard</a>
                <a href="view_result.php">My Results</a>
                <a href="../logout.php">Logout</a>
            </nav>
        </div>
    </div>

    <div class="container dashboard">
        <div class="content-box">
            <h3>My Results</h3>
            
            <?php if (isset($_GET['puzzle']) && isset($show_pending) && $show_pending): ?>
                <div class="pending-message">
                    <div class="icon">⏳</div>
                    <h3>Result Pending</h3>
                    <p>The teacher has not published this result yet.</p>
                    <p style="margin-top: 10px;">Please check back later or contact your teacher for more information.</p>
                    <div style="margin-top: 30px;">
                        <a href="view_result.php" class="btn btn-secondary">View Published Results</a>
                        <a href="student_dashboard.php" class="btn btn-primary">Back to Dashboard</a>
                    </div>
                </div>
                
            <?php elseif (isset($_GET['puzzle']) && !$show_pending): ?>
                <?php
                $attempt = $attempts[0];
                $grid_data = json_decode($attempt['grid_data'], true);
                $student_answers = json_decode($attempt['answers'], true);
                
                // Extract words (ACROSS and DOWN)
                $words = extractWords($grid_data, $student_answers);
                
                // Count correct/incorrect/incomplete WORDS
                $total_words = count($words['across']) + count($words['down']);
                $correct_words = 0;
                $incorrect_words = 0;
                $incomplete_words = 0;
                
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
                        $incomplete_words++;
                    } elseif ($allCorrect) {
                        $correct_words++;
                    } else {
                        $incorrect_words++;
                    }
                }
                ?>
                
                <div class="score-card">
                    <h2><?php echo htmlspecialchars($attempt['title']); ?></h2>
                    <p style="font-size: 16px; opacity: 0.9;">Teacher: <?php echo htmlspecialchars($attempt['teacher_name']); ?></p>
                    <p style="font-size: 14px; opacity: 0.8; margin-top: 5px;">
                        Completed: <?php echo date('M d, Y h:i A', strtotime($attempt['completed_at'])); ?>
                    </p>
                    
                    <div class="score-display">
                        <?php echo number_format($attempt['score'], 1); ?>%
                    </div>
                    
                    <div class="score-info">
                        <div class="score-info-item">
                            <p>Correct Words</p>
                            <h4><?php echo $correct_words; ?></h4>
                        </div>
                        <div class="score-info-item">
                            <p>Incorrect Words</p>
                            <h4><?php echo $incorrect_words; ?></h4>
                        </div>
                        <div class="score-info-item">
                            <p>Incomplete Words</p>
                            <h4><?php echo $incomplete_words; ?></h4>
                        </div>
                        <div class="score-info-item">
                            <p>Total Words</p>
                            <h4><?php echo $total_words; ?></h4>
                        </div>
                        <div class="score-info-item">
                            <p>Time Taken</p>
                            <h4><?php 
                                $time_taken = $attempt['time_taken'];
                                $mins = floor($time_taken / 60);
                                $secs = $time_taken % 60;
                                echo sprintf('%d:%02d', $mins, $secs);
                            ?></h4>
                        </div>
                    </div>
                </div>

                <div class="grid-legend">
                    <div class="legend-item">
                        <div class="legend-box correct">A</div>
                        <span>Correct Answer</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-box incorrect">B</div>
                        <span>Wrong Answer (correct shown in corner)</span>
                    </div>
                </div>

                <div class="crossword-container">
                    <div class="crossword-grid" style="flex: 1; min-width: 100%;">
                        <h4 style="margin-bottom: 15px;">Your Answers Review</h4>
                        <div style="text-align: center;">
                            <div class="grid-wrapper" id="result-grid" style="margin: 0 auto;"></div>
                        </div>
                    </div>
                </div>

                <script>
                    const gridData = <?php echo json_encode($grid_data); ?>;
                    const studentAnswers = <?php echo json_encode($student_answers); ?>;

                    function renderResultGrid() {
                        const gridContainer = document.getElementById('result-grid');
                        if (!gridContainer) return;
                        
                        gridContainer.innerHTML = '';

                        if (!Array.isArray(gridData)) {
                            gridContainer.innerHTML = '<p style="color: red;">Error loading grid data</p>';
                            return;
                        }

                        gridData.forEach((row, i) => {
                            if (!Array.isArray(row)) return;
                            
                            const rowDiv = document.createElement('div');
                            rowDiv.className = 'grid-row';

                            row.forEach((cell, j) => {
                                const cellDiv = document.createElement('div');
                                cellDiv.className = 'grid-cell';
                                cellDiv.style.width = '40px';
                                cellDiv.style.height = '40px';
                                cellDiv.style.position = 'relative';
                                cellDiv.style.border = '1px solid #d1d5db';
                                cellDiv.style.display = 'flex';
                                cellDiv.style.alignItems = 'center';
                                cellDiv.style.justifyContent = 'center';

                                if (cell.isBlack) {
                                    cellDiv.style.background = '#1f2937';
                                } else {
                                    const key = `${i}-${j}`;
                                    const studentAnswer = studentAnswers[key] || '';
                                    const correctAnswer = cell.letter || '';

                                    const span = document.createElement('span');
                                    span.textContent = studentAnswer || '';
                                    span.style.fontSize = '20px';
                                    span.style.fontWeight = 'bold';

                                    if (!studentAnswer) {
                                        cellDiv.style.background = '#f3f4f6';
                                        span.style.color = '#9ca3af';
                                    } else if (studentAnswer.toUpperCase() === correctAnswer.toUpperCase()) {
                                        cellDiv.style.background = '#d1fae5';
                                        span.style.color = '#065f46';
                                        cellDiv.style.border = '2px solid #10b981';
                                    } else {
                                        cellDiv.style.background = '#fee2e2';
                                        span.style.color = '#991b1b';
                                        cellDiv.style.border = '2px solid #ef4444';
                                        
                                        const correctSpan = document.createElement('span');
                                        correctSpan.textContent = correctAnswer;
                                        correctSpan.style.cssText = `
                                            position: absolute;
                                            bottom: 2px;
                                            right: 2px;
                                            font-size: 10px;
                                            color: #059669;
                                            font-weight: bold;
                                            background: white;
                                            padding: 1px 3px;
                                            border-radius: 3px;
                                        `;
                                        cellDiv.appendChild(correctSpan);
                                    }

                                    cellDiv.appendChild(span);

                                    if (cell.number) {
                                        const numberSpan = document.createElement('span');
                                        numberSpan.textContent = cell.number;
                                        numberSpan.style.cssText = `
                                            position: absolute;
                                            top: 2px;
                                            left: 2px;
                                            font-size: 10px;
                                            font-weight: bold;
                                            color: #6366f1;
                                        `;
                                        cellDiv.appendChild(numberSpan);
                                    }
                                }

                                rowDiv.appendChild(cellDiv);
                            });

                            gridContainer.appendChild(rowDiv);
                        });
                    }

                    renderResultGrid();

                    function printResult() {
                        window.print();
                    }
                </script>

                <div class="result-actions">
                    <button onclick="printResult()" class="btn btn-secondary">
                        🖨️ Print Result
                    </button>
                    <a href="view_result.php" class="btn btn-secondary">
                        📊 All Results
                    </a>
                    <a href="student_dashboard.php" class="btn btn-primary">
                        🏠 Dashboard
                    </a>
                </div>

            <?php else: ?>
                <?php if (count($attempts) > 0): ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Puzzle Title</th>
                                    <th>Teacher</th>
                                    <th>Score</th>
                                    <th>Correct</th>
                                    <th>Wrong</th>
                                    <th>Completed</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $counter = 1;
                                foreach ($attempts as $attempt): 
                                ?>
                                    <tr>
                                        <td><?php echo $counter++; ?></td>
                                        <td><strong><?php echo htmlspecialchars($attempt['title']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($attempt['teacher_name']); ?></td>
                                        <td>
                                            <strong style="color: <?php 
                                                echo $attempt['score'] >= 75 ? '#10b981' : 
                                                    ($attempt['score'] >= 50 ? '#f59e0b' : '#ef4444'); 
                                            ?>; font-size: 18px;">
                                                <?php echo number_format($attempt['score'], 1); ?>%
                                            </strong>
                                        </td>
                                        <td style="color: #10b981; font-weight: 600;">
                                            <?php echo $attempt['correct_answers'] ?? 0; ?>
                                        </td>
                                        <td style="color: #ef4444; font-weight: 600;">
                                            <?php echo $attempt['wrong_answers'] ?? 0; ?>
                                        </td>
                                        <td><?php echo date('M d, Y h:i A', strtotime($attempt['completed_at'])); ?></td>
                                        <td>
                                            <a href="?puzzle=<?php echo $attempt['puzzle_id']; ?>" 
                                               class="btn btn-primary">
                                                View Details
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div style="margin-top: 30px; text-align: center;">
                        <a href="student_dashboard.php" class="btn btn-primary">
                            Back to Dashboard
                        </a>
                    </div>
                <?php else: ?>
                    <div class="pending-message">
                        <div class="icon">📝</div>
                        <h3>No Published Results Yet</h3>
                        <p>Your teacher hasn't published any results yet.</p>
                        <p style="margin-top: 10px;">Once published, your puzzle results will appear here!</p>
                        <div style="margin-top: 30px;">
                            <a href="student_dashboard.php" class="btn btn-primary">
                                Back to Dashboard
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
<script src="../assets/js/responsive.js"></script>