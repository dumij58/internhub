<?php
require_once '../../includes/config.php';

// --- Page-specific variables ---
$page_title = 'My Applications';
global $pages_path;

// Check if user is logged in and is a student
if (!isLoggedIn() || $_SESSION['role'] !== 'student') {
    header('Location: ' . $pages_path . '/auth/login.php?msg=Please login as a student to view applications');
    exit;
}

$db = getDB();
$user_id = $_SESSION['user_id'];

// Get user's applications
$applications_query = "SELECT a.*, i.title as internship_title, i.company_id, i.location, i.duration_months,
                       cp.company_name, ic.name as category_name
                       FROM applications a
                       JOIN internships i ON a.internship_id = i.id
                       JOIN company_profiles cp ON i.company_id = cp.id
                       LEFT JOIN internship_categories ic ON i.category_id = ic.id
                       WHERE a.student_id = :user_id
                       ORDER BY a.application_date DESC";

$applications_stmt = $db->prepare($applications_query);
$applications_stmt->execute(['user_id' => $user_id]);
$applications = $applications_stmt->fetchAll();

// --- Include the header ---
require_once '../../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>My Applications</h2>
                <a href="find_internships.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Find More Internships
                </a>
            </div>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    Application submitted successfully!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (empty($applications)): ?>
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">No Applications Yet</h4>
                        <p class="text-muted">You haven't applied for any internships yet. Start exploring opportunities!</p>
                        <a href="find_internships.php" class="btn btn-primary">Find Internships</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($applications as $application): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <span class="badge bg-<?php 
                                        echo $application['status'] === 'accepted' ? 'success' : 
                                            ($application['status'] === 'rejected' ? 'danger' : 
                                            ($application['status'] === 'shortlisted' ? 'warning' : 'primary')); 
                                    ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $application['status'])); ?>
                                    </span>
                                    <small class="text-muted"><?php echo date('M d, Y', strtotime($application['application_date'])); ?></small>
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo escape($application['internship_title']); ?></h5>
                                    <p class="text-muted mb-2">
                                        <strong><?php echo escape($application['company_name']); ?></strong>
                                    </p>
                                    
                                    <p class="card-text">
                                        <strong>Location:</strong> <?php echo escape($application['location']); ?><br>
                                        <strong>Duration:</strong> <?php echo $application['duration_months']; ?> months<br>
                                        <strong>Category:</strong> <?php echo escape($application['category_name']); ?><br>
                                    </p>
                                    
                                    <p class="card-text">
                                        <strong>Application Date:</strong><br>
                                        <small class="text-muted"><?php echo date('M d, Y H:i', strtotime($application['application_date'])); ?></small>
                                    </p>
                                    
                                    <?php if ($application['reviewed_date']): ?>
                                        <p class="card-text">
                                            <strong>Last Updated:</strong><br>
                                            <small class="text-muted"><?php echo date('M d, Y H:i', strtotime($application['reviewed_date'])); ?></small>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <div class="card-footer">
                                    <div class="btn-group w-100" role="group">
                                        <?php if ($application['resume_path']): ?>
                                            <a href="<?php echo $application['resume_path']; ?>" 
                                               class="btn btn-outline-primary btn-sm" target="_blank">
                                                <i class="fas fa-file-pdf"></i> Resume
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if ($application['cover_letter_path']): ?>
                                            <a href="<?php echo $application['cover_letter_path']; ?>" 
                                               class="btn btn-outline-secondary btn-sm" target="_blank">
                                                <i class="fas fa-file-alt"></i> Cover Letter
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if ($application['status'] === 'submitted'): ?>
                                        <div class="mt-2">
                                            <small class="text-muted">
                                                <i class="fas fa-clock"></i> Under Review
                                            </small>
                                        </div>
                                    <?php elseif ($application['status'] === 'shortlisted'): ?>
                                        <div class="mt-2">
                                            <small class="text-success">
                                                <i class="fas fa-star"></i> Shortlisted! You may be contacted for an interview.
                                            </small>
                                        </div>
                                    <?php elseif ($application['status'] === 'accepted'): ?>
                                        <div class="mt-2">
                                            <small class="text-success">
                                                <i class="fas fa-check-circle"></i> Congratulations! Your application was accepted.
                                            </small>
                                        </div>
                                    <?php elseif ($application['status'] === 'rejected'): ?>
                                        <div class="mt-2">
                                            <small class="text-danger">
                                                <i class="fas fa-times-circle"></i> Unfortunately, your application was not selected this time.
                                            </small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// --- Include the footer ---
require_once '../../includes/footer.php';
?>
