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
    // Get the document root and current script directory
    $documentRoot = $_SERVER['DOCUMENT_ROOT'];
    $currentDir = dirname(dirname(__FILE__)); // Go up from includes/ to project root
    
    // Calculate the relative path from document root to project root
    $relativePath = str_replace($documentRoot, '', $currentDir);
    
    // Ensure it starts with / and doesn't end with /
    $relativePath = '/' . trim($relativePath, '/');
    
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
    global $db;
    try {
        $sql = "SELECT * FROM users WHERE username = :username";
        $stmt = $db->prepare($sql);
        $stmt->execute(['username' => $username]);
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
    $user_id = $_SESSION['user_id'] ?? null;
    $sql = "INSERT INTO system_logs (user_id, action, details) VALUES (:uid, :action, :details)";
    $stmt = $db->prepare($sql);
    $stmt->execute(['uid' => $user_id, 'action' => $action, 'details' => $details]);
}

// Helper function to get count from a table with optional conditions
function getCount($db, $table, $condition = null, $params = []) {
    $sql = "SELECT COUNT(*) FROM $table";
    if ($condition) {
        $sql .= " WHERE $condition";
    }
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

// Helper function to get data
function getData($db, $sql, $params = []) {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>