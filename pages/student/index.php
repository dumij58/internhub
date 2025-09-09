<?php
require_once '../../includes/config.php';

// --- Page-specific variables ---
$page_title = 'Student Dashboard';
global $pages_path;

if (isLoggedIn()) {
    $role =  $_SESSION['role'];
    if ($role !== 'student') {
        logActivity('Unauthorized Access Attempt', 'User changed the url from "' . $role . '" to "student".');
        header('Location: ' . $pages_path . '/error.php' . '?error_message=401-Unauthorized');
        exit;
    }
}

// --- Include the header ---
require_once '../../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <h1>Welcome, <?php echo isset($_SESSION['username']) ? escape($_SESSION['username']) : 'Guest'; ?>!</h1>
            <p class="lead">Find and apply for internship opportunities</p>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-search fa-3x text-primary mb-3"></i>
                    <h5 class="card-title">Find Internships</h5>
                    <p class="card-text">Browse and search for available internship opportunities.</p>
                    <a href="find_internships.php" class="btn btn-primary">Find Internships</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-file-alt fa-3x text-success mb-3"></i>
                    <h5 class="card-title">My Applications</h5>
                    <p class="card-text">View the status of your submitted applications.</p>
                    <a href="my_applications.php" class="btn btn-success">View Applications</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-user fa-3x text-warning mb-3"></i>
                    <h5 class="card-title">My Profile</h5>
                    <p class="card-text">Update your profile information and skills.</p>
                    <a href="profile.php" class="btn btn-warning">View Profile</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// --- Include the footer ---
require_once '../../includes/footer.php';
?>