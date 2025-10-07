<?php
require_once '../config.php';
require_once '../functions/helpers.php';

// Check if logged in
if (!isLoggedIn('student')) {
    redirect('auth/student_login.php');
}

$student = getCurrentUser('student');
$student_id = $student['id'];

// Get puzzle
if (!isset($_GET['id'])) {
    redirect('student_dashboard.php');
}

$puzzle_id = intval($_GET['id']);

// Check if already attempted or in progress
$stmt = $conn->prepare("SELECT id, attempt_status, locked, start_time FROM attempts WHERE student_id = ? AND puzzle_id = ? ORDER BY id DESC LIMIT 1");
$stmt->bind_param("ii", $student_id, $puzzle_id);
$stmt->execute();
$result = $stmt->get_result();

$attempt_id = null;
$should_create_new_attempt = true;
$session_key = "puzzle_start_time_{$puzzle_id}_{$student_id}";

if ($result->num_rows > 0) {
    $attempt = $result->fetch_assoc();
    
    // Priority 1: If teacher unlocked (locked = 0) - DELETE OLD ATTEMPT and start fresh
    if ($attempt['locked'] == 0) {
        $delete_stmt = $conn->prepare("DELETE FROM attempts WHERE id = ?");
        $delete_stmt->bind_param("i", $attempt['id']);
        $delete_stmt->execute();
        
        // Clear the timer session
        unset($_SESSION[$session_key]);
        
        // Allow fresh start
        $should_create_new_attempt = true;
        $attempt_id = null;
    }
    // Priority 2: If completed and locked - redirect to view result
    elseif ($attempt['attempt_status'] === 'completed' && $attempt['locked'] == 1) {
        $_SESSION['info'] = 'You have already completed this puzzle.';
        redirect('view_result.php?puzzle=' . $puzzle_id);
        exit();
    }
    // Priority 3: If abandoned and locked - no retry allowed
    elseif ($attempt['attempt_status'] === 'abandoned' && $attempt['locked'] == 1) {
        $_SESSION['error'] = 'This puzzle attempt was abandoned. Contact your teacher for retry permission.';
        redirect('student_dashboard.php');
        exit();
    }
    // Priority 4: If in progress and locked
    elseif ($attempt['attempt_status'] === 'in_progress' && $attempt['locked'] == 1) {
        // Check if timer session exists
        if (isset($_SESSION[$session_key])) {
            // Timer exists - this is an active attempt, allow continuation
            $attempt_id = $attempt['id'];
            $should_create_new_attempt = false;
        } else {
            // No timer - was auto-submitted
            $_SESSION['error'] = 'You left this puzzle. Contact your teacher for retry permission.';
            redirect('student_dashboard.php');
            exit();
        }
    }
}

