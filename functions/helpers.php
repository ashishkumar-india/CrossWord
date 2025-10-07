<?php
/**
 * Enhanced Helper Functions with Security
 * Crossword Game - Fully Secured Version
 */

// ============ INPUT SANITIZATION ============

/**
 * Sanitize input data (XSS Protection)
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize for database (additional layer)
 */
function sanitizeForDB($conn, $data) {
    return $conn->real_escape_string(sanitize($data));
}

// ============ VALIDATION ============

/**
 * Validate email with DNS check
 */
function isValidEmail($email) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    
    if (!preg_match('/@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email)) {
        return false;
    }
    
    return true;
}

/**
 * Validate password strength
 */
function isStrongPassword($password) {
    if (strlen($password) < 8) return false;
    if (!preg_match('/[A-Z]/', $password)) return false;
    if (!preg_match('/[a-z]/', $password)) return false;
    if (!preg_match('/[0-9]/', $password)) return false;
    return true;
}

/**
 * Validate integer input
 */
function validateInt($value, $min = null, $max = null) {
    $value = filter_var($value, FILTER_VALIDATE_INT);
    if ($value === false) return false;
    
    if ($min !== null && $value < $min) return false;
    if ($max !== null && $value > $max) return false;
    
    return $value;
}

// ============ PASSWORD SECURITY ============

/**
 * Hash password with bcrypt (cost 12)
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify password with timing attack protection
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Check if password needs rehashing
 */
function needsRehash($hash) {
    return password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => 12]);
}

// ============ SESSION SECURITY ============

/**
 * Enhanced login check with improved security
 */
function isLoggedIn($type) {
    $id_key = $type === 'student' ? 'student_id' : ($type === 'teacher' ? 'teacher_id' : null);
    
    if (!$id_key || !isset($_SESSION[$id_key]) || !is_numeric($_SESSION[$id_key])) {
        return false;
    }
    
    // Check session timestamp
    if (!isset($_SESSION['last_activity'])) {
        return false;
    }
    
    // Session timeout - 30 minutes
    if (time() - $_SESSION['last_activity'] > 1800) {
        session_unset();
        session_destroy();
        return false;
    }
    
    // Update last activity
    $_SESSION['last_activity'] = time();
    
    // Loose IP validation (check first 3 octets)
    if (isset($_SESSION['user_ip_prefix'])) {
        $current_prefix = implode('.', array_slice(explode('.', $_SERVER['REMOTE_ADDR']), 0, 3));
        if ($_SESSION['user_ip_prefix'] !== $current_prefix) {
            logSecurityEvent('SESSION_WARNING', 'IP prefix mismatch', $_SESSION[$id_key]);
        }
    }
    
    return true;
}

/**
 * Login user with session regeneration
 */
function loginUser($type, $userId, $userData = []) {
    // Destroy old session (prevent session fixation)
    session_regenerate_id(true);
    
    // Set user session data
    if ($type === 'student') {
        $_SESSION['student_id'] = $userId;
        $_SESSION['student_name'] = $userData['name'] ?? '';
        $_SESSION['student_program'] = $userData['program'] ?? '';
    } else {
        $_SESSION['teacher_id'] = $userId;
        $_SESSION['teacher_name'] = $userData['name'] ?? '';
    }
    
    // Set security markers
    $_SESSION['user_ip_prefix'] = implode('.', array_slice(explode('.', $_SERVER['REMOTE_ADDR']), 0, 3));
    $_SESSION['user_agent_hash'] = hash('sha256', $_SERVER['HTTP_USER_AGENT']);
    $_SESSION['last_activity'] = time();
    $_SESSION['login_time'] = time();
    
    // Generate new CSRF token
    unset($_SESSION['csrf_token']);
    unset($_SESSION['csrf_token_time']);
    generateCSRFToken();
    
    logSecurityEvent('LOGIN_SUCCESS', "User type: $type", $userId);
}

/**
 * Get current user data
 */
