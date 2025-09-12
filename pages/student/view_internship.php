<?php
require_once '../../includes/config.php';
requireLogin();

if ($_SESSION['role'] !== 'student') {
    logActivity('Unauthorized Access Attempt', 'User tried to access internship detail view');
    header('Location: ../../pages/error.php?error_message=403 - Access denied');
    exit;
}

$db = getDB();
$user_id = $_SESSION['user_id'];
$internship_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$internship_id) {
    header('Location: find_internships.php?error=Invalid internship ID');
    exit;
}

// Get detailed internship information
$internship_query = "SELECT i.*, cp.company_name, cp.company_description, cp.company_website, 
                            cp.address, cp.phone_number, cp.industry_type, cp.verified,
                            ic.name as category_name, ic.description as category_description,
                            COUNT(DISTINCT a.id) as application_count,
                            DATEDIFF(i.application_deadline, NOW()) as days_left
                     FROM internships i 
                     JOIN company_profiles cp ON i.company_id = cp.id
                     LEFT JOIN internship_categories ic ON i.category_id = ic.id
                     LEFT JOIN applications a ON i.id = a.internship_id
                     WHERE i.id = ? AND i.status = 'published'
                     GROUP BY i.id";

$stmt = $db->prepare($internship_query);
$stmt->execute([$internship_id]);
$internship = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$internship) {
    header('Location: find_internships.php?error=Internship not found');
    exit;
}

// Check if user has already applied
$applied_query = "SELECT status, application_date FROM applications WHERE internship_id = ? AND student_id = ?";
$stmt = $db->prepare($applied_query);
$stmt->execute([$internship_id, $user_id]);
$user_application = $stmt->fetch(PDO::FETCH_ASSOC);

// Get similar internships
$similar_query = "SELECT i.*, cp.company_name, COUNT(DISTINCT a.id) as application_count
                  FROM internships i 
                  JOIN company_profiles cp ON i.company_id = cp.id
                  LEFT JOIN applications a ON i.id = a.internship_id
                  WHERE i.category_id = ? AND i.id != ? AND i.status = 'published' 
                        AND i.application_deadline > NOW()
                  GROUP BY i.id
                  ORDER BY i.created_at DESC
                  LIMIT 3";
$stmt = $db->prepare($similar_query);
$stmt->execute([$internship['category_id'], $internship_id]);
$similar_internships = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = $internship['title'] . ' - ' . $internship['company_name'];

require_once '../../includes/header.php';
?>

