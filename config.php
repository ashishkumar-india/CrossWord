<?php
/**
 * Secure Database Configuration
 * Crossword Game Application
 * Updated for Local Network & Mobile Access
 */

// Start session with configuration optimized for local network access
if (session_status() === PHP_SESSION_NONE) {
    // Detect if accessing via IP (local network) or localhost
    $is_local_network = !empty($_SERVER['HTTP_HOST']) && 
                        (preg_match('/^192\.168\.\d+\.\d+$/', $_SERVER['HTTP_HOST']) || 
                         preg_match('/^10\.\d+\.\d+\.\d+$/', $_SERVER['HTTP_HOST']) ||
                         $_SERVER['HTTP_HOST'] === 'localhost' ||
                         $_SERVER['HTTP_HOST'] === '127.0.0.1');
    
    // Secure session configuration (relaxed for local network)
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Disabled for HTTP local access
    ini_set('session.cookie_samesite', 'Lax'); // Changed from Strict to Lax
    ini_set('session.use_strict_mode', 1);
    ini_set('session.gc_maxlifetime', 3600); // 60 minutes (increased from 30)
    ini_set('session.sid_length', 48);
    ini_set('session.sid_bits_per_character', 6);
    
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '', // Empty domain works with IP addresses
        'secure' => false, // Set to true when using HTTPS
        'httponly' => true,
        'samesite' => 'Lax' // More permissive for local network
    ]);
    
    session_start();
    
    // Regenerate session ID periodically for security
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

// Security Headers (relaxed for local development)
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN"); // Changed from DENY to allow iframes
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

// Only apply strict CSP and other headers in production
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self';");
}

// Error handling (enable for development, disable for production)
$is_development = true; // Set to false in production

if ($is_development) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL);
}

ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php_errors.log');

// Database configuration - Railway MySQL or Local XAMPP
define('DB_HOST', getenv('MYSQLHOST') ?: 'localhost');
define('DB_USER', getenv('MYSQLUSER') ?: 'root');
define('DB_PASS', getenv('MYSQLPASSWORD') ?: '');
define('DB_NAME', getenv('MYSQLDATABASE') ?: 'crossword_game');
define('DB_PORT', getenv('MYSQLPORT') ?: '3306');

// Create connection with error handling
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, (int)DB_PORT);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Set charset to prevent encoding attacks
    if (!$conn->set_charset("utf8mb4")) {
        throw new Exception("Error setting charset: " . $conn->error);
    }
    
} catch (Exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    
    if ($is_development) {
        die("Database connection error: " . $e->getMessage());
    } else {
        die("Database connection error. Please try again later.");
    }
}

// Timezone
date_default_timezone_set('Asia/Kolkata');

// Application constants
define('APP_NAME', 'Crossword Game');
define('BASE_URL', '/crossword-game/'); // Updated path
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB

// Create necessary directories if they don't exist
$directories = ['logs', 'uploads', 'temp'];
foreach ($directories as $dir) {
    $dir_path = __DIR__ . '/' . $dir;
    if (!file_exists($dir_path)) {
        if (!mkdir($dir_path, 0755, true)) {
            error_log("Failed to create directory: " . $dir_path);
        }
    }
}

// Include helpers
$helpers_path = __DIR__ . '/functions/helpers.php';
if (file_exists($helpers_path)) {
    require_once $helpers_path;
} else {
    error_log("Helpers file not found: " . $helpers_path);
    if ($is_development) {
        die("Critical error: helpers.php not found");
    }
}

// Optional: Session activity tracking (uncomment to enable)
/*
if (isset($_SESSION['user_id'])) {
    $_SESSION['last_activity'] = time();
    
    // Auto-logout after 30 minutes of inactivity
    $timeout_duration = 1800;
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
        session_unset();
        session_destroy();
        header('Location: ' . BASE_URL . 'auth/student_login.php?timeout=1');
        exit();
    }
}
*/

// Optional: Prevent session fixation
if (!isset($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = true;
}

// Optional: User-Agent validation (uncomment to enable)
/*
if (!isset($_SESSION['user_agent'])) {
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
} elseif ($_SESSION['user_agent'] !== ($_SERVER['HTTP_USER_AGENT'] ?? '')) {
    // Different user agent detected - possible session hijacking
    session_unset();
    session_destroy();
    header('Location: ' . BASE_URL . 'auth/student_login.php?error=session_invalid');
    exit();
}
*/
?>
