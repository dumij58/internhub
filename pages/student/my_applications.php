<?php
require_once '../../includes/config.php';
requireLogin();
$role =  $_SESSION['role'];
if ($role !== 'student') {
    logActivity('Unauthorized Access Attempt', 'User changed the url from "' . $role . '" to "student".');
    http_response_code(401);
    exit;
}

// --- Page-specific variables ---
$db = getDB();
$user_id = $_SESSION['user_id'];
$page_title = 'My Applications';
global $pages_path;

// Get student profile
$profile_query = "SELECT * FROM student_profiles WHERE user_id = ?";
$stmt = $db->prepare($profile_query);
$stmt->execute([$user_id]);
$student_profile = $stmt->fetch(PDO::FETCH_ASSOC);

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Filter
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Build query with filters
$where_conditions = ['a.student_id = ?'];
$params = [$user_id];

if (!empty($status_filter)) {
    $where_conditions[] = 'a.status = ?';
    $params[] = $status_filter;
}

$where_clause = implode(' AND ', $where_conditions);

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total 
                FROM applications a
                JOIN internships i ON a.internship_id = i.id
                JOIN company_profiles cp ON i.company_id = cp.id
                WHERE $where_clause";
$stmt = $db->prepare($count_query);
$stmt->execute($params);
$total_records = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_records / $limit);

// Get applications with pagination
$applications_query = "SELECT a.*, i.title as internship_title, i.location, i.stipend, 
                              cp.company_name, cp.industry_type,
                              a.application_date, a.status
                       FROM applications a
                       JOIN internships i ON a.internship_id = i.id
                       JOIN company_profiles cp ON i.company_id = cp.id
                       WHERE $where_clause
                       ORDER BY a.application_date DESC
                       LIMIT $limit OFFSET $offset";
$stmt = $db->prepare($applications_query);
$stmt->execute($params);
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get status counts for filter tabs
$status_counts_query = "SELECT 
    COUNT(*) as total,
    COUNT(CASE WHEN status = 'draft' THEN 1 END) as draft,
    COUNT(CASE WHEN status = 'submitted' THEN 1 END) as submitted,
    COUNT(CASE WHEN status = 'under_review' THEN 1 END) as under_review,
    COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected,
    COUNT(CASE WHEN status = 'accepted' THEN 1 END) as accepted
    FROM applications a
    WHERE a.student_id = ?";
$stmt = $db->prepare($status_counts_query);
$stmt->execute([$user_id]);
$status_counts = $stmt->fetch(PDO::FETCH_ASSOC);

// --- Include the header ---
require_once '../../includes/header.php';
?>

