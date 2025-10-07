<?php
require_once '../config.php';
require_once '../functions/helpers.php';

// Check if logged in
if (!isLoggedIn('teacher')) {
    redirect('auth/teacher_login.php');
}

$teacher = getCurrentUser('teacher');
$teacher_id = $teacher['id'];

// Handle delete
if (isset($_GET['delete'])) {
    $puzzle_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM puzzles WHERE id = ? AND teacher_id = ?");
    $stmt->bind_param("ii", $puzzle_id, $teacher_id);
    $stmt->execute();
    header("Location: manage_puzzles.php");
    exit();
}

// Handle toggle active
if (isset($_GET['toggle'])) {
    $puzzle_id = intval($_GET['toggle']);
    $stmt = $conn->prepare("UPDATE puzzles SET is_active = NOT is_active WHERE id = ? AND teacher_id = ?");
    $stmt->bind_param("ii", $puzzle_id, $teacher_id);
    $stmt->execute();
    header("Location: manage_puzzles.php");
    exit();
}

// Get all puzzles
$stmt = $conn->prepare("SELECT p.*, COUNT(a.id) as attempts_count 
                        FROM puzzles p 
                        LEFT JOIN attempts a ON p.id = a.puzzle_id 
                        WHERE p.teacher_id = ? 
                        GROUP BY p.id 
                        ORDER BY p.created_at DESC");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$puzzles = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Puzzles</title>
    <link rel="stylesheet" href="../assets/css/style.css">
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
            <h3>Manage Puzzles</h3>
            
            <?php if ($puzzles->num_rows > 0): ?>
                <div class="table-responsive">
                    <table >
                        <thead>
                            <tr>
                                <th style="text-align: center;">ID</th>
                                <th style="text-align: center;">Title</th>
                                <th style="text-align: center;">Grid Size</th>
                                <th style="text-align: center;">Created</th>
                                <th style="text-align: center;">Status</th>
                                <th style="text-align: center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($puzzle = $puzzles->fetch_assoc()): ?>
                                <tr>
                                    <td style="text-align: center;"><?php echo $puzzle['id']; ?></td>
                                    <td style="text-align: center;"><?php echo htmlspecialchars($puzzle['title']); ?></td>
                                    <td style="text-align: center;"><?php echo $puzzle['grid_size']; ?>x<?php echo $puzzle['grid_size']; ?></td>
                                    <td style="text-align: center;"><?php echo formatDate($puzzle['created_at']); ?></td>
                                    <td style="text-align: center;">
                                        <?php if ($puzzle['is_active']): ?>
                                            <span class="badge badge-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td >
                                        <div class="action-buttons" style="text-align: center; display: flex; justify-content: center; flex-wrap: wrap;">
                                                <a href="?toggle=<?php echo $puzzle['id']; ?>" 
                                                class="btn <?php echo $puzzle['is_active'] ? 'btn-warning' : 'btn-success'; ?>"
                                                style="padding: 8px 16px; font-size: 13px; min-width: 100px; text-align: center;"
                                                onclick="return confirm('Toggle puzzle status?')">
                                                    <?php echo $puzzle['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                                </a>
                                                <a href="edit_puzzle.php?id=<?php echo $puzzle['id']; ?>" 
                                                class="btn btn-info"
                                                style="padding: 8px 16px; font-size: 13px; min-width: 100px; text-align: center;"
                                                onclick="return confirm('Are you sure you want to Edit this puzzle?')">
                                                    Edit
                                                </a>

                                                <a href="?delete=<?php echo $puzzle['id']; ?>" 
                                                class="btn btn-danger"
                                                style="padding: 8px 16px; font-size: 13px; min-width: 100px; text-align: center;"
                                                onclick="return confirm('Are you sure you want to delete this puzzle?')">
                                                    Delete
                                                </a>
                                            </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: #6b7280; padding: 30px;">
                    No puzzles found. <a href="create_puzzle.php" class="link">Create your first puzzle</a>
                </p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
<script src="../assets/js/responsive.js"></script>