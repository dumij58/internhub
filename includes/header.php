<?php
// A reusable header and navigation bar for the entire application.

require_once 'config.php';

// Set a default title if one isn't provided by the page including this file
$current_page_title = isset($page_title) ? $page_title : 'InternHub';
global $root_path, $pages_path, $assets_path;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo escape($current_page_title); ?> - InternHub</title>
    <link rel="stylesheet" href="<?php echo $assets_path; ?>/css/style.css">
</head>
<body>
    <header>
        <div class="nav-container">
            <nav class="nav-bar">
                <div class="nav-brand">
                        <span><img src="<?php echo $assets_path; ?>/images/logo.webp" alt="InternHub Logo" class="nav-brand-logo"></span>
                        <span class="nav-brand-name brand-name">Intern<span class="bn-hub">Hub</span></span>
                </div>
                <div class="nav-links-container">
                    <div class="nav-links">
                        <?php if (isset($_SESSION['role'])): ?>
                            <?php if ($_SESSION['role'] === 'student'): ?>
                                <!-- Student Navigation -->
                                <a href="<?php echo $pages_path; ?>/student/profile.php">My Profile</a>
                                <a href="<?php echo $pages_path; ?>/student/find_internships.php">Find Internships</a>
                                <a href="<?php echo $pages_path; ?>/student/my_applications.php">My Applications</a>
                            <?php elseif ($_SESSION['role'] === 'company'): ?>
                                <!-- Company Navigation -->
                                <a href="<?php echo $pages_path; ?>/company/dashboard.php">Company Dashboard</a>
                                <a href="<?php echo $pages_path; ?>/company/post_internship.php">Post Internships</a>
                                <a href="<?php echo $pages_path; ?>/company/view_applications.php">Received Applications</a>
                            <?php elseif ($_SESSION['role'] === 'admin'): ?>
                                <!-- Admin Navigation -->
                                <a href="<?php echo $pages_path; ?>/admin/index.php">Dashboard</a>
                                <a href="<?php echo $pages_path; ?>/admin/analytics.php">Analytics</a>
                                <a href="<?php echo $pages_path; ?>/admin/system_logs.php">Logs</a>
                            <?php endif; ?>
                            <?php if ($_SESSION['role'] !== 'admin'): ?>
                                <a href="<?php echo $pages_path; ?>/functionalities.php">Functionalities</a>
                                <a href="<?php echo $pages_path; ?>/help.php">Help</a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <div class="nav-user nav-links dropdown">
                        <?php if (isset($_SESSION['role'])): ?>
                            <span class="nav-user-name">
                                <?php echo escape($_SESSION['username']); ?>
                            </span>
                            <span class="nav-user-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-person-circle" viewBox="0 0 16 16">
                                    <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0"/>
                                    <path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8m8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1"/>
                                </svg>
                            </span>
                            <div class="dropdown-content">
                                <a href="<?php echo "{$pages_path}/auth/logout.php"; ?>">Logout</a>
                            </div>
                        <?php else: ?>
                            <span class="nav-login">
                                <!-- Checks if the current page is an admin page or not and sets the login page path accordingly -->
                                <a href="<?php echo $pages_path . (basename(dirname($_SERVER['PHP_SELF'])) === 'admin' ? '/admin' : '/auth') . '/login.php'; ?>">Login</a>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </nav>
        </div>
    </header>


    <div class="main-container">
        <div class="content">