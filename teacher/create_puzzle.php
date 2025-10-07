<?php
require_once '../config.php';
require_once '../functions/helpers.php';

// Check if logged in
if (!isLoggedIn('teacher')) {
    redirect('auth/teacher_login.php');
}

$teacher = getCurrentUser('teacher');
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_puzzle'])) {
    // Remove CSRF check, proceed directly to validation
    $title = sanitize($_POST['title']);
    $time_limit = intval($_POST['time_limit']);
    $grid_data = $_POST['grid_data'];
    $clues_across = $_POST['clues_across'];
    $clues_down = $_POST['clues_down'];
    $grid_size = intval($_POST['grid_size']);
    
    if (empty($title) || empty($grid_data)) {
        $error = 'Please provide title and add at least one word';
    } elseif ($time_limit < 5 || $time_limit > 120) {
        $error = 'Time limit must be between 5 and 120 minutes';
    } else {
        $teacher_id = $teacher['id'];
        
        // Check if correct_answers column exists
        $check_column = $conn->query("SHOW COLUMNS FROM puzzles LIKE 'correct_answers'");
        
        if ($check_column->num_rows > 0) {
            // Column exists - use it
            $correct_answers = $_POST['correct_answers'];
            $stmt = $conn->prepare("INSERT INTO puzzles (teacher_id, title, time_limit, grid_size, grid_data, correct_answers, clues_across, clues_down, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())");
            $stmt->bind_param("isiissss", $teacher_id, $title, $time_limit, $grid_size, $grid_data, $correct_answers, $clues_across, $clues_down);
        } else {
            // Column doesn't exist - skip it
            $stmt = $conn->prepare("INSERT INTO puzzles (teacher_id, title, time_limit, grid_size, grid_data, clues_across, clues_down, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())");
            $stmt->bind_param("isiisss", $teacher_id, $title, $time_limit, $grid_size, $grid_data, $clues_across, $clues_down);
        }
        
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Puzzle created successfully!';
            header('Location: manage_puzzles.php');
            exit();
        } else {
            $error = 'Failed to create puzzle: ' . $conn->error;
            error_log("Puzzle creation failed: " . $conn->error);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Puzzle - Live Preview</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <style>
        body { background: #f3f4f6; }
        
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
        <div class="info-banner">
            <h3>✏️ Create Crossword Puzzle</h3>
            <p style="font-size: 14px; margin-top: 5px; opacity: 0.9;">Words will automatically intersect to create a compact crossword layout</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="POST" id="puzzleForm" onsubmit="return validateForm()">
            
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; max-width: 900px;">
                <div class="form-group">
                    <label for="title">Puzzle Title *</label>
                    <input type="text" id="title" name="title" required placeholder="e.g., Computer Science Basics" maxlength="100">
                </div>

                <div class="form-group">
                    <label for="time_limit">Time Limit (minutes) *</label>
                    <select id="time_limit" name="time_limit" required style="padding: 12px; border: 2px solid #e5e7eb; border-radius: 8px;">
                        <option value="10">10 minutes</option>
                        <option value="15">15 minutes</option>
                        <option value="20">20 minutes</option>
                        <option value="30" selected>30 minutes</option>
                        <option value="45">45 minutes</option>
                        <option value="60">60 minutes</option>
                    </select>
                </div>
            </div>

            <div class="creator-container">
                <!-- Left Side: Word Entry -->
                <div class="word-entry-section">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h3 style="color: #6366f1; margin: 0;">📝 Words & Clues</h3>
                        <span class="live-indicator">● LIVE</span>
                    </div>

                    <div id="words-container"></div>

                    <button type="button" class="add-word-btn" onclick="addWordEntry()">
                        + Add Another Word
                    </button>
                </div>

                <!-- Right Side: Live Preview -->
                <div class="preview-section">
                    <h3 style="color: #6366f1; margin-bottom: 15px;">Live Preview</h3>
                    
                    <div class="stats-bar">
                        <div class="stat-item">
                            <div class="value" id="total-words">0</div>
                            <div class="label">Total Words</div>
                        </div>
                        <div class="stat-item">
                            <div class="value" id="across-count">0</div>
                            <div class="label">Across</div>
                        </div>
                        <div class="stat-item">
                            <div class="value" id="down-count">0</div>
                            <div class="label">Down</div>
                        </div>
                    </div>

                    <div style="text-align: center; margin: 20px 0;">
                        <div id="preview-grid" class="preview-grid">
                            <p style="color: #6b7280; padding: 40px; text-align: center;">Start typing to see your crossword...</p>
                        </div>
                    </div>

                    <div class="clue-preview-box">
                        <h4>→ ACROSS</h4>
                        <div id="across-preview"><p style="color: #9ca3af; font-size: 13px;">No clues yet</p></div>
                    </div>

                    <div class="clue-preview-box">
                        <h4>↓ DOWN</h4>
                        <div id="down-preview"><p style="color: #9ca3af; font-size: 13px;">No clues yet</p></div>
                    </div>
                </div>
            </div>

            <input type="hidden" id="grid_data" name="grid_data">
            <input type="hidden" id="correct_answers" name="correct_answers">
            <input type="hidden" id="clues_across" name="clues_across">
            <input type="hidden" id="clues_down" name="clues_down">
            <input type="hidden" id="grid_size" name="grid_size" value="25">

            <div id="save-section" style="display: none;">
                <p style="color: #065f46; font-size: 16px; margin-bottom: 15px; font-weight: 600;">
                    ✅ Crossword generated successfully! Ready to save.
                </p>
                <button type="submit" name="create_puzzle" class="btn btn-primary" style="padding: 15px 50px; font-size: 18px;">
                    💾 Save Crossword Puzzle
                </button>
            </div>
        </form>
    </div>

 <script>
let wordEntries = [];
let wordIdCounter = 0;
let generationTimeout = null;
let unplacedWords = []; // Track words that couldn't be placed

for (let i = 0; i < 5; i++) {
    addWordEntry();
}

function addWordEntry() {
    const id = wordIdCounter++;
    const container = document.getElementById('words-container');
    
    const wordItem = document.createElement('div');
    wordItem.className = 'word-item';
    wordItem.id = `word-${id}`;
    wordItem.innerHTML = `
        <input type="text" 
               class="answer-input" 
               placeholder="Answer (e.g., COMPUTER)" 
               oninput="handleInput(${id}, 'answer', this.value)"
               maxlength="20">
        <input type="text" 
               placeholder="Clue/Description" 
               oninput="handleInput(${id}, 'clue', this.value)"
               maxlength="200">
        <button type="button" class="remove-word-btn" onclick="removeWord(${id})" title="Remove">×</button>
    `;
    
    container.appendChild(wordItem);
    
    wordEntries.push({
        id: id,
        answer: '',
        clue: ''
    });
}

function handleInput(id, field, value) {
    const entry = wordEntries.find(w => w.id === id);
    if (entry) {
        if (field === 'answer') {
            entry[field] = value.toUpperCase().replace(/[^A-Z]/g, '');
            event.target.value = entry[field];
        } else {
            entry[field] = value;
        }
    }

    clearTimeout(generationTimeout);
    generationTimeout = setTimeout(() => {
        generateCrossword();
    }, 300);
}

function removeWord(id) {
    const element = document.getElementById(`word-${id}`);
    if (element) {
        element.remove();
    }
    wordEntries = wordEntries.filter(w => w.id !== id);
    generateCrossword();
}

function validateForm() {
    const gridData = document.getElementById('grid_data').value;
    
    if (!gridData) {
        alert('Please add at least 2 words to create a crossword puzzle');
        return false;
    }
    
    const title = document.getElementById('title').value.trim();
    if (!title) {
        alert('Please provide a puzzle title');
        return false;
    }
    
    // Check if there are unplaced words
    if (unplacedWords.length > 0) {
        const wordsList = unplacedWords.map(w => `"${w.answer}"`).join(', ');
        const confirmed = confirm(
            `⚠️ WARNING: ${unplacedWords.length} word(s) could not fit in the crossword:\n\n${wordsList}\n\n` +
            `These words will NOT be included in the puzzle.\n\nDo you want to save anyway?`
        );
        
        if (!confirmed) {
            return false;
        }
    }
    
    console.log('Form validation passed');
    return true;
}

function generateCrossword() {
    const validWords = wordEntries.filter(w => w.answer && w.clue && w.answer.length >= 2);
    
    if (validWords.length === 0) {
        document.getElementById('preview-grid').innerHTML = '<p style="color: #6b7280; padding: 40px; text-align: center;">Start typing to see your crossword...</p>';
        document.getElementById('across-preview').innerHTML = '<p style="color: #9ca3af; font-size: 13px;">No clues yet</p>';
        document.getElementById('down-preview').innerHTML = '<p style="color: #9ca3af; font-size: 13px;">No clues yet</p>';
        document.getElementById('save-section').style.display = 'none';
        hideWarningBanner();
        updateStats(0, 0, 0);
        return;
    }

    const result = buildCompactCrossword(validWords);
    
    if (result) {
        displayPreview(result.grid, result.placedWords);
        
        const acrossCount = result.placedWords.filter(w => w.direction === 'across').length;
        const downCount = result.placedWords.filter(w => w.direction === 'down').length;
        updateStats(validWords.length, acrossCount, downCount);

        // Check for unplaced words
        const placedAnswers = result.placedWords.map(w => w.answer);
        unplacedWords = validWords.filter(w => !placedAnswers.includes(w.answer));
        
        if (unplacedWords.length > 0) {
            showWarningBanner(unplacedWords);
        } else {
            hideWarningBanner();
        }

        document.getElementById('grid_data').value = JSON.stringify(result.grid);
        document.getElementById('grid_size').value = Math.max(result.grid.length, result.grid[0]?.length || 0);

        // Store correct answers
        const correctAnswers = {};
        result.placedWords.forEach(word => {
            if (word.direction === 'across') {
                for (let i = 0; i < word.answer.length; i++) {
                    correctAnswers[`${word.row}-${word.col + i}`] = word.answer[i];
                }
            } else {
                for (let i = 0; i < word.answer.length; i++) {
                    correctAnswers[`${word.row + i}-${word.col}`] = word.answer[i];
                }
            }
        });
        document.getElementById('correct_answers').value = JSON.stringify(correctAnswers);

        const acrossClues = result.placedWords
            .filter(w => w.direction === 'across')
            .map(w => `${w.number}. ${w.clue}`)
            .join('\n');

        const downClues = result.placedWords
            .filter(w => w.direction === 'down')
            .map(w => `${w.number}. ${w.clue}`)
            .join('\n');

        document.getElementById('clues_across').value = acrossClues;
        document.getElementById('clues_down').value = downClues;

        document.getElementById('save-section').style.display = 'block';
    }
}

function showWarningBanner(unplacedWords) {
    let banner = document.getElementById('unplaced-words-banner');
    
    if (!banner) {
        banner = document.createElement('div');
        banner.id = 'unplaced-words-banner';
        banner.style.cssText = `
            background: #fef3c7;
            border: 2px solid #f59e0b;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            animation: slideDown 0.3s ease;
        `;
        
        const container = document.querySelector('.creator-container');
        container.parentNode.insertBefore(banner, container);
    }
    
    const wordsList = unplacedWords.map(w => 
        `<li style="margin: 5px 0;"><strong>${w.answer}</strong> - ${w.clue}</li>`
    ).join('');
    
    banner.innerHTML = `
        <div style="display: flex; align-items: start; gap: 15px;">
            <div style="font-size: 32px;">⚠️</div>
            <div style="flex: 1;">
                <h4 style="color: #92400e; margin: 0 0 10px 0; font-size: 18px;">
                    Warning: ${unplacedWords.length} Word(s) Cannot Fit
                </h4>
                <p style="color: #78350f; margin: 0 0 10px 0; font-size: 14px;">
                    These words could not be placed because they don't share common letters with existing words:
                </p>
                <ul style="color: #78350f; margin: 10px 0; padding-left: 20px; font-size: 14px;">
                    ${wordsList}
                </ul>
                <p style="color: #78350f; margin: 10px 0 0 0; font-size: 13px; font-weight: 600;">
                    💡 Tip: Try adding words that share letters with existing words, or remove these words.
                </p>
            </div>
        </div>
    `;
    
    // Add animation
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    `;
    if (!document.getElementById('warning-animation-style')) {
        style.id = 'warning-animation-style';
        document.head.appendChild(style);
    }
}

function hideWarningBanner() {
    const banner = document.getElementById('unplaced-words-banner');
    if (banner) {
        banner.remove();
    }
}

function buildCompactCrossword(words) {
    const gridSize = 25;
    const grid = createEmptyGrid(gridSize);
    const placedWords = [];
    let currentNumber = 1;

    const sortedWords = [...words].sort((a, b) => b.answer.length - a.answer.length);

    if (sortedWords.length > 0) {
        const firstWord = sortedWords[0];
        const startRow = Math.floor(gridSize / 2);
        const startCol = Math.floor((gridSize - firstWord.answer.length) / 2);

        placeWordInGrid(grid, firstWord.answer, startRow, startCol, 'across', currentNumber);
        placedWords.push({
            number: currentNumber++,
            answer: firstWord.answer,
            clue: firstWord.clue,
            direction: 'across',
            row: startRow,
            col: startCol
        });
    }

    for (let i = 1; i < sortedWords.length; i++) {
        const word = sortedWords[i];
        const placements = findAllPlacements(grid, word.answer, gridSize, placedWords);
        
        if (placements.length > 0) {
            const placement = placements[0];
            
            placeWordInGrid(grid, word.answer, placement.row, placement.col, placement.direction, currentNumber);
            placedWords.push({
                number: currentNumber++,
                answer: word.answer,
                clue: word.clue,
                direction: placement.direction,
                row: placement.row,
                col: placement.col
            });
        }
        // If no placement found, word will be added to unplacedWords in generateCrossword()
    }

    return extractCompactGrid(grid, gridSize, placedWords);
}

function createEmptyGrid(size) {
    return Array(size).fill().map(() => 
        Array(size).fill().map(() => ({ letter: '', isBlack: true, number: null }))
    );
}

function findAllPlacements(grid, word, gridSize, placedWords) {
    const placements = [];

    for (const placedWord of placedWords) {
        for (let i = 0; i < word.length; i++) {
            for (let j = 0; j < placedWord.answer.length; j++) {
                if (word[i] === placedWord.answer[j]) {
                    let row, col, direction;

                    if (placedWord.direction === 'across') {
                        row = placedWord.row - i;
                        col = placedWord.col + j;
                        direction = 'down';
                    } else {
                        row = placedWord.row + j;
                        col = placedWord.col - i;
                        direction = 'across';
                    }

                    if (canPlaceWord(grid, word, row, col, direction, gridSize)) {
                        placements.push({ row, col, direction });
                    }
                }
            }
        }
    }

    return placements;
}

function canPlaceWord(grid, word, row, col, direction, gridSize) {
    if (direction === 'across') {
        if (col < 0 || col + word.length > gridSize || row < 0 || row >= gridSize) return false;
        
        for (let i = 0; i < word.length; i++) {
            const cell = grid[row][col + i];
            if (!cell.isBlack && cell.letter !== word[i]) return false;
        }
    } else {
        if (row < 0 || row + word.length > gridSize || col < 0 || col >= gridSize) return false;
        
        for (let i = 0; i < word.length; i++) {
            const cell = grid[row + i][col];
            if (!cell.isBlack && cell.letter !== word[i]) return false;
        }
    }
    return true;
}

function placeWordInGrid(grid, word, row, col, direction, number) {
    if (direction === 'across') {
        for (let i = 0; i < word.length; i++) {
            const cell = grid[row][col + i];
            grid[row][col + i] = {
                letter: word[i],
                isBlack: false,
                number: i === 0 ? number : (cell.number || null)
            };
        }
    } else {
        for (let i = 0; i < word.length; i++) {
            const cell = grid[row + i][col];
            grid[row + i][col] = {
                letter: word[i],
                isBlack: false,
                number: i === 0 ? number : (cell.number || null)
            };
        }
    }
}

function extractCompactGrid(grid, gridSize, placedWords) {
    if (placedWords.length === 0) return null;

    let minRow = gridSize, maxRow = 0, minCol = gridSize, maxCol = 0;
    
    for (let i = 0; i < gridSize; i++) {
        for (let j = 0; j < gridSize; j++) {
            if (!grid[i][j].isBlack) {
                minRow = Math.min(minRow, i);
                maxRow = Math.max(maxRow, i);
                minCol = Math.min(minCol, j);
                maxCol = Math.max(maxCol, j);
            }
        }
    }

    const compactGrid = [];
    for (let i = minRow; i <= maxRow; i++) {
        const row = [];
        for (let j = minCol; j <= maxCol; j++) {
            row.push(grid[i][j]);
        }
        compactGrid.push(row);
    }

    return { grid: compactGrid, placedWords };
}

function displayPreview(grid, placedWords) {
    const previewGrid = document.getElementById('preview-grid');
    previewGrid.innerHTML = '';

    const maxDimension = Math.max(grid.length, grid[0]?.length || 0);
    const cellSize = Math.min(30, Math.floor(400 / maxDimension));

    const gridWrapper = document.createElement('div');
    gridWrapper.style.display = 'inline-block';

    grid.forEach((row) => {
        const rowDiv = document.createElement('div');
        rowDiv.className = 'grid-row';

        row.forEach((cell) => {
            const cellDiv = document.createElement('div');
            cellDiv.className = 'grid-cell';
            cellDiv.style.width = cellSize + 'px';
            cellDiv.style.height = cellSize + 'px';

            if (cell.isBlack) {
                cellDiv.classList.add('black');
            } else {
                const span = document.createElement('span');
                span.textContent = cell.letter;
                span.style.fontSize = Math.floor(cellSize * 0.5) + 'px';
                span.style.fontWeight = 'bold';
                cellDiv.appendChild(span);

                if (cell.number) {
                    const numberSpan = document.createElement('span');
                    numberSpan.className = 'cell-number';
                    numberSpan.textContent = cell.number;
                    numberSpan.style.fontSize = Math.floor(cellSize * 0.3) + 'px';
                    cellDiv.appendChild(numberSpan);
                }
            }

            rowDiv.appendChild(cellDiv);
        });

        gridWrapper.appendChild(rowDiv);
    });

    previewGrid.appendChild(gridWrapper);

    const acrossPreview = document.getElementById('across-preview');
    const downPreview = document.getElementById('down-preview');

    acrossPreview.innerHTML = '';
    downPreview.innerHTML = '';

    placedWords
        .sort((a, b) => a.number - b.number)
        .forEach(word => {
            const clueDiv = document.createElement('div');
            clueDiv.className = 'clue-preview-item';
            clueDiv.innerHTML = `
                <strong>${word.number}.</strong> ${word.clue} 
                <span style="color: #6b7280; font-size: 11px;">(${word.answer.length})</span>
            `;

            if (word.direction === 'across') {
                acrossPreview.appendChild(clueDiv);
            } else {
                downPreview.appendChild(clueDiv);
            }
        });
}

function updateStats(total, across, down) {
    document.getElementById('total-words').textContent = total;
    document.getElementById('across-count').textContent = across;
    document.getElementById('down-count').textContent = down;
}

generateCrossword();
</script>

    <script src="../assets/js/responsive.js"></script>
</body>
</html>
