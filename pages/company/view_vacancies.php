<?php
require_once '../../includes/config.php';

// --- Page-specific variables ---
$page_title = 'My Job Vacancies';
global $pages_path;

// Check if user is logged in and is a company
if (!isLoggedIn() || $_SESSION['role'] !== 'company') {
    header('Location: ' . $pages_path . '/auth/login.php?msg=Please login as a company to view vacancies');
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

// Get vacancies posted by this company
$vacancies_query = "SELECT i.*, ic.name as category_name, 
                    COUNT(a.id) as application_count
                    FROM internships i 
                    LEFT JOIN internship_categories ic ON i.category_id = ic.id
                    LEFT JOIN applications a ON i.id = a.internship_id
                    WHERE i.company_id = :company_id 
                    GROUP BY i.id
                    ORDER BY i.created_at DESC";
$vacancies_stmt = $db->prepare($vacancies_query);
$vacancies_stmt->execute(['company_id' => $company['id']]);
$vacancies = $vacancies_stmt->fetchAll();

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $vacancy_id = intval($_POST['vacancy_id']);
    $action = $_POST['action'];
    
    try {
        switch ($action) {
            case 'close':
                $update_sql = "UPDATE internships SET status = 'closed' WHERE id = :id AND company_id = :company_id";
                break;
            case 'publish':
                $update_sql = "UPDATE internships SET status = 'published' WHERE id = :id AND company_id = :company_id";
                break;
            case 'delete':
                $update_sql = "UPDATE internships SET status = 'cancelled' WHERE id = :id AND company_id = :company_id";
                break;
            default:
                throw new Exception("Invalid action");
        }
        
        $stmt = $db->prepare($update_sql);
        $stmt->execute(['id' => $vacancy_id, 'company_id' => $company['id']]);
        
        logActivity('Vacancy Updated', "Updated vacancy ID: $vacancy_id, Action: $action");
        header('Location: view_vacancies.php?success=1');
        exit;
        
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
                <h2>My Job Vacancies</h2>
                <a href="post_vacancy.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Post New Vacancy
                </a>
            </div>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    Vacancy updated successfully!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo escape($error_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (empty($vacancies)): ?>
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-briefcase fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">No Vacancies Posted Yet</h4>
                        <p class="text-muted">Start by posting your first job vacancy to attract talented interns.</p>
                        <a href="post_vacancy.php" class="btn btn-primary">Post Your First Vacancy</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($vacancies as $vacancy): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <span class="badge bg-<?php 
                                        echo $vacancy['status'] === 'published' ? 'success' : 
                                            ($vacancy['status'] === 'closed' ? 'warning' : 'secondary'); 
                                    ?>">
                                        <?php echo ucfirst($vacancy['status']); ?>
                                    </span>
                                    <small class="text-muted"><?php echo date('M d, Y', strtotime($vacancy['created_at'])); ?></small>
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo escape($vacancy['title']); ?></h5>
                                    <p class="card-text">
                                        <strong>Department:</strong> <?php echo escape($vacancy['department']); ?><br>
                                        <strong>Category:</strong> <?php echo escape($vacancy['category_name']); ?><br>
                                        <strong>Location:</strong> <?php echo escape($vacancy['location']); ?><br>
                                        <strong>Duration:</strong> <?php echo $vacancy['duration_months']; ?> months<br>
                                        <strong>Type:</strong> <?php echo ucfirst($vacancy['internship_type']); ?><br>
                                        <?php if ($vacancy['stipend']): ?>
                                            <strong>Stipend:</strong> LKR <?php echo number_format($vacancy['stipend']); ?><br>
                                        <?php endif; ?>
                                        <strong>Applications:</strong> <?php echo $vacancy['application_count']; ?>
                                    </p>
                                    
                                    <div class="mb-2">
                                        <strong>Deadline:</strong> 
                                        <span class="<?php echo strtotime($vacancy['application_deadline']) < time() ? 'text-danger' : 'text-success'; ?>">
                                            <?php echo date('M d, Y', strtotime($vacancy['application_deadline'])); ?>
                                        </span>
                                    </div>
                                    
                                    <p class="card-text">
                                        <small class="text-muted">
                                            <?php echo substr(strip_tags($vacancy['description']), 0, 100); ?>...
                                        </small>
                                    </p>
                                </div>
                                <div class="card-footer">
                                    <div class="btn-group w-100" role="group">
                                        <a href="view_applications.php?vacancy_id=<?php echo $vacancy['id']; ?>" 
                                           class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-users"></i> Applications
                                        </a>
                                        <a href="edit_vacancy.php?id=<?php echo $vacancy['id']; ?>" 
                                           class="btn btn-outline-secondary btn-sm">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                    </div>
                                    
                                    <div class="mt-2">
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="vacancy_id" value="<?php echo $vacancy['id']; ?>">
                                            
                                            <?php if ($vacancy['status'] === 'published'): ?>
                                                <button type="submit" name="action" value="close" 
                                                        class="btn btn-warning btn-sm me-1"
                                                        onclick="return confirm('Are you sure you want to close this vacancy?')">
                                                    <i class="fas fa-lock"></i> Close
                                                </button>
                                            <?php elseif ($vacancy['status'] === 'closed'): ?>
                                                <button type="submit" name="action" value="publish" 
                                                        class="btn btn-success btn-sm me-1">
                                                    <i class="fas fa-unlock"></i> Reopen
                                                </button>
                                            <?php endif; ?>
                                            
                                            <button type="submit" name="action" value="delete" 
                                                    class="btn btn-danger btn-sm"
                                                    onclick="return confirm('Are you sure you want to delete this vacancy? This action cannot be undone.')">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </div>
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
