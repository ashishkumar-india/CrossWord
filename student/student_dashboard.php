<?php
require_once '../config.php';
require_once '../functions/helpers.php';

// Check if logged in
if (!isLoggedIn('student')) {
    redirect('auth/student_login.php');
}

$student = getCurrentUser('student');
$student_id = $student['id'];

// Get available active puzzles with attempt status, locked status, and result_published
$stmt = $conn->prepare("SELECT p.*, t.name as teacher_name,
                        (SELECT id FROM attempts WHERE puzzle_id = p.id AND student_id = ? ORDER BY id DESC LIMIT 1) as attempt_id,
                        (SELECT attempt_status FROM attempts WHERE puzzle_id = p.id AND student_id = ? ORDER BY id DESC LIMIT 1) as attempt_status,
                        (SELECT locked FROM attempts WHERE puzzle_id = p.id AND student_id = ? ORDER BY id DESC LIMIT 1) as attempt_locked,
                        (SELECT result_published FROM attempts WHERE puzzle_id = p.id AND student_id = ? ORDER BY id DESC LIMIT 1) as result_published,
                        (SELECT score FROM attempts WHERE puzzle_id = p.id AND student_id = ? ORDER BY id DESC LIMIT 1) as attempt_score
                        FROM puzzles p 
                        INNER JOIN teachers t ON p.teacher_id = t.id
                        WHERE p.is_active = 1
                        ORDER BY p.created_at DESC");
$stmt->bind_param("iiiii", $student_id, $student_id, $student_id, $student_id, $student_id);
$stmt->execute();
$puzzles = $stmt->get_result();

// Get student statistics - count ALL completed attempts
$stmt = $conn->prepare("SELECT 
                            COUNT(DISTINCT a.id) as attempts_count, 
                            COALESCE(AVG(CASE WHEN a.result_published = 1 THEN a.score END), 0) as avg_score,
                            COALESCE(SUM(CASE WHEN a.result_published = 1 THEN a.correct_answers END), 0) as total_correct,
                            COALESCE(SUM(CASE WHEN a.result_published = 1 THEN a.wrong_answers END), 0) as total_wrong
                        FROM attempts a
                        WHERE a.student_id = ? AND a.attempt_status = 'completed'");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$all_stats = $stmt->get_result()->fetch_assoc();

// Count published vs unpublished attempts
$published_count_stmt = $conn->prepare("SELECT COUNT(*) as published_count FROM attempts WHERE student_id = ? AND result_published = 1 AND attempt_status = 'completed'");
$published_count_stmt->bind_param("i", $student_id);
$published_count_stmt->execute();
$published_data = $published_count_stmt->get_result()->fetch_assoc();
$published_count = $published_data['published_count'];

$total_completed = $all_stats['attempts_count'];
$unpublished_count = $total_completed - $published_count;

$stats = [
    'attempts_count' => $total_completed,
    'published_count' => $published_count,
    'unpublished_count' => $unpublished_count,
    'avg_score' => $all_stats['avg_score'],
    'total_correct' => $all_stats['total_correct'],
    'total_wrong' => $all_stats['total_wrong']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <style>
        .results-pending-notice {
            background: #fef3c7;
            border: 2px solid #fbbf24;
            border-radius: 10px;
            padding: 15px 20px;
            margin: 20px 0;
            text-align: center;
        }

        .results-pending-notice p {
            margin: 5px 0;
            color: #92400e;
        }

        .results-pending-notice strong {
            color: #78350f;
        }

        .locked-icon {
            display: block;
            margin-top: 8px;
            font-size: 12px;
            color: #9ca3af;
            font-weight: 600;
        }
        
        .badge {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            display: inline-block;
        }
        
        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }
        
        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }
        
        .badge-info {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .badge-locked {
            background: #fecaca;
            color: #7f1d1d;
        }
        
        .unlocked-badge {
            background: #d1fae5;
            color: #065f46;
            animation: pulse-glow 2s infinite;
        }
        
        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); }
            50% { box-shadow: 0 0 0 4px rgba(16, 185, 129, 0); }
        }
        
        .action-text {
            color: #6b7280;
            font-size: 13px;
            font-style: italic;
        }
        
        .action-locked {
            color: #ef4444;
            font-size: 13px;
            font-weight: 600;
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
                <a href="student_dashboard.php">Dashboard</a>
                <a href="view_result.php">My Results</a>
                <a href="../logout.php">Logout</a>
            </nav>
        </div>
    </div>

    <div class="container dashboard">
        <div class="welcome-section">
            <h2>Welcome, <?php echo htmlspecialchars(ucfirst($student['name'])); ?></h2>
            <p>Program: <?php echo htmlspecialchars($student['program']); ?></p>
        </div>

        <?php if ($stats['unpublished_count'] > 0): ?>
        <div class="results-pending-notice">
            <p><strong>⏳ Results Pending</strong></p>
            <p>You have <strong><?php echo $stats['unpublished_count']; ?></strong> puzzle result(s) waiting to be published by your teacher.</p>
            <p style="font-size: 13px; margin-top: 10px;">Published: <strong><?php echo $stats['published_count']; ?></strong> | Pending: <strong><?php echo $stats['unpublished_count']; ?></strong></p>
        </div>
        <?php endif; ?>

        <div class="stats-grid">
            <!-- Total Attempts Card -->
            <div class="stat-card">
                <h3><?php echo $stats['attempts_count']; ?></h3>
                <p>Total Attempts</p>
                <?php if ($stats['unpublished_count'] > 0): ?>
                    <span class="locked-icon">🔒 <?php echo $stats['unpublished_count']; ?> Hidden</span>
                <?php endif; ?>
            </div>

            <!-- Average Score Card (only published results) -->
            <div class="stat-card success">
                <h3>
                    <?php 
                    if ($stats['published_count'] > 0) {
                        echo number_format($stats['avg_score'], 1) . '%';
                    } else {
                        echo '--';
                    }
                    ?>
                </h3>
                <p>Average Score</p>
                <?php if ($stats['published_count'] == 0 && $stats['attempts_count'] > 0): ?>
                    <span class="locked-icon">🔒 No Published Results</span>
                <?php elseif ($stats['published_count'] > 0): ?>
                    <span class="locked-icon" style="color: #dceae6ff;">✓ <?php echo $stats['published_count']; ?> Published</span>
                <?php endif; ?>
            </div>

            <!-- Available Puzzles Card -->
            <div class="stat-card warning">
                <h3><?php echo $puzzles->num_rows; ?></h3>
                <p>Available Puzzles</p>
            </div>
        </div>

        <div class="content-box">
            <h3>Available Puzzles</h3>
            
            <?php if ($puzzles->num_rows > 0): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Title</th>
                                <th>Teacher</th>
                                <th>Grid Size</th>
                                <th>Time Limit</th>
                                <th>Created</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $counter = 1;
                            while ($puzzle = $puzzles->fetch_assoc()): 
                                $attempt_id = $puzzle['attempt_id'];
                                $attempt_status = $puzzle['attempt_status'];
                                $attempt_locked = $puzzle['attempt_locked'];
                                $result_published = $puzzle['result_published'] ?? 0;
                                $has_attempt = !empty($attempt_id);
                            ?>
                                <tr>
                                    <td><?php echo $counter++; ?></td>
                                    <td><strong><?php echo htmlspecialchars($puzzle['title']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($puzzle['teacher_name']); ?></td>
                                    <td><?php echo $puzzle['grid_size']; ?>×<?php echo $puzzle['grid_size']; ?></td>
                                    <td><?php echo $puzzle['time_limit']; ?> min</td>
                                    <td><?php echo formatDate($puzzle['created_at']); ?></td>
                                    <td>
                                        <?php 
                                        // STATUS COLUMN LOGIC
                                        
                                        if ($has_attempt && $attempt_locked == 0): 
                                            // Teacher unlocked
                                        ?>
                                            <span class="badge unlocked-badge">🔓 Retry Available</span>
                                        
                                        <?php 
                                        elseif ($has_attempt && $attempt_status === 'completed' && $attempt_locked == 1): 
                                            // Successfully completed
                                        ?>
                                            <span class="badge badge-success">✅ Completed</span>
                                        
                                        <?php 
                                        elseif ($has_attempt && $attempt_status === 'abandoned' && $attempt_locked == 1): 
                                            // Time ran out
                                        ?>
                                            <span class="badge badge-danger">⏰ Time Expired</span>
                                        
                                        <?php 
                                        elseif ($has_attempt && $attempt_status === 'in_progress' && $attempt_locked == 1): 
                                            // Student left/logged out
                                        ?>
                                            <span class="badge badge-locked">🔒 Left Puzzle</span>
                                        
                                        <?php 
                                        else: 
                                            // Not started
                                        ?>
                                            <span class="badge badge-warning">⭕ Not Started</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        // ACTION COLUMN LOGIC - PER-ATTEMPT PUBLISHING
                                        
                                        // Case 1: Teacher unlocked - allow retry
                                        if ($has_attempt && $attempt_locked == 0): 
                                        ?>
                                            <a href="play_puzzle.php?id=<?php echo $puzzle['id']; ?>" class="btn btn-primary">
                                                🔄 Retry Puzzle
                                            </a>
                                        
                                        <?php 
                                        // Case 2: Completed + THIS ATTEMPT is published
                                        elseif ($has_attempt && $attempt_status === 'completed' && $attempt_locked == 1 && $result_published == 1): 
                                        ?>
                                            <a href="view_result.php?puzzle=<?php echo $puzzle['id']; ?>" class="btn btn-secondary">
                                                📊 View Result
                                            </a>
                                        
                                        <?php 
                                        // Case 3: Completed but THIS ATTEMPT not published yet
                                        elseif ($has_attempt && $attempt_status === 'completed' && $attempt_locked == 1 && $result_published == 0): 
                                        ?>
                                            <span class="action-text">
                                                ⏳ Result Pending
                                            </span>
                                        
                                        <?php 
                                        // Case 4: Abandoned (time expired)
                                        elseif ($has_attempt && $attempt_status === 'abandoned' && $attempt_locked == 1): 
                                        ?>
                                            <span class="action-locked">
                                                🔒 Contact Teacher
                                            </span>
                                        
                                        <?php 
                                        // Case 5: Left puzzle (in_progress + locked)
                                        elseif ($has_attempt && $attempt_status === 'in_progress' && $attempt_locked == 1): 
                                        ?>
                                            <span class="action-locked">
                                                🔒 Contact Teacher
                                            </span>
                                        
                                        <?php 
                                        // Case 6: Not started
                                        else: 
                                        ?>
                                            <a href="play_puzzle.php?id=<?php echo $puzzle['id']; ?>" class="btn btn-primary">
                                                ▶️ Play Now
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: #6b7280; padding: 40px;">
                    📭 No puzzles available at the moment. Check back later!
                </p>
            <?php endif; ?>
        </div>
    </div>

    <script src="../assets/js/responsive.js"></script>
</body>
</html>