function getCurrentUser($type) {
    global $conn;
    
    if ($type === 'student' && isset($_SESSION['student_id'])) {
        $stmt = $conn->prepare("SELECT id, name, email, program FROM students WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['student_id']);
    } elseif ($type === 'teacher' && isset($_SESSION['teacher_id'])) {
        $stmt = $conn->prepare("SELECT id, name, email FROM teachers WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['teacher_id']);
    } else {
        return null;
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0 ? $result->fetch_assoc() : null;
}

/**
 * Check if student account is active
 */
function checkStudentActive($conn, $student_id) {
    $stmt = $conn->prepare("SELECT is_active FROM students WHERE id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $student = $result->fetch_assoc();
        
        if (isset($student['is_active']) && $student['is_active'] == 0) {
            session_unset();
            session_destroy();
            
            session_start();
            $_SESSION['error'] = "Your account has been disabled. Please contact your teacher.";
            
            header("Location: /crossword_game/auth/student_login.php");
            exit();
        }
    }
    return true;
}

// ============ CSRF PROTECTION ============

/**
 * Generate CSRF token with session binding
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
        $_SESSION['csrf_token_session'] = session_id();
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token with enhanced security
 */
function verifyCSRFToken($token) {
    // Only validate CSRF for POST, PUT, DELETE requests
    $method = $_SERVER['REQUEST_METHOD'];
    if (!in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'])) {
        return true;
    }
    
    if (empty($token) || !isset($_SESSION['csrf_token'])) {
        logSecurityEvent('CSRF_FAIL', 'Token missing');
        return false;
    }
    
    // Token must be bound to current session
    if (!isset($_SESSION['csrf_token_session']) || 
        $_SESSION['csrf_token_session'] !== session_id()) {
        logSecurityEvent('CSRF_FAIL', 'Session mismatch');
        return false;
    }
    
    // Check token age (1 hour max)
    if (!isset($_SESSION['csrf_token_time']) || 
        time() - $_SESSION['csrf_token_time'] > 3600) {
        unset($_SESSION['csrf_token']);
        unset($_SESSION['csrf_token_time']);
        unset($_SESSION['csrf_token_session']);
        logSecurityEvent('CSRF_FAIL', 'Token expired');
        return false;
    }
    
    // Timing-safe comparison
    $valid = hash_equals($_SESSION['csrf_token'], $token);
    
    if (!$valid) {
        logSecurityEvent('CSRF_FAIL', 'Token mismatch');
    }
    
    return $valid;
}

/**
 * Get CSRF input field
 */
function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">';
}

// ============ SECURE REDIRECT ============

/**
 * Secure redirect with whitelist
 */
function redirect($path) {
    $path = ltrim($path, '/');
    
    $allowed_paths = [
        'index.php',
        'auth/student_login.php',
        'auth/student_register.php',
        'auth/teacher_login.php',
        'auth/teacher_register.php',
        'student/student_dashboard.php',
        'student/play_puzzle.php',
        'student/view_result.php',
        'teacher/teacher_dashboard.php',
        'teacher/create_puzzle.php',
        'teacher/manage_puzzles.php',
        'teacher/view_students.php',
        'teacher/manage_students.php',
        'teacher/edit_puzzle.php',
        'teacher/view_logs.php',
        'logout.php'
    ];
    
    $is_allowed = in_array($path, $allowed_paths) || 
                  preg_match('#^(student|teacher)/[a-z_]+\.php$#', $path);
    
    if (!$is_allowed) {
        error_log("Blocked redirect attempt to: " . $path);
        $path = 'index.php';
    }
    
    $path = preg_replace('/\?.*$/', '', $path);
    
    header("Location: /crossword_game/" . $path);
    exit();
}

// ============ RATE LIMITING ============

/**
 * Enhanced rate limiting with IP tracking
 */
function checkRateLimit($action, $max_attempts, $time_window, $block_duration = 3600) {
    $key = $action . '_' . $_SERVER['REMOTE_ADDR'];
    $block_key = $key . '_blocked';
    
    // Check if currently blocked
    if (isset($_SESSION[$block_key]) && $_SESSION[$block_key] > time()) {
        $remaining = $_SESSION[$block_key] - time();
        logSecurityEvent('RATE_LIMIT_BLOCKED', "Action: $action, Remaining: {$remaining}s");
        return false;
    }
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 1, 'first_attempt' => time()];
        return true;
    }
    
    $data = $_SESSION[$key];
    
    // Reset if time window passed
    if (time() - $data['first_attempt'] > $time_window) {
        $_SESSION[$key] = ['count' => 1, 'first_attempt' => time()];
        unset($_SESSION[$block_key]);
        return true;
    }
    
    // Check if exceeded
    if ($data['count'] >= $max_attempts) {
        $_SESSION[$block_key] = time() + $block_duration;
        logSecurityEvent('RATE_LIMIT_EXCEEDED', "Action: $action, Blocked for {$block_duration}s");
        return false;
    }
    
    $_SESSION[$key]['count']++;
    return true;
}

/**
 * IP-based rate limiting (database)
 */
function checkIPRateLimit($conn, $action, $max_attempts, $window_seconds) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $cutoff_time = date('Y-m-d H:i:s', time() - $window_seconds);
    
    // Count recent failed attempts from this IP
    $stmt = $conn->prepare("SELECT COUNT(*) as attempts FROM login_logs 
                           WHERE ip_address = ? AND status = 'failed' 
                           AND login_time > ?");
    $stmt->bind_param("ss", $ip, $cutoff_time);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    return $result['attempts'] < $max_attempts;
}

// ============ MESSAGES ============

