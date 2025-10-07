<?php
require_once '../config.php';
require_once '../functions/helpers.php';

$error = '';

// Rate limiting configuration
$max_attempts = 5;
$lockout_time = 900; // 15 minutes

// Initialize login attempts tracking
if (!isset($_SESSION['student_login_attempts'])) {
    $_SESSION['student_login_attempts'] = 0;
    $_SESSION['student_last_attempt_time'] = time();
}

// Check if account is locked out
if (isset($_SESSION['student_lockout_until']) && time() < $_SESSION['student_lockout_until']) {
    $remaining = ceil(($_SESSION['student_lockout_until'] - time()) / 60);
    $error = "Too many failed login attempts. Please try again in {$remaining} minutes.";
} elseif (isset($_SESSION['student_lockout_until']) && time() >= $_SESSION['student_lockout_until']){
    unset($_SESSION['student_lockout_until']);
    $_SESSION['student_login_attempts'] = 0;
}

// Check IP-based rate limiting
if (!checkIPRateLimit($conn, 'student_login', 10, 900)) {
    $error = 'Too many failed attempts from your network. Please try again later.';
    logSecurityEvent('IP_RATE_LIMIT', 'Blocked login from ' . $_SERVER['REMOTE_ADDR']);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_SESSION['student_lockout_until'])) {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token'])) {
        $error = 'Security token missing. Please refresh and try again.';
        logSecurityEvent('CSRF', 'Missing CSRF token on student login');
    } elseif (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = 'Invalid security token. Please refresh and try again.';
        logSecurityEvent('CSRF', 'Invalid CSRF token on student login');
    } else {
        $name = sanitize($_POST['name']);
        $program = sanitize($_POST['program']);
        $password = $_POST['password'];
        
        // Input validation
        if (empty($name) || empty($program) || empty($password)) {
            $error = 'All fields are required';
            $_SESSION['student_login_attempts']++;
        } elseif (strlen($name) > 100 || strlen($program) > 50 || strlen($password) > 255) {
            $error = 'Invalid input length';
            $_SESSION['student_login_attempts']++;
            logSecurityEvent('INPUT', 'Excessive input length on student login');
        } elseif (!in_array($program, ['MSc AI', 'MSc CS'])) {
            $error = 'Invalid program selection';
            $_SESSION['student_login_attempts']++;
            logSecurityEvent('INPUT', 'Invalid program value: ' . $program);
        } else {
            // Query with email included
            $stmt = $conn->prepare("SELECT id, name, email, program, password, is_active FROM students WHERE name = ? AND program = ?");
            $stmt->bind_param("ss", $name, $program);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $login_success = false;
            $user_id = 0;
            $user_email = $name;
            
            if ($result->num_rows === 0) {
                // CRITICAL: Dummy password verification to prevent timing attacks
                password_verify($password, '$2y$10$abcdefghijklmnopqrstuvwxyz123456789ABCDEFGHIJKLMN');
                $error = 'Invalid credentials';
                $_SESSION['student_login_attempts']++;
            } else {
                $student = $result->fetch_assoc();
                $user_id = $student['id'];
                $user_email = $student['email'] ?? $student['name'];
                
                // CRITICAL: Always verify password first
                $password_valid = verifyPassword($password, $student['password']);
                
                // Check account status AFTER password verification
                if (isset($student['is_active']) && $student['is_active'] == 0) {
                    $error = 'Your account has been disabled by the teacher. Please contact your teacher.';
                    $_SESSION['student_login_attempts']++;
                    logSecurityEvent('ACCESS', 'Disabled account login attempt: ' . $name, $student['id']);
                } elseif ($password_valid) {
                    $login_success = true;
                } else {
                    $error = 'Invalid credentials';
                    $_SESSION['student_login_attempts']++;
                }
            }
            
            // Log the attempt
            logLogin($conn, 'student', $user_id, $user_email, $login_success ? 'success' : 'failed');
            
            // Handle successful login
            if ($login_success) {
                // Reset attempts
                $_SESSION['student_login_attempts'] = 0;
                unset($_SESSION['student_last_attempt_time']);
                unset($_SESSION['student_lockout_until']);
                
                // Use helper function for secure login
                loginUser('student', $student['id'], [
                    'name' => $student['name'],
                    'program' => $student['program']
                ]);
                
                // Redirect to dashboard
                header('Location: ../student/student_dashboard.php');
                exit();
            }
            
            // Check if max attempts reached
            if ($_SESSION['student_login_attempts'] >= $max_attempts) {
                $_SESSION['student_lockout_until'] = time() + $lockout_time;
                $error = "Too many failed login attempts. Your account has been locked for 15 minutes.";
                logSecurityEvent('LOCKOUT', 'Student account locked due to failed attempts: ' . $name);
            }
        }
    }
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['csrf_token_time'] = time();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login - Crossword Game</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);min-height:100vh;margin:0}
        .navbar{background:rgba(255,255,255,0.95);backdrop-filter:blur(10px);box-shadow:0 2px 10px rgba(0,0,0,0.1);position:sticky;top:0;z-index:1000}
        .navbar .container{display:flex;justify-content:space-between;align-items:center;padding:15px 20px;max-width:1200px;margin:0 auto}
        .navbar h1{margin:0;font-size:24px;color:#6366f1}
        .navbar nav{display:flex;gap:20px;align-items:center}
        .navbar nav a{text-decoration:none;color:#374151;font-weight:500;transition:color 0.3s}
        .navbar nav a:hover{color:#6366f1}
        .main-container{display:flex;align-items:center;justify-content:center;min-height:calc(100vh - 80px);padding:40px 20px}
        .auth-box{background:white;padding:40px;border-radius:20px;box-shadow:0 20px 60px rgba(0,0,0,0.3);width:100%;max-width:450px}
        .auth-box h2{text-align:center;color:#1f2937;margin-bottom:10px;font-size:28px}
        .auth-box>p{text-align:center;color:#6b7280;margin-bottom:30px;font-size:15px}
        .error-message{background:#fee2e2;border-left:4px solid #ef4444;color:#991b1b;padding:15px 20px;border-radius:8px;margin-bottom:20px;font-size:14px}
        .lockout-warning{background:#fee2e2;border-left:4px solid #dc2626;color:#7f1d1d;padding:15px 20px;border-radius:8px;margin-bottom:20px;font-size:14px;font-weight:600}
        .btn-primary{width:100%;padding:14px;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;border:none;border-radius:8px;font-size:16px;font-weight:600;cursor:pointer;transition:all 0.3s;margin-top:10px}
        .btn-primary:hover:not(:disabled){transform:translateY(-2px);box-shadow:0 10px 25px rgba(102,126,234,0.4)}
        .btn-primary:disabled{background:#9ca3af;cursor:not-allowed}
        .security-badge{display:flex;align-items:center;gap:8px;margin-top:15px;padding:10px;background:#f0fdf4;border-radius:8px;font-size:12px;color:#166534}
        .attempts-warning{text-align:center;color:#dc2626;font-size:13px;margin-top:10px;font-weight:600}
        .text-center{text-align:center}
        .mt-20{margin-top:20px}
        .link{color:#6366f1;text-decoration:none;font-weight:600}
        .link:hover{text-decoration:underline}
    </style>
</head>
<body>
    <div class="navbar">
        <div class="container">
            <h1>Crossword Game</h1>
            <nav>
                <a href="../index.php">Home</a>
                <a href="student_register.php">Register</a>
            </nav>
        </div>
    </div>

    <div class="main-container">
        <div class="auth-box">
            <h2>🎓 Student Login</h2>
            <p>Login to play puzzles</p>

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
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                
                <div class="form-group">
                    <label for="name">Name *</label>
                    <input type="text" id="name" name="name" required 
                           maxlength="100"
                           placeholder="Your name"
                           value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                           <?php echo (isset($_SESSION['student_lockout_until']) && time() < $_SESSION['student_lockout_until']) ? 'disabled' : ''; ?>>
                </div>

                <div class="form-group">
                    <label for="program">Program *</label>
                    <select id="program" name="program" required
                            <?php echo (isset($_SESSION['student_lockout_until']) && time() < $_SESSION['student_lockout_until']) ? 'disabled' : ''; ?>>
                        <option value="">Select Program</option>
                        <option value="MSc AI" <?php echo (isset($_POST['program']) && $_POST['program'] === 'MSc AI') ? 'selected' : ''; ?>>MSc AI</option>
                        <option value="MSc CS" <?php echo (isset($_POST['program']) && $_POST['program'] === 'MSc CS') ? 'selected' : ''; ?>>MSc CS</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" required
                           maxlength="255"
                           placeholder="Enter your password"
                           <?php echo (isset($_SESSION['student_lockout_until']) && time() < $_SESSION['student_lockout_until']) ? 'disabled' : ''; ?>>
                </div>

                <button type="submit" class="btn btn-primary" 
                        <?php echo (isset($_SESSION['student_lockout_until']) && time() < $_SESSION['student_lockout_until']) ? 'disabled' : ''; ?>>
                    Login
                </button>
                
                <?php if (isset($_SESSION['student_login_attempts']) && $_SESSION['student_login_attempts'] > 0 && $_SESSION['student_login_attempts'] < $max_attempts): ?>
                    <div class="attempts-warning">
                        ⚠️ <?php echo $max_attempts - $_SESSION['student_login_attempts']; ?> attempts remaining
                    </div>
                <?php endif; ?>
                
                <!-- <div class="security-badge">
                    <span>🔒</span>
                    <span>Secured with CSRF protection & rate limiting</span>
                </div> -->
            </form>

            <p class="text-center mt-20">
                Don't have an account? 
                <a href="student_register.php" class="link">Register here</a>
            </p>
        </div>
    </div>
</body>
</html>
