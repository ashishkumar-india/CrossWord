<?php
require_once '../config.php';
require_once '../functions/helpers.php';

// Check if logged in
if (!isLoggedIn('teacher')) {
    redirect('auth/teacher_login.php');
}

$teacher = getCurrentUser('teacher');
$teacher_id = $teacher['id'];

// Get puzzle ID
if (!isset($_GET['id'])) {
    $_SESSION['error'] = 'Puzzle ID not provided';
    header('Location: manage_puzzles.php');
    exit();
}

$puzzle_id = intval($_GET['id']);

// Get puzzle data
$stmt = $conn->prepare("SELECT * FROM puzzles WHERE id = ? AND teacher_id = ?");
$stmt->bind_param("ii", $puzzle_id, $teacher_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = 'Puzzle not found or unauthorized';
    header('Location: manage_puzzles.php');
    exit();
}

$puzzle = $result->fetch_assoc();

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title']);
    $time_limit = intval($_POST['time_limit']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    $update_stmt = $conn->prepare("UPDATE puzzles SET title = ?, time_limit = ?, is_active = ? WHERE id = ? AND teacher_id = ?");
    $update_stmt->bind_param("siiii", $title, $time_limit, $is_active, $puzzle_id, $teacher_id);
    
    if ($update_stmt->execute()) {
        $_SESSION['message'] = 'Puzzle updated successfully!';
        header('Location: manage_puzzles.php');
        exit();
    } else {
        $error = 'Failed to update puzzle';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Puzzle</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <style>
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

.btn-primarys {
    background-color: var(--primary);
    color: white;
}

.btn-primarys:hover {
    background-color: var(--primary-dark);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}
.btn-secondarys {
    background-color: #6b7280;
    color: white;
}

.btn-secondarys:hover {
    background-color: #4b5563;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(107, 114, 128, 0.3);
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
        <div class="content-box">
            <h3>✏️ Edit Puzzle</h3>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <div class="puzzle-info">
                <p><strong>Puzzle ID:</strong> #<?php echo $puzzle['id']; ?></p>
                <p><strong>Created:</strong> <?php echo formatDate($puzzle['created_at']); ?></p>
                <p><strong>Grid Size:</strong> <?php echo $puzzle['grid_size']; ?>×<?php echo $puzzle['grid_size']; ?></p>
                <p style="color: #ef4444; margin-top: 10px;">⚠️ Note: You can only edit the title, time limit, and status. Grid and clues cannot be changed.</p>
            </div>

            <form method="POST" class="form-container">
                <div class="form-group">
                    <label for="title">Puzzle Title *</label>
                    <input type="text" id="title" name="title" required 
                           value="<?php echo htmlspecialchars($puzzle['title']); ?>">
                </div>

                <div class="form-group">
                    <label for="time_limit">Time Limit (minutes) *</label>
                    <input type="number" id="time_limit" name="time_limit" min="1" max="120" required
                           value="<?php echo $puzzle['time_limit']; ?>">
                </div>

                <div class="form-group">
                    <div class="checkbox-wrapper">
                        <div style="float: left;"><input type="checkbox" id="is_active" name="is_active" value="1" 
                               <?php echo $puzzle['is_active'] ? 'checked' : ''; ?>></div>
                        <label for="is_active" style="margin: 0;">Make this puzzle active (visible to students)</label>
                    </div>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btns btn-primarys">💾 Save Changes</button>
                    <a href="teacher_dashboard.php" class="btns btn-secondarys"> ❌Cancel</a>
                </div>
            </form>
        </div>
    </div>
    
<script src="../assets/js/responsive.js"></script>
</body>
</html>

