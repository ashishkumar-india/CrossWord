<?php
require_once '../config.php';
require_once '../functions/helpers.php';

// Check if logged in
if (!isLoggedIn('teacher')) {
    redirect('auth/teacher_login.php');
}

$teacher = getCurrentUser('teacher');

// Handle delete student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_student'])) {
    $student_id = intval($_POST['student_id']);
    
    if ($student_id > 0) {
        try {
            $conn->begin_transaction();
            
            // Delete student attempts first (foreign key)
            $delete_attempts = $conn->prepare("DELETE FROM attempts WHERE student_id = ?");
            $delete_attempts->bind_param("i", $student_id);
            $delete_attempts->execute();
            
            // Delete student
            $delete_student = $conn->prepare("DELETE FROM students WHERE id = ?");
            $delete_student->bind_param("i", $student_id);
            
            if ($delete_student->execute()) {
                $conn->commit();
                $_SESSION['message'] = "Student deleted successfully!";
            } else {
                throw new Exception("Failed to delete student");
            }
        } catch (Exception $e) {
            $conn->rollback();
            error_log("Delete Error: " . $e->getMessage());
            $_SESSION['error'] = "Failed to delete student: " . $e->getMessage();
        }
    }
    
    header("Location: view_students.php");
    exit();
}

// Handle enable/disable student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_student'])) {
    $student_id = intval($_POST['student_id']);
    $action = intval($_POST['toggle_action']); // 1 = enable, 0 = disable
    
    if ($student_id > 0) {
        try {
            // Add 'is_active' column if it doesn't exist
            $check_column = $conn->query("SHOW COLUMNS FROM students LIKE 'is_active'");
            if ($check_column->num_rows == 0) {
                $conn->query("ALTER TABLE students ADD COLUMN is_active TINYINT(1) DEFAULT 1 AFTER results_published");
            }
            
            $toggle_stmt = $conn->prepare("UPDATE students SET is_active = ? WHERE id = ?");
            $toggle_stmt->bind_param("ii", $action, $student_id);
            
            if ($toggle_stmt->execute()) {
                $status = $action ? "enabled" : "disabled";
                $_SESSION['message'] = "Student {$status} successfully!";
            } else {
                $_SESSION['error'] = "Failed to update student status";
            }
        } catch (Exception $e) {
            error_log("Toggle Error: " . $e->getMessage());
            $_SESSION['error'] = "Error: " . $e->getMessage();
        }
    }
    
    header("Location: view_students.php");
    exit();
}

$students = [];

