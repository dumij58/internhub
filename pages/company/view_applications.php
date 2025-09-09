<?php
require_once '../../includes/config.php';

// --- Page-specific variables ---
$page_title = 'View Applications';
global $pages_path;

// Check if user is logged in and is a company
if (!isLoggedIn() || $_SESSION['role'] !== 'company') {
    header('Location: ' . $pages_path . '/auth/login.php?msg=Please login as a company to view applications');
    exit;
}

$db = getDB();
$user_id = $_SESSION['user_id'];

// Get company profile
$company_query = "SELECT * FROM company_profiles WHERE user_id = :user_id";
$company_stmt = $db->prepare($company_query);
$company_stmt->execute(['user_id' => $user_id]);
$company = $company_stmt->fetch();

if (!$company) {
    header('Location: ' . $pages_path . '/auth/company_details.php?msg=Please complete your company profile first');
    exit;
}

// Get applications for this company's vacancies
$applications_query = "SELECT a.*, i.title as internship_title, i.company_id,
                       sp.first_name, sp.last_name, sp.university, sp.major
                       FROM applications a
                       JOIN internships i ON a.internship_id = i.id
                       LEFT JOIN student_profiles sp ON a.student_id = sp.user_id
                       WHERE i.company_id = :company_id
                       ORDER BY a.application_date DESC";

$applications_stmt = $db->prepare($applications_query);
$applications_stmt->execute(['company_id' => $company['id']]);
$applications = $applications_stmt->fetchAll();

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $application_id = intval($_POST['application_id']);
    $action = $_POST['action'];
    
    try {
        $valid_statuses = ['under_review', 'shortlisted', 'rejected', 'accepted'];
        if (in_array($action, $valid_statuses)) {
            $update_sql = "UPDATE applications SET status = :status, reviewed_date = NOW(), reviewed_by = :reviewed_by 
                          WHERE id = :id AND internship_id IN (SELECT id FROM internships WHERE company_id = :company_id)";
            $stmt = $db->prepare($update_sql);
            $stmt->execute([
                'status' => $action,
                'reviewed_by' => $user_id,
                'id' => $application_id,
                'company_id' => $company['id']
            ]);
            
            logActivity('Application Status Updated', "Updated application ID: $application_id to $action");
            header('Location: view_applications.php?success=1');
            exit;
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// --- Include the header ---
require_once '../../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Applications</h2>
                <a href="view_vacancies.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left"></i> Back to Vacancies
                </a>
            </div>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    Application status updated successfully!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (empty($applications)): ?>
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">No Applications Found</h4>
                        <p class="text-muted">No students have applied for your internships yet.</p>
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
                                    <h5 class="card-title"><?php echo escape($application['full_name']); ?></h5>
                                    <p class="card-text">
                                        <strong>Email:</strong> <?php echo escape($application['email']); ?><br>
                                        <strong>Phone:</strong> <?php echo escape($application['phone']); ?><br>
                                        <strong>University:</strong> <?php echo escape($application['university']); ?><br>
                                        <strong>Degree:</strong> <?php echo escape($application['degree_program']); ?><br>
                                        <?php if ($application['year_of_study']): ?>
                                            <strong>Year:</strong> <?php echo $application['year_of_study']; ?><br>
                                        <?php endif; ?>
                                        <?php if ($application['gpa']): ?>
                                            <strong>GPA:</strong> <?php echo $application['gpa']; ?><br>
                                        <?php endif; ?>
                                    </p>
                                    
                                    <p class="card-text">
                                        <strong>Applied for:</strong><br>
                                        <small class="text-muted"><?php echo escape($application['internship_title']); ?></small>
                                    </p>
                                    
                                    <?php if (!empty($application['key_skills'])): ?>
                                        <p class="card-text">
                                            <strong>Skills:</strong><br>
                                            <small class="text-muted"><?php echo escape($application['key_skills']); ?></small>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <div class="card-footer">
                                    <div class="btn-group w-100 mb-2" role="group">
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
                                    
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="application_id" value="<?php echo $application['id']; ?>">
                                        
                                        <div class="btn-group w-100" role="group">
                                            <button type="submit" name="action" value="shortlisted" 
                                                    class="btn btn-warning btn-sm"
                                                    <?php echo $application['status'] === 'shortlisted' ? 'disabled' : ''; ?>>
                                                <i class="fas fa-star"></i> Shortlist
                                            </button>
                                            <button type="submit" name="action" value="accepted" 
                                                    class="btn btn-success btn-sm"
                                                    <?php echo $application['status'] === 'accepted' ? 'disabled' : ''; ?>>
                                                <i class="fas fa-check"></i> Accept
                                            </button>
                                            <button type="submit" name="action" value="rejected" 
                                                    class="btn btn-danger btn-sm"
                                                    <?php echo $application['status'] === 'rejected' ? 'disabled' : ''; ?>>
                                                <i class="fas fa-times"></i> Reject
                                            </button>
                                        </div>
                                    </form>
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
