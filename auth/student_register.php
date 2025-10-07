<?php
require_once '../config.php';
require_once '../functions/helpers.php';

$error = '';
$success = '';

// Rate limiting for registration
if (!isset($_SESSION['student_registration_attempts'])) {
    $_SESSION['student_registration_attempts'] = 0;
    $_SESSION['student_last_registration_time'] = time();
}

// Check if too many registration attempts
if (isset($_SESSION['student_registration_lockout_until']) && time() < $_SESSION['student_registration_lockout_until']) {
    $remaining = ceil(($_SESSION['student_registration_lockout_until'] - time()) / 60);
    $error = "Too many registration attempts. Please try again in {$remaining} minutes.";
} elseif (isset($_SESSION['student_registration_lockout_until']) && time() >= $_SESSION['student_registration_lockout_until']) {
    unset($_SESSION['student_registration_lockout_until']);
    $_SESSION['student_registration_attempts'] = 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_SESSION['student_registration_lockout_until'])) {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Invalid security token. Please refresh and try again.';
    } else {
        $name = sanitize($_POST['name']);
        $email = sanitize($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $program = $_POST['program'];
        $other_program = sanitize($_POST['other_program'] ?? '');
        
        // Enhanced Validation
        if (empty($name) || empty($email) || empty($password) || empty($confirm_password) || empty($program)) {
            $error = 'All fields are required';
            $_SESSION['student_registration_attempts']++;
        } elseif (!isValidEmail($email)) {
            $error = 'Invalid email address';
            $_SESSION['student_registration_attempts']++;
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters';
            $_SESSION['student_registration_attempts']++;
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $error = 'Password must contain at least one uppercase letter';
            $_SESSION['student_registration_attempts']++;
        } elseif (!preg_match('/[a-z]/', $password)) {
            $error = 'Password must contain at least one lowercase letter';
            $_SESSION['student_registration_attempts']++;
        } elseif (!preg_match('/[0-9]/', $password)) {
            $error = 'Password must contain at least one number';
            $_SESSION['student_registration_attempts']++;
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match';
            $_SESSION['student_registration_attempts']++;
        } elseif (strlen($name) < 3) {
            $error = 'Name must be at least 3 characters';
            $_SESSION['student_registration_attempts']++;
        } elseif ($program === 'Other' && empty($other_program)) {
            $error = 'Please specify your program';
            $_SESSION['student_registration_attempts']++;
        } else {
            // Check if email exists
            $stmt = $conn->prepare("SELECT id FROM students WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = 'Email already registered. Please use a different email or login.';
                $_SESSION['student_registration_attempts']++;
            } else {
                // Use other program if selected
                if ($program === 'Other' && !empty($other_program)) {
                    $program = $other_program;
                }
                
                // Insert student
                $hashedPassword = hashPassword($password);
                $stmt = $conn->prepare("INSERT INTO students (name, email, password, program, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->bind_param("ssss", $name, $email, $hashedPassword, $program);
                
                if ($stmt->execute()) {
                    // Reset attempts on success
                    $_SESSION['student_registration_attempts'] = 0;
                    unset($_SESSION['student_last_registration_time']);
                    
                    // LOG REGISTRATION
                    logRegistration($conn, 'student', $name, $email, $program);
                    
                    $success = 'Registration successful! You can now login with your credentials.';
                } else {
                    $error = 'Registration failed. Please try again.';
                    $_SESSION['student_registration_attempts']++;
                }
            }
        }
        
        // Check if max attempts reached
        if ($_SESSION['student_registration_attempts'] >= 10) {
            $_SESSION['student_registration_lockout_until'] = time() + 1800; // 30 minutes
            $error = "Too many registration attempts. Please try again in 30 minutes.";
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
    <title>Student Registration</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
       
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
                <a href="student_login.php">Login</a>
                <a href="teacher_login.php">Teacher Login</a>
            </nav>
        </div>
    </div>

    <div class="main-container">
        <div class="auth-box">
            <h2>🎓 Student Registration</h2>
            <p>Create your student account</p>

            <?php if ($error): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success-message">
                    ✓ <?php echo htmlspecialchars($success); ?>
                </div>
                <p class="text-center mt-20">
                    <a href="student_login.php" class="link" style="font-size: 16px;">→ Go to Login</a>
                </p>
            <?php else: ?>
                <div class="password-requirements">
                    <strong>Password Requirements:</strong>
                    <ul>
                        <li>At least 8 characters</li>
                        <li>One uppercase letter (A-Z)</li>
                        <li>One lowercase letter (a-z)</li>
                        <li>One number (0-9)</li>
                    </ul>
                </div>

                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <div class="form-group">
                        <label for="name">Full Name *</label>
                        <input type="text" id="name" name="name" required 
                               minlength="3"
                               placeholder="Your full name"
                               value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                               <?php echo isset($_SESSION['student_registration_lockout_until']) ? 'disabled' : ''; ?>>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" required 
                               placeholder="your.email@example.com"
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                               <?php echo isset($_SESSION['student_registration_lockout_until']) ? 'disabled' : ''; ?>>
                    </div>

                    <div class="form-group">
                        <label for="password">Password *</label>
                        <input type="password" id="password" name="password" required 
                               minlength="8"
                               placeholder="Enter strong password"
                               <?php echo isset($_SESSION['student_registration_lockout_until']) ? 'disabled' : ''; ?>>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" required 
                               minlength="8"
                               placeholder="Re-enter password"
                               <?php echo isset($_SESSION['student_registration_lockout_until']) ? 'disabled' : ''; ?>>
                    </div>

                    <div class="form-group">
                        <label for="program">Program *</label>
                        <select id="program" name="program" required onchange="toggleOtherProgram(this.value)"
                                <?php echo isset($_SESSION['student_registration_lockout_until']) ? 'disabled' : ''; ?>>
                            <option value="">Select Program</option>
                            <option value="MSc AI" <?php echo (isset($_POST['program']) && $_POST['program'] === 'MSc AI') ? 'selected' : ''; ?>>MSc AI</option>
                            <option value="MSc CS" <?php echo (isset($_POST['program']) && $_POST['program'] === 'MSc CS') ? 'selected' : ''; ?>>MSc CS</option>
                            <option value="Other" <?php echo (isset($_POST['program']) && $_POST['program'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>

                    <div class="form-group" id="other-program-group" style="display: none;">
                        <label for="other_program">Specify Program *</label>
                        <input type="text" id="other_program" name="other_program"
                               placeholder="Enter your program"
                               value="<?php echo htmlspecialchars($_POST['other_program'] ?? ''); ?>"
                               <?php echo isset($_SESSION['student_registration_lockout_until']) ? 'disabled' : ''; ?>>
                    </div>

                    <button type="submit" class="btn btn-primary"
                            <?php echo isset($_SESSION['student_registration_lockout_until']) ? 'disabled' : ''; ?>>
                        Create Account
                    </button>
                    
                    <!-- <div class="security-badge">
                        <span>🔒</span>
                        <span>Your data is encrypted and secure</span>
                    </div> -->
                </form>

                <p class="text-center mt-20">
                    Already have an account? 
                    <a href="student_login.php" class="link">Login here</a>
                </p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleMenu() {
            document.getElementById('navMenu').classList.toggle('active');
        }
        
        document.addEventListener('click', function(event) {
            const navbar = document.querySelector('.navbar');
            const navMenu = document.getElementById('navMenu');
            
            if (!navbar.contains(event.target) && navMenu.classList.contains('active')) {
                navMenu.classList.remove('active');
            }
        });
        
        function toggleOtherProgram(value) {
            const otherGroup = document.getElementById('other-program-group');
            const otherInput = document.getElementById('other_program');
            
            if (value === 'Other') {
                otherGroup.style.display = 'block';
                otherInput.required = true;
            } else {
                otherGroup.style.display = 'none';
                otherInput.required = false;
            }
        }
        
        window.onload = function() {
            const programSelect = document.getElementById('program');
            if (programSelect.value === 'Other') {
                toggleOtherProgram('Other');
            }
        };
    </script>
</body>
</html>
