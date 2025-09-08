<?php
// Admin dashboard page for managing users and functionalities.
require_once '../../includes/config.php';
requireAdmin();

// --- Page-specific variables ---
$page_title = 'Admin Login';
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

// --- Include the header ---
require_once '../../includes/header.php';
?>

<div class="admin-panel">
    <div class="admin-analytics">
        <div class="h2c">
            <span>System Overview</span>
            <span class="h-link"><a href="<?php echo $pages_path . '/admin/analytics.php'; ?>">View Analytics</a></span>
        </div>
        <div class="analytics-container">
            <div class="dash-overview-grid">
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
        </div>
    </div>

    <div class="admin-tasks">
        <h2>Administrative Tasks</h2>
        <table border="0" colspan="4" cellspacing="10" width="100%" class="admin-task-table">
            <tr>
                <th>Students</th>
                <th>Applications</th>
                <th>Companies</th>
                <th>Internships</th>
            </tr>
            <tr align="center" class="table-row-rg">
                <td><a href=<?php echo $tasks_path . '/students.php';?>>Manage Students</a></td>
                <td><a href=<?php echo $tasks_path . '/applications.php';?>>Manage Applications</a></td>
                <td><a href=<?php echo $tasks_path . '/companies.php';?>>Manage Companies</a></td>
                <td><a href=<?php echo $tasks_path . '/internships.php';?>>Manage Internships</a></td>
            </tr>
            <tr align="center" class="table-row-rg">
                <td><a href=<?php echo $tasks_path . '/student_profiles.php';?>>Manage Student Profiles</a></td>
                <td></td>
                <td><a href=<?php echo $tasks_path . '/company_profiles.php';?>>Manage Company Profiles</a></td>
                <td></td>
            </tr>
        </table>
    </div>
    
    <div class="admin-logs">
        <div class="h2c">
            <span>Recent System Logs</span>
            <span class="h-link"><a href="<?php echo $pages_path . '/admin/system_logs.php'; ?>">View all logs</a></span>
        </div>
        <table border="0" colspan="4" cellspacing="10" width="100%">
            <tr>
                <th>User ID</th>
                <th>Action</th>
                <th>Details</th>
            </tr>
            <?php
            $stmt = $db->query("SELECT * FROM system_logs ORDER BY created_at DESC LIMIT 5");
            while ($log = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "<tr align='center' width='90%' class='admin-task-table'>
                        <td>{$log['user_id']}</td>
                        <td>{$log['action']}</td>
                        <td>{$log['details']}</td>
                    </tr>";
            }
            ?>
        </table>
    </div>
</div>

<?php
// --- Include the footer ---
require_once '../../includes/footer.php';
?>