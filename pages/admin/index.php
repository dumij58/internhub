<?php
// New Admin Dashboard - Clean Implementation
require_once '../../includes/config.php';
requireAdmin();

// --- Page-specific variables ---
$page_title = 'Admin Dashboard';
global $pages_path;
$tasks_path = $pages_path . '/admin/tasks';
$db = getDB();

// Get overview statistics
$totalAdmins = getCount($db, 'users', 'user_type_id = ?', [1]);
$totalUsers = getCount($db, 'users') - $totalAdmins;
$totalStudents = getCount($db, 'users', 'user_type_id = ?', [2]);
$totalCompanies = getCount($db, 'users', 'user_type_id = ?', [3]);
$totalInternships = getCount($db, 'internships');
$activeInternships = getCount($db, 'internships', 'status = ?', ['published']);
$totalApplications = getCount($db, 'applications');
$verifiedCompanies = getCount($db, 'company_profiles', 'verified = ?', [1]);
$acceptedApplications = getCount($db, 'applications', 'status = ?', ['accepted']);

// Calculate success rate
$successRate = $totalApplications > 0 ? round(($acceptedApplications / $totalApplications) * 100, 1) : 0;

// --- Include the header ---
require_once '../../includes/header.php';
?>

<div class="new-dashboard">
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div>
            <h1 class="dashboard-title">Admin Dashboard</h1>
            <p class="dashboard-subtitle">System overview and management tools</p>
        </div>
        <a href="<?php echo $pages_path . '/admin/analytics.php'; ?>" class="view-all-link">
            View Detailed Analytics
        </a>
    </div>

    <!-- Statistics Overview -->
    <div class="stats-overview">
        <div class="stat-box">
            <h3>Total Students</h3>
            <div class="stat-value"><?php echo number_format($totalStudents); ?></div>
            <p class="stat-description">
                <?php echo $totalUsers > 0 ? round(($totalStudents/$totalUsers)*100, 1) : 0; ?>% of total users
            </p>
        </div>
        
        <div class="stat-box">
            <h3>Total Companies</h3>
            <div class="stat-value"><?php echo number_format($totalCompanies); ?></div>
            <p class="stat-description">
                <?php echo $verifiedCompanies; ?> verified companies
            </p>
        </div>
        
        <div class="stat-box">
            <h3>Internships</h3>
            <div class="stat-value"><?php echo number_format($totalInternships); ?></div>
            <p class="stat-description">
                <?php echo $activeInternships; ?> currently active
            </p>
        </div>
        
        <div class="stat-box">
            <h3>Applications</h3>
            <div class="stat-value"><?php echo number_format($totalApplications); ?></div>
            <p class="stat-description">
                <?php echo $acceptedApplications; ?> accepted (<?php echo $successRate; ?>% success rate)
            </p>
        </div>
    </div>

    <!-- Administrative Actions -->
    <div class="dashboard-section">
        <div class="section-header">
            <h2 class="section-title">Administrative Tools</h2>
        </div>
        
        <div class="admin-actions">
            <div class="action-card">
                <h4>System Administration</h4>
                <div class="action-links">
                    <a href="<?php echo $tasks_path . '/admins.php'; ?>" class="action-link">
                        Manage Administrators
                    </a>
                </div>
            </div>
            
            <div class="action-card">
                <h4>Student Management</h4>
                <div class="action-links">
                    <a href="<?php echo $tasks_path . '/students.php'; ?>" class="action-link">
                        Manage Students
                    </a>
                    <a href="<?php echo $tasks_path . '/student_profiles.php'; ?>" class="action-link">
                        Student Profiles
                    </a>
                </div>
            </div>
            
            <div class="action-card">
                <h4>Company Management</h4>
                <div class="action-links">
                    <a href="<?php echo $tasks_path . '/companies.php'; ?>" class="action-link">
                        Manage Companies
                    </a>
                    <a href="<?php echo $tasks_path . '/company_profiles.php'; ?>" class="action-link">
                        Company Profiles
                    </a>
                </div>
            </div>
            
            <div class="action-card">
                <h4>Internship Management</h4>
                <div class="action-links">
                    <a href="<?php echo $tasks_path . '/internships.php'; ?>" class="action-link">
                        Manage Internships
                    </a>
                </div>
            </div>
            
            <div class="action-card">
                <h4>Application Management</h4>
                <div class="action-links">
                    <a href="<?php echo $tasks_path . '/applications.php'; ?>" class="action-link">
                        Manage Applications
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent System Logs -->
    <div class="dashboard-section">
        <div class="section-header">
            <h2 class="section-title">Recent System Activity</h2>
            <a href="<?php echo $pages_path . '/admin/system_logs.php'; ?>" class="view-all-link">
                View All Logs
            </a>
        </div>
        
        <?php
        $stmt = $db->query("SELECT * FROM system_logs ORDER BY created_at DESC LIMIT 8");
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($logs) > 0): ?>
            <table class="logs-table">
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Action</th>
                        <th>Details</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo escape($log['user_id']); ?></td>
                            <td><?php echo escape($log['action']); ?></td>
                            <td><?php echo escape($log['details'] ?: '-'); ?></td>
                            <td><?php echo date('M d, H:i', strtotime($log['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-logs">
                No recent system activity to display
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// --- Include the footer ---
require_once '../../includes/footer.php';
?>
