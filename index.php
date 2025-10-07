<?php
require_once 'config.php';
require_once 'functions/helpers.php';  // ADD THIS LINE

// Redirect if already logged in
if (isLoggedIn('student')) {
    redirect('student/student_dashboard.php');
}
if (isLoggedIn('teacher')) {
    redirect('teacher/teacher_dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crossword Game - Home</title>
    <link rel="stylesheet" href="assets/css/style.css">
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
            <h1> Crossword Game</h1>
        </div>
    </div>

    <div class="container">
        <div class="auth-container">
            <div class="auth-box" style="max-width: 600px;">
                <h2 style="text-align: center; margin-bottom: 30px;">Welcome to Crossword Game</h2>
                <p style="text-align: center; font-size: 18px;">Choose your role to continue</p>
                
                <div class="stats-grid" style="margin-top: 40px;">
                    <div class="stat-card" onclick="window.location.href='auth/student_login.php'">
                        
                        <p style="font-size: 20px; font-weight: 600;color: #fff">Student Login</p>
                        <p style="font-size: 14px; margin-top: 10px;color: #fff">Play puzzles and view results</p>
                    </div>
                    
                    <div class="stat-card success" onclick="window.location.href='auth/teacher_login.php'">
                        
                        <p style="font-size: 20px; font-weight: 600;color: #fff">Teacher Login</p>
                        <p style="font-size: 14px; margin-top: 10px;color: #fff">Create and manage puzzles</p>
                    </div>
                </div>

                <div style="margin-top: 40px; padding-top: 30px; border-top: 2px solid #e5e7eb;">
                    <p style="text-align: center; color: #6b7280; margin-bottom: 15px;">Don't have an account?</p>
                    <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                        <a href="auth/student_register.php" class="btns btn-primarys" style="width: auto;">Register as Student</a>
                        <a href="auth/teacher_register.php" class="btns btn-secondarys" style="width: auto;">Register as Teacher</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
