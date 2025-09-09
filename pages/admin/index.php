<?php
// New Admin Dashboard - Clean Implementation with Consistent Design
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

<style>
/* Dashboard Specific Enhancements - Using Existing Design System */
.new-admin-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.new-overview-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.new-admin-section {
    background: white;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.new-tasks-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.new-task-card {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
}

.new-task-card h4 {
    color: #333;
    margin: 0 0 15px 0;
    font-size: 1.1rem;
    font-weight: 600;
}

.new-task-links {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.new-logs-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

.new-logs-table th,
.new-logs-table td {
    padding: 10px;
    text-align: left;
    border-bottom: 1px solid #f0f0f0;
    font-size: 0.9rem;
}

.new-logs-table th {
    background: #f8f9fa;
    font-weight: 600;
    color: #333;
}

.new-logs-table tr:hover {
    background-color: rgba(0, 0, 0, 0.05);
    transition: background-color 0.3s ease-in-out;
}

.no-logs-message {
    text-align: center;
    color: #666;
    padding: 40px;
    font-style: italic;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .new-overview-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
    }
    
    .new-tasks-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }
}

@media (max-width: 480px) {
    .new-overview-grid {
        grid-template-columns: 1fr;
        gap: 10px;
    }
    
    .new-admin-container {
        padding: 10px;
    }
}
</style>

<div class="new-admin-container">
    <!-- Header Section -->
    <div class="h2c">
        <span>System Overview</span>
        <span class="h-link"><a href="<?php echo $pages_path . '/admin/analytics.php'; ?>">View Analytics</a></span>
    </div>

    <!-- Statistics Overview -->
    <div class="new-overview-grid">
        <div class="stat-card analytics">
            <h3>Students</h3>
            <div class="stat-number"><?php echo number_format($totalStudents); ?></div>
            <div class="stat-label"><?php echo $totalUsers > 0 ? round(($totalStudents/$totalUsers)*100, 1) : 0; ?>% of total users</div>
        </div>
        
        <div class="stat-card analytics">
            <h3>Companies</h3>
            <div class="stat-number"><?php echo number_format($totalCompanies); ?></div>
            <div class="stat-label"><?php echo $verifiedCompanies; ?> verified</div>
        </div>
        
        <div class="stat-card analytics">
            <h3>Internships</h3>
            <div class="stat-number"><?php echo number_format($totalInternships); ?></div>
            <div class="stat-label"><?php echo $activeInternships; ?> currently active</div>
        </div>
        
        <div class="stat-card analytics">
            <h3>Applications</h3>
            <div class="stat-number"><?php echo number_format($totalApplications); ?></div>
            <div class="stat-label"><?php echo $acceptedApplications; ?> accepted</div>
        </div>
    </div>

    <!-- Administrative Tasks -->
    <div class="new-admin-section">
        <h2>Administrative Tasks</h2>
        
        <div class="new-tasks-grid">
            <div class="new-task-card">
                <h4>Students</h4>
                <div class="new-task-links">
                    <a href="<?php echo $tasks_path . '/students.php'; ?>" class="h-link" style="text-decoration: none;">
                        <span style="font-size: medium; position: relative; font-weight: 400; color: rgb(0, 92, 137); border: 1px solid rgb(0, 92, 137); border-radius: 10px; padding: 0.1rem 0.5rem; transition: background-color 0.15s ease-in-out, color 0.15s ease-in-out; display: inline-block;">Manage Students</span>
                    </a>
                    <a href="<?php echo $tasks_path . '/student_profiles.php'; ?>" class="h-link" style="text-decoration: none;">
                        <span style="font-size: medium; position: relative; font-weight: 400; color: rgb(0, 92, 137); border: 1px solid rgb(0, 92, 137); border-radius: 10px; padding: 0.1rem 0.5rem; transition: background-color 0.15s ease-in-out, color 0.15s ease-in-out; display: inline-block;">Manage Student Profiles</span>
                    </a>
                </div>
            </div>
            
            <div class="new-task-card">
                <h4>Applications</h4>
                <div class="new-task-links">
                    <a href="<?php echo $tasks_path . '/applications.php'; ?>" class="h-link" style="text-decoration: none;">
                        <span style="font-size: medium; position: relative; font-weight: 400; color: rgb(0, 92, 137); border: 1px solid rgb(0, 92, 137); border-radius: 10px; padding: 0.1rem 0.5rem; transition: background-color 0.15s ease-in-out, color 0.15s ease-in-out; display: inline-block;">Manage Applications</span>
                    </a>
                </div>
            </div>
            
            <div class="new-task-card">
                <h4>Companies</h4>
                <div class="new-task-links">
                    <a href="<?php echo $tasks_path . '/companies.php'; ?>" class="h-link" style="text-decoration: none;">
                        <span style="font-size: medium; position: relative; font-weight: 400; color: rgb(0, 92, 137); border: 1px solid rgb(0, 92, 137); border-radius: 10px; padding: 0.1rem 0.5rem; transition: background-color 0.15s ease-in-out, color 0.15s ease-in-out; display: inline-block;">Manage Companies</span>
                    </a>
                    <a href="<?php echo $tasks_path . '/company_profiles.php'; ?>" class="h-link" style="text-decoration: none;">
                        <span style="font-size: medium; position: relative; font-weight: 400; color: rgb(0, 92, 137); border: 1px solid rgb(0, 92, 137); border-radius: 10px; padding: 0.1rem 0.5rem; transition: background-color 0.15s ease-in-out, color 0.15s ease-in-out; display: inline-block;">Manage Company Profiles</span>
                    </a>
                </div>
            </div>
            
            <div class="new-task-card">
                <h4>Internships</h4>
                <div class="new-task-links">
                    <a href="<?php echo $tasks_path . '/internships.php'; ?>" class="h-link" style="text-decoration: none;">
                        <span style="font-size: medium; position: relative; font-weight: 400; color: rgb(0, 92, 137); border: 1px solid rgb(0, 92, 137); border-radius: 10px; padding: 0.1rem 0.5rem; transition: background-color 0.15s ease-in-out, color 0.15s ease-in-out; display: inline-block;">Manage Internships</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent System Logs -->
    <div class="new-admin-section">
        <div class="h2c">
            <span>Recent System Logs</span>
            <span class="h-link"><a href="<?php echo $pages_path . '/admin/system_logs.php'; ?>">View all logs</a></span>
        </div>
        
        <?php
        $stmt = $db->query("SELECT * FROM system_logs ORDER BY created_at DESC LIMIT 5");
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($logs) > 0): ?>
            <table class="new-logs-table">
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Action</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo escape($log['user_id']); ?></td>
                            <td><?php echo escape($log['action']); ?></td>
                            <td><?php echo escape($log['details'] ?: '-'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-logs-message">
                No recent system activity to display
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// --- Include the footer ---
require_once '../../includes/footer.php';
?>
