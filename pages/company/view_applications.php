<?php
require_once '../../includes/config.php';
requireLogin();

// Check if user is company
if ($_SESSION['role'] !== 'company') {
    logActivity('Unauthorized Access Attempt', 'User tried to access company application management');
    header('Location: ../../pages/error.php?error_message=403 - Access denied');
    exit;
}

$db = getDB();
$user_id = $_SESSION['user_id'];
$internship_id = isset($_GET['internship_id']) ? intval($_GET['internship_id']) : null;
$page_title = 'View Applications';

// Get company profile
$stmt = $db->prepare("SELECT * FROM company_profiles WHERE user_id = ?");
$stmt->execute([$user_id]);
$company_profile = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$company_profile) {
    $_SESSION['error_message'] = 'Company profile not found.';
    header('Location: dashboard.php');
    exit;
}

// Build base query
$where_conditions = ["i.company_id = ?"];
$params = [$company_profile['id']];

// If specific internship is requested, verify ownership and add to filter
if ($internship_id) {
    $verify_stmt = $db->prepare("SELECT title FROM internships WHERE id = ? AND company_id = ?");
    $verify_stmt->execute([$internship_id, $company_profile['id']]);
    $internship = $verify_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$internship) {
        $_SESSION['error_message'] = 'Internship not found or access denied.';
        header('Location: manage_internships.php');
        exit;
    }
    
    $where_conditions[] = "a.internship_id = ?";
    $params[] = $internship_id;
    $page_title = 'Applications for: ' . $internship['title'];
}

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        $application_id = intval($_POST['application_id']);
        $new_status = $_POST['action'];
        
        $valid_statuses = ['under_review', 'shortlisted', 'rejected', 'accepted', 'withdrawn'];
        
        if (in_array($new_status, $valid_statuses)) {
            // Verify the application belongs to this company
            $verify_app_stmt = $db->prepare("
                SELECT a.id, i.title, sp.first_name, sp.last_name 
                FROM applications a 
                JOIN internships i ON a.internship_id = i.id 
                LEFT JOIN student_profiles sp ON a.student_id = sp.user_id
                WHERE a.id = ? AND i.company_id = ?
            ");
            $verify_app_stmt->execute([$application_id, $company_profile['id']]);
            $app_details = $verify_app_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($app_details) {
                $update_stmt = $db->prepare("
                    UPDATE applications 
                    SET status = ?, reviewed_date = CURRENT_TIMESTAMP, reviewed_by = ? 
                    WHERE id = ?
                ");
                $update_stmt->execute([$new_status, $user_id, $application_id]);
                
                logActivity('Application Status Updated', 
                    "Updated application from {$app_details['first_name']} {$app_details['last_name']} for '{$app_details['title']}' to: $new_status");
                
                $_SESSION['success_message'] = 'Application status updated successfully!';
            } else {
                $_SESSION['error_message'] = 'Application not found or access denied.';
            }
        } else {
            $_SESSION['error_message'] = 'Invalid status.';
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = 'An error occurred while updating the application.';
        error_log($e->getMessage());
    }
    
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$search_query = $_GET['search'] ?? '';

if ($status_filter !== 'all') {
    $where_conditions[] = "a.status = ?";
    $params[] = $status_filter;
}

if (!empty($search_query)) {
    $where_conditions[] = "(sp.first_name LIKE ? OR sp.last_name LIKE ? OR u.email LIKE ? OR i.title LIKE ?)";
    $search_param = "%$search_query%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_clause = implode(' AND ', $where_conditions);

// Get applications with details
$sql = "SELECT a.*, i.title as internship_title, i.id as internship_id,
               sp.first_name, sp.last_name, sp.university, sp.major, sp.year_of_study, sp.gpa, sp.bio, sp.skills,
               u.email, u.username,
               ic.name as category_name
        FROM applications a
        JOIN internships i ON a.internship_id = i.id
        JOIN users u ON a.student_id = u.user_id
        LEFT JOIN student_profiles sp ON a.student_id = sp.user_id
        LEFT JOIN internship_categories ic ON i.category_id = ic.id
        WHERE $where_clause
        ORDER BY a.application_date DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats_sql = "SELECT 
    COUNT(*) as total_applications,
    COUNT(CASE WHEN a.status = 'submitted' THEN 1 END) as new_applications,
    COUNT(CASE WHEN a.status = 'under_review' THEN 1 END) as under_review,
    COUNT(CASE WHEN a.status = 'shortlisted' THEN 1 END) as shortlisted,
    COUNT(CASE WHEN a.status = 'accepted' THEN 1 END) as accepted,
    COUNT(CASE WHEN a.status = 'rejected' THEN 1 END) as rejected
    FROM applications a
    JOIN internships i ON a.internship_id = i.id
    WHERE i.company_id = ?" . ($internship_id ? " AND a.internship_id = ?" : "");

$stats_params = [$company_profile['id']];
if ($internship_id) {
    $stats_params[] = $internship_id;
}

$stats_stmt = $db->prepare($stats_sql);
$stats_stmt->execute($stats_params);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

require_once '../../includes/header.php';
?>

<div class="management-container">
    <div class="management-header">
        <div class="header-content">
            <h1>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-people" viewBox="0 0 16 16">
                    <path d="M15 14s1 0 1-1-1-4-5-4-5 3-5 4 1 1 1 1h8Zm-7.978-1A.261.261 0 0 1 7 12.996c.001-.264.167-1.03.76-1.72C8.312 10.629 9.282 10 11 10c1.717 0 2.687.63 3.24 1.276.593.69.758 1.457.76 1.72l-.008.002A.274.274 0 0 1 15 13H7.022ZM11 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm3-2a3 3 0 1 1-6 0 3 3 0 0 1 6 0ZM6.936 9.28a5.88 5.88 0 0 0-1.23-.247A7.35 7.35 0 0 0 5 9c-4 0-5 3-5 4 0 .667.333 1 1 1h4.216A2.238 2.238 0 0 1 5 13c0-1.01.377-2.042 1.09-2.904.243-.294.526-.569.846-.816ZM4.92 10A5.493 5.493 0 0 0 4 13H1c0-.26.164-1.03.76-1.724.545-.636 1.492-1.256 3.16-1.275ZM1.5 5.5a3 3 0 1 1 6 0 3 3 0 0 1-6 0Zm3-2a2 2 0 1 0 0 4 2 2 0 0 0 0-4Z"/>
                </svg>
                <?php echo $internship_id ? 'Applications for Internship' : 'All Applications'; ?>
            </h1>
            <p>Review and manage student applications</p>
        </div>
        <div class="header-actions">
            <a href="<?php echo $internship_id ? 'manage_internships.php' : 'dashboard.php'; ?>" class="btn btn-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5a.5.5 0 0 0 .5-.5z"/>
                </svg>
                Back
            </a>
        </div>
    </div>

    <!-- Statistics -->
    <div class="stats-row">
        <div class="stat-card total">
            <div class="stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-collection" viewBox="0 0 16 16">
                    <path d="M2.5 3.5a.5.5 0 0 1 0-1h11a.5.5 0 0 1 0 1h-11zm2-2a.5.5 0 0 1 0-1h7a.5.5 0 0 1 0 1h-7zM0 13a1.5 1.5 0 0 0 1.5 1.5h13A1.5 1.5 0 0 0 16 13V6a1.5 1.5 0 0 0-1.5-1.5h-13A1.5 1.5 0 0 0 0 6v7zm1.5.5A.5.5 0 0 1 1 13V6a.5.5 0 0 1 .5-.5h13a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-.5.5h-13z"/>
                </svg>
            </div>
            <div class="stat-content">
                <h3><?php echo $stats['total_applications']; ?></h3>
                <p>Total Applications</p>
            </div>
        </div>
        
        <div class="stat-card info">
            <div class="stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-inbox" viewBox="0 0 16 16">
                    <path d="M4.98 4a.5.5 0 0 0-.39.188L1.54 8H6a.5.5 0 0 1 .5.5 1.5 1.5 0 1 0 3 0A.5.5 0 0 1 10 8h4.46l-3.05-3.812A.5.5 0 0 0 11.02 4H4.98zm9.954 5H10.45a2.5 2.5 0 0 1-4.9 0H1.066l.32 2.562a.5.5 0 0 0 .497.438h12.234a.5.5 0 0 0 .496-.438L14.933 9zM3.809 3.563A1.5 1.5 0 0 1 4.981 3h6.038a1.5 1.5 0 0 1 1.172.563l3.7 4.625a.5.5 0 0 1 .105.374l-.39 3.124A1.5 1.5 0 0 1 14.117 13H1.883a1.5 1.5 0 0 1-1.489-1.314l-.39-3.124a.5.5 0 0 1 .106-.374l3.7-4.625z"/>
                </svg>
            </div>
            <div class="stat-content">
                <h3><?php echo $stats['new_applications']; ?></h3>
                <p>New Applications</p>
            </div>
        </div>
        
        <div class="stat-card warning">
            <div class="stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-star" viewBox="0 0 16 16">
                    <path d="M2.866 14.85c-.078.444.36.791.746.593l4.39-2.256 4.389 2.256c.386.198.824-.149.746-.592l-.83-4.73 3.522-3.356c.33-.314.16-.888-.282-.95l-4.898-.696L8.465.792a.513.513 0 0 0-.927 0L5.354 5.12l-4.898.696c-.441.062-.612.636-.283.95l3.523 3.356-.83 4.73zm4.905-2.767-3.686 1.894.694-3.957a.565.565 0 0 0-.163-.505L1.71 6.745l4.052-.576a.525.525 0 0 0 .393-.288L8 2.223l1.847 3.658a.525.525 0 0 0 .393.288l4.052.575-2.906 2.77a.565.565 0 0 0-.163.506l.694 3.957-3.686-1.894a.503.503 0 0 0-.461 0z"/>
                </svg>
            </div>
            <div class="stat-content">
                <h3><?php echo $stats['shortlisted']; ?></h3>
                <p>Shortlisted</p>
            </div>
        </div>
        
        <div class="stat-card published">
            <div class="stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-check-circle" viewBox="0 0 16 16">
                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                    <path d="M10.97 4.97a.235.235 0 0 0-.02.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.061L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-1.071-1.05z"/>
                </svg>
            </div>
            <div class="stat-content">
                <h3><?php echo $stats['accepted']; ?></h3>
                <p>Accepted</p>
            </div>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="filters-section">
        <form method="GET" class="filters-form">
            <?php if ($internship_id): ?>
                <input type="hidden" name="internship_id" value="<?php echo $internship_id; ?>">
            <?php endif; ?>
            
            <div class="filter-group">
                <label for="status">Status:</label>
                <select name="status" id="status" onchange="this.form.submit()">
                    <option value="all" <?php echo ($status_filter === 'all') ? 'selected' : ''; ?>>All Statuses</option>
                    <option value="submitted" <?php echo ($status_filter === 'submitted') ? 'selected' : ''; ?>>New Applications</option>
                    <option value="under_review" <?php echo ($status_filter === 'under_review') ? 'selected' : ''; ?>>Under Review</option>
                    <option value="shortlisted" <?php echo ($status_filter === 'shortlisted') ? 'selected' : ''; ?>>Shortlisted</option>
                    <option value="accepted" <?php echo ($status_filter === 'accepted') ? 'selected' : ''; ?>>Accepted</option>
                    <option value="rejected" <?php echo ($status_filter === 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                </select>
            </div>
            
            <div class="search-group">
                <input type="text" 
                       name="search" 
                       placeholder="Search applications..." 
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

    <!-- Applications List -->
    <div class="applications-section">
        <?php if (empty($applications)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="currentColor" class="bi bi-people" viewBox="0 0 16 16">
                        <path d="M15 14s1 0 1-1-1-4-5-4-5 3-5 4 1 1 1 1h8Zm-7.978-1A.261.261 0 0 1 7 12.996c.001-.264.167-1.03.76-1.72C8.312 10.629 9.282 10 11 10c1.717 0 2.687.63 3.24 1.276.593.69.758 1.457.76 1.72l-.008.002A.274.274 0 0 1 15 13H7.022ZM11 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm3-2a3 3 0 1 1-6 0 3 3 0 0 1 6 0ZM6.936 9.28a5.88 5.88 0 0 0-1.23-.247A7.35 7.35 0 0 0 5 9c-4 0-5 3-5 4 0 .667.333 1 1 1h4.216A2.238 2.238 0 0 1 5 13c0-1.01.377-2.042 1.09-2.904.243-.294.526-.569.846-.816ZM4.92 10A5.493 5.493 0 0 0 4 13H1c0-.26.164-1.03.76-1.724.545-.636 1.492-1.256 3.16-1.275ZM1.5 5.5a3 3 0 1 1 6 0 3 3 0 0 1-6 0Zm3-2a2 2 0 1 0 0 4 2 2 0 0 0 0-4Z"/>
                    </svg>
                </div>
                <h3>No applications found</h3>
                <p>
                    <?php if (!empty($search_query) || $status_filter !== 'all'): ?>
                        Try adjusting your filters or search terms.
                    <?php else: ?>
                        No students have applied yet.
                    <?php endif; ?>
                </p>
            </div>
        <?php else: ?>
            <div class="applications-grid">
                <?php foreach ($applications as $application): ?>
                    <div class="application-card">
                        <div class="application-header">
                            <div class="student-info">
                                <h3><?php echo escape(($application['first_name'] ?? '') . ' ' . ($application['last_name'] ?? '')); ?></h3>
                                <p class="email"><?php echo escape($application['email']); ?></p>
                            </div>
                            <div class="application-status">
                                <span class="status-badge status-<?php echo $application['status']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $application['status'])); ?>
                                </span>
                                <small class="application-date">
                                    Applied: <?php echo date('M j, Y', strtotime($application['application_date'])); ?>
                                </small>
                            </div>
                        </div>
                        
                        <div class="application-content">
                            <?php if (!$internship_id): ?>
                                <div class="internship-info">
                                    <h4><?php echo escape($application['internship_title']); ?></h4>
                                    <span class="category-badge"><?php echo escape($application['category_name']); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="student-details">
                                <?php if ($application['university']): ?>
                                    <div class="detail-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-mortarboard" viewBox="0 0 16 16">
                                            <path d="M8.211 2.047a.5.5 0 0 0-.422 0l-7.5 3.5a.5.5 0 0 0 .025.917l7.5 3a.5.5 0 0 0 .372 0L14.25 7.14a.5.5 0 0 0 0-.883l-7.5-3.5zM8 2.5L1.021 5.52 8 8.54l6.979-3.02L8 2.5zM8.5 11.5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2a.5.5 0 0 1 .5.5z"/>
                                            <path d="M3.5 7.5a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-1z"/>
                                        </svg>
                                        <span><?php echo escape($application['university']); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($application['major']): ?>
                                    <div class="detail-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-book" viewBox="0 0 16 16">
                                            <path d="M1 2.828c.885-.37 2.154-.769 3.388-.893 1.33-.134 2.458.063 3.112.752v9.746c-.935-.53-2.12-.603-3.213-.493-1.18.12-2.37.461-3.287.811V2.828zm7.5-.141c.654-.689 1.782-.886 3.112-.752 1.234.124 2.503.523 3.388.893v9.923c-.918-.35-2.107-.692-3.287-.81-1.094-.111-2.278-.039-3.213.492V2.687zM8 1.783C7.015.936 5.587.81 4.287.94c-1.514.153-3.042.672-3.994 1.105A.5.5 0 0 0 0 2.5v11a.5.5 0 0 0 .707.455c.882-.4 2.303-.881 3.68-1.02 1.409-.142 2.59.087 3.223.877a.5.5 0 0 0 .78 0c.633-.79 1.814-1.019 3.222-.877 1.378.139 2.8.62 3.681 1.02A.5.5 0 0 0 16 13.5v-11a.5.5 0 0 0-.293-.455c-.952-.433-2.48-.952-3.994-1.105C10.413.809 8.985.936 8 1.783z"/>
                                        </svg>
                                        <span><?php echo escape($application['major']); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($application['year_of_study']): ?>
                                    <div class="detail-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-calendar" viewBox="0 0 16 16">
                                            <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/>
                                        </svg>
                                        <span>Year <?php echo $application['year_of_study']; ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($application['gpa']): ?>
                                    <div class="detail-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-trophy" viewBox="0 0 16 16">
                                            <path d="M2.5.5A.5.5 0 0 1 3 0h10a.5.5 0 0 1 .5.5c0 .538-.012 1.05-.034 1.536a3 3 0 1 1-1.133 5.89c-.79 1.865-1.878 2.777-2.833 3.011v2.173l1.425.356c.194.048.377.135.537.255L13.3 15.1a.5.5 0 0 1-.3.9H3a.5.5 0 0 1-.3-.9l1.838-1.379c.16-.12.343-.207.537-.255L6.5 13.11v-2.173c-.955-.234-2.043-1.146-2.833-3.012a3 3 0 1 1-1.132-5.89A33.076 33.076 0 0 1 2.5.5zm.099 2.54a2 2 0 0 0 .72 3.935c-.333-1.05-.588-2.346-.72-3.935zm10.083 3.935a2 2 0 0 0 .72-3.935c-.133 1.59-.388 2.885-.72 3.935z"/>
                                        </svg>
                                        <span>GPA: <?php echo $application['gpa']; ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($application['bio']): ?>
                                <div class="bio-section">
                                    <h5>About</h5>
                                    <p><?php echo nl2br(escape($application['bio'])); ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($application['skills']): ?>
                                <div class="skills-section">
                                    <h5>Skills</h5>
                                    <p><?php echo escape($application['skills']); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="application-actions">
                            <div class="document-links">
                                <?php if ($application['resume_path']): ?>
                                    <a href="<?php echo escape($application['resume_path']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-file-pdf" viewBox="0 0 16 16">
                                            <path d="M4 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H4zm0 1h8a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1z"/>
                                            <path d="M4.603 12.087a.81.81 0 0 1-.438-.42c-.195-.388-.13-.776.08-1.102.198-.307.526-.568.897-.787a7.68 7.68 0 0 1 1.482-.645 19.701 19.701 0 0 0 1.062-2.227 7.269 7.269 0 0 1-.43-1.295c-.086-.4-.119-.796-.046-1.136.075-.354.274-.672.65-.823.192-.077.4-.12.602-.077a.7.7 0 0 1 .477.365c.088.164.12.356.127.538.007.187-.012.395-.047.614-.084.51-.27 1.134-.52 1.794a10.954 10.954 0 0 0 .98 1.686 5.753 5.753 0 0 1 1.334.05c.364.065.734.195.96.465.12.144.193.32.2.518.007.192-.047.382-.138.563a1.04 1.04 0 0 1-.354.416.856.856 0 0 1-.51.138c-.331-.014-.654-.196-.933-.417a5.716 5.716 0 0 1-.911-.95 11.642 11.642 0 0 0-1.997.406 11.311 11.311 0 0 1-1.021 1.51c-.29.35-.608.655-.926.787a.793.793 0 0 1-.58.029zm1.379-1.901c-.166.076-.32.156-.459.238-.328.194-.541.383-.647.547-.094.145-.096.25-.04.361.01.022.02.036.026.044a.27.27 0 0 0 .035-.012c.137-.056.355-.235.635-.572a8.18 8.18 0 0 0 .45-.606zm1.64-1.33a12.647 12.647 0 0 1 1.01-.193 11.666 11.666 0 0 1-.51-.858 20.741 20.741 0 0 1-.5 1.05zm2.446.45c.15.163.296.3.435.41.24.19.407.253.498.256a.107.107 0 0 0 .07-.015.307.307 0 0 0 .094-.125.436.436 0 0 0 .059-.2.095.095 0 0 0-.026-.063c-.052-.062-.2-.152-.518-.209a3.876 3.876 0 0 0-.612-.053zM8.078 5.8a6.7 6.7 0 0 0 .2-.828c.031-.188.043-.343.038-.465a.613.613 0 0 0-.032-.198.517.517 0 0 0-.145.04c-.087.035-.158.106-.196.283-.04.192-.03.469.046.822.024.111.054.227.089.346z"/>
                                        </svg>
                                        Resume
                                    </a>
                                <?php endif; ?>
                                
                                <?php if ($application['additional_documents']): ?>
                                    <a href="#" class="btn btn-sm btn-outline-secondary" title="Additional Documents">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-folder" viewBox="0 0 16 16">
                                            <path d="M.54 3.87.5 3a2 2 0 0 1 2-2h3.672a2 2 0 0 1 1.414.586l.828.828A2 2 0 0 0 9.828 3h4.672a2 2 0 0 1 2 2l-.04.87a1.99 1.99 0 0 0-.342-.17L15 5.5a1 1 0 0 0-1-1h-4.672a3 3 0 0 1-2.12-.879l-.83-.828A1 1 0 0 0 5.672 2H2.5a1 1 0 0 0-1 1l.04.87a1.99 1.99 0 0 0-.342.17z"/>
                                            <path d="M0 6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V6zm1 6a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V6a1 1 0 0 0-1-1H2a1 1 0 0 0-1 1v6z"/>
                                        </svg>
                                        Documents
                                    </a>
                                <?php endif; ?>
                            </div>
                            
                            <form method="POST" class="status-actions">
                                <input type="hidden" name="application_id" value="<?php echo $application['id']; ?>">
                                
                                <?php if ($application['status'] === 'submitted'): ?>
                                    <button type="submit" name="action" value="under_review" class="btn btn-sm btn-info">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-eye" viewBox="0 0 16 16">
                                            <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/>
                                            <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/>
                                        </svg>
                                        Review
                                    </button>
                                <?php endif; ?>
                                
                                <?php if (in_array($application['status'], ['submitted', 'under_review'])): ?>
                                    <button type="submit" name="action" value="shortlisted" class="btn btn-sm btn-warning">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-star" viewBox="0 0 16 16">
                                            <path d="M2.866 14.85c-.078.444.36.791.746.593l4.39-2.256 4.389 2.256c.386.198.824-.149.746-.592l-.83-4.73 3.522-3.356c.33-.314.16-.888-.282-.95l-4.898-.696L8.465.792a.513.513 0 0 0-.927 0L5.354 5.12l-4.898.696c-.441.062-.612.636-.283.95l3.523 3.356-.83 4.73zm4.905-2.767-3.686 1.894.694-3.957a.565.565 0 0 0-.163-.505L1.71 6.745l4.052-.576a.525.525 0 0 0 .393-.288L8 2.223l1.847 3.658a.525.525 0 0 0 .393.288l4.052.575-2.906 2.77a.565.565 0 0 0-.163.506l.694 3.957-3.686-1.894a.503.503 0 0 0-.461 0z"/>
                                        </svg>
                                        Shortlist
                                    </button>
                                <?php endif; ?>
                                
                                <?php if (!in_array($application['status'], ['accepted', 'rejected'])): ?>
                                    <button type="submit" name="action" value="accepted" class="btn btn-sm btn-success">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-check" viewBox="0 0 16 16">
                                            <path d="M10.97 4.97a.235.235 0 0 0-.02.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.061L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-1.071-1.05z"/>
                                        </svg>
                                        Accept
                                    </button>
                                    <button type="submit" name="action" value="rejected" class="btn btn-sm btn-danger">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-x" viewBox="0 0 16 16">
                                            <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                                        </svg>
                                        Reject
                                    </button>
                                <?php endif; ?>
                            </form>
                        </div>
                        
                        <?php if ($application['reviewed_date']): ?>
                            <div class="application-footer">
                                <small class="text-muted">
                                    Last updated: <?php echo date('M j, Y g:i A', strtotime($application['reviewed_date'])); ?>
                                </small>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.applications-section {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
}

.applications-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: 25px;
}

.application-card {
    background: white;
    border: 2px solid #e9ecef;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.3s ease;
}

.application-card:hover {
    border-color: #005c89;
    box-shadow: 0 10px 25px rgba(0, 92, 137, 0.1);
}

.application-header {
    padding: 20px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    display: flex;
    justify-content: space-between;
    align-items: start;
    gap: 15px;
}

.student-info h3 {
    margin: 0 0 5px 0;
    color: #333;
    font-size: 1.2rem;
    font-weight: 600;
}

.student-info .email {
    margin: 0;
    color: #666;
    font-size: 0.9rem;
}

.application-status {
    text-align: right;
    flex-shrink: 0;
}

.application-date {
    display: block;
    color: #999;
    font-size: 0.8rem;
    margin-top: 5px;
}

.application-content {
    padding: 20px;
}

.internship-info {
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid #e9ecef;
}

.internship-info h4 {
    margin: 0 0 5px 0;
    color: #005c89;
    font-size: 1rem;
    font-weight: 600;
}

.category-badge {
    background-color: rgba(0, 92, 137, 0.1);
    color: #005c89;
    padding: 3px 8px;
    border-radius: 10px;
    font-size: 0.75rem;
    font-weight: 600;
}

.student-details {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-bottom: 15px;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #666;
    font-size: 0.9rem;
}

.detail-item svg {
    color: #005c89;
    flex-shrink: 0;
}

.bio-section, .skills-section {
    margin-bottom: 15px;
}

.bio-section h5, .skills-section h5 {
    margin: 0 0 8px 0;
    color: #333;
    font-size: 0.95rem;
    font-weight: 600;
}

.bio-section p, .skills-section p {
    margin: 0;
    color: #666;
    font-size: 0.9rem;
    line-height: 1.4;
}

.application-actions {
    padding: 20px;
    background: #f8f9fa;
    border-top: 1px solid #e9ecef;
}

.document-links {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
    flex-wrap: wrap;
}

.status-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.application-footer {
    padding: 15px 20px;
    background: #f1f3f4;
    border-top: 1px solid #e9ecef;
    text-align: center;
}

@media (max-width: 768px) {
    .applications-grid {
        grid-template-columns: 1fr;
    }
    
    .application-header {
        flex-direction: column;
        gap: 10px;
    }
    
    .application-status {
        text-align: left;
    }
    
    .status-actions {
        flex-direction: column;
    }
}
</style>

<?php require_once '../../includes/footer.php'; ?>
