<?php
require_once '../config.php';
require_once '../functions/helpers.php';

$error = '';

// Rate limiting - prevent brute force attacks
$max_attempts = 5;
$lockout_time = 900; // 15 minutes

// Initialize login attempts tracking
if (!isset($_SESSION['teacher_login_attempts'])) {
    $_SESSION['teacher_login_attempts'] = 0;
    $_SESSION['teacher_last_attempt_time'] = time();
}

// Check if account is locked out
if (isset($_SESSION['teacher_lockout_until']) && time() < $_SESSION['teacher_lockout_until']) {
    $remaining = ceil(($_SESSION['teacher_lockout_until'] - time()) / 60);
    $error = "Too many failed login attempts. Please try again in {$remaining} minutes.";
} elseif (isset($_SESSION['teacher_lockout_until']) && time() >= $_SESSION['teacher_lockout_until']) {
    // Reset after lockout expires
    unset($_SESSION['teacher_lockout_until']);
    $_SESSION['teacher_login_attempts'] = 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_SESSION['teacher_lockout_until'])) {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Invalid security token. Please refresh and try again.';
    } else {
        $email = sanitize($_POST['email']);
        $password = $_POST['password'];
        
        // Validation
        if (empty($email) || empty($password)) {
            $error = 'All fields are required';
            $_SESSION['teacher_login_attempts']++;
        } else {
            // Check credentials
            $stmt = $conn->prepare("SELECT * FROM teachers WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                $error = 'Invalid email or password';
                $_SESSION['teacher_login_attempts']++;
                
                // LOG FAILED LOGIN (teacher not found)
                logLogin($conn, 'teacher', 0, $email, 'failed');
            } else {
                $teacher = $result->fetch_assoc();
                
                if (verifyPassword($password, $teacher['password'])) {
                    // Successful login - Reset attempts
                    $_SESSION['teacher_login_attempts'] = 0;
                    unset($_SESSION['teacher_last_attempt_time']);
                    unset($_SESSION['teacher_lockout_until']);
                    
                    // Regenerate session ID to prevent session fixation
                    session_regenerate_id(true);
                    
                    // Set session data
                    $_SESSION['teacher_id'] = $teacher['id'];
                    $_SESSION['teacher_name'] = $teacher['name'];
                    $_SESSION['teacher_email'] = $teacher['email'];
                    $_SESSION['last_activity'] = time();
                    $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'];
                    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
                    
                    // LOG SUCCESSFUL LOGIN
                    logLogin($conn, 'teacher', $teacher['id'], $teacher['email'], 'success');
                    
                    redirect('teacher/teacher_dashboard.php');
                } else {
                    $error = 'Invalid email or password';
                    $_SESSION['teacher_login_attempts']++;
                    
                    // LOG FAILED LOGIN (wrong password)
                    logLogin($conn, 'teacher', $teacher['id'], $email, 'failed');
                }
            }
            
            // Check if max attempts reached
            if ($_SESSION['teacher_login_attempts'] >= $max_attempts) {
                $_SESSION['teacher_lockout_until'] = time() + $lockout_time;
                $error = "Too many failed login attempts. Your account has been locked for 15 minutes.";
            }
        }
    }
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Login</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            margin: 0;
        }
        
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .navbar .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .navbar h1 {
            margin: 0;
            font-size: 24px;
            color: #6366f1;
        }
        
        .navbar nav {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        
        .navbar nav a {
            text-decoration: none;
            color: #374151;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .navbar nav a:hover {
            color: #6366f1;
        }
        
        .menu-toggle {
            display: none;
            flex-direction: column;
            cursor: pointer;
            gap: 4px;
        }
        
        .menu-toggle span {
            width: 25px;
            height: 3px;
            background: #374151;
            border-radius: 2px;
            transition: all 0.3s;
        }
        
        @media (max-width: 768px) {
            .navbar h1 {
                font-size: 20px;
            }
            
            .menu-toggle {
                display: flex;
            }
            
            .navbar nav {
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: white;
                flex-direction: column;
                gap: 0;
                padding: 0;
                max-height: 0;
                overflow: hidden;
                transition: max-height 0.3s ease;
                box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            }
            
            .navbar nav.active {
                max-height: 300px;
            }
            
            .navbar nav a {
                padding: 15px 20px;
                border-bottom: 1px solid #e5e7eb;
                width: 100%;
                text-align: center;
            }
            
            .navbar nav a:last-child {
                border-bottom: none;
            }
        }
        
        .main-container {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: calc(100vh - 80px);
            padding: 40px 20px;
        }
        
        .auth-box {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 450px;
        }
        
        .auth-box h2 {
            text-align: center;
            color: #1f2937;
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .auth-box > p {
            text-align: center;
            color: #6b7280;
            margin-bottom: 30px;
            font-size: 15px;
        }
        
        .error-message {
            background: #fee2e2;
            border-left: 4px solid #ef4444;
            color: #991b1b;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .lockout-warning {
            background: #fee2e2;
            border-left: 4px solid #dc2626;
            color: #7f1d1d;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 600;
        }
        
        .btn-primary {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }
        
        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        
        .btn-primary:disabled {
            background: #9ca3af;
            cursor: not-allowed;
            transform: none;
        }
        
        .security-badge {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 15px;
            padding: 10px;
            background: #f0fdf4;
            border-radius: 8px;
            font-size: 12px;
            color: #166534;
        }
        
        .attempts-warning {
            text-align: center;
            color: #dc2626;
            font-size: 13px;
            margin-top: 10px;
            font-weight: 600;
        }
        
        .text-center {
            text-align: center;
        }
        
        .mt-20 {
            margin-top: 20px;
        }
        
        .link {
            color: #6366f1;
            text-decoration: none;
            font-weight: 600;
        }
        
        .link:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 480px) {
            .auth-box {
                padding: 30px 20px;
            }
            
            .auth-box h2 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="container">
            <h1>Crossword Game</h1>
            <div class="menu-toggle" onclick="toggleMenu()">
                <span></span>
                <span></span>
                <span></span>
            </div>
            <nav id="navMenu">
                <a href="../index.php">Home</a>
                <a href="teacher_register.php">Register</a>
                <a href="student_login.php">Student Login</a>
            </nav>
        </div>
    </div>

    <div class="main-container">
        <div class="auth-box">
            <h2>👨‍🏫 Teacher Login</h2>
            <p>Login to manage puzzles</p>

            <?php if ($error): ?>
                <?php 
                $errorClass = (strpos($error, 'locked') !== false || strpos($error, 'Too many') !== false) ? 'lockout-warning' : 'error-message';
                ?>
                <div class="<?php echo $errorClass; ?>">
                    <?php 
                    if (strpos($error, 'locked') !== false || strpos($error, 'Too many') !== false) {
                        echo '<strong>⚠️ Security Lockout</strong><br>';
                    }
                    echo htmlspecialchars($error); 
                    ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" id="email" name="email" required 
                           placeholder="teacher@example.com"
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                           <?php echo (isset($_SESSION['teacher_lockout_until']) && time() < $_SESSION['teacher_lockout_until']) ? 'disabled' : ''; ?>>
                </div>

                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" required
                           placeholder="Enter your password"
                           <?php echo (isset($_SESSION['teacher_lockout_until']) && time() < $_SESSION['teacher_lockout_until']) ? 'disabled' : ''; ?>>
                </div>

                <button type="submit" class="btn btn-primary" 
                        <?php echo (isset($_SESSION['teacher_lockout_until']) && time() < $_SESSION['teacher_lockout_until']) ? 'disabled' : ''; ?>>
                    Login
                </button>
                
                <?php if (isset($_SESSION['teacher_login_attempts']) && $_SESSION['teacher_login_attempts'] > 0 && $_SESSION['teacher_login_attempts'] < $max_attempts): ?>
                    <div class="attempts-warning">
                        ⚠️ <?php echo $max_attempts - $_SESSION['teacher_login_attempts']; ?> attempts remaining
                    </div>
                <?php endif; ?>
                
                <!-- <div class="security-badge">
                    <span>🔒</span>
                    <span>Secured with CSRF protection & rate limiting</span>
                </div> -->
            </form>

            <p class="text-center mt-20">
                Don't have an account? 
                <a href="teacher_register.php" class="link">Register here</a>
            </p>
        </div>
    </div>

    <script>
        function toggleMenu() {
            const navMenu = document.getElementById('navMenu');
            navMenu.classList.toggle('active');
        }
        
        document.addEventListener('click', function(event) {
            const navbar = document.querySelector('.navbar');
            const navMenu = document.getElementById('navMenu');
            
            if (!navbar.contains(event.target) && navMenu.classList.contains('active')) {
                navMenu.classList.remove('active');
            }
        });
    </script>
</body>
</html>