<div class="profile-container">
    <!-- Page Header -->
    <div class="card">
        <div class="card-body">
            <div class="page-header">
                <h1>
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-file-earmark-text" viewBox="0 0 16 16">
                        <path d="M5.5 7a.5.5 0 0 0 0 1h5a.5.5 0 0 0 0-1h-5zM5 9.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5zm0 2a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5z"/>
                        <path d="M9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V4.5L9.5 0zm0 1v2A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5z"/>
                    </svg>
                    My Applications
                </h1>
                <div class="page-actions">
                    <a href="find_internships.php" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16">
                            <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                        </svg>
                        Find More Internships
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="card">
        <div class="card-body">
            <div class="filter-tabs">
                <a href="?status=" class="filter-tab <?php echo empty($status_filter) ? 'active' : ''; ?>">
                    All (<?php echo $status_counts['total']; ?>)
                </a>
                <a href="?status=submitted" class="filter-tab <?php echo $status_filter === 'submitted' ? 'active' : ''; ?>">
                    Submitted (<?php echo $status_counts['submitted']; ?>)
                </a>
                <a href="?status=under_review" class="filter-tab <?php echo $status_filter === 'under_review' ? 'active' : ''; ?>">
                    Under Review (<?php echo $status_counts['under_review']; ?>)
                </a>
                <a href="?status=accepted" class="filter-tab <?php echo $status_filter === 'accepted' ? 'active' : ''; ?>">
                    Accepted (<?php echo $status_counts['accepted']; ?>)
                </a>
                <a href="?status=rejected" class="filter-tab <?php echo $status_filter === 'rejected' ? 'active' : ''; ?>">
                    Rejected (<?php echo $status_counts['rejected']; ?>)
                </a>
                <a href="?status=draft" class="filter-tab <?php echo $status_filter === 'draft' ? 'active' : ''; ?>">
                    Draft (<?php echo $status_counts['draft']; ?>)
                </a>
            </div>
        </div>
    </div>

    <!-- Applications List -->
    <div class="card">
        <div class="card-body">
            <?php if (empty($applications)): ?>
                <div class="empty-state">
                    <div class="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" class="bi bi-inbox" viewBox="0 0 16 16">
                            <path d="M4.98 4a.5.5 0 0 0-.39.188L1.54 8H6a.5.5 0 0 1 .5.5 1.5 1.5 0 1 0 3 0A.5.5 0 0 1 10 8h4.46l-3.05-3.812A.5.5 0 0 0 11.02 4H4.98zm9.954 5H10.45a2.5 2.5 0 0 1-4.9 0H1.066l.32 2.562a.5.5 0 0 0 .497.438h12.234a.5.5 0 0 0 .496-.438L14.933 9zM3.809 3.563A1.5 1.5 0 0 1 4.981 3h6.038a1.5 1.5 0 0 1 1.172.563l3.7 4.625a.5.5 0 0 1 .105.374l-.39 3.124A1.5 1.5 0 0 1 14.117 13H1.883a1.5 1.5 0 0 1-1.489-1.314l-.39-3.124a.5.5 0 0 1 .106-.374l3.7-4.625z"/>
                        </svg>
                    </div>
                    <h3>No applications found</h3>
                    <p>
                        <?php if (!empty($status_filter)): ?>
                            No applications with status "<?php echo ucfirst(str_replace('_', ' ', $status_filter)); ?>" found.
                        <?php else: ?>
                            You haven't applied to any internships yet.
                        <?php endif; ?>
                    </p>
                    <a href="find_internships.php" class="btn btn-primary">Find Internships</a>
                </div>
            <?php else: ?>
                <div class="applications-grid">
                    <?php foreach ($applications as $app): ?>
                        <div class="application-card">
                            <div class="application-header">
                                <div class="application-title">
                                    <h3><?php echo escape($app['internship_title']); ?></h3>
                                    <p class="company-name"><?php echo escape($app['company_name']); ?></p>
                                </div>
                                <span class="application-status status-<?php echo $app['status']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $app['status'])); ?>
                                </span>
                            </div>
                            
                            <div class="application-details">
                                <div class="detail-item">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-geo-alt" viewBox="0 0 16 16">
                                        <path d="M12.166 8.94c-.524 1.062-1.234 2.12-1.96 3.07A31.493 31.493 0 0 1 8 14.58a31.481 31.481 0 0 1-2.206-2.57c-.726-.95-1.436-2.008-1.96-3.07C3.304 7.867 3 6.862 3 6a5 5 0 0 1 10 0c0 .862-.305 1.867-.834 2.94zM8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10z"/>
                                        <path d="M8 8a2 2 0 1 1 0-4 2 2 0 0 1 0 4zm0 1a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
                                    </svg>
                                    <?php echo escape($app['location'] ?? 'Location not specified'); ?>
                                </div>
                                
                                <?php if (!empty($app['stipend'])): ?>
                                    <div class="detail-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-currency-dollar" viewBox="0 0 16 16">
                                            <path d="M4 10.781c.148 1.667 1.513 2.85 3.591 3.003V15h1.043v-1.216c2.27-.179 3.678-1.438 3.678-3.3 0-1.59-.947-2.51-2.956-3.028l-.722-.187V3.467c1.122.11 1.879.714 2.07 1.616h1.47c-.166-1.6-1.54-2.748-3.54-2.875V1H7.591v1.233c-1.939.23-3.27 1.472-3.27 3.156 0 1.454.966 2.483 2.661 2.917l.61.162v4.031c-1.149-.17-1.94-.8-2.131-1.718H4zm3.391-3.836c-1.043-.263-1.6-.825-1.6-1.616 0-.944.704-1.641 1.8-1.828v3.495l-.2-.05zm1.591 1.872c1.287.323 1.852.859 1.852 1.769 0 1.097-.826 1.828-2.2 1.939V8.73l.348.086z"/>
                                        </svg>
                                        $<?php echo number_format($app['stipend'], 2); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="detail-item">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-calendar" viewBox="0 0 16 16">
                                        <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/>
                                    </svg>
                                    Applied on <?php echo date('M j, Y', strtotime($app['application_date'])); ?>
                                </div>
                                
                                <div class="detail-item">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-building" viewBox="0 0 16 16">
                                        <path fill-rule="evenodd" d="M14.763.075A.5.5 0 0 1 15 .5v15a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5V14h-1v1.5a.5.5 0 0 1-.5.5h-9a.5.5 0 0 1-.5-.5V10a.5.5 0 0 1 .342-.474L6 7.64V4.5a.5.5 0 0 1 .276-.447l8-4a.5.5 0 0 1 .487.022ZM6 8.694 1 10.36V15h5V8.694ZM7 15h2v-1.5a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 .5.5V15h2V1.309l-7 3.5V15Z"/>
                                    </svg>
                                    <?php echo escape($app['industry_type'] ?? 'Industry not specified'); ?>
                                </div>
                            </div>
                            
                            <div class="application-actions">
                                <a href="../internships/view.php?id=<?php echo $app['internship_id']; ?>" class="btn btn-sm btn-outline">View Details</a>
                                <?php if ($app['status'] === 'draft'): ?>
                                    <a href="../internships/apply.php?id=<?php echo $app['internship_id']; ?>" class="btn btn-sm btn-primary">Complete Application</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?><?php echo !empty($status_filter) ? '&status=' . $status_filter : ''; ?>" class="btn btn-sm btn-outline">&laquo; Previous</a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?php echo $i; ?><?php echo !empty($status_filter) ? '&status=' . $status_filter : ''; ?>" 
                               class="btn btn-sm <?php echo $i === $page ? 'btn-primary' : 'btn-outline'; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?><?php echo !empty($status_filter) ? '&status=' . $status_filter : ''; ?>" class="btn btn-sm btn-outline">Next &raquo;</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.filter-tabs {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.filter-tab {
    padding: 0.5rem 1rem;
    border: 1px solid #ddd;
    border-radius: 0.375rem;
    text-decoration: none;
    color: #666;
    transition: all 0.2s;
}

.filter-tab:hover,
.filter-tab.active {
    background: #007bff;
    color: white;
    border-color: #007bff;
}

.applications-grid {
    display: grid;
    gap: 1.5rem;
}

.application-card {
    border: 1px solid #ddd;
    border-radius: 0.5rem;
    padding: 1.5rem;
    transition: shadow 0.2s;
}

.application-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.application-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.application-title h3 {
    margin: 0 0 0.25rem 0;
    color: #333;
}

.company-name {
    margin: 0;
    color: #666;
    font-size: 0.9rem;
}

.application-status {
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: capitalize;
}

.status-draft { background: #ffc107; color: #856404; }
.status-submitted { background: #17a2b8; color: white; }
.status-under_review { background: #6f42c1; color: white; }
.status-accepted { background: #28a745; color: white; }
.status-rejected { background: #dc3545; color: white; }

.application-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #666;
    font-size: 0.9rem;
}

.application-actions {
    display: flex;
    gap: 0.75rem;
    padding-top: 1rem;
    border-top: 1px solid #eee;
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    margin-top: 2rem;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.page-header h1 {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin: 0;
}

.page-actions {
    display: flex;
    gap: 0.75rem;
}
</style>

<?php
// --- Include the footer ---
require_once '../../includes/footer.php';
?>