function showSuccess($message) {
    return '<div class="alert alert-success">' . sanitize($message) . '</div>';
}

function showError($message) {
    return '<div class="alert alert-error">' . sanitize($message) . '</div>';
}

function showWarning($message) {
    return '<div class="alert alert-warning">' . sanitize($message) . '</div>';
}

// ============ DATE/TIME ============

function formatDate($date) {
    if (empty($date)) return 'N/A';
    try {
        return date('M d, Y h:i A', strtotime($date));
    } catch (Exception $e) {
        return 'Invalid Date';
    }
}

function formatDateShort($date) {
    if (empty($date)) return 'N/A';
    try {
        return date('M d, Y', strtotime($date));
    } catch (Exception $e) {
        return 'Invalid Date';
    }
}

function timeAgo($timestamp) {
    $time = strtotime($timestamp);
    $diff = time() - $time;
    
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' minutes ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    
    return formatDateShort($timestamp);
}

// ============ PUZZLE FUNCTIONS ============

function calculateScore($answers, $correctAnswers) {
    $correct = 0;
    $total = count($correctAnswers);
    
    if ($total === 0) return 0;
    
    foreach ($correctAnswers as $key => $value) {
        if (isset($answers[$key]) && strtoupper(trim($answers[$key])) === strtoupper(trim($value))) {
            $correct++;
        }
    }
    
    return round(($correct / $total) * 100, 2);
}

function canAccessPuzzle($puzzleId, $studentId, $conn) {
    $stmt = $conn->prepare("SELECT is_active FROM puzzles WHERE id = ?");
    $stmt->bind_param("i", $puzzleId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) return false;
    
    $puzzle = $result->fetch_assoc();
    return $puzzle['is_active'] == 1;
}

function getProgramOptions() {
    return ['MSc AI', 'MSc CS'];
}

// ============ LOGGING ============

function logSecurityEvent($type, $message, $userId = null) {
    $logFile = __DIR__ . '/../logs/security.log';
    
    if (!file_exists(dirname($logFile))) {
        mkdir(dirname($logFile), 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'];
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $user = $userId ?? 'Guest';
    
    $logMessage = "[{$timestamp}] [{$type}] IP: {$ip} | User: {$user} | {$message} | UA: {$userAgent}\n";
    
    @file_put_contents($logFile, $logMessage, FILE_APPEND);
}

function logLogin($conn, $userType, $userId, $email, $status = 'success') {
    try {
        $stmt = $conn->prepare("INSERT INTO login_logs (user_type, user_id, email, ip_address, user_agent, status, login_time) 
                                VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $ip = $_SERVER['REMOTE_ADDR'];
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $stmt->bind_param("sissss", $userType, $userId, $email, $ip, $ua, $status);
        $stmt->execute();
        $stmt->close();
    } catch (Exception $e) {
        error_log("Login log error: " . $e->getMessage());
    }
}

function logRegistration($conn, $userType, $name, $email, $program = null) {
    try {
        $table_check = $conn->query("SHOW TABLES LIKE 'registration_logs'");
        if ($table_check->num_rows == 0) {
            $conn->query("CREATE TABLE IF NOT EXISTS registration_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_type ENUM('student', 'teacher') NOT NULL,
                name VARCHAR(255),
                email VARCHAR(255),
                program VARCHAR(100),
                ip_address VARCHAR(45),
                user_agent TEXT,
                registration_time DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_email (email),
                INDEX idx_time (registration_time)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        
        $stmt = $conn->prepare("INSERT INTO registration_logs (user_type, name, email, program, ip_address, user_agent, registration_time) 
                                VALUES (?, ?, ?, ?, ?, ?, NOW())");
        
        if ($stmt === false) {
            error_log("Registration log prepare failed: " . $conn->error);
            return false;
        }
        
        $ip = $_SERVER['REMOTE_ADDR'];
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $stmt->bind_param("ssssss", $userType, $name, $email, $program, $ip, $ua);
        $stmt->execute();
        $stmt->close();
        return true;
    } catch (Exception $e) {
        error_log("Registration log error: " . $e->getMessage());
        return false;
    }
}

// ============ FILE UPLOAD SECURITY ============

function validateFileUpload($file, $allowedTypes = ['image/jpeg', 'image/png', 'image/gif']) {
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['success' => false, 'error' => 'No file uploaded'];
    }
    
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'error' => 'File too large (max 5MB)'];
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        return ['success' => false, 'error' => 'Invalid file type'];
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    
    if (!in_array($extension, $allowedExtensions)) {
        return ['success' => false, 'error' => 'Invalid file extension'];
    }
    
    return ['success' => true];
}

function generateSafeFilename($originalName) {
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    $timestamp = time();
    $random = bin2hex(random_bytes(8));
    return $timestamp . '_' . $random . '.' . $extension;
}
?>
