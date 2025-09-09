<?php
require_once '../../includes/config.php';

// --- Page-specific variables ---
$page_title = 'Find Internships';
global $pages_path;

// Check if user is logged in and is a student
if (!isLoggedIn() || $_SESSION['role'] !== 'student') {
    header('Location: ' . $pages_path . '/auth/login.php?msg=Please login as a student to view internships');
    exit;
}

$db = getDB();
$user_id = $_SESSION['user_id'];

// Get internships
$internships_query = "SELECT i.*, cp.company_name, ic.name as category_name,
                      COUNT(a.id) as application_count
                      FROM internships i 
                      JOIN company_profiles cp ON i.company_id = cp.id
                      LEFT JOIN internship_categories ic ON i.category_id = ic.id
                      LEFT JOIN applications a ON i.id = a.internship_id
                      WHERE i.status = 'published'
                      GROUP BY i.id
                      ORDER BY i.created_at DESC";
$internships_stmt = $db->prepare($internships_query);
$internships_stmt->execute();
$internships = $internships_stmt->fetchAll();

// Get user's applied internships
$applied_query = "SELECT internship_id FROM applications WHERE student_id = :user_id";
$applied_stmt = $db->prepare($applied_query);
$applied_stmt->execute(['user_id' => $user_id]);
$applied_internships = array_column($applied_stmt->fetchAll(), 'internship_id');

// --- Include the header ---
require_once '../../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4">Find Internships</h2>
            
            <?php if (empty($internships)): ?>
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">No Internships Available</h4>
                        <p class="text-muted">Check back later for new opportunities.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($internships as $internship): ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <h5 class="card-title"><?php echo escape($internship['title']); ?></h5>
                                    <p class="text-muted mb-2">
                                        <strong><?php echo escape($internship['company_name']); ?></strong> | 
                                        <?php echo escape($internship['department']); ?>
                                    </p>
                                    <p class="card-text">
                                        <?php echo substr(strip_tags($internship['description']), 0, 200); ?>...
                                    </p>
                                    <p><strong>Location:</strong> <?php echo escape($internship['location']); ?></p>
                                    <p><strong>Duration:</strong> <?php echo $internship['duration_months']; ?> months</p>
                                </div>
                                <div class="col-md-4 text-end">
                                    <?php if (in_array($internship['id'], $applied_internships)): ?>
                                        <span class="badge bg-success mb-2">Already Applied</span><br>
                                        <a href="my_applications.php" class="btn btn-outline-primary btn-sm">
                                            View Application
                                        </a>
                                    <?php else: ?>
                                        <a href="apply_vacancy.php?id=<?php echo $internship['id']; ?>" 
                                           class="btn btn-primary btn-sm">
                                            Apply Now
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// --- Include the footer ---
require_once '../../includes/footer.php';
?>
