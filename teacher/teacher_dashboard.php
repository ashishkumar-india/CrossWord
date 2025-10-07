<?php
require_once '../config.php';
require_once '../functions/helpers.php';

// Check if logged in
if (!isLoggedIn('teacher')) {
    redirect('auth/teacher_login.php');
}

$teacher = getCurrentUser('teacher');

// Get statistics
$teacher_id = $teacher['id'];

// Total students
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM students");
$stmt->execute();
$total_students = $stmt->get_result()->fetch_assoc()['count'];

// Total puzzles
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM puzzles WHERE teacher_id = ?");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$total_puzzles = $stmt->get_result()->fetch_assoc()['count'];

// Total results
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM attempts a 
                        INNER JOIN puzzles p ON a.puzzle_id = p.id 
                        WHERE p.teacher_id = ?");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$total_results = $stmt->get_result()->fetch_assoc()['count'];

// Handle publish/unpublish
if (isset($_POST['toggle_publish'])) {
    $puzzle_id = $_POST['puzzle_id'];
    $new_status = $_POST['new_status'];
    
    $stmt = $conn->prepare("UPDATE puzzles SET is_published = ? WHERE id = ? AND teacher_id = ?");
    $stmt->bind_param("iii", $new_status, $puzzle_id, $teacher_id);
    $stmt->execute();
    
    header("Location: teacher_dashboard.php");
    exit();
}

// Get recent puzzles
$stmt = $conn->prepare("SELECT * FROM puzzles WHERE teacher_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$recent_puzzles = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard</title>
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
        <div class="welcome-section">
            <h2>Welcome, <?php echo strtoupper($teacher['name']); ?></h2>
        </div>

        <div class="stats-grid">
            <div class="stat-card" onclick="window.location.href='view_students.php'">
                <h3><?php echo $total_students; ?></h3>
                <p>Total Students</p>
            </div>

            <div class="stat-card success" onclick="window.location.href='manage_puzzles.php'">
                <h3><?php echo $total_puzzles; ?></h3>
                <p>Total Puzzles</p>
            </div>

            <div class="stat-card warning" onclick="window.location.href='manage_students.php'">
                <h3><?php echo $total_results; ?></h3>
                <p>Total Results</p>
            </div>
        </div>

        <div class="content-box">
            <h3>Recent Puzzles</h3>
            
            <?php if ($recent_puzzles->num_rows > 0): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th style="text-align: center;">Title</th>
                                <th style="text-align: center;">Created</th>
                                <th style="text-align: center;">Status</th>
                                <th style="text-align: center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($puzzle = $recent_puzzles->fetch_assoc()): ?>
                                <tr>
                                    <td style="text-align: center;"><?php echo htmlspecialchars($puzzle['title']); ?></td>
                                    <td style="text-align: center;"><?php echo formatDate($puzzle['created_at']); ?></td>
                                    <td style="text-align: center;">
                                        <?php if ($puzzle['is_active']): ?>
                                            <span class="badge badge-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons" style="justify-content: center;">
                                            <a href="edit_puzzle.php?id=<?php echo $puzzle['id']; ?>" class="btns btn-primarys">Edit</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: #6b7280; padding: 30px;">
                    No puzzles created yet. <a href="create_puzzle.php" class="link">Create your first puzzle</a>
                </p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Hamburger menu functionality
        document.addEventListener('DOMContentLoaded', function() {
            const hamburger = document.getElementById('hamburger');
            const navMenu = document.getElementById('navMenu');
            
            if (hamburger && navMenu) {
                // Toggle menu
                hamburger.addEventListener('click', function(e) {
                    e.stopPropagation();
                    hamburger.classList.toggle('active');
                    navMenu.classList.toggle('active');
                });

                // Close menu on link click
                const navLinks = navMenu.querySelectorAll('a');
                navLinks.forEach(link => {
                    link.addEventListener('click', function() {
                        hamburger.classList.remove('active');
                        navMenu.classList.remove('active');
                    });
                });

                // Close menu when clicking outside
                document.addEventListener('click', function(event) {
                    const navbar = document.querySelector('.navbar');
                    if (navbar && !navbar.contains(event.target)) {
                        hamburger.classList.remove('active');
                        navMenu.classList.remove('active');
                    }
                });

                // Close menu on resize to desktop
                window.addEventListener('resize', function() {
                    if (window.innerWidth > 768) {
                        hamburger.classList.remove('active');
                        navMenu.classList.remove('active');
                    }
                });
            }
        });
    </script>
</body>
</html>