<div class="profile-container">
    <!-- Breadcrumb -->
    <div class="breadcrumb-nav">
        <a href="find_internships.php" class="breadcrumb-link">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5a.5.5 0 0 0 .5-.5z"/>
            </svg>
            Back to Search
        </a>
        <span class="breadcrumb-separator">/</span>
        <span class="breadcrumb-current"><?php echo escape($internship['title']); ?></span>
    </div>

    <!-- Main Internship Details -->
    <div class="card">
        <div class="card-body">
            <div class="internship-header">
                <div class="header-content">
                    <div class="company-logo">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-building" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M14.763.075A.5.5 0 0 1 15 .5v15a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5V14h-1v1.5a.5.5 0 0 1-.5.5h-9a.5.5 0 0 1-.5-.5V10a.5.5 0 0 1 .342-.474L6 7.64V4.5a.5.5 0 0 1 .276-.447l8-4a.5.5 0 0 1 .487.022ZM6 8.694 1 10.36V15h5V8.694ZM7 15h2v-1.5a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 .5.5V15h2V1.309l-7 3.5V15Z"/>
                        </svg>
                    </div>
                    <div class="header-info">
                        <h1><?php echo escape($internship['title']); ?></h1>
                        <div class="company-info">
                            <h2><?php echo escape($internship['company_name']); ?></h2>
                            <?php if ($internship['verified']): ?>
                                <span class="verified-badge">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-check-circle-fill" viewBox="0 0 16 16">
                                        <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.061L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                                    </svg>
                                    Verified Company
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="header-meta">
                            <span><?php echo escape($internship['industry_type']); ?></span>
                            <span>•</span>
                            <span><?php echo escape($internship['category_name']); ?></span>
                        </div>
                    </div>
                </div>

                <div class="header-actions">
                    <?php if ($user_application): ?>
                        <div class="application-status">
                            <span class="status-badge status-<?php echo $user_application['status']; ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check-circle" viewBox="0 0 16 16">
                                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                    <path d="M10.97 4.97a.235.235 0 0 0-.02.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.061L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-1.071-1.05z"/>
                                </svg>
                                Application <?php echo ucfirst(str_replace('_', ' ', $user_application['status'])); ?>
                            </span>
                            <small class="text-muted">Applied on <?php echo date('M j, Y', strtotime($user_application['application_date'])); ?></small>
                        </div>
                        <a href="my_applications.php" class="btn btn-outline-primary">
                            View My Application
                        </a>
                    <?php else: ?>
                        <?php if ($internship['days_left'] > 0): ?>
                            <div class="deadline-warning">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-clock" viewBox="0 0 16 16">
                                    <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/>
                                    <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/>
                                </svg>
                                <?php echo $internship['days_left']; ?> days left to apply
                            </div>
                            <a href="apply_vacancy.php?id=<?php echo $internship_id; ?>" class="btn btn-primary btn-lg">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-file-earmark-plus" viewBox="0 0 16 16">
                                    <path d="M8 6.5a.5.5 0 0 1 .5.5v1.5H10a.5.5 0 0 1 0 1H8.5V11a.5.5 0 0 1-1 0V9.5H6a.5.5 0 0 1 0-1h1.5V7a.5.5 0 0 1 .5-.5z"/>
                                    <path d="M14 4.5V14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h5.5L14 4.5zm-3 0A1.5 1.5 0 0 1 9.5 3V1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V4.5h-2z"/>
                                </svg>
                                Apply Now
                            </a>
                        <?php else: ?>
                            <div class="deadline-expired">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x-circle" viewBox="0 0 16 16">
                                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                    <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                                </svg>
                                Application Deadline Passed
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Key Details Grid -->
            <div class="details-grid">
                <div class="detail-card">
                    <div class="detail-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-geo-alt" viewBox="0 0 16 16">
                            <path d="M12.166 8.94c-.524 1.062-1.234 2.12-1.96 3.07A31.493 31.493 0 0 1 8 14.58a31.481 31.481 0 0 1-2.206-2.57c-.726-.95-1.436-2.008-1.96-3.07C3.304 7.867 3 6.862 3 6a5 5 0 0 1 10 0c0 .862-.305 1.867-.834 2.94zM8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10z"/>
                            <path d="M8 8a2 2 0 1 1 0-4 2 2 0 0 1 0 4zm0 1a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
                        </svg>
                    </div>
                    <div class="detail-content">
                        <h3>Location</h3>
                        <p><?php echo escape($internship['location']); ?></p>
                        <?php if ($internship['remote_option']): ?>
                            <span class="badge remote">Remote Work Available</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="detail-card">
                    <div class="detail-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-clock" viewBox="0 0 16 16">
                            <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/>
                            <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/>
                        </svg>
                    </div>
                    <div class="detail-content">
                        <h3>Duration</h3>
                        <p><?php echo $internship['duration_months']; ?> months</p>
                        <?php if ($internship['start_date']): ?>
                            <small>Starts <?php echo date('M j, Y', strtotime($internship['start_date'])); ?></small>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($internship['salary'] > 0): ?>
                <div class="detail-card">
                    <div class="detail-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-currency-dollar" viewBox="0 0 16 16">
                            <path d="M4 10.781c.148 1.667 1.513 2.85 3.591 3.003V15h1.043v-1.216c2.27-.179 3.678-1.438 3.678-3.3 0-1.59-.947-2.51-2.956-3.028l-.722-.187V3.467c1.122.11 1.879.714 2.07 1.616h1.47c-.166-1.6-1.54-2.748-3.54-2.875V1H7.591v1.233c-1.939.23-3.27 1.472-3.27 3.156 0 1.454.966 2.483 2.661 2.917l.61.162v4.031c-1.149-.17-1.94-.8-2.131-1.718H4zm3.391-3.836c-1.043-.263-1.6-.825-1.6-1.616 0-.944.704-1.641 1.8-1.828v3.495l-.2-.05zm1.591 1.872c1.287.323 1.852.859 1.852 1.769 0 1.097-.826 1.828-2.2 1.939V8.73l.348.086z"/>
                        </svg>
                    </div>
                    <div class="detail-content">
                        <h3>Salary</h3>
                        <p>LKR <?php echo number_format($internship['salary']); ?>/month</p>
                    </div>
                </div>
                <?php endif; ?>

                <div class="detail-card">
                    <div class="detail-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-bar-chart-steps" viewBox="0 0 16 16">
                            <path d="M.5 0a.5.5 0 0 1 .5.5v15a.5.5 0 0 1-1 0V.5A.5.5 0 0 1 .5 0zM2 1.5a.5.5 0 0 1 .5-.5h4a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-4a.5.5 0 0 1-.5-.5v-1zm2 4a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-7a.5.5 0 0 1-.5-.5v-1zm2 4a.5.5 0 0 1 .5-.5h6a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-6a.5.5 0 0 1-.5-.5v-1z"/>
                        </svg>
                    </div>
                    <div class="detail-content">
                        <h3>Experience Level</h3>
                        <p><?php echo ucfirst($internship['experience_level']); ?></p>
                    </div>
                </div>

                <div class="detail-card">
                    <div class="detail-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-calendar-check" viewBox="0 0 16 16">
                            <path d="M10.854 7.146a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L7.5 9.793l2.646-2.647a.5.5 0 0 1 .708 0z"/>
                            <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/>
                        </svg>
                    </div>
                    <div class="detail-content">
                        <h3>Application Deadline</h3>
                        <p><?php echo date('M j, Y', strtotime($internship['application_deadline'])); ?></p>
                        <?php if ($internship['days_left'] > 0): ?>
                            <small class="deadline-countdown"><?php echo $internship['days_left']; ?> days left</small>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="detail-card">
                    <div class="detail-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-people" viewBox="0 0 16 16">
                            <path d="M15 14s1 0 1-1-1-4-5-4-5 3-5 4 1 1 1 1h8Zm-7.978-1A.261.261 0 0 1 7 12.996c.001-.264.167-1.03.76-1.72C8.312 10.629 9.282 10 11 10c1.717 0 2.687.63 3.24 1.276.593.69.758 1.457.76 1.72l-.008.002A.274.274 0 0 1 15 13H7.022ZM11 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm3-2a3 3 0 1 1-6 0 3 3 0 0 1 6 0ZM6.936 9.28a5.88 5.88 0 0 0-1.23-.247A7.35 7.35 0 0 0 5 9c-4 0-5 3-5 4 0 .667.333 1 1 1h4.216A2.238 2.238 0 0 1 5 13c0-1.01.377-2.042 1.09-2.904.243-.294.526-.569.846-.816ZM4.92 10A5.493 5.493 0 0 0 4 13H1c0-.26.164-1.03.76-1.724.545-.636 1.492-1.256 3.16-1.275ZM1.5 5.5a3 3 0 1 1 6 0 3 3 0 0 1-6 0Zm3-2a2 2 0 1 0 0 4 2 2 0 0 0 0-4Z"/>
                        </svg>
                    </div>
                    <div class="detail-content">
                        <h3>Applications</h3>
                        <p><?php echo $internship['application_count']; ?> applicants</p>
                        <?php if ($internship['max_applicants']): ?>
                            <small>Max: <?php echo $internship['max_applicants']; ?> positions</small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Description Sections -->
    <div class="content-grid">
        <!-- Description -->
        <div class="card">
            <div class="card-header">
                <h3>
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-file-text" viewBox="0 0 16 16">
                        <path d="M5 4a.5.5 0 0 0 0 1h6a.5.5 0 0 0 0-1H5zm-.5 2.5A.5.5 0 0 1 5 6h6a.5.5 0 0 1 0 1H5a.5.5 0 0 1-.5-.5zM5 8a.5.5 0 0 0 0 1h6a.5.5 0 0 0 0-1H5zm0 2a.5.5 0 0 0 0 1h3a.5.5 0 0 0 0-1H5z"/>
                        <path d="M2 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2zm10-1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1z"/>
                    </svg>
                    Job Description
                </h3>
            </div>
            <div class="card-body">
                <div class="description-content">
                    <?php echo nl2br(escape($internship['description'])); ?>
                </div>
            </div>
        </div>

        <!-- Company Info -->
        <div class="card">
            <div class="card-header">
                <h3>
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-building" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M14.763.075A.5.5 0 0 1 15 .5v15a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5V14h-1v1.5a.5.5 0 0 1-.5.5h-9a.5.5 0 0 1-.5-.5V10a.5.5 0 0 1 .342-.474L6 7.64V4.5a.5.5 0 0 1 .276-.447l8-4a.5.5 0 0 1 .487.022ZM6 8.694 1 10.36V15h5V8.694ZM7 15h2v-1.5a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 .5.5V15h2V1.309l-7 3.5V15Z"/>
                    </svg>
                    About the Company
                </h3>
            </div>
            <div class="card-body">
                <div class="company-details">
                    <?php if ($internship['company_description']): ?>
                        <p><?php echo nl2br(escape($internship['company_description'])); ?></p>
                    <?php endif; ?>
                    
                    <div class="company-meta">
                        <?php if ($internship['company_website']): ?>
                            <div class="meta-item">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-globe" viewBox="0 0 16 16">
                                    <path d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8zm7.5-6.923c-.67.204-1.335.82-1.887 1.855A7.97 7.97 0 0 0 5.145 4H7.5V1.077zM4.09 4a9.267 9.267 0 0 1 .64-1.539 6.7 6.7 0 0 1 .597-.933A7.025 7.025 0 0 0 2.255 4H4.09zm-.582 3.5c.03-.877.138-1.718.312-2.5H1.674a6.958 6.958 0 0 0-.656 2.5h2.49zM4.847 5a12.5 12.5 0 0 0-.338 2.5H7.5V5H4.847zM8.5 5v2.5h2.99a12.495 12.495 0 0 0-.337-2.5H8.5zM4.51 8.5a12.5 12.5 0 0 0 .337 2.5H7.5V8.5H4.51zm3.99 0V11h2.653c.187-.765.306-1.608.338-2.5H8.5zM5.145 12c.138.386.295.744.468 1.068.552 1.035 1.218 1.65 1.887 1.855V12H5.145zm.182 2.472a6.696 6.696 0 0 1-.597-.933A9.268 9.268 0 0 1 4.09 12H2.255a7.024 7.024 0 0 0 3.072 2.472zM3.82 11a13.652 13.652 0 0 1-.312-2.5h-2.49c.062.89.291 1.733.656 2.5H3.82zm6.853 3.472A7.024 7.024 0 0 0 13.745 12H11.91a9.27 9.27 0 0 1-.64 1.539 6.688 6.688 0 0 1-.597.933zM8.5 12v2.923c.67-.204 1.335-.82 1.887-1.855.173-.324.33-.682.468-1.068H8.5zm3.68-1h2.146c.365-.767.594-1.61.656-2.5h-2.49a13.65 13.65 0 0 1-.312 2.5zm2.802-3.5a6.959 6.959 0 0 0-.656-2.5H12.18c.174.782.282 1.623.312 2.5h2.49zM11.27 2.461c.247.464.462.98.64 1.539h1.835a7.024 7.024 0 0 0-3.072-2.472c.218.284.418.598.597.933zM10.855 4a7.966 7.966 0 0 0-.468-1.068C9.835 1.897 9.17 1.282 8.5 1.077V4h2.355z"/>
                                </svg>
                                <a href="<?php echo escape($internship['company_website']); ?>" target="_blank">
                                    <?php echo escape($internship['company_website']); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($internship['address']): ?>
                            <div class="meta-item">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-geo-alt" viewBox="0 0 16 16">
                                    <path d="M12.166 8.94c-.524 1.062-1.234 2.12-1.96 3.07A31.493 31.493 0 0 1 8 14.58a31.481 31.481 0 0 1-2.206-2.57c-.726-.95-1.436-2.008-1.96-3.07C3.304 7.867 3 6.862 3 6a5 5 0 0 1 10 0c0 .862-.305 1.867-.834 2.94zM8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10z"/>
                                    <path d="M8 8a2 2 0 1 1 0-4 2 2 0 0 1 0 4zm0 1a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
                                </svg>
                                <?php echo escape($internship['address']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Responsibilities and Requirements -->
    <?php if ($internship['responsibilities'] || $internship['requirements']): ?>
        <div class="content-grid">
            <?php if ($internship['responsibilities']): ?>
                <div class="card">
                    <div class="card-header">
                        <h3>
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-list-check" viewBox="0 0 16 16">
                                <path fill-rule="evenodd" d="M7 2.5a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-7a.5.5 0 0 1-.5-.5v-1zM2 1a2 2 0 0 0-2 2v2a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2H2zm0 8a2 2 0 0 0-2 2v2a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2v-2a2 2 0 0 0-2-2H2zm.854-3.646a.5.5 0 0 1-.708-.708l1-1a.5.5 0 0 1 .708 0l.646.647.646-.647a.5.5 0 0 1 .708.708l-1 1a.5.5 0 0 1-.708 0l-.646-.647-.646.647zm0 8a.5.5 0 0 1-.708-.708l1-1a.5.5 0 0 1 .708 0l.646.647.646-.647a.5.5 0 0 1 .708.708l-1 1a.5.5 0 0 1-.708 0l-.646-.647-.646.647zM7 10.5a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-7a.5.5 0 0 1-.5-.5v-1z"/>
                            </svg>
                            Key Responsibilities
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="responsibilities-content">
                            <?php echo nl2br(escape($internship['responsibilities'])); ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($internship['requirements']): ?>
                <div class="card">
                    <div class="card-header">
                        <h3>
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-check2-square" viewBox="0 0 16 16">
                                <path d="M3 14.5A1.5 1.5 0 0 1 1.5 13V3A1.5 1.5 0 0 1 3 1.5h8a.5.5 0 0 1 0 1H3a.5.5 0 0 0-.5.5v10a.5.5 0 0 0 .5.5h10a.5.5 0 0 0 .5-.5V8a.5.5 0 0 1 1 0v5a1.5 1.5 0 0 1-1.5 1.5H3z"/>
                                <path d="m8.354 10.354 7-7a.5.5 0 0 0-.708-.708L8 9.293 5.354 6.646a.5.5 0 1 0-.708.708l3 3a.5.5 0 0 0 .708 0z"/>
                            </svg>
                            Requirements & Qualifications
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="requirements-content">
                            <?php echo nl2br(escape($internship['requirements'])); ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Similar Internships -->
    <?php if (!empty($similar_internships)): ?>
        <div class="card">
            <div class="card-header">
                <h3>
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-collection" viewBox="0 0 16 16">
                        <path d="M2.5 3.5a.5.5 0 0 1 0-1h11a.5.5 0 0 1 0 1h-11zm2-2a.5.5 0 0 1 0-1h7a.5.5 0 0 1 0 1h-7zM0 13a1.5 1.5 0 0 0 1.5 1.5h13A1.5 1.5 0 0 0 16 13V6a1.5 1.5 0 0 0-1.5-1.5h-13A1.5 1.5 0 0 0 0 6v7zm1.5.5A.5.5 0 0 1 1 13V6a.5.5 0 0 1 .5-.5h13a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-.5.5h-13z"/>
                    </svg>
                    Similar Internships
                </h3>
            </div>
            <div class="card-body">
                <div class="similar-internships">
                    <?php foreach ($similar_internships as $similar): ?>
                        <div class="similar-card">
                            <h4><a href="view_internship.php?id=<?php echo $similar['id']; ?>"><?php echo escape($similar['title']); ?></a></h4>
                            <p class="company"><?php echo escape($similar['company_name']); ?></p>
                            <div class="similar-meta">
                                <span><?php echo escape($similar['location']); ?></span>
                                <span>•</span>
                                <span><?php echo $similar['duration_months']; ?> months</span>
                                <span>•</span>
                                <span><?php echo $similar['application_count']; ?> applicants</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.breadcrumb-nav {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
    font-size: 0.9rem;
}

.breadcrumb-link {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #007bff;
    text-decoration: none;
}

.breadcrumb-link:hover {
    text-decoration: underline;
}

.breadcrumb-separator {
    color: #666;
}

.breadcrumb-current {
    color: #666;
    font-weight: 500;
}

.internship-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #eee;
}

