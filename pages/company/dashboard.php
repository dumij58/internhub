<?php
require_once '../../includes/config.php';
requireLogin();
$role =  $_SESSION['role'];
if ($role !== 'company') {
    logActivity('Unauthorized Access Attempt', 'User changed the url from "' . $role . '" to "company".');
    http_response_code(401);
    exit;
}

// --- Page-specific variables ---
$db = getDB();
$user_id = $_SESSION['user_id'];
global $pages_path;

// Fetch company profile data
$profile_query = "SELECT cp.*, u.username, u.email 
                  FROM company_profiles cp 
                  JOIN users u ON cp.user_id = u.user_id 
                  WHERE cp.user_id = ?";
$stmt = $db->prepare($profile_query);
$stmt->execute([$user_id]);
$company_profile = $stmt->fetch(PDO::FETCH_ASSOC);

$company_name = $company_profile['company_name'] ?? 'Company';
$page_title = $company_name . ' Dashboard';

// Fetch company statistics
$stats_query = "SELECT 
    COUNT(CASE WHEN status = 'published' THEN 1 END) as active_internships,
    COUNT(CASE WHEN status = 'draft' THEN 1 END) as draft_internships,
    COUNT(CASE WHEN status = 'closed' THEN 1 END) as closed_internships,
    COUNT(*) as total_internships
    FROM internships 
    WHERE company_id = ?";