// Get puzzle data
$stmt = $conn->prepare("SELECT p.*, t.name as teacher_name FROM puzzles p 
                        INNER JOIN teachers t ON p.teacher_id = t.id 
                        WHERE p.id = ? AND p.is_active = 1");
$stmt->bind_param("i", $puzzle_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = 'Puzzle not found or inactive.';
    redirect('student_dashboard.php');
    exit();
}

$puzzle = $result->fetch_assoc();
$grid_data = json_decode($puzzle['grid_data'], true);

// Create NEW attempt record if needed
if ($should_create_new_attempt && $attempt_id === null) {
    $create_attempt = $conn->prepare("INSERT INTO attempts (student_id, puzzle_id, start_time, attempt_status, locked) VALUES (?, ?, NOW(), 'in_progress', 1)");
    $create_attempt->bind_param("ii", $student_id, $puzzle_id);
    
    if ($create_attempt->execute()) {
        $attempt_id = $conn->insert_id;
    } else {
        $_SESSION['error'] = 'Failed to create attempt: ' . $conn->error;
        redirect('student_dashboard.php');
        exit();
    }
}

// Initialize timer session with absolute timestamp
if (!isset($_SESSION[$session_key])) {
    $_SESSION[$session_key] = time();
}
$start_time = $_SESSION[$session_key];
$time_limit = $puzzle['time_limit'] * 60;

// Calculate deadline as absolute timestamp
$deadline_timestamp = $start_time + $time_limit;
$current_time = time();
$elapsed_time = $current_time - $start_time;
$remaining_time = max(0, $time_limit - $elapsed_time);

// If time expired, mark as abandoned
if ($remaining_time <= 0) {
    $answers = json_encode([]);
    $update_stmt = $conn->prepare("UPDATE attempts SET answers = ?, time_taken = ?, score = 0, attempt_status = 'abandoned', completed_at = NOW(), locked = 1 WHERE id = ?");
    $update_stmt->bind_param("sii", $answers, $time_limit, $attempt_id);
    $update_stmt->execute();
    unset($_SESSION[$session_key]);
    
    $_SESSION['error'] = 'Time expired! Your attempt has been marked as abandoned. Contact teacher for retry permission.';
    redirect('student_dashboard.php');
    exit();
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Play Puzzle - <?php echo htmlspecialchars($puzzle['title']); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background: #f3f4f6;
        }

        .puzzle-header {
            background: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .timer-box {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            padding: 15px 30px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(239, 68, 68, 0.3);
            min-width: 200px;
        }

        .timer-box .label {
            font-size: 12px;
            opacity: 0.9;
            margin-bottom: 5px;
        }

        .timer-box .time {
            font-size: 36px;
            font-weight: bold;
            font-family: 'Courier New', monospace;
        }

        .timer-warning {
            animation: pulse-red 1s infinite;
        }

        @keyframes pulse-red {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .warning-banner {
            background: #fee2e2;
            border: 2px solid #ef4444;
            color: #991b1b;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 600;
        }

        .puzzle-container {
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 25px;
            align-items: start;
        }

        .grid-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .clues-section {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            position: sticky;
            top: 20px;
            max-height: calc(100vh - 40px);
            overflow-y: auto;
        }

        .grid-wrapper {
            display: inline-block;
            border: 3px solid #6366f1;
            border-radius: 10px;
            overflow: hidden;
            background: white;
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.25);
        }

        .grid-row {
            display: flex;
            flex-direction: row;
            line-height: 0;
        }

        .grid-cell {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #d1d5db;
            box-sizing: border-box;
        }

        .grid-cell.black {
            background: #1f2937;
            border-color: #111827;
        }

        .grid-cell input {
            width: 100%;
            height: 100%;
            border: none;
            text-align: center;
            font-weight: bold;
            text-transform: uppercase;
            background: white;
            transition: all 0.2s;
            padding: 0;
            margin: 0;
            box-sizing: border-box;
        }

        .grid-cell input:focus {
            outline: none;
            background: #dbeafe;
            box-shadow: inset 0 0 0 2px #3b82f6;
            z-index: 10;
        }

        .grid-cell.filled input {
            background: #f0fdf4;
            color: #166534;
        }

        .cell-number {
            position: absolute;
            top: 2px;
            left: 3px;
            font-weight: bold;
            color: #6366f1 !important;
            pointer-events: none;
            z-index: 5;
        }

        .clues-box {
            margin-bottom: 20px;
        }

        .clues-box h4 {
            color: #6366f1;
            margin-bottom: 12px;
            font-size: 16px;
            padding-bottom: 8px;
            border-bottom: 2px solid #e5e7eb;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .clue-item {
            padding: 10px 12px;
            margin-bottom: 8px;
            background: #f9fafb;
            border-radius: 8px;
            border-left: 3px solid #6366f1;
            font-size: 14px;
            line-height: 1.5;
            cursor: pointer;
            transition: all 0.3s;
        }

        .clue-item:hover {
            background: #eff6ff;
            border-left-color: #3b82f6;
            transform: translateX(5px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .progress-bar {
            background: #e5e7eb;
            height: 10px;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 20px;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #10b981, #059669);
            transition: width 0.3s;
            border-radius: 10px;
        }

        .progress-text {
            text-align: center;
            margin-top: 10px;
            font-size: 14px;
            color: #6b7280;
            font-weight: 600;
        }

        .submit-section {
            margin-top: 30px;
            text-align: center;
            padding-top: 25px;
            border-top: 2px solid #e5e7eb;
        }

        .submit-btn {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            padding: 16px 60px;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 5px 15px rgba(16, 185, 129, 0.3);
        }

        .submit-btn:hover:not(:disabled) {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
        }

        .auto-save {
            display: inline-block;
            padding: 6px 14px;
            background: #dcfce7;
            color: #166534;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .stats-mini {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 20px;
        }

        .stat-mini {
            text-align: center;
            padding: 15px;
            background: linear-gradient(135deg, #f9fafb, #ffffff);
            border-radius: 10px;
            border: 1px solid #e5e7eb;
        }

        .stat-mini .value {
            font-size: 28px;
            font-weight: bold;
            color: #6366f1;
            margin-bottom: 5px;
        }

        .stat-mini .label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        @media (max-width: 1200px) {
            .puzzle-container {
                grid-template-columns: 1fr;
            }
            .clues-section {
                position: relative;
                top: 0;
                max-height: none;
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

    <div class="container" style="padding: 20px;">
        <div class="warning-banner">
            ⚠️ <strong>IMPORTANT:</strong> Do NOT switch tabs, logout, or close browser! Your puzzle will be auto-submitted and locked! (Refresh is OK)
        </div>

        <div class="puzzle-header">
            <div>
                <h2 style="color: #6366f1; margin-bottom: 5px; font-size: 24px;"><?php echo htmlspecialchars($puzzle['title']); ?></h2>
                <p style="color: #6b7280; font-size: 14px; margin: 5px 0;">By: <?php echo htmlspecialchars($puzzle['teacher_name']); ?></p>
                <span class="auto-save" id="auto-save">● Ready</span>
            </div>
            <div class="timer-box" id="timer-box">
                <div class="label">⏱️ TIME REMAINING</div>
                <div class="time" id="timer">--:--</div>
            </div>
        </div>

        <div class="puzzle-container">
            <div class="grid-section">
                <div style="text-align: center;">
                    <div id="crossword-grid" class="grid-wrapper"></div>
                </div>

                <div class="progress-bar">
                    <div class="progress-fill" id="progress-fill" style="width: 0%"></div>
                </div>
                <div class="progress-text" id="progress-text">0% Complete</div>

                <div class="stats-mini">
                    <div class="stat-mini">
                        <div class="value" id="filled-count">0</div>
                        <div class="label">Filled</div>
                    </div>
                    <div class="stat-mini">
                        <div class="value" id="empty-count">0</div>
                        <div class="label">Remaining</div>
                    </div>
                    <div class="stat-mini">
                        <div class="value" id="total-count">0</div>
                        <div class="label">Total</div>
                    </div>
                </div>

                <div class="submit-section">
                    <button onclick="submitPuzzle()" class="submit-btn" id="submit-btn">
                        📝 Submit Puzzle
                    </button>
                    <p style="color: #6b7280; font-size: 13px; margin-top: 12px;">
                        Make sure to complete all answers before submitting
                    </p>
                </div>
            </div>

            <div class="clues-section">
                <div class="clues-box">
                    <h4><span style="font-size: 18px;">→</span> ACROSS</h4>
                    <div id="clues-across">
                        <?php
                        $clues = explode("\n", $puzzle['clues_across']);
                        foreach ($clues as $clue) {
                            $clue = trim($clue);
                            if (!empty($clue)) {
                                echo '<div class="clue-item">' . htmlspecialchars($clue) . '</div>';
                            }
                        }
                        ?>
                    </div>
                </div>

                <div class="clues-box">
                    <h4><span style="font-size: 18px;">↓</span> DOWN</h4>
                    <div id="clues-down">
                        <?php
                        $clues = explode("\n", $puzzle['clues_down']);
                        foreach ($clues as $clue) {
                            $clue = trim($clue);
                            if (!empty($clue)) {
                                echo '<div class="clue-item">' . htmlspecialchars($clue) . '</div>';
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form id="submitForm" method="POST" action="../submit_attempt.php" style="display: none;">
        <input type="hidden" name="puzzle_id" value="<?php echo $puzzle_id; ?>">
        <input type="hidden" name="attempt_id" value="<?php echo $attempt_id; ?>">
        <input type="hidden" name="answers" id="answers">
        <input type="hidden" name="time_taken" id="time_taken">
    </form>

<script>
const gridData = <?php echo json_encode($grid_data); ?>;
const puzzleId = <?php echo $puzzle_id; ?>;
const studentId = <?php echo $student_id; ?>;
const attemptId = <?php echo $attempt_id; ?>;

let timeRemaining = <?php echo $remaining_time; ?>;
const timeLimit = <?php echo $time_limit; ?>;
let timerInterval;
let totalCells = 0;
let syncInProgress = false;
let isManualSubmit = false;
let autoSubmitInProgress = false;
let isRefreshing = false;
let pageLoadTime = Date.now();
let saveTimeout = null;

// LocalStorage keys (backup only)
const storageKey = `puzzle_${puzzleId}_student_${studentId}_attempt_${attemptId}`;
const refreshFlagKey = `${storageKey}_refreshing`;

console.log('Puzzle loaded - Puzzle:', puzzleId, 'Attempt:', attemptId);

// Detect page refresh
function detectPageRefresh() {
    const perfEntries = performance.getEntriesByType("navigation");
    if (perfEntries.length > 0 && perfEntries[0].type === 'reload') {
        return true;
    }
    
    try {
        const flag = sessionStorage.getItem(refreshFlagKey);
        if (flag === 'true') {
            return true;
        }
    } catch(e) {}
    
    return false;
}

isRefreshing = detectPageRefresh();

// Auto-submit puzzle
function autoSubmitPuzzle(reason) {
    if (autoSubmitInProgress || isManualSubmit || isRefreshing) {
        console.log('⏭️ Skip auto-submit:', reason);
        return;
    }
    
    autoSubmitInProgress = true;
    console.log('🚨 AUTO-SUBMIT:', reason);
    
    clearInterval(timerInterval);
    
    const answers = collectAnswers();
    const timeTaken = timeLimit - timeRemaining;
    
    const data = JSON.stringify({
        puzzle_id: puzzleId,
        attempt_id: attemptId,
        answers: answers,
        time_taken: timeTaken
    });
    
    const blob = new Blob([data], { type: 'application/json' });
    const submitted = navigator.sendBeacon('auto_submit_puzzle.php', blob);
    
    if (!submitted) {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'auto_submit_puzzle.php', false);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.send(data);
    }
    
    console.log('✅ Auto-submitted');
}

// Save answers to DATABASE with debounce
function saveAnswersToDatabase() {
    // Clear previous timeout
    if (saveTimeout) {
        clearTimeout(saveTimeout);
    }
    
    // Debounce: wait 500ms after last keystroke
    saveTimeout = setTimeout(() => {
        const answers = collectAnswers();
        
        fetch('save_answer.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                attempt_id: attemptId,
                answers: answers
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('💾 Saved to DB:', data.saved_count, 'answers');
                
                const indicator = document.getElementById('auto-save');
                if (indicator) {
                    indicator.textContent = '✓ Saved to DB';
                    indicator.style.background = '#d1fae5';
                    indicator.style.color = '#065f46';
                    
                    setTimeout(() => {
                        indicator.textContent = '● Auto-save';
                        indicator.style.background = '#dcfce7';
                        indicator.style.color = '#166534';
                    }, 2000);
                }
            } else {
                console.error('Save failed:', data.error);
            }
        })
        .catch(error => {
            console.error('Network error:', error);
        });
    }, 500); // Wait 500ms after last keystroke
}

// Load existing answers from database
function loadAnswersFromDatabase() {
    // Answers are already loaded from PHP
    // Grid will be populated from database on page load
    console.log('📂 Loading answers from database');
}

// Sync timer with server
function syncWithServer() {
    if (syncInProgress) return;
    
    syncInProgress = true;
    
    fetch(`sync_timer.php?puzzle_id=${puzzleId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                timeRemaining = data.remaining_time;
                updateTimerDisplay();
            }
        })
        .catch(error => console.error('Sync failed:', error))
        .finally(() => {
            syncInProgress = false;
        });
}

// Start timer
function startTimer() {
    syncWithServer();
    updateTimerDisplay();
    
    timerInterval = setInterval(() => {
        timeRemaining--;
        
        if (timeRemaining <= 0) {
            clearInterval(timerInterval);
            alert('⏰ Time is up! Submitting puzzle...');
            submitPuzzle();
            return;
        }

        updateTimerDisplay();

        if (timeRemaining === 300) {
            document.getElementById('timer-box').classList.add('timer-warning');
            alert('⚠️ Only 5 minutes remaining!');
        }

        if (timeRemaining % 30 === 0) {
            syncWithServer();
        }
    }, 1000);
}

// Update timer display
function updateTimerDisplay() {
    const minutes = Math.floor(timeRemaining / 60);
    const seconds = timeRemaining % 60;
    document.getElementById('timer').textContent = 
        `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
    
    if (timeRemaining <= 300) {
        document.getElementById('timer-box').classList.add('timer-warning');
    }
}

// Render crossword grid
function renderGrid() {
    const gridContainer = document.getElementById('crossword-grid');
    gridContainer.innerHTML = '';

    if (!gridData || !Array.isArray(gridData)) {
        gridContainer.innerHTML = '<p style="color: red; padding: 20px;">Error loading grid</p>';
        return;
    }

    const maxDimension = Math.max(gridData.length, gridData[0]?.length || 0);
    let cellSize;
    
    if (maxDimension <= 8) cellSize = 60;
    else if (maxDimension <= 10) cellSize = 55;
    else if (maxDimension <= 12) cellSize = 50;
    else if (maxDimension <= 15) cellSize = 45;
    else if (maxDimension <= 18) cellSize = 40;
    else cellSize = Math.max(32, Math.floor(700 / maxDimension));

    const fontSize = Math.max(16, Math.floor(cellSize * 0.55));
    const numberSize = Math.max(10, Math.floor(cellSize * 0.28));

    // Load saved answers from database (already in attempts table)
    let savedAnswers = null;
    <?php
    // Check if there are existing answers
    $existing_answers_query = $conn->prepare("SELECT answers FROM attempts WHERE id = ?");
    $existing_answers_query->bind_param("i", $attempt_id);
    $existing_answers_query->execute();
    $existing_result = $existing_answers_query->get_result();
    if ($existing_result->num_rows > 0) {
        $existing_row = $existing_result->fetch_assoc();
        if (!empty($existing_row['answers'])) {
            echo "savedAnswers = " . $existing_row['answers'] . ";";
        }
    }
    ?>

    let restoredCount = 0;

    gridData.forEach((row, i) => {
        const rowDiv = document.createElement('div');
        rowDiv.className = 'grid-row';

        row.forEach((cell, j) => {
            const cellDiv = document.createElement('div');
            cellDiv.className = 'grid-cell';
            cellDiv.style.width = cellSize + 'px';
            cellDiv.style.height = cellSize + 'px';

            if (cell.isBlack) {
                cellDiv.classList.add('black');
            } else {
                totalCells++;
                
                const input = document.createElement('input');
                input.type = 'text';
                input.maxLength = 1;
                input.dataset.row = i;
                input.dataset.col = j;
                input.style.fontSize = fontSize + 'px';
                
                // Restore saved answer from database
                if (savedAnswers) {
                    const key = `${i}-${j}`;
                    if (savedAnswers[key]) {
                        input.value = savedAnswers[key];
                        cellDiv.classList.add('filled');
                        restoredCount++;
                    }
                }
                
                input.addEventListener('input', function(e) {
                    this.value = this.value.toUpperCase().replace(/[^A-Z]/g, '');
                    if (this.value) {
                        cellDiv.classList.add('filled');
                        const nextCell = findNextCell(i, j, 'right');
                        if (nextCell) nextCell.focus();
                    } else {
                        cellDiv.classList.remove('filled');
                    }
                    updateProgress();
                    saveAnswersToDatabase(); // SAVE TO DATABASE ON EVERY KEYSTROKE
                });

                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Backspace' && !this.value) {
                        const prevCell = findNextCell(i, j, 'left');
                        if (prevCell) {
                            prevCell.value = '';
                            prevCell.parentElement.classList.remove('filled');
                            prevCell.focus();
                            e.preventDefault();
                            updateProgress();
                            saveAnswersToDatabase(); // SAVE TO DATABASE ON DELETE
                        }
                    } else if (e.key === 'ArrowRight') {
                        e.preventDefault();
                        findNextCell(i, j, 'right')?.focus();
                    } else if (e.key === 'ArrowLeft') {
                        e.preventDefault();
                        findNextCell(i, j, 'left')?.focus();
                    } else if (e.key === 'ArrowDown') {
                        e.preventDefault();
                        findNextCell(i, j, 'down')?.focus();
                    } else if (e.key === 'ArrowUp') {
                        e.preventDefault();
                        findNextCell(i, j, 'up')?.focus();
                    }
                });

                cellDiv.appendChild(input);

                if (cell.number) {
                    const numberSpan = document.createElement('span');
                    numberSpan.className = 'cell-number';
                    numberSpan.textContent = cell.number;
                    numberSpan.style.fontSize = numberSize + 'px';
                    cellDiv.appendChild(numberSpan);
                }
            }

            rowDiv.appendChild(cellDiv);
        });

        gridContainer.appendChild(rowDiv);
    });

    if (restoredCount > 0) {
        console.log('✅ Restored', restoredCount, 'answers from database');
    }

    updateProgress();
}

// Find next cell for navigation
function findNextCell(row, col, direction) {
    let nextRow = row, nextCol = col;

    if (direction === 'right') nextCol++;
    else if (direction === 'left') nextCol--;
    else if (direction === 'down') nextRow++;
    else if (direction === 'up') nextRow--;

    while (nextRow >= 0 && nextRow < gridData.length && nextCol >= 0 && nextCol < gridData[0].length) {
        if (!gridData[nextRow][nextCol].isBlack) {
            return document.querySelector(`input[data-row="${nextRow}"][data-col="${nextCol}"]`);
        }
        if (direction === 'right') nextCol++;
        else if (direction === 'left') nextCol--;
        else if (direction === 'down') nextRow++;
        else if (direction === 'up') nextRow--;
        else break;
    }
    return null;
}

// Update progress bar
function updateProgress() {
    const inputs = document.querySelectorAll('.grid-cell input');
    let filled = 0;
    inputs.forEach(input => { if (input.value) filled++; });

    const percentage = totalCells > 0 ? Math.round((filled / totalCells) * 100) : 0;
    document.getElementById('progress-fill').style.width = percentage + '%';
    document.getElementById('progress-text').textContent = percentage + '% Complete';
    
    document.getElementById('filled-count').textContent = filled;
    document.getElementById('empty-count').textContent = totalCells - filled;
    document.getElementById('total-count').textContent = totalCells;
}

// Collect all answers
function collectAnswers() {
    const answers = {};
    document.querySelectorAll('.grid-cell input').forEach(input => {
        answers[`${input.dataset.row}-${input.dataset.col}`] = input.value.toUpperCase();
    });
    return answers;
}

// Submit puzzle
function submitPuzzle() {
    isManualSubmit = true;
    clearInterval(timerInterval);
    
    const answers = collectAnswers();
    const filled = Object.values(answers).filter(v => v).length;
    
    if (filled < totalCells * 0.5) {
        if (!confirm(`You have only filled ${filled} out of ${totalCells} cells. Submit anyway?`)) {
            isManualSubmit = false;
            startTimer();
            return;
        }
    }
    
    try {
        sessionStorage.removeItem(refreshFlagKey);
    } catch(e) {}
    
    document.getElementById('answers').value = JSON.stringify(answers);
    document.getElementById('time_taken').value = timeLimit - timeRemaining;
    document.getElementById('submitForm').submit();
}

// Event listeners
document.addEventListener('keydown', function(e) {
    if (e.key === 'F5' || (e.ctrlKey && e.key === 'r') || (e.metaKey && e.key === 'r')) {
        try {
            sessionStorage.setItem(refreshFlagKey, 'true');
        } catch(e) {}
    }
});

window.addEventListener('beforeunload', function(e) {
    try {
        sessionStorage.setItem(refreshFlagKey, 'true');
    } catch(e) {}
    
    if (isManualSubmit) {
        return undefined;
    }
    
    return undefined; // No warning
});

document.addEventListener('visibilitychange', function() {
    if (Date.now() - pageLoadTime < 2000 || isRefreshing) {
        return;
    }
    
    if (document.hidden && !isManualSubmit) {
        console.log('Tab switched - AUTO SUBMIT');
        autoSubmitPuzzle('Tab switched');
        setTimeout(() => window.location.href = 'student_dashboard.php', 100);
    }
});

window.addEventListener('load', function() {
    setTimeout(function() {
        isRefreshing = false;
        try {
            sessionStorage.removeItem(refreshFlagKey);
        } catch(e) {}
    }, 1500);
});

// Initialize
renderGrid();
startTimer();

// console.log('✅ Database auto-save enabled');
// console.log('🔄 Refresh = answers restored from DB');
// console.log('🚪 Tab switch/close = auto-submit');
</script>

</body>
</html>
<script src="../assets/js/responsive.js"></script>