.header-content {
    display: flex;
    gap: 1rem;
    flex: 1;
}

.company-logo {
    background: #f8f9fa;
    border-radius: 0.5rem;
    padding: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

.header-info h1 {
    margin: 0 0 0.5rem 0;
    font-size: 1.75rem;
    color: #333;
}

.company-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.5rem;
}

.company-info h2 {
    margin: 0;
    font-size: 1.25rem;
    color: #666;
}

.verified-badge {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    background: #d4edda;
    color: #155724;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.8rem;
    font-weight: 500;
}

.header-meta {
    color: #666;
    font-size: 0.9rem;
}

.header-actions {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.75rem;
}

.application-status {
    text-align: right;
}

.status-badge {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    font-weight: 500;
    margin-bottom: 0.25rem;
}

.status-submitted { background: #17a2b8; color: white; }
.status-under_review { background: #6f42c1; color: white; }
.status-accepted { background: #28a745; color: white; }
.status-rejected { background: #dc3545; color: white; }

.deadline-warning, .deadline-expired {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
    margin-bottom: 0.75rem;
}

.deadline-warning {
    color: #856404;
}

.deadline-expired {
    color: #721c24;
}

.details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.detail-card {
    display: flex;
    gap: 1rem;
    padding: 1rem;
    border: 1px solid #e9ecef;
    border-radius: 0.5rem;
    background: #f8f9fa;
}

.detail-icon {
    color: #007bff;
    flex-shrink: 0;
}

.detail-content h3 {
    margin: 0 0 0.25rem 0;
    font-size: 0.9rem;
    font-weight: 600;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.detail-content p {
    margin: 0;
    font-size: 1rem;
    font-weight: 500;
    color: #333;
}

.detail-content small {
    font-size: 0.8rem;
    color: #666;
}

.badge.remote {
    background: #e3f2fd;
    color: #1976d2;
    padding: 0.2rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    margin-top: 0.25rem;
    display: inline-block;
}

.deadline-countdown {
    color: #dc3545;
    font-weight: 500;
}

.content-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.description-content, .responsibilities-content, .requirements-content {
    line-height: 1.6;
    color: #333;
}

.company-details {
    line-height: 1.6;
}

.company-meta {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #eee;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
    color: #666;
}

.meta-item a {
    color: #007bff;
    text-decoration: none;
}

.meta-item a:hover {
    text-decoration: underline;
}

.similar-internships {
    display: grid;
    gap: 1rem;
}

.similar-card {
    padding: 1rem;
    border: 1px solid #e9ecef;
    border-radius: 0.375rem;
    transition: all 0.2s;
}

.similar-card:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.similar-card h4 {
    margin: 0 0 0.25rem 0;
    font-size: 1rem;
}

.similar-card h4 a {
    color: #333;
    text-decoration: none;
}

.similar-card h4 a:hover {
    color: #007bff;
}

.similar-card .company {
    margin: 0 0 0.5rem 0;
    color: #666;
    font-size: 0.9rem;
}

.similar-meta {
    font-size: 0.8rem;
    color: #666;
}

@media (max-width: 768px) {
    .internship-header {
        flex-direction: column;
        gap: 1rem;
    }
    
    .header-actions {
        align-items: stretch;
        width: 100%;
    }
    
    .details-grid {
        grid-template-columns: 1fr;
    }
    
    .content-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php require_once '../../includes/footer.php'; ?>