// Simple query to get students with attempt counts
try {
    $query = "
    SELECT 
        s.id, 
        s.name, 
        s.email, 
        s.program, 
        s.created_at,
        COALESCE(s.is_active, 1) as is_active,
        COUNT(a.id) as attempts_count
    FROM 
        students s 
    LEFT JOIN 
        attempts a ON s.id = a.student_id AND a.attempt_status = 'completed'
    GROUP BY 
        s.id
    ORDER BY 
        s.created_at DESC";


    $stmt = $conn->prepare($query);
    
    if ($stmt === false) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute statement: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    
    $stmt->close();
} catch (Exception $e) {
    error_log("Database Error: " . $e->getMessage());
    $error_message = "An error occurred while retrieving student data.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Students - Crossword Game</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    
    <style>
   .status-badge {
            padding: 5px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        .status-active {
            background: #d1fae5;
            color: #065f46;
        }
        .status-inactive {
            background: #fee2e2;
            color: #991b1b;
        }
        .action-btn {
            border: none;
            padding: 8px 16px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 13px;
            margin: 2px;
            cursor: pointer;
            border-radius: 6px;
            transition: all 0.3s;
            font-weight: 500;
        }
        .btn-enable {
            background-color: #10b981;
            color: white;
        }
        .btn-enable:hover {
            background-color: #059669;
            transform: translateY(-2px);
        }
        .btn-disable {
            background-color: #f59e0b;
            color: white;
        }
        .btn-disable:hover {
            background-color: #d97706;
            transform: translateY(-2px);
        }
        .btn-delete {
            background-color: #ef4444;
            color: white;
        }
        .btn-delete:hover {
            background-color: #dc2626;
            transform: translateY(-2px);
        }
        .no-data {
            text-align: center;
            color: #6b7280;
            padding: 60px 20px;
            font-size: 16px;
        }
        .no-data-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .stat-highlight {
            font-weight: 700;
            color: #6366f1;
            font-size: 16px;
        }
        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
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
            <h3>👥 View Students</h3>
            <!-- <p style="color: #6b7280; margin-bottom: 20px;">Manage student accounts and view basic information</p> -->
            
            <?php 
            if (isset($_SESSION['error'])) {
                echo "<div class='error-message'>" . htmlspecialchars($_SESSION['error']) . "</div>";
                unset($_SESSION['error']);
            }
            
            if (isset($_SESSION['message'])) {
                echo "<div class='success-message'>" . htmlspecialchars($_SESSION['message']) . "</div>";
                unset($_SESSION['message']);
            }
            
            if (isset($error_message)) {
                echo "<div class='error-message'>" . htmlspecialchars($error_message) . "</div>";
            }
            ?>
            
            <?php if (!empty($students)): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Program</th>
                                <th>Registered Date</th>
                                <th>Total Attempts</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $index => $student): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><strong><?php echo htmlspecialchars($student['name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                                    <td><?php echo htmlspecialchars($student['program']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($student['created_at'])); ?></td>
                                    <td>
                                        <span class="stat-highlight"><?php echo intval($student['attempts_count']); ?></span>
                                    </td>
                                    <td>
                                        <?php 
                                        $is_active = $student['is_active'] ?? 1;
                                        if ($is_active): 
                                        ?>
                                            <span class="status-badge status-active">✓ Active</span>
                                        <?php else: ?>
                                            <span class="status-badge status-inactive">● Disabled</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <!-- Enable/Disable Button -->
                                            <form method="POST" style="display: inline; margin: 0;">
                                                <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                                <?php if ($is_active): ?>
                                                    <input type="hidden" name="toggle_action" value="0">
                                                    <button type="submit" name="toggle_student" class="action-btn btn-disable" 
                                                            onclick="return confirm('Disable this student? They will not be able to login.');">
                                                        🔒 Disable
                                                    </button>
                                                <?php else: ?>
                                                    <input type="hidden" name="toggle_action" value="1">
                                                    <button type="submit" name="toggle_student" class="action-btn btn-enable"
                                                            onclick="return confirm('Enable this student?');">
                                                        ✓ Enable
                                                    </button>
                                                <?php endif; ?>
                                            </form>
                                            
                                            <!-- Delete Button -->
                                            <form method="POST" style="display: inline; margin: 0;">
                                                <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                                <button type="submit" name="delete_student" class="action-btn btn-delete" 
                                                        onclick="return confirm('⚠️ WARNING: Delete student \'<?php echo htmlspecialchars($student['name']); ?>\'?\n\nThis will permanently delete:\n- Student account\n- All their attempts\n- All their results\n\nThis action CANNOT be undone!');">
                                                    🗑️ Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div style="margin-top: 20px; padding: 15px; background: #f9fafb; border-radius: 8px; text-align: center;">
                    <p style="color: #6b7280; margin: 0;">
                        <strong>Total Students:</strong> <?php echo count($students); ?> | 
                        <strong>Total Attempts:</strong> <?php echo array_sum(array_column($students, 'attempts_count')); ?>
                    </p>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <div class="no-data-icon">📭</div>
                    <p><strong>No students registered yet</strong></p>
                    <p style="font-size: 14px; color: #9ca3af;">Students will appear here once they register</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
<script src="../assets/js/responsive.js"></script>