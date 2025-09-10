<?php
require_once '../../includes/config.php';
requireAdmin();
$page_title = 'System Analytics';
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

// Get application status distribution
$applicationStatusData = getData($db, "
    SELECT status, COUNT(*) as count 
    FROM applications 
    GROUP BY status 
    ORDER BY count DESC
");

// Get internships by status
$internshipStatusData = getData($db, "
    SELECT status, COUNT(*) as count 
    FROM internships 
    GROUP BY status 
    ORDER BY count DESC
");

// Get top universities
$topUniversities = getData($db, "
    SELECT university, COUNT(*) as count 
    FROM student_profiles 
    WHERE university IS NOT NULL AND university != ''
    GROUP BY university 
    ORDER BY count DESC 
    LIMIT 5
");

// Get top majors
$topMajors = getData($db, "
    SELECT major, COUNT(*) as count 
    FROM student_profiles 
    WHERE major IS NOT NULL AND major != ''
    GROUP BY major 
    ORDER BY count DESC 
    LIMIT 5
");

// Get monthly registration trends (last 6 months)
$registrationTrends = getData($db, "
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as count
    FROM users 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month
");

// Get application trends (last 30 days)
$applicationTrends = getData($db, "
    SELECT 
        DATE(application_date) as date,
        COUNT(*) as count
    FROM applications 
    WHERE application_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(application_date)
    ORDER BY date
");

// Get average GPA statistics
$gpaStats = $db->query("
    SELECT 
        AVG(gpa) as avg_gpa,
        MIN(gpa) as min_gpa,
        MAX(gpa) as max_gpa,
        COUNT(gpa) as total_with_gpa
    FROM student_profiles 
    WHERE gpa IS NOT NULL AND gpa > 0
")->fetch(PDO::FETCH_ASSOC);

// Get stipend statistics
$stipendStats = $db->query("
    SELECT 
        AVG(stipend) as avg_stipend,
        MIN(stipend) as min_stipend,
        MAX(stipend) as max_stipend,
        COUNT(stipend) as total_with_stipend
    FROM internships 
    WHERE stipend IS NOT NULL AND stipend > 0
")->fetch(PDO::FETCH_ASSOC);

// Get recent activities
$recentActivities = getData($db, "
    SELECT sl.action, sl.details, sl.created_at, u.username
    FROM system_logs sl
    LEFT JOIN users u ON sl.user_id = u.user_id
    ORDER BY sl.created_at DESC
    LIMIT 10
");

// Success rate calculation
$acceptedApplications = getCount($db, 'applications', 'status = ?', ['accepted']);
$successRate = $totalApplications > 0 ? round(($acceptedApplications / $totalApplications) * 100, 2) : 0;

require_once '../../includes/header.php';
?>

<div class="analytics-container">
    <div class="analytics-header">
        <h1 style="margin: 0; color: #333;">System Analytics Dashboard</h1>
        <div style="color: #666; font-size: 0.9rem;">
            Last Updated: <?php echo date('M d, Y H:i'); ?>
        </div>
    </div>

    <!-- Overview Statistics -->
    <div class="analytics-overview-grid">
        <div class="stat-card analytics">
            <!-- Total Users except admins -->
            <h3>Total Users</h3>
            <div class="stat-number"><?php echo number_format($totalUsers); ?></div>
            <div class="stat-label">Registered accounts</div>
        </div>
        
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
            <h3>Total Internships</h3>
            <div class="stat-number"><?php echo number_format($totalInternships); ?></div>
            <div class="stat-label"><?php echo $activeInternships; ?> currently active</div>
        </div>
        
        <div class="stat-card analytics">
            <h3>Applications</h3>
            <div class="stat-number"><?php echo number_format($totalApplications); ?></div>
            <div class="stat-label"><?php echo $acceptedApplications; ?> accepted</div>
        </div>
        
        <div class="stat-card analytics">
            <h3>Success Rate</h3>
            <div class="stat-number"><?php echo $successRate; ?>%</div>
            <div class="stat-label">Application acceptance rate</div>
        </div>
    </div>

    <!-- Charts Grid -->
    <div class="analytics-grid">
        <!-- Application Status Distribution -->
        <div class="analytics-section">
            <h2 class="chart-title">Application Status Distribution</h2>
            <?php foreach ($applicationStatusData as $status): ?>
                <?php 
                $percentage = $totalApplications > 0 ? ($status['count'] / $totalApplications) * 100 : 0;
                $statusClass = 'status-' . strtolower(str_replace(' ', '_', $status['status']));
                ?>
                <div style="margin: 10px 0;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                        <span class="status-badge <?php echo $statusClass; ?>">
                            <?php echo ucwords(str_replace('_', ' ', $status['status'])); ?>
                        </span>
                        <span><?php echo $status['count']; ?> (<?php echo round($percentage, 1); ?>%)</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Internship Status Distribution -->
        <div class="analytics-section">
            <h2 class="chart-title">Internship Status Distribution</h2>
            <?php foreach ($internshipStatusData as $status): ?>
                <?php 
                $percentage = $totalInternships > 0 ? ($status['count'] / $totalInternships) * 100 : 0;
                $statusClass = 'status-' . strtolower(str_replace(' ', '_', $status['status']));
                ?>
                <div style="margin: 10px 0;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                        <span class="status-badge <?php echo $statusClass; ?>">
                            <?php echo ucwords(str_replace('_', ' ', $status['status'])); ?>
                        </span>
                        <span><?php echo $status['count']; ?> (<?php echo round($percentage, 1); ?>%)</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="analytics-grid">
        <!-- Top Universities -->
        <div class="analytics-section">
            <h2 class="chart-title">Top Universities</h2>
            <?php if (count($topUniversities) > 0): ?>
                <?php 
                $maxCount = max(array_column($topUniversities, 'count'));
                foreach ($topUniversities as $university): 
                    $percentage = $maxCount > 0 ? ($university['count'] / $maxCount) * 100 : 0;
                ?>
                    <div style="margin: 10px 0;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <span><?php echo escape($university['university']); ?></span>
                            <span><?php echo $university['count']; ?> students</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color: #666; text-align: center;">No university data available</p>
            <?php endif; ?>
        </div>

        <!-- Top Majors -->
        <div class="analytics-section">
            <h2 class="chart-title">Top Majors</h2>
            <?php if (count($topMajors) > 0): ?>
                <?php 
                $maxCount = max(array_column($topMajors, 'count'));
                foreach ($topMajors as $major): 
                    $percentage = $maxCount > 0 ? ($major['count'] / $maxCount) * 100 : 0;
                ?>
                    <div style="margin: 10px 0;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <span><?php echo escape($major['major']); ?></span>
                            <span><?php echo $major['count']; ?> students</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color: #666; text-align: center;">No major data available</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Statistics Tables -->
    <div class="analytics-grid">
        <!-- GPA Statistics -->
        <div class="analytics-section">
            <h2 class="chart-title">Student GPA Statistics</h2>
            <?php if ($gpaStats['total_with_gpa'] > 0): ?>
                <table class="data-table">
                    <tr><th>Metric</th><th>Value</th></tr>
                    <tr><td>Average GPA</td><td><?php echo round($gpaStats['avg_gpa'], 2); ?></td></tr>
                    <tr><td>Highest GPA</td><td><?php echo $gpaStats['max_gpa']; ?></td></tr>
                    <tr><td>Lowest GPA</td><td><?php echo $gpaStats['min_gpa']; ?></td></tr>
                    <tr><td>Students with GPA</td><td><?php echo $gpaStats['total_with_gpa']; ?></td></tr>
                </table>
            <?php else: ?>
                <p style="color: #666; text-align: center;">No GPA data available</p>
            <?php endif; ?>
        </div>

        <!-- Registration Trends -->
        <?php if (count($registrationTrends) > 0): ?>
        <div class="analytics-section">
            <h2 class="chart-title">User Registration Trends (Last 6 Months)</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Month</th>
                        <th colspan="2">New Registrations</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $maxRegistrations = max(array_column($registrationTrends, 'count'));
                    foreach ($registrationTrends as $trend): 
                        $percentage = $maxRegistrations > 0 ? ($trend['count'] / $maxRegistrations) * 100 : 0;
                        $monthName = date('F Y', strtotime($trend['month'] . '-01'));
                    ?>
                        <tr>
                            <td><?php echo $monthName; ?></td>
                            <td><?php echo $trend['count']; ?></td>
                            <td>
                                <div class="progress-bar" style="width: 200px;">
                                    <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

    </div>

    
    <!-- Stipend Statistics
    <div class="analytics-section">
        <h2 class="chart-title">Internship Stipend Statistics</h2>
        <?php if ($stipendStats['total_with_stipend'] > 0): ?>
            <table class="data-table">
                <tr><th>Metric</th><th>Value</th></tr>
                <tr><td>Average Stipend</td><td>₨ <?php echo number_format($stipendStats['avg_stipend'], 2); ?></td></tr>
                <tr><td>Highest Stipend</td><td>₨ <?php echo number_format($stipendStats['max_stipend'], 2); ?></td></tr>
                <tr><td>Lowest Stipend</td><td>₨ <?php echo number_format($stipendStats['min_stipend'], 2); ?></td></tr>
                <tr><td>Paid Internships</td><td><?php echo $stipendStats['total_with_stipend']; ?></td></tr>
            </table>
        <?php else: ?>
            <p style="color: #666; text-align: center;">No stipend data available</p>
        <?php endif; ?>
    </div>
        -->

    <!-- Recent System Activities
    <div class="analytics-section">
        <h2 class="chart-title">Recent System Activities</h2>
        <?php if (count($recentActivities) > 0): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Action</th>
                        <th>Details</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentActivities as $activity): ?>
                        <tr>
                            <td><?php echo escape($activity['username'] ?: 'System'); ?></td>
                            <td><?php echo escape($activity['action']); ?></td>
                            <td><?php echo escape($activity['details'] ?: '-'); ?></td>
                            <td><?php echo date('M d, H:i', strtotime($activity['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="color: #666; text-align: center;">No recent activities</p>
        <?php endif; ?>
    </div>
     -->

    <!-- Quick Actions -->
    <div class="analytics-section" style="text-align: center; margin-top: 20px; margin-bottom: 0;">
        <h2 class="chart-title">Quick Actions</h2>
        <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
            <a href="tasks/students.php" class="btn btn-primary btn-sm">Manage Students</a>
            <a href="tasks/companies.php" class="btn btn-primary btn-sm">Manage Companies</a>
            <a href="tasks/internships.php" class="btn btn-primary btn-sm">Manage Internships</a>
            <a href="tasks/applications.php" class="btn btn-primary btn-sm">Manage Applications</a>
            <a href="tasks/system_logs.php" class="btn btn-primary btn-sm">View System Logs</a>
        </div>
    </div>
</div>

<!-- Auto-refresh functionality -->
<script>
// Auto-refresh every 5 minutes
setTimeout(function() {
    location.reload();
}, 300000);

// Add tooltips to progress bars
document.addEventListener('DOMContentLoaded', function() {
    const progressBars = document.querySelectorAll('.progress-fill');
    progressBars.forEach(bar => {
        bar.style.transition = 'width 1s ease-in-out';
    });
});
</script>

<?php require_once '../../includes/footer.php'; ?>