$stmt = $db->prepare($stats_query);
$stmt->execute([$company_profile['id']]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch total applications
$app_query = "SELECT COUNT(*) as total_applications
              FROM applications a
              JOIN internships i ON a.internship_id = i.id
              WHERE i.company_id = ?";
$stmt = $db->prepare($app_query);
$stmt->execute([$company_profile['id']]);
$app_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch recent applications
$recent_apps_query = "SELECT a.*, i.title as internship_title, sp.first_name, sp.last_name, u.email,
                             a.application_date, a.status
                      FROM applications a
                      JOIN internships i ON a.internship_id = i.id
                      JOIN users u ON a.student_id = u.user_id
                      LEFT JOIN student_profiles sp ON u.user_id = sp.user_id
                      WHERE i.company_id = ?
                      ORDER BY a.application_date DESC
                      LIMIT 5";
$stmt = $db->prepare($recent_apps_query);
$stmt->execute([$company_profile['id']]);
$recent_applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate profile completion percentage
$completion_fields = [
    'company_name' => $company_profile['company_name'],
    'industry_type' => $company_profile['industry_type'],
    'company_website' => $company_profile['company_website'],
    'company_description' => $company_profile['company_description'],
    'address' => $company_profile['address'],
    'phone_number' => $company_profile['phone_number']
];

$completed_fields = 0;
foreach ($completion_fields as $field => $value) {
    if (!empty($value)) {
        $completed_fields++;
    }
}
$completion_percentage = round(($completed_fields / count($completion_fields)) * 100);

// --- Include the header ---
require_once '../../includes/header.php';
?>

<div class="profile-container">
    <!-- Profile Header -->
    <div class="card">
        <div class="card-body">
            <div class="profile-header">
                <!-- Company Logo -->
                <div class="company-logo">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-building" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M14.763.075A.5.5 0 0 1 15 .5v15a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5V14h-1v1.5a.5.5 0 0 1-.5.5h-9a.5.5 0 0 1-.5-.5V10a.5.5 0 0 1 .342-.474L6 7.64V4.5a.5.5 0 0 1 .276-.447l8-4a.5.5 0 0 1 .487.022ZM6 8.694 1 10.36V15h5V8.694ZM7 15h2v-1.5a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 .5.5V15h2V1.309l-7 3.5V15Z"/>
                        <path d="M2 11h1v1H2v-1Zm2 0h1v1H4v-1Zm-2 2h1v1H2v-1Zm2 0h1v1H4v-1Zm4-4h1v1H8V9Zm2 0h1v1h-1V9Zm-2 2h1v1H8v-1Zm2 0h1v1h-1v-1Zm2-2h1v1h-1V9Zm0 2h1v1h-1v-1ZM8 7h1v1H8V7Zm2 0h1v1h-1V7Zm2 0h1v1h-1V7ZM8 5h1v1H8V5Zm2 0h1v1h-1V5Zm2 0h1v1h-1V5Zm0-2h1v1h-1V3Z"/>
                    </svg>
                </div>
                <div class="company-info">
                    <!-- Company Info -->
                    <h1><?php echo escape($company_profile['company_name'] ?? 'Company Name'); ?></h1>
                    <div class="company-meta">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-diagram-3" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M6 3.5A1.5 1.5 0 0 1 7.5 2h1A1.5 1.5 0 0 1 10 3.5v1A1.5 1.5 0 0 1 8.5 6v1H14a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-1 0V8h-5v.5a.5.5 0 0 1-1 0V8h-5v.5a.5.5 0 0 1-1 0v-1A.5.5 0 0 1 2 7h5.5V6A1.5 1.5 0 0 1 6 4.5v-1zM8.5 5a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1zM0 11.5A1.5 1.5 0 0 1 1.5 10h1A1.5 1.5 0 0 1 4 11.5v1A1.5 1.5 0 0 1 2.5 14h-1A1.5 1.5 0 0 1 0 12.5v-1zm1.5-.5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1zm4.5.5A1.5 1.5 0 0 1 7.5 10h1a1.5 1.5 0 0 1 1.5 1.5v1A1.5 1.5 0 0 1 8.5 14h-1A1.5 1.5 0 0 1 6 12.5v-1zm1.5-.5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1zm4.5.5a1.5 1.5 0 0 1 1.5-1.5h1a1.5 1.5 0 0 1 1.5 1.5v1a1.5 1.5 0 0 1-1.5 1.5h-1a1.5 1.5 0 0 1-1.5-1.5v-1zm1.5-.5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1z"/>
                        </svg>
                        <?php echo escape($company_profile['industry_type'] ?? 'Industry not specified'); ?>
                    </div>
                    <?php if (!empty($company_profile['company_website'])): ?>
                        <div class="company-meta">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-globe" viewBox="0 0 16 16">
                                <path d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8zm7.5-6.923c-.67.204-1.335.82-1.887 1.855A7.97 7.97 0 0 0 5.145 4H7.5V1.077zM4.09 4a9.267 9.267 0 0 1 .64-1.539 6.7 6.7 0 0 1 .597-.933A7.025 7.025 0 0 0 2.255 4H4.09zm-.582 3.5c.03-.877.138-1.718.312-2.5H1.674a6.958 6.958 0 0 0-.656 2.5h2.49zM4.847 5a12.5 12.5 0 0 0-.338 2.5H7.5V5H4.847zM8.5 5v2.5h2.99a12.495 12.495 0 0 0-.337-2.5H8.5zM4.51 8.5a12.5 12.5 0 0 0 .337 2.5H7.5V8.5H4.51zm3.99 0V11h2.653c.187-.765.306-1.608.338-2.5H8.5zM5.145 12c.138.386.295.744.468 1.068.552 1.035 1.218 1.65 1.887 1.855V12H5.145zm.182 2.472a6.696 6.696 0 0 1-.597-.933A9.268 9.268 0 0 1 4.09 12H2.255a7.024 7.024 0 0 0 3.072 2.472zM3.82 11a13.652 13.652 0 0 1-.312-2.5h-2.49c.062.89.291 1.733.656 2.5H3.82zm6.853 3.472A7.024 7.024 0 0 0 13.745 12H11.91a9.27 9.27 0 0 1-.64 1.539 6.688 6.688 0 0 1-.597.933zM8.5 12v2.923c.67-.204 1.335-.82 1.887-1.855.173-.324.33-.682.468-1.068H8.5zm3.68-1h2.146c.365-.767.594-1.61.656-2.5h-2.49a13.65 13.65 0 0 1-.312 2.5zm2.802-3.5a6.959 6.959 0 0 0-.656-2.5H12.18c.174.782.282 1.623.312 2.5h2.49zM11.27 2.461c.247.464.462.98.64 1.539h1.835a7.024 7.024 0 0 0-3.072-2.472c.218.284.418.598.597.933zM10.855 4a7.966 7.966 0 0 0-.468-1.068C9.835 1.897 9.17 1.282 8.5 1.077V4h2.355z"/>
                            </svg>
                            <a href="<?php echo escape($company_profile['company_website']); ?>" target="_blank">
                                <?php echo escape($company_profile['company_website']); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                    <?php if ($company_profile['verified']): ?>
                        <span class="badge verified">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" class="bi bi-check-circle" viewBox="0 0 16 16">
                                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                <path d="M10.97 4.97a.235.235 0 0 0-.02.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.061L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-1.071-1.05z"/>
                            </svg>
                            Verified
                        </span>
                    <?php else: ?>
                        <span class="badge pending">
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" class="bi bi-clock" viewBox="0 0 16 16">
                                <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/>
                                <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/>
                            </svg>
                            Pending Verification
                        </span>
                    <?php endif; ?>
                </div>
                <!-- Shows a progress bar for profile completion -->
                <div class="profile-actions">
                    <?php if ($completion_percentage < 100): ?>
                    <div class="completion-indicator">
                        <small>Profile Completion</small>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $completion_percentage; ?>%"></div>
                        </div>
                        <small><?php echo $completion_percentage; ?>% Complete</small>
                    </div>
                    <?php endif; ?>
                    <div class="action-buttons">
                        <!-- Edit Profile Button -->
                        <button class="btn btn-primary btn-sm btn-icon" onclick="openEditModal()">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil" viewBox="0 0 16 16">
                                <path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708L4.5 15.207a.5.5 0 0 1-.146.103l-3 1a.5.5 0 0 1-.595-.595l1-3a.5.5 0 0 1 .103-.146L12.146.146zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207 11.207 2.5zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293L12.793 5.5zM9.854 8.146a.5.5 0 0 1-.708.708L5.5 5.207l-.646.647.646.646a.5.5 0 0 1-.708.708L3.5 5.914a.5.5 0 0 1 0-.708l1-1a.5.5 0 0 1 .708 0L9.854 8.146z"/>
                            </svg>
                            Edit Profile
                        </button>

                        <!-- Change Password Button -->
                        <button class="btn btn-primary btn-sm btn-icon" onclick="openChangePasswordModal()">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-key" viewBox="0 0 16 16">
                                <path d="M0 8a4 4 0 0 1 7.465-2H14a.5.5 0 0 1 .354.146l1.5 1.5a.5.5 0 0 1 0 .708l-1.5 1.5a.5.5 0 0 1-.708 0L13 9.207l-.646.647a.5.5 0 0 1-.708 0L11 9.207l-.646.647a.5.5 0 0 1-.708 0L9 9.207l-.646.647A.5.5 0 0 1 8 10h-.535A4 4 0 0 1 0 8zm4-3a3 3 0 1 0 2.712 4.285A.5.5 0 0 1 7.163 9h.63l.853-.854a.5.5 0 0 1 .708 0l.646.647.646-.647a.5.5 0 0 1 .708 0l.646.647.646-.647a.5.5 0 0 1 .708 0l.646.647.793-.793-1-1h-6.63a.5.5 0 0 1-.451-.285A3 3 0 0 0 4 5z"/>
                                <path d="M4 8a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/>
                            </svg>
                            Change Password
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card primary">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-briefcase" viewBox="0 0 16 16">
                <path d="M6.5 1A1.5 1.5 0 0 0 5 2.5V3H1.5A1.5 1.5 0 0 0 0 4.5v8A1.5 1.5 0 0 0 1.5 14h13a1.5 1.5 0 0 0 1.5-1.5v-8A1.5 1.5 0 0 0 14.5 3H11v-.5A1.5 1.5 0 0 0 9.5 1h-3zm0 1h3a.5.5 0 0 1 .5.5V3H6v-.5a.5.5 0 0 1 .5-.5zm1.886 6.914L15 7.151V12.5a.5.5 0 0 1-.5.5h-13a.5.5 0 0 1-.5-.5V7.15l6.614 1.764a1.5 1.5 0 0 0 .772 0zM1.5 4h13a.5.5 0 0 1 .5.5v1.616L8.129 7.948a.5.5 0 0 1-.258 0L1 6.116V4.5a.5.5 0 0 1 .5-.5z"/>
            </svg>
            <h3><?php echo $stats['active_internships'] ?? 0; ?></h3>
            <p>Active Internships</p>
        </div>
        <div class="stat-card info">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-people" viewBox="0 0 16 16">
                <path d="M15 14s1 0 1-1-1-4-5-4-5 3-5 4 1 1 1 1h8Zm-7.978-1A.261.261 0 0 1 7 12.996c.001-.264.167-1.03.76-1.72C8.312 10.629 9.282 10 11 10c1.717 0 2.687.63 3.24 1.276.593.69.758 1.457.76 1.72l-.008.002A.274.274 0 0 1 15 13H7.022ZM11 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm3-2a3 3 0 1 1-6 0 3 3 0 0 1 6 0ZM6.936 9.28a5.88 5.88 0 0 0-1.23-.247A7.35 7.35 0 0 0 5 9c-4 0-5 3-5 4 0 .667.333 1 1 1h4.216A2.238 2.238 0 0 1 5 13c0-1.01.377-2.042 1.09-2.904.243-.294.526-.569.846-.816ZM4.92 10A5.493 5.493 0 0 0 4 13H1c0-.26.164-1.03.76-1.724.545-.636 1.492-1.256 3.16-1.275ZM1.5 5.5a3 3 0 1 1 6 0 3 3 0 0 1-6 0Zm3-2a2 2 0 1 0 0 4 2 2 0 0 0 0-4Z"/>
            </svg>
            <h3><?php echo $app_stats['total_applications'] ?? 0; ?></h3>
            <p>Total Applications</p>
        </div>
        <div class="stat-card warning">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-file-earmark-text" viewBox="0 0 16 16">
                <path d="M5.5 7a.5.5 0 0 0 0 1h5a.5.5 0 0 0 0-1h-5zM5 9.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5zm0 2a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5z"/>
                <path d="M9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V4.5L9.5 0zm0 1v2A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5z"/>
            </svg>
            <h3><?php echo $stats['draft_internships'] ?? 0; ?></h3>
            <p>Draft Posts</p>
        </div>
        <div class="stat-card success">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-check-circle" viewBox="0 0 16 16">
                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                <path d="M10.97 4.97a.235.235 0 0 0-.02.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.061L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-1.071-1.05z"/>
            </svg>
            <h3><?php echo $stats['closed_internships'] ?? 0; ?></h3>
            <p>Completed</p>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <h2>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-lightning" viewBox="0 0 16 16">
                <path d="M5.52.359A.5.5 0 0 1 6 0h4a.5.5 0 0 1 .474.658L8.694 6H12.5a.5.5 0 0 1 .395.807l-7 9a.5.5 0 0 1-.873-.454L6.823 9.5H3.5a.5.5 0 0 1-.48-.641l2.5-8.5zM6.374 1 4.168 8.5H7.5a.5.5 0 0 1 .478.647L6.78 13.04 11.478 7H8a.5.5 0 0 1-.474-.658L9.306 1H6.374z"/>
            </svg>
            Quick Actions
        </h2>
        <div class="actions-grid">
            <a href="post_internship.php" class="action-card primary">
                <div class="action-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-plus-circle" viewBox="0 0 16 16">
                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                        <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
                    </svg>
                </div>
                <h3>Post New Internship</h3>
                <p>Create a new internship opportunity for students</p>
            </a>
            
            <a href="manage_internships.php" class="action-card info">
                <div class="action-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-list-ul" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M5 11.5a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5zm-3 1a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm0 4a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm0 4a1 1 0 1 0 0-2 1 1 0 0 0 0 2z"/>
                    </svg>
                </div>
                <h3>Manage Internships</h3>
                <p>View, edit, and manage all your internship postings</p>
            </a>
            
            <a href="view_applications.php" class="action-card warning">
                <div class="action-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-people" viewBox="0 0 16 16">
                        <path d="M15 14s1 0 1-1-1-4-5-4-5 3-5 4 1 1 1 1h8Zm-7.978-1A.261.261 0 0 1 7 12.996c.001-.264.167-1.03.76-1.72C8.312 10.629 9.282 10 11 10c1.717 0 2.687.63 3.24 1.276.593.69.758 1.457.76 1.72l-.008.002A.274.274 0 0 1 15 13H7.022ZM11 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm3-2a3 3 0 1 1-6 0 3 3 0 0 1 6 0ZM6.936 9.28a5.88 5.88 0 0 0-1.23-.247A7.35 7.35 0 0 0 5 9c-4 0-5 3-5 4 0 .667.333 1 1 1h4.216A2.238 2.238 0 0 1 5 13c0-1.01.377-2.042 1.09-2.904.243-.294.526-.569.846-.816ZM4.92 10A5.493 5.493 0 0 0 4 13H1c0-.26.164-1.03.76-1.724.545-.636 1.492-1.256 3.16-1.275ZM1.5 5.5a3 3 0 1 1 6 0 3 3 0 0 1-6 0Zm3-2a2 2 0 1 0 0 4 2 2 0 0 0 0-4Z"/>
                    </svg>
                </div>
                <h3>Review Applications</h3>
                <p>Review and manage student applications</p>
            </a>
        </div>
    </div>

    <!-- Company Information and Recent Applications -->
    <div class="content-grid">
        <!-- Company Information -->
        <div class="card">
            <div class="card-header">
                <h3>
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-info-circle" viewBox="0 0 16 16">
                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                        <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533L8.93 6.588zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/>
                    </svg>
                    Company Information
                </h3>
            </div>
            <div class="card-body company-details">
                <?php if (!empty($company_profile['company_description'])): ?>
                    <div>
                        <strong>About Us:</strong>
                        <p><?php echo nl2br(escape($company_profile['company_description'])); ?></p>
                    </div>
                <?php endif; ?>
                
                <p>
                    <strong>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-envelope" viewBox="0 0 16 16">
                            <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4Zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1H2Zm13 2.383-4.708 2.825L15 11.105V5.383Zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741ZM1 11.105l4.708-2.897L1 5.383v5.722Z"/>
                        </svg>
                        Email:
                    </strong>
                    <?php echo escape($company_profile['email']); ?>
                </p>
                
                <?php if (!empty($company_profile['phone_number'])): ?>
                    <p>
                        <strong>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-telephone" viewBox="0 0 16 16">
                                <path d="M3.654 1.328a.678.678 0 0 0-1.015-.063L1.605 2.3c-.483.484-.661 1.169-.45 1.77a17.568 17.568 0 0 0 4.168 6.608 17.569 17.569 0 0 0 6.608 4.168c.601.211 1.286.033 1.77-.45l1.034-1.034a.678.678 0 0 0-.063-1.015l-2.307-1.794a.678.678 0 0 0-.58-.122L9.98 10.97a.68.68 0 0 1-.198-.013c-.59-.18-1.175-.58-1.661-1.066-.486-.486-.886-1.072-1.066-1.662a.68.68 0 0 1-.013-.197l.540-1.805a.678.678 0 0 0-.122-.58L3.654 1.328ZM1.884.511a1.745 1.745 0 0 1 2.612.163L6.29 2.98c.329.423.445.974.315 1.494l-.547 1.829a.678.678 0 0 0 .178.643c.142.142.372.322.624.501.253.179.46.339.624.501a.678.678 0 0 0 .643.178l1.829-.547a1.745 1.745 0 0 1 1.494.315l2.306 1.794c.829.645.905 1.87.163 2.611l-1.034 1.034c-.74.74-1.846 1.065-2.877.702a18.634 18.634 0 0 1-7.01-4.42 18.634 18.634 0 0 1-4.42-7.009c-.362-1.03-.037-2.137.703-2.877L1.885.511Z"/>
                            </svg>
                            Phone:
                        </strong>
                        <?php echo escape($company_profile['phone_number']); ?>
                    </p>
                <?php endif; ?>
                
                <?php if (!empty($company_profile['address'])): ?>
                    <p>
                        <strong>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-geo-alt" viewBox="0 0 16 16">
                                <path d="M12.166 8.94c-.524 1.062-1.234 2.12-1.96 3.07A31.493 31.493 0 0 1 8 14.58a31.481 31.481 0 0 1-2.206-2.57c-.726-.95-1.436-2.008-1.96-3.07C3.304 7.867 3 6.862 3 6a5 5 0 0 1 10 0c0 .862-.305 1.867-.834 2.94zM8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10z"/>
                                <path d="M8 8a2 2 0 1 1 0-4 2 2 0 0 1 0 4zm0 1a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
                            </svg>
                            Address:
                        </strong>
                        <?php echo nl2br(escape($company_profile['address'])); ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Applications -->
        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h3>
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-clock-history" viewBox="0 0 16 16">
                        <path d="M8.515 1.019A7 7 0 0 0 8 1V0a8 8 0 0 1 .589.022l-.074.997zm2.004.45a7.003 7.003 0 0 0-.985-.299l.219-.976c.383.086.76.2 1.126.342l-.36.933zm1.37.71a7.01 7.01 0 0 0-.439-.27l.493-.87a8.025 8.025 0 0 1 .979.654l-.572.774a6.958 6.958 0 0 0-.461-.288zm.28 2.204a7.001 7.001 0 0 0-.197-.539l.893-.450a7.973 7.973 0 0 1 .293.730l-.989.259zm-.976 1.68a7.002 7.002 0 0 0 .064-.534l.998-.064a7.956 7.956 0 0 1-.057.632l-.947-.07zm-.245 1.91a7.012 7.012 0 0 0 .308-.534l.917.4a7.969 7.969 0 0 1-.435.694l-.79-.56zm-2.49 2.209A7.005 7.005 0 0 0 9.09 8.5l.966.25a7.995 7.995 0 0 1-1.004 2.312l-.829-.563zm-1.866.87a7.01 7.01 0 0 0 .463-.293l.523.851a8.025 8.025 0 0 1-.653.434l-.333-.992zm-2.173-.394l-.108-.991a7.956 7.956 0 0 0 .632.057l.07-.947a7.001 7.001 0 0 1-.594-.07zm-.394-1.91c-.081-.37-.166-.742-.202-1.119l.998-.074a7.956 7.956 0 0 0 .168.991l-.954.202zm-.875-3.362c-.128-.467-.195-.943-.195-1.43 0-.487.067-.963.195-1.43l.963.26a7.011 7.011 0 0 0-.168 1.17c0 .405.059.809.168 1.17l-.963.26zm.314-5.398a7.973 7.973 0 0 1 .293-.73l-.893.45a7.001 7.001 0 0 0 .197.539l.403-.259zm.195 12.566L8.5 16l-.5-.866a7.003 7.003 0 0 0 .985.3l-.22.976zm2.82-1.273l.36.933a8.025 8.025 0 0 1-1.126.342c-.383-.086-.76-.2-1.126-.342l.36-.933c.394.14.803.218 1.226.218.423 0 .832-.078 1.226-.218zm.176-2.066c.176-.641.272-1.31.272-1.966s-.096-1.325-.272-1.966l.963-.26c.212.641.309 1.31.309 1.966s-.097 1.325-.309 1.966l-.963-.26zM8 1a7 7 0 1 0 4.95 11.95l.707.707A8 8 0 1 1 8 0v1z"/>
                        <path d="M7.5 3a.5.5 0 0 1 .5.5v5.21l3.248 1.856a.5.5 0 0 1-.496.868l-3.5-2A.5.5 0 0 1 7 9V3.5a.5.5 0 0 1 .5-.5z"/>
                    </svg>
                    Recent Applications
                </h3>
                <a href="view_applications.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($recent_applications)): ?>
                    <div class="empty-state">
                        <div class="icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" class="bi bi-inbox" viewBox="0 0 16 16">
                                <path d="M4.98 4a.5.5 0 0 0-.39.188L1.54 8H6a.5.5 0 0 1 .5.5 1.5 1.5 0 1 0 3 0A.5.5 0 0 1 10 8h4.46l-3.05-3.812A.5.5 0 0 0 11.02 4H4.98zm9.954 5H10.45a2.5 2.5 0 0 1-4.9 0H1.066l.32 2.562a.5.5 0 0 0 .497.438h12.234a.5.5 0 0 0 .496-.438L14.933 9zM3.809 3.563A1.5 1.5 0 0 1 4.981 3h6.038a1.5 1.5 0 0 1 1.172.563l3.7 4.625a.5.5 0 0 1 .105.374l-.39 3.124A1.5 1.5 0 0 1 14.117 13H1.883a1.5 1.5 0 0 1-1.489-1.314l-.39-3.124a.5.5 0 0 1 .106-.374l3.7-4.625z"/>
                            </svg>
                        </div>
                        <p>No applications yet</p>
                        <a href="post_internship.php" class="btn btn-primary btn-sm">Post Your First Internship</a>
                    </div>
                <?php else: ?>
                    <div class="applications-list">
                        <?php foreach ($recent_applications as $app): ?>
                            <div class="application-item">
                                <div class="application-info">
                                    <h4><?php echo escape($app['first_name'] . ' ' . $app['last_name']); ?></h4>
                                    <p><?php echo escape($app['internship_title']); ?></p>
                                    <small><?php echo date('M j, Y', strtotime($app['application_date'])); ?></small>
                                </div>
                                <span class="application-status status-<?php echo $app['status']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $app['status'])); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Edit Profile Modal -->
<div class="modal-overlay" id="editProfileModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Edit Company Profile</h3>
        </div>
        <form id="editProfileForm" method="POST" action="update_profile.php">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label for="company_name" class="form-label">Company Name *</label>
                        <input type="text" class="form-control" id="company_name" name="company_name" 
                               value="<?php echo escape($company_profile['company_name'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="industry_type" class="form-label">Industry Type *</label>
                        <select class="form-control" id="industry_type" name="industry_type" required>
                            <option value="">Select Industry</option>
                            <option value="IT" <?php echo ($company_profile['industry_type'] === 'IT') ? 'selected' : ''; ?>>Information Technology</option>
                            <option value="Finance" <?php echo ($company_profile['industry_type'] === 'Finance') ? 'selected' : ''; ?>>Finance & Banking</option>
                            <option value="Marketing" <?php echo ($company_profile['industry_type'] === 'Marketing') ? 'selected' : ''; ?>>Marketing & Advertising</option>
                            <option value="Engineering" <?php echo ($company_profile['industry_type'] === 'Engineering') ? 'selected' : ''; ?>>Engineering</option>
                            <option value="Healthcare" <?php echo ($company_profile['industry_type'] === 'Healthcare') ? 'selected' : ''; ?>>Healthcare</option>
                            <option value="Education" <?php echo ($company_profile['industry_type'] === 'Education') ? 'selected' : ''; ?>>Education</option>
                            <option value="Retail" <?php echo ($company_profile['industry_type'] === 'Retail') ? 'selected' : ''; ?>>Retail & E-commerce</option>
                            <option value="Manufacturing" <?php echo ($company_profile['industry_type'] === 'Manufacturing') ? 'selected' : ''; ?>>Manufacturing</option>
                            <option value="Consulting" <?php echo ($company_profile['industry_type'] === 'Consulting') ? 'selected' : ''; ?>>Consulting</option>
                            <option value="Non-profit" <?php echo ($company_profile['industry_type'] === 'Non-profit') ? 'selected' : ''; ?>>Non-profit</option>
                            <option value="Other" <?php echo ($company_profile['industry_type'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="company_website" class="form-label">Company Website</label>
                        <input type="url" class="form-control" id="company_website" name="company_website" 
                               value="<?php echo escape($company_profile['company_website'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="phone_number" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" id="phone_number" name="phone_number" 
                               value="<?php echo escape($company_profile['phone_number'] ?? ''); ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="address" class="form-label">Company Address</label>
                    <textarea class="form-control" id="address" name="address" rows="3"><?php echo escape($company_profile['address'] ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="company_description" class="form-label">Company Description</label>
                    <textarea class="form-control" id="company_description" name="company_description" rows="4"><?php echo escape($company_profile['company_description'] ?? ''); ?></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Change Password Modal -->
<div class="modal-overlay" id="changePasswordModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Change Password</h3>
        </div>
        <form id="changePasswordForm" method="POST" action="change_password.php">
            <div class="modal-body">
                <div class="form-group">
                    <label for="current_password" class="form-label">Current Password *</label>
                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                </div>
                <div class="form-group">
                    <label for="new_password" class="form-label">New Password *</label>
                    <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                </div>
                <div class="form-group">
                    <label for="confirm_password" class="form-label">Confirm New Password *</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" onclick="closeChangePasswordModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Change Password</button>
            </div>
        </form>
    </div>
</div>

<script src='<?php echo $assets_path; ?>/js/company_profile.js'></script>

<?php
// --- Include the footer ---
require_once '../../includes/footer.php';
?>