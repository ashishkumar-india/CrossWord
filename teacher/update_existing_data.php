<?php
require_once '../config.php';
require_once '../functions/calculate_answers.php';

// Check if logged in as teacher
if (!isset($_SESSION['teacher_id'])) {
    header("Location: ../auth/teacher_login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Database</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .update-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .success-box {
            color: #388e3c;
            background-color: #e8f5e9;
            padding: 15px;
            border: 1px solid #66bb6a;
            border-radius: 6px;
            margin: 20px 0;
        }
        .error-box {
            color: #d32f2f;
            background-color: #ffebee;
            padding: 15px;
            border: 1px solid #ef5350;
            border-radius: 6px;
            margin: 20px 0;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #2196F3;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="update-container">
        <h2>Update Attempt Statistics</h2>
        <p>This will calculate and update correct/wrong answers for all existing attempts.</p>
        
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = updateAllAttemptStatistics($conn);
            
            if ($result['success']) {
                echo "<div class='success-box'>";
                echo "<strong>Success!</strong><br>";
                echo htmlspecialchars($result['message']);
                echo "</div>";
            } else {
                echo "<div class='error-box'>";
                echo "<strong>Error!</strong><br>";
                echo htmlspecialchars($result['message']);
                echo "</div>";
            }
        }
        ?>
        
        <form method="POST">
            <button type="submit" class="btn" onclick="return confirm('Are you sure you want to update all attempts?');">
                Run Update
            </button>
        </form>
        
        <a href="manage_students.php" class="btn" style="background: #757575;">Back to Manage Students</a>
    </div>
</body>
</html>
