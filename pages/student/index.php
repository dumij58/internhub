<?php
require_once '../../includes/config.php';
requireLogin();
global $pages_path;

$role = $_SESSION['role'];
if ($role !== 'student') {
    logActivity('Unauthorized Access Attempt', "User changed the url from \"{$role}\" to \"student\".");
    header("Location: {$pages_path}/error.php?error_message=401-Unauthorized&redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit;
} else {
    header("Location: {$pages_path}/student/dashboard.php");
    exit;
}
?>
