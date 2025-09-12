<?php
// Output buffering for header manipulation
ob_start();

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define paths - Auto-detect if not set via environment
$root_path = detectPathPrefix();
$pages_path = $root_path . '/pages';
$assets_path = $root_path . '/assets';
$includes_path = $root_path . '/includes';

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'internhub');
define('DB_USER', 'root'); // Change this to your MySQL username
define('DB_PASS', ''); // Change this to your MySQL password

// Application Settings
define('SITE_NAME', 'Internship Application & Tracking System');
define('SITE_URL', 'http://localhost' . $root_path . '/');
// define('UPLOAD_DIR', __DIR__ . '/uploads/'); // Directory for file uploads
// define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB max file size

// Security Settings
define('HASH_ALGO', PASSWORD_DEFAULT);

// Time zone setting
date_default_timezone_set('Asia/Colombo'); // Adjust to your timezone

// Error reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * Auto-detect the path prefix based on the current script location
 * This provides a fallback when PATH_PREFIX is not set
 */
function detectPathPrefix() {
    // Normalize paths to forward slashes for cross-platform consistency (Windows/Unix)
    $documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : '';
    $documentRoot = str_replace('\\', '/', rtrim($documentRoot, '/'));

    // Go up from includes/ to project root and normalize
    $currentDir = dirname(dirname(__FILE__));
    $currentDir = str_replace('\\', '/', rtrim($currentDir, '/'));

    $relativePath = '';
    if ($documentRoot !== '' && strpos($currentDir, $documentRoot) === 0) {
        // Remove document root prefix
        $relativePath = substr($currentDir, strlen($documentRoot));
    } else {
        // Fallback: derive from the script name when DOCUMENT_ROOT mismatch occurs
        $scriptName = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
        $scriptName = str_replace('\\', '/', $scriptName);
        // Example: /internhub/pages/auth/login.php -> /internhub
        $relativePath = preg_replace('#/(pages|includes|assets)(/.*)?$#', '', dirname($scriptName));
        if ($relativePath === null) {
            $relativePath = '';
        }
    }

    // Ensure leading slash, no trailing slash, and only forward slashes
    $relativePath = '/' . ltrim(trim($relativePath, '/'), '/');
    return $relativePath === '/' ? '' : $relativePath;
}

/**
 * Database Connection Function
 * Creates and returns a PDO connection object
 * Throws an exception if connection fails
 */
class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $conn;

    public function getDBConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, 
                                 $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}

// Global database instance
function getDB() {
    $db = new Database();
    return $db->getDBConnection();
}

// Fetches a user's record from the database by their username.
function getUser($username) {
    $db = getDB();
    try {
        $sql = "SELECT * FROM users WHERE username = :username";
        $stmt = $db->prepare($sql);
        $stmt->execute(array('username' => $username));
        return $stmt->fetch();
    } catch (PDOException $e) {
        logActivity('Database Error in '. __FUNCTION__, $e->getMessage());
        return false;
    }
}

// Get current user ID
function getCurrentUserId() {
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
}

// Redirect to login if not logged in
function requireLogin() {
    global $pages_path;
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . $pages_path . '/auth/login.php?msg=Please login to continue');
        exit();
    }
}

// Redirects to home if user is not an admin
function requireAdmin() {
    global $pages_path;
    requireLogin();
    if (!isAdmin()) {
        header('Location: ' . $pages_path . '/error.php?error_message=403 - Access denied. Admin privileges required.');
        exit();
    }
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Check if current user is admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Get user role based on role ID
function getUserRole($user_type_id) {
    switch($user_type_id) {
        case 1: return 'admin';
        case 2: return 'student';
        case 3: return 'company';
        default: return 'unknown';
    }
}

// Hash password securely
function hashPassword($password) {
    return password_hash($password, HASH_ALGO);
}

// Sanitizes output to prevent XSS attacks.
function escape($data) {
    if (isset($data)) {
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    } else {
        return '';
    }
    
}

/**
 * Log user activity
 * Records important actions in the activity_logs table
 */
function logActivity($action, $details = null) {
    $db = getDB();
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $sql = "INSERT INTO system_logs (user_id, action, details) VALUES (:uid, :action, :details)";
    $stmt = $db->prepare($sql);
    $stmt->execute(array('uid' => $user_id, 'action' => $action, 'details' => $details));
}

// Helper function to get count from a table with optional conditions
function getCount($db, $table, $condition = null, $params = array()) {
    $sql = "SELECT COUNT(*) FROM $table";
    if ($condition) {
        $sql .= " WHERE $condition";
    }
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

// Helper function to get data
function getData($db, $sql, $params = array()) {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>