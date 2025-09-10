<?php
require_once '../../includes/config.php';
requireLogin();

// Check if user is company
if ($_SESSION['role'] !== 'company') {
    logActivity('Unauthorized Access Attempt', 'User tried to access company internship management');
    header('Location: ../../pages/error.php?error_message=403 - Access denied');
    exit;
}

$db = getDB();
$user_id = $_SESSION['user_id'];
$page_title = 'Manage Internships';

// Get company profile
$stmt = $db->prepare("SELECT * FROM company_profiles WHERE user_id = ?");
$stmt->execute([$user_id]);
$company_profile = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$company_profile) {
    $_SESSION['error_message'] = 'Company profile not found.';
    header('Location: dashboard.php');
    exit;
}

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if ($_POST['action'] === 'update_status') {
            $internship_id = intval($_POST['internship_id']);
            $new_status = $_POST['status'];
            
            // Verify this internship belongs to the company
            $verify_stmt = $db->prepare("SELECT id FROM internships WHERE id = ? AND company_id = ?");
            $verify_stmt->execute([$internship_id, $company_profile['id']]);
            
            if ($verify_stmt->fetch()) {
                $update_stmt = $db->prepare("UPDATE internships SET status = ? WHERE id = ?");
                $update_stmt->execute([$new_status, $internship_id]);
                
                logActivity('Internship Status Updated', "Changed internship ID $internship_id to status: $new_status");
                $_SESSION['success_message'] = 'Internship status updated successfully!';
            } else {
                $_SESSION['error_message'] = 'Internship not found or access denied.';
            }
        } elseif ($_POST['action'] === 'delete_internship') {
            $internship_id = intval($_POST['internship_id']);
            
            // Verify this internship belongs to the company
            $verify_stmt = $db->prepare("SELECT id, title FROM internships WHERE id = ? AND company_id = ?");
            $verify_stmt->execute([$internship_id, $company_profile['id']]);
            $internship = $verify_stmt->fetch();
            
            if ($internship) {
                // Check if there are applications
                $app_check_stmt = $db->prepare("SELECT COUNT(*) FROM applications WHERE internship_id = ?");
                $app_check_stmt->execute([$internship_id]);
                $app_count = $app_check_stmt->fetchColumn();
                
                if ($app_count > 0) {
                    $_SESSION['error_message'] = 'Cannot delete internship with existing applications.';
                } else {
                    $delete_stmt = $db->prepare("DELETE FROM internships WHERE id = ?");
                    $delete_stmt->execute([$internship_id]);
                    
                    logActivity('Internship Deleted', "Deleted internship: {$internship['title']}");
                    $_SESSION['success_message'] = 'Internship deleted successfully!';
                }
            } else {
                $_SESSION['error_message'] = 'Internship not found or access denied.';
            }
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = 'An error occurred. Please try again.';
        error_log($e->getMessage());
    }
    
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$search_query = $_GET['search'] ?? '';

// Build query
$where_conditions = ["company_id = ?"];
$params = [$company_profile['id']];

if ($status_filter !== 'all') {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
}

if (!empty($search_query)) {
    $where_conditions[] = "(title LIKE ? OR description LIKE ?)";
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
}

$where_clause = implode(' AND ', $where_conditions);

// Get internships with application counts
$sql = "SELECT i.*, ic.name as category_name,
               COUNT(a.id) as application_count,
               COUNT(CASE WHEN a.status = 'submitted' THEN 1 END) as new_applications
        FROM internships i
        LEFT JOIN internship_categories ic ON i.category_id = ic.id
        LEFT JOIN applications a ON i.id = a.internship_id
        WHERE $where_clause
        GROUP BY i.id
        ORDER BY i.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$internships = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats_sql = "SELECT 
    COUNT(CASE WHEN status = 'published' THEN 1 END) as published_count,
    COUNT(CASE WHEN status = 'draft' THEN 1 END) as draft_count,
    COUNT(CASE WHEN status = 'closed' THEN 1 END) as closed_count,
    COUNT(*) as total_count
    FROM internships 
    WHERE company_id = ?";
