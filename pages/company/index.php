<?php
require_once '../../includes/config.php';
requireLogin();
global $pages_path;

if (isLoggedIn()) {
    $role =  $_SESSION['role'];
    if ($role !== 'company') {
        logActivity('Unauthorized Access Attempt', 'User changed the url from "' . $role . '" to "company".');
        header('Location: ' . $pages_path . '/error.php' . '?error_message=401-Unauthorized');
        exit;
    }
}

// --- Include the header ---
require_once '../../includes/header.php';
?>

<h1>Welcome, <?php echo isset($_SESSION['username']) ? escape($_SESSION['username']) : 'Guest'; ?>!</h1>
<p>This is your company dashboard.</p>

<?php
// --- Include the footer ---
require_once '../../includes/footer.php';
?>