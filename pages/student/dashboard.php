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
$student_name = $_SESSION['student_name'] ?? 'Student';
$page_title = $student_name . ' Dashboard';
global $pages_path;

// Fetch student profile data
$profile_query = "SELECT sp.*, u.username, u.email
                  FROM student_profiles sp 
                  JOIN users u ON sp.user_id = u.user_id 
                  WHERE sp.user_id = ?";
$stmt = $db->prepare($profile_query);
$stmt->execute([$user_id]);
$student_profile = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch student statistics
$stats_query = "SELECT 
    COUNT(CASE WHEN status = 'submitted' THEN 1 END) as active_applications,
    COUNT(CASE WHEN status = 'draft' THEN 1 END) as draft_applications,
    COUNT(CASE WHEN status = 'under_review' THEN 1 END) as under_review_applications,
    COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_applications,
    COUNT(CASE WHEN status = 'accepted' THEN 1 END) as accepted_applications,
    COUNT(*) as total_applications
    FROM applications 
    WHERE student_id = ?";
$stmt = $db->prepare($stats_query);
$stmt->execute([$user_id]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch recent applications
$recent_apps_query = "SELECT a.*, i.title as internship_title, sp.first_name, sp.last_name, u.email,
                             a.application_date, a.status, cp.company_name
                      FROM applications a
                      JOIN internships i ON a.internship_id = i.id
                      JOIN company_profiles cp ON i.company_id = cp.id
                      JOIN users u ON a.student_id = u.user_id
                      LEFT JOIN student_profiles sp ON u.user_id = sp.user_id
                      WHERE a.student_id = ?
                      ORDER BY a.application_date DESC
                      LIMIT 5";
$stmt = $db->prepare($recent_apps_query);
$stmt->execute([$user_id]);
$recent_applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate profile completion percentage
$completion_fields = [
    'student_id' => $student_profile['student_id'],
    'first_name' => $student_profile['first_name'],
    'last_name' => $student_profile['last_name'],
    'university' => $student_profile['university'],
    'major' => $student_profile['major'],
    'year_of_study' => $student_profile['year_of_study'],
    'gpa' => $student_profile['gpa'],
    'email' => $student_profile['email'],
    'phone' => $student_profile['phone'],
    'bio' => $student_profile['bio']
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
                <div class="student-photo">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-person-circle" viewBox="0 0 16 16">
                        <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0"/>
                        <path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8m8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1"/>
                    </svg>
                </div>
                <div class="student-info">
                    <h1><?php echo escape($student_profile['first_name'] ?? 'First Name'); ?> <?php echo escape($student_profile['last_name'] ?? 'Last Name'); ?></h1>
                    <div class="student-meta">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-diagram-3" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M6 3.5A1.5 1.5 0 0 1 7.5 2h1A1.5 1.5 0 0 1 10 3.5v1A1.5 1.5 0 0 1 8.5 6v1H14a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-1 0V8h-5v.5a.5.5 0 0 1-1 0V8h-5v.5a.5.5 0 0 1-1 0v-1A.5.5 0 0 1 2 7h5.5V6A1.5 1.5 0 0 1 6 4.5v-1zM8.5 5a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1zM0 11.5A1.5 1.5 0 0 1 1.5 10h1A1.5 1.5 0 0 1 4 11.5v1A1.5 1.5 0 0 1 2.5 14h-1A1.5 1.5 0 0 1 0 12.5v-1zm1.5-.5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1zm4.5.5A1.5 1.5 0 0 1 7.5 10h1a1.5 1.5 0 0 1 1.5 1.5v1A1.5 1.5 0 0 1 8.5 14h-1A1.5 1.5 0 0 1 6 12.5v-1zm1.5-.5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1zm4.5.5a1.5 1.5 0 0 1 1.5-1.5h1a1.5 1.5 0 0 1 1.5 1.5v1a1.5 1.5 0 0 1-1.5 1.5h-1a1.5 1.5 0 0 1-1.5-1.5v-1zm1.5-.5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1z"/>
                        </svg>
                        <?php echo escape($student_profile['major'] ?? 'Major not specified'); ?>
                        <?php if (!empty($student_profile['university'])): ?>
                            • <?php echo escape($student_profile['university']); ?>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($student_profile['year_of_study'])): ?>
                        <div class="student-meta">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-calendar" viewBox="0 0 16 16">
                                <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/>
                            </svg>
                            Year <?php echo escape($student_profile['year_of_study']); ?>
                            <?php if (!empty($student_profile['gpa'])): ?>
                                • GPA: <?php echo escape($student_profile['gpa']); ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
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
                        <button class="btn btn-primary btn-sm btn-icon" onclick="openEditModal()">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil" viewBox="0 0 16 16">
                                <path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708L4.5 15.207a.5.5 0 0 1-.146.103l-3 1a.5.5 0 0 1-.595-.595l1-3a.5.5 0 0 1 .103-.146L12.146.146zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207 11.207 2.5zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293L12.793 5.5zM9.854 8.146a.5.5 0 0 1-.708.708L5.5 5.207l-.646.647.646.646a.5.5 0 0 1-.708.708L3.5 5.914a.5.5 0 0 1 0-.708l1-1a.5.5 0 0 1 .708 0L9.854 8.146z"/>
                            </svg>
                            Edit Profile
                        </button>
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

    <!-- Quick Actions -->
    <div class="stu-quick-actions">
        <div class="action-card highlight">
            <div class="action-content">
                <div class="action-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16">
                        <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                    </svg>
                </div>
                <div>
                    <h4>Find Internships</h4>
                    <p>Discover new opportunities that match your skills and interests</p>
                </div>
            </div>
            <a href="find_internships.php" class="btn btn-primary">
                Search Now
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-right" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z"/>
                </svg>
            </a>
        </div>

        <?php if ($stats['draft_applications'] > 0): ?>
        <div class="action-card warning">
            <div class="action-content">
                <div class="action-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-file-earmark-text" viewBox="0 0 16 16">
                        <path d="M5.5 7a.5.5 0 0 0 0 1h5a.5.5 0 0 0 0-1h-5zM5 9.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5zm0 2a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5z"/>
                        <path d="M9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V4.5L9.5 0zm0 1v2A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5z"/>
                    </svg>
                </div>
                <div>
                    <h4>Complete Draft Applications</h4>
                    <p>You have <?php echo $stats['draft_applications']; ?> draft application<?php echo $stats['draft_applications'] > 1 ? 's' : ''; ?> waiting to be submitted</p>
                </div>
            </div>
            <a href="my_applications.php?status=draft" class="btn btn-warning">
                Continue
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-right" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z"/>
                </svg>
            </a>
        </div>
        <?php endif; ?>

        <div class="action-card secondary">
            <div class="action-content">
                <div class="action-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-file-text" viewBox="0 0 16 16">
                        <path d="M5 4a.5.5 0 0 0 0 1h6a.5.5 0 0 0 0-1H5zm-.5 2.5A.5.5 0 0 1 5 6h6a.5.5 0 0 1 0 1H5a.5.5 0 0 1-.5-.5zM5 8a.5.5 0 0 0 0 1h6a.5.5 0 0 0 0-1H5zm0 2a.5.5 0 0 0 0 1h3a.5.5 0 0 0 0-1H5z"/>
                        <path d="M2 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2zm10-1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1z"/>
                    </svg>
                </div>
                <div>
                    <h4>Manage Applications</h4>
                    <p>View and track all your internship applications in one place</p>
                </div>
            </div>
            <a href="my_applications.php" class="btn btn-outline-primary">
                View All
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-right" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z"/>
                </svg>
            </a>
        </div>
    </div>

    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card primary">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-briefcase" viewBox="0 0 16 16">
                <path d="M6.5 1A1.5 1.5 0 0 0 5 2.5V3H1.5A1.5 1.5 0 0 0 0 4.5v8A1.5 1.5 0 0 0 1.5 14h13a1.5 1.5 0 0 0 1.5-1.5v-8A1.5 1.5 0 0 0 14.5 3H11v-.5A1.5 1.5 0 0 0 9.5 1h-3zm0 1h3a.5.5 0 0 1 .5.5V3H6v-.5a.5.5 0 0 1 .5-.5zm1.886 6.914L15 7.151V12.5a.5.5 0 0 1-.5.5h-13a.5.5 0 0 1-.5-.5V7.15l6.614 1.764a1.5 1.5 0 0 0 .772 0zM1.5 4h13a.5.5 0 0 1 .5.5v1.616L8.129 7.948a.5.5 0 0 1-.258 0L1 6.116V4.5a.5.5 0 0 1 .5-.5z"/>
            </svg>
            <h3><?php echo $stats['active_applications'] ?? 0; ?></h3>
            <p>Submitted Applications</p>
        </div>
        <div class="stat-card info">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-eye" viewBox="0 0 16 16">
                <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/>
                <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/>
            </svg>
            <h3><?php echo $stats['under_review_applications'] ?? 0; ?></h3>
            <p>Under Review</p>
        </div>
        <div class="stat-card warning">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-file-earmark-text" viewBox="0 0 16 16">
                <path d="M5.5 7a.5.5 0 0 0 0 1h5a.5.5 0 0 0 0-1h-5zM5 9.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5zm0 2a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5z"/>
                <path d="M9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V4.5L9.5 0zm0 1v2A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5z"/>
            </svg>
            <h3><?php echo $stats['draft_applications'] ?? 0; ?></h3>
            <p>Draft Applications</p>
        </div>
        <div class="stat-card success">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-check-circle" viewBox="0 0 16 16">
                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                <path d="M10.97 4.97a.235.235 0 0 0-.02.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.061L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-1.071-1.05z"/>
            </svg>
            <h3><?php echo $stats['accepted_applications'] ?? 0; ?></h3>
            <p>Accepted</p>
        </div>
    </div>

    <!-- Student Information and Recent Applications -->
    <div class="content-grid">
        <!-- Student Information -->
        <div class="card">
            <div class="card-header">
                <h3>
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-info-circle" viewBox="0 0 16 16">
                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                        <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533L8.93 6.588zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/>
                    </svg>
                    Student Information
                </h3>
            </div>
            <div class="card-body student-details">
                <?php if (!empty($student_profile['bio'])): ?>
                    <div>
                        <strong>About Me:</strong>
                        <p><?php echo nl2br(escape($student_profile['bio'])); ?></p>
                    </div>
                <?php endif; ?>
                
                <p>
                    <strong>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-envelope" viewBox="0 0 16 16">
                            <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4Zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1H2Zm13 2.383-4.708 2.825L15 11.105V5.383Zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741ZM1 11.105l4.708-2.897L1 5.383v5.722Z"/>
                        </svg>
                        Email:
                    </strong>
                    <?php echo escape($student_profile['email']); ?>
                </p>
                
                <?php if (!empty($student_profile['phone'])): ?>
                    <p>
                        <strong>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-telephone" viewBox="0 0 16 16">
                                <path d="M3.654 1.328a.678.678 0 0 0-1.015-.063L1.605 2.3c-.483.484-.661 1.169-.45 1.77a17.568 17.568 0 0 0 4.168 6.608 17.569 17.569 0 0 0 6.608 4.168c.601.211 1.286.033 1.77-.45l1.034-1.034a.678.678 0 0 0-.063-1.015l-2.307-1.794a.678.678 0 0 0-.58-.122L9.98 10.97a.68.68 0 0 1-.198-.013c-.59-.18-1.175-.58-1.661-1.066-.486-.486-.886-1.072-1.066-1.662a.68.68 0 0 1-.013-.197l.540-1.805a.678.678 0 0 0-.122-.58L3.654 1.328ZM1.884.511a1.745 1.745 0 0 1 2.612.163L6.29 2.98c.329.423.445.974.315 1.494l-.547 1.829a.678.678 0 0 0 .178.643c.142.142.372.322.624.501.253.179.46.339.624.501a.678.678 0 0 0 .643.178l1.829-.547a1.745 1.745 0 0 1 1.494.315l2.306 1.794c.829.645.905 1.87.163 2.611l-1.034 1.034c-.74.74-1.846 1.065-2.877.702a18.634 18.634 0 0 1-7.01-4.42 18.634 18.634 0 0 1-4.42-7.009c-.362-1.03-.037-2.137.703-2.877L1.885.511Z"/>
                            </svg>
                            Phone:
                        </strong>
                        <?php echo escape($student_profile['phone']); ?>
                    </p>
                <?php endif; ?>
                
                <?php if (!empty($student_profile['portfolio_url'])): ?>
                    <p>
                        <strong>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-globe" viewBox="0 0 16 16">
                                <path d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8zm7.5-6.923c-.67.204-1.335.82-1.887 1.855A7.97 7.97 0 0 0 5.145 4H7.5V1.077zM4.09 4a9.267 9.267 0 0 1 .64-1.539 6.7 6.7 0 0 1 .597-.933A7.025 7.025 0 0 0 2.255 4H4.09zm-.582 3.5c.03-.877.138-1.718.312-2.5H1.674a6.958 6.958 0 0 0-.656 2.5h2.49zM4.847 5a12.5 12.5 0 0 0-.338 2.5H7.5V5H4.847zM8.5 5v2.5h2.99a12.495 12.495 0 0 0-.337-2.5H8.5zM4.51 8.5a12.5 12.5 0 0 0 .337 2.5H7.5V8.5H4.51zm3.99 0V11h2.653c.187-.765.306-1.608.338-2.5H8.5zM5.145 12c.138.386.295.744.468 1.068.552 1.035 1.218 1.65 1.887 1.855V12H5.145zm.182 2.472a6.696 6.696 0 0 1-.597-.933A9.268 9.268 0 0 1 4.09 12H2.255a7.024 7.024 0 0 0 3.072 2.472zM3.82 11a13.652 13.652 0 0 1-.312-2.5h-2.49c.062.89.291 1.733.656 2.5H3.82zm6.853 3.472A7.024 7.024 0 0 0 13.745 12H11.91a9.27 9.27 0 0 1-.64 1.539 6.688 6.688 0 0 1-.597.933zM8.5 12v2.923c.67-.204 1.335-.82 1.887-1.855.173-.324.33-.682.468-1.068H8.5zm3.68-1h2.146c.365-.767.594-1.61.656-2.5h-2.49a13.65 13.65 0 0 1-.312 2.5zm2.802-3.5a6.959 6.959 0 0 0-.656-2.5H12.18c.174.782.282 1.623.312 2.5h2.49zM11.27 2.461c.247.464.462.98.64 1.539h1.835a7.024 7.024 0 0 0-3.072-2.472c.218.284.418.598.597.933zM10.855 4a7.966 7.966 0 0 0-.468-1.068C9.835 1.897 9.17 1.282 8.5 1.077V4h2.355z"/>
                            </svg>
                            Portfolio:
                        </strong>
                        <a href="<?php echo escape($student_profile['portfolio_url']); ?>" target="_blank">
                            <?php echo escape($student_profile['portfolio_url']); ?>
                        </a>
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
                <a href="my_applications.php" class="btn btn-sm btn-primary">View All</a>
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
                        <a href="find_internships.php" class="btn btn-primary btn-sm">Find Internships</a>
                    </div>
                <?php else: ?>
                    <div class="applications-list">
                        <?php foreach ($recent_applications as $app): ?>
                            <div class="application-item">
                                <div class="application-info">
                                    <h4><?php echo escape($app['internship_title']); ?></h4>
                                    <p><?php echo escape($app['company_name']); ?></p>
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
            <h3>Edit Student Profile</h3>
        </div>
        <form id="editProfileForm" method="POST" action="update_profile.php">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name" class="form-label">First Name *</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" 
                               value="<?php echo escape($student_profile['first_name'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="last_name" class="form-label">Last Name *</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" 
                               value="<?php echo escape($student_profile['last_name'] ?? ''); ?>" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="student_id" class="form-label">Student ID</label>
                        <input type="text" class="form-control" id="student_id" name="student_id" 
                               value="<?php echo escape($student_profile['student_id'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" id="phone" name="phone" 
                               value="<?php echo escape($student_profile['phone'] ?? ''); ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="university" class="form-label">University</label>
                        <input type="text" class="form-control" id="university" name="university" 
                               value="<?php echo escape($student_profile['university'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="major" class="form-label">Major</label>
                        <input type="text" class="form-control" id="major" name="major" 
                               value="<?php echo escape($student_profile['major'] ?? ''); ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="year_of_study" class="form-label">Year of Study</label>
                        <select class="form-control" id="year_of_study" name="year_of_study">
                            <option value="">Select Year</option>
                            <option value="1" <?php echo ($student_profile['year_of_study'] == 1) ? 'selected' : ''; ?>>1st Year</option>
                            <option value="2" <?php echo ($student_profile['year_of_study'] == 2) ? 'selected' : ''; ?>>2nd Year</option>
                            <option value="3" <?php echo ($student_profile['year_of_study'] == 3) ? 'selected' : ''; ?>>3rd Year</option>
                            <option value="4" <?php echo ($student_profile['year_of_study'] == 4) ? 'selected' : ''; ?>>4th Year</option>
                            <option value="5" <?php echo ($student_profile['year_of_study'] == 5) ? 'selected' : ''; ?>>5th Year</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="gpa" class="form-label">GPA</label>
                        <input type="number" step="0.01" min="0" max="4.0" class="form-control" id="gpa" name="gpa" 
                               value="<?php echo escape($student_profile['gpa'] ?? ''); ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="portfolio_url" class="form-label">Portfolio URL</label>
                    <input type="url" class="form-control" id="portfolio_url" name="portfolio_url" 
                           value="<?php echo escape($student_profile['portfolio_url'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="bio" class="form-label">Bio</label>
                    <textarea class="form-control" id="bio" name="bio" rows="4"><?php echo escape($student_profile['bio'] ?? ''); ?></textarea>
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

<script src='<?php echo $assets_path; ?>/js/student_profile.js'></script>

<?php
// --- Include the footer ---
require_once '../../includes/footer.php';
?>