$stats_stmt = $db->prepare($stats_sql);
$stats_stmt->execute([$company_profile['id']]);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

require_once '../../includes/header.php';
?>

<div class="management-container">
    <div class="management-header">
        <div class="header-content">
            <h1>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-briefcase" viewBox="0 0 16 16">
                    <path d="M6.5 1A1.5 1.5 0 0 0 5 2.5V3H1.5A1.5 1.5 0 0 0 0 4.5v8A1.5 1.5 0 0 0 1.5 14h13a1.5 1.5 0 0 0 1.5-1.5v-8A1.5 1.5 0 0 0 14.5 3H11v-.5A1.5 1.5 0 0 0 9.5 1h-3zm0 1h3a.5.5 0 0 1 .5.5V3H6v-.5a.5.5 0 0 1 .5-.5zm1.886 6.914L15 7.151V12.5a.5.5 0 0 1-.5.5h-13a.5.5 0 0 1-.5-.5V7.15l6.614 1.764a1.5 1.5 0 0 0 .772 0zM1.5 4h13a.5.5 0 0 1 .5.5v1.616L8.129 7.948a.5.5 0 0 1-.258 0L1 6.116V4.5a.5.5 0 0 1 .5-.5z"/>
                </svg>
                Manage Internships
            </h1>
            <p>View and manage all your internship postings</p>
        </div>
        <div class="header-actions">
            <a href="post_internship.php" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-plus-circle" viewBox="0 0 16 16">
                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                    <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
                </svg>
                Post New Internship
            </a>
        </div>
    </div>

    <!-- Statistics -->
    <div class="stats-row">
        <div class="stat-card published">
            <div class="stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-eye" viewBox="0 0 16 16">
                    <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/>
                    <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/>
                </svg>
            </div>
            <div class="stat-content">
                <h3><?php echo $stats['published_count']; ?></h3>
                <p>Published</p>
            </div>
        </div>
        
        <div class="stat-card draft">
            <div class="stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-file-earmark-text" viewBox="0 0 16 16">
                    <path d="M5.5 7a.5.5 0 0 0 0 1h5a.5.5 0 0 0 0-1h-5zM5 9.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5zm0 2a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5z"/>
                    <path d="M9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V4.5L9.5 0zm0 1v2A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5z"/>
                </svg>
            </div>
            <div class="stat-content">
                <h3><?php echo $stats['draft_count']; ?></h3>
                <p>Drafts</p>
            </div>
        </div>
        
        <div class="stat-card closed">
            <div class="stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-check-circle" viewBox="0 0 16 16">
                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                    <path d="M10.97 4.97a.235.235 0 0 0-.02.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.061L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-1.071-1.05z"/>
                </svg>
            </div>
            <div class="stat-content">
                <h3><?php echo $stats['closed_count']; ?></h3>
                <p>Closed</p>
            </div>
        </div>
        
        <div class="stat-card total">
            <div class="stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-collection" viewBox="0 0 16 16">
                    <path d="M2.5 3.5a.5.5 0 0 1 0-1h11a.5.5 0 0 1 0 1h-11zm2-2a.5.5 0 0 1 0-1h7a.5.5 0 0 1 0 1h-7zM0 13a1.5 1.5 0 0 0 1.5 1.5h13A1.5 1.5 0 0 0 16 13V6a1.5 1.5 0 0 0-1.5-1.5h-13A1.5 1.5 0 0 0 0 6v7zm1.5.5A.5.5 0 0 1 1 13V6a.5.5 0 0 1 .5-.5h13a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-.5.5h-13z"/>
                </svg>
            </div>
            <div class="stat-content">
                <h3><?php echo $stats['total_count']; ?></h3>
                <p>Total</p>
            </div>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="filters-section">
        <form method="GET" class="filters-form">
            <div class="filter-group">
                <label for="status">Status:</label>
                <select name="status" id="status" onchange="this.form.submit()">
                    <option value="all" <?php echo ($status_filter === 'all') ? 'selected' : ''; ?>>All Statuses</option>
                    <option value="published" <?php echo ($status_filter === 'published') ? 'selected' : ''; ?>>Published</option>
                    <option value="draft" <?php echo ($status_filter === 'draft') ? 'selected' : ''; ?>>Draft</option>
                    <option value="closed" <?php echo ($status_filter === 'closed') ? 'selected' : ''; ?>>Closed</option>
                    <option value="cancelled" <?php echo ($status_filter === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            
            <div class="search-group">
                <input type="text" 
                       name="search" 
                       placeholder="Search internships..." 
                       value="<?php echo escape($search_query); ?>"
                       class="search-input">
                <button type="submit" class="btn btn-secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16">
                        <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                    </svg>
                </button>
            </div>
        </form>
    </div>

    <!-- Internships List -->
    <div class="internships-section">
        <?php if (empty($internships)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="currentColor" class="bi bi-inbox" viewBox="0 0 16 16">
                        <path d="M4.98 4a.5.5 0 0 0-.39.188L1.54 8H6a.5.5 0 0 1 .5.5 1.5 1.5 0 1 0 3 0A.5.5 0 0 1 10 8h4.46l-3.05-3.812A.5.5 0 0 0 11.02 4H4.98zm9.954 5H10.45a2.5 2.5 0 0 1-4.9 0H1.066l.32 2.562a.5.5 0 0 0 .497.438h12.234a.5.5 0 0 0 .496-.438L14.933 9zM3.809 3.563A1.5 1.5 0 0 1 4.981 3h6.038a1.5 1.5 0 0 1 1.172.563l3.7 4.625a.5.5 0 0 1 .105.374l-.39 3.124A1.5 1.5 0 0 1 14.117 13H1.883a1.5 1.5 0 0 1-1.489-1.314l-.39-3.124a.5.5 0 0 1 .106-.374l3.7-4.625z"/>
                    </svg>
                </div>
                <h3>No internships found</h3>
                <p>
                    <?php if (!empty($search_query) || $status_filter !== 'all'): ?>
                        Try adjusting your filters or search terms.
                    <?php else: ?>
                        You haven't posted any internships yet.
                    <?php endif; ?>
                </p>
                <a href="post_internship.php" class="btn btn-primary">Post Your First Internship</a>
            </div>
        <?php else: ?>
            <div class="internships-grid">
                <?php foreach ($internships as $internship): ?>
                    <div class="internship-card">
                        <div class="card-header">
                            <h3><?php echo escape($internship['title']); ?></h3>
                            <div class="status-badge status-<?php echo $internship['status']; ?>">
                                <?php echo ucfirst($internship['status']); ?>
                            </div>
                        </div>
                        
                        <div class="card-content">
                            <div class="internship-meta">
                                <span class="meta-item">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-tag" viewBox="0 0 16 16">
                                        <path d="M6 4.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-1 0a.5.5 0 1 0-1 0 .5.5 0 0 0 1 0z"/>
                                        <path d="M2 1h4.586a1 1 0 0 1 .707.293L15 9a1 1 0 0 1 0 1.414l-4 4a1 1 0 0 1-1.414 0L2 6.586a1 1 0 0 1-.293-.707V2a1 1 0 0 1 1-1zm0 5.586 6.586 6.586 3-3L5 3.414V6.586z"/>
                                    </svg>
                                    <?php echo escape($internship['category_name']); ?>
                                </span>
                                <span class="meta-item">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-geo-alt" viewBox="0 0 16 16">
                                        <path d="M12.166 8.94c-.524 1.062-1.234 2.12-1.96 3.07A31.493 31.493 0 0 1 8 14.58a31.481 31.481 0 0 1-2.206-2.57c-.726-.95-1.436-2.008-1.96-3.07C3.304 7.867 3 6.862 3 6a5 5 0 0 1 10 0c0 .862-.305 1.867-.834 2.94zM8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10z"/>
                                        <path d="M8 8a2 2 0 1 1 0-4 2 2 0 0 1 0 4zm0 1a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
                                    </svg>
                                    <?php echo escape($internship['location']); ?>
                                    <?php if ($internship['remote_option']): ?>
                                        <span class="remote-badge">Remote OK</span>
                                    <?php endif; ?>
                                </span>
                                <span class="meta-item">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-clock" viewBox="0 0 16 16">
                                        <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/>
                                        <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/>
                                    </svg>
                                    <?php echo $internship['duration_months']; ?> month<?php echo ($internship['duration_months'] != 1) ? 's' : ''; ?>
                                </span>
                            </div>
                            
                            <div class="application-stats">
                                <div class="stat">
                                    <span class="stat-number"><?php echo $internship['application_count']; ?></span>
                                    <span class="stat-label">Total Applications</span>
                                </div>
                                <?php if ($internship['new_applications'] > 0): ?>
                                    <div class="stat new">
                                        <span class="stat-number"><?php echo $internship['new_applications']; ?></span>
                                        <span class="stat-label">New Applications</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="dates-info">
                                <small>
                                    <strong>Deadline:</strong> <?php echo date('M j, Y', strtotime($internship['application_deadline'])); ?>
                                    <br>
                                    <strong>Start:</strong> <?php echo date('M j, Y', strtotime($internship['start_date'])); ?>
                                </small>
                            </div>
                        </div>
                        
                        <div class="card-actions">
                            <div class="action-buttons">
                                <a href="edit_internship.php?id=<?php echo $internship['id']; ?>" class="btn btn-sm btn-secondary">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-pencil" viewBox="0 0 16 16">
                                        <path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708L4.5 15.207a.5.5 0 0 1-.146.103l-3 1a.5.5 0 0 1-.595-.595l1-3a.5.5 0 0 1 .103-.146L12.146.146zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207 11.207 2.5zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293L12.793 5.5zM9.854 8.146a.5.5 0 0 1-.708.708L5.5 5.207l-.646.647.646.646a.5.5 0 0 1-.708.708L3.5 5.914a.5.5 0 0 1 0-.708l1-1a.5.5 0 0 1 .708 0L9.854 8.146z"/>
                                    </svg>
                                    Edit
                                </a>
                                
                                <a href="view_applications.php?internship_id=<?php echo $internship['id']; ?>" class="btn btn-sm btn-info">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-people" viewBox="0 0 16 16">
                                        <path d="M15 14s1 0 1-1-1-4-5-4-5 3-5 4 1 1 1 1h8Zm-7.978-1A.261.261 0 0 1 7 12.996c.001-.264.167-1.03.76-1.72C8.312 10.629 9.282 10 11 10c1.717 0 2.687.63 3.24 1.276.593.69.758 1.457.76 1.72l-.008.002A.274.274 0 0 1 15 13H7.022ZM11 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm3-2a3 3 0 1 1-6 0 3 3 0 0 1 6 0ZM6.936 9.28a5.88 5.88 0 0 0-1.23-.247A7.35 7.35 0 0 0 5 9c-4 0-5 3-5 4 0 .667.333 1 1 1h4.216A2.238 2.238 0 0 1 5 13c0-1.01.377-2.042 1.09-2.904.243-.294.526-.569.846-.816ZM4.92 10A5.493 5.493 0 0 0 4 13H1c0-.26.164-1.03.76-1.724.545-.636 1.492-1.256 3.16-1.275ZM1.5 5.5a3 3 0 1 1 6 0 3 3 0 0 1-6 0Zm3-2a2 2 0 1 0 0 4 2 2 0 0 0 0-4Z"/>
                                    </svg>
                                    Applications (<?php echo $internship['application_count']; ?>)
                                </a>
                            </div>
                            
                            <div class="status-actions">
                                <?php if ($internship['status'] === 'draft'): ?>
                                    <button class="btn btn-sm btn-success" onclick="updateStatus(<?php echo $internship['id']; ?>, 'published')">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-rocket-takeoff" viewBox="0 0 16 16">
                                            <path d="M9.752 6.193c.599.63 1.303.929 1.976.929 1.06 0 1.857-.659 1.857-1.54 0-.577-.315-1.077-.793-1.372C12.498 4.055 12 4 11.5 4c-.605 0-1.07.082-1.384.193a4.924 4.924 0 0 0-.364.193zm-2.495-1.83c-.03-.17-.032-.353-.032-.535C7.225 2.025 8.58.778 10.362.778 12.146.778 13.5 2.025 13.5 3.828c0 .182-.002.365-.032.535-.149.835-.66 1.518-1.302 1.518-.641 0-1.153-.683-1.302-1.518z"/>
                                            <path d="M9.5 6.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 0 1h-1a.5.5 0 0 1-.5-.5zM6.5 14.5a.5.5 0 0 1-1 0V9a.5.5 0 0 1 1 0v5.5zm3 0a.5.5 0 0 1-1 0V9a.5.5 0 0 1 1 0v5.5z"/>
                                        </svg>
                                        Publish
                                    </button>
                                <?php elseif ($internship['status'] === 'published'): ?>
                                    <button class="btn btn-sm btn-warning" onclick="updateStatus(<?php echo $internship['id']; ?>, 'closed')">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-pause-circle" viewBox="0 0 16 16">
                                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                            <path d="M5 6.25a1.25 1.25 0 1 1 2.5 0v3.5a1.25 1.25 0 1 1-2.5 0v-3.5zm3.5 0a1.25 1.25 0 1 1 2.5 0v3.5a1.25 1.25 0 1 1-2.5 0v-3.5z"/>
                                        </svg>
                                        Close
                                    </button>
                                <?php elseif ($internship['status'] === 'closed'): ?>
                                    <button class="btn btn-sm btn-success" onclick="updateStatus(<?php echo $internship['id']; ?>, 'published')">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-play-circle" viewBox="0 0 16 16">
                                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                            <path d="M6.271 5.055a.5.5 0 0 1 .52.038L11 7.055a.5.5 0 0 1 0 .89L6.791 9.907a.5.5 0 0 1-.791-.39V5.5a.5.5 0 0 1 .271-.445z"/>
                                        </svg>
                                        Reopen
                                    </button>
                                <?php endif; ?>
                                
                                <?php if ($internship['application_count'] == 0): ?>
                                    <button class="btn btn-sm btn-danger" onclick="deleteInternship(<?php echo $internship['id']; ?>, '<?php echo escape($internship['title']); ?>')">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16">
                                            <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/>
                                            <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/>
                                        </svg>
                                        Delete
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="card-footer">
                            <small class="text-muted">
                                Created: <?php echo date('M j, Y', strtotime($internship['created_at'])); ?>
                            </small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Hidden forms for actions -->
<form id="statusForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="update_status">
    <input type="hidden" name="internship_id" id="statusInternshipId">
    <input type="hidden" name="status" id="statusValue">
</form>

<form id="deleteForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="delete_internship">
    <input type="hidden" name="internship_id" id="deleteInternshipId">
</form>

<script>
function updateStatus(internshipId, newStatus) {
    const statusMap = {
        'published': 'publish',
        'closed': 'close',
        'draft': 'save as draft'
    };
    
    const action = statusMap[newStatus] || newStatus;
    
    if (confirm(`Are you sure you want to ${action} this internship?`)) {
        document.getElementById('statusInternshipId').value = internshipId;
        document.getElementById('statusValue').value = newStatus;
        document.getElementById('statusForm').submit();
    }
}

function deleteInternship(internshipId, title) {
    if (confirm(`Are you sure you want to delete "${title}"? This action cannot be undone.`)) {
        document.getElementById('deleteInternshipId').value = internshipId;
        document.getElementById('deleteForm').submit();
    }
}
</script>

<?php require_once '../../includes/footer.php'; ?>
