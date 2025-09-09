<?php
require_once '../../includes/config.php';

// --- Page-specific variables ---
$page_title = 'Company Dashboard';
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

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <h1>Welcome, <?php echo isset($_SESSION['username']) ? escape($_SESSION['username']) : 'Guest'; ?>!</h1>
            <p class="lead">Manage your internship opportunities and applications</p>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-plus-circle fa-3x text-primary mb-3"></i>
                    <h5 class="card-title">Post New Vacancy</h5>
                    <p class="card-text">Create and publish new internship opportunities for students.</p>
                    <a href="post_vacancy.php" class="btn btn-primary">Post Vacancy</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-briefcase fa-3x text-success mb-3"></i>
                    <h5 class="card-title">My Vacancies</h5>
                    <p class="card-text">View and manage your posted internship vacancies.</p>
                    <a href="view_vacancies.php" class="btn btn-success">View Vacancies</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-users fa-3x text-warning mb-3"></i>
                    <h5 class="card-title">Applications</h5>
                    <p class="card-text">Review and manage student applications for your internships.</p>
                    <a href="view_applications.php" class="btn btn-warning">View Applications</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// --- Include the footer ---
require_once '../../includes/footer.php';
?>