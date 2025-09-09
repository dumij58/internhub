<?php
/**
 * Sample Data Removal Script
 * This script removes all sample data generated for analytics testing
 * Run this script to clean up the database after testing
 * 
 * This will remove:
 * - Sample student and company users (keeping default system users)
 * - Sample internships, applications, notifications, and system logs
 * 
 * This will preserve:
 * - Default system users (admin, uoc, codalyth)
 * - Any manually created legitimate data
 */

require_once '../../includes/config.php';
requireAdmin();

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

$db = getDB();

try {
    $db->beginTransaction();
    
    echo "<h2>Starting Sample Data Removal...</h2>\n";
    
    // Check if there's sample data to remove
    $checkStmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username LIKE 'student%' OR username LIKE 'company%'");
    $checkStmt->execute();
    $sampleCount = $checkStmt->fetchColumn();
    
    if ($sampleCount == 0) {
        echo "<p style='color: orange;'>⚠️ No sample data found to remove.</p>\n";
        $db->rollback();
        echo "<p><a href='seed-default-users.php'>Create Default Users</a></p>\n";
        echo "<p><a href='seed-sample-data.php'>Create Sample Data</a></p>\n";
        return;
    }
    
    // Get sample user IDs (users with student### or company### usernames)
    $sampleUserIds = [];
    $stmt = $db->query("SELECT user_id FROM users WHERE username LIKE 'student%' OR username LIKE 'company%'");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $sampleUserIds[] = $row['user_id'];
    }
    
    if (empty($sampleUserIds)) {
        echo "<p>No sample users found.</p>\n";
        $db->rollback();
        return;
    }
    
    $sampleUserIdsList = implode(',', $sampleUserIds);
    
    // 1. Remove notifications for sample users
    echo "<p>Removing sample notifications...</p>\n";
    $stmt = $db->prepare("
        DELETE FROM notifications 
        WHERE user_id IN ({$sampleUserIdsList})
    ");
    $stmt->execute();
    $deletedNotifications = $stmt->rowCount();
    
    // 2. Remove system logs for sample users
    echo "<p>Removing sample system logs...</p>\n";
    $stmt = $db->prepare("
        DELETE FROM system_logs 
        WHERE user_id IN ({$sampleUserIdsList})
    ");
    $stmt->execute();
    $deletedLogs = $stmt->rowCount();
    
    // 3. Remove applications from sample users
    echo "<p>Removing sample applications...</p>\n";
    $stmt = $db->prepare("
        DELETE FROM applications 
        WHERE student_id IN ({$sampleUserIdsList})
    ");
    $stmt->execute();
    $deletedApplications = $stmt->rowCount();
    
    // 4. Remove internships created by sample companies
    echo "<p>Removing sample internships...</p>\n";
    $stmt = $db->prepare("
        DELETE FROM internships 
        WHERE created_by IN ({$sampleUserIdsList})
    ");
    $stmt->execute();
    $deletedInternships = $stmt->rowCount();
    
    // 5. Remove sample company profiles
    echo "<p>Removing sample company profiles...</p>\n";
    $stmt = $db->prepare("
        DELETE FROM company_profiles 
        WHERE user_id IN ({$sampleUserIdsList})
    ");
    $stmt->execute();
    $deletedCompanyProfiles = $stmt->rowCount();
    
    // 6. Remove sample student profiles
    echo "<p>Removing sample student profiles...</p>\n";
    $stmt = $db->prepare("
        DELETE FROM student_profiles 
        WHERE user_id IN ({$sampleUserIdsList})
    ");
    $stmt->execute();
    $deletedStudentProfiles = $stmt->rowCount();
    
    // 7. Remove sample users
    echo "<p>Removing sample users...</p>\n";
    $stmt = $db->prepare("
        DELETE FROM users 
        WHERE user_id IN ({$sampleUserIdsList})
    ");
    $stmt->execute();
    $deletedUsers = $stmt->rowCount();
    
    // 8. Reset AUTO_INCREMENT values (optional, only if tables are empty)
    echo "<p>Checking AUTO_INCREMENT reset...</p>\n";
    
    // Only reset if no data remains in critical tables
    $userCount = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($userCount <= 3) { // Only default users remain
        $db->exec("ALTER TABLE notifications AUTO_INCREMENT = 1");
        $db->exec("ALTER TABLE system_logs AUTO_INCREMENT = 1");
        $db->exec("ALTER TABLE applications AUTO_INCREMENT = 1");
        $db->exec("ALTER TABLE internships AUTO_INCREMENT = 1");
        echo "<p>AUTO_INCREMENT values reset for clean state.</p>\n";
    }
    
    $db->commit();
    echo "<h3 style='color: green;'>✅ Sample data removal completed successfully!</h3>\n";
    echo "<p><strong>Summary of removed data:</strong></p>\n";
    echo "<ul>\n";
    echo "<li>{$deletedUsers} Sample users removed</li>\n";
    echo "<li>{$deletedStudentProfiles} Sample student profiles removed</li>\n";
    echo "<li>{$deletedCompanyProfiles} Sample company profiles removed</li>\n";
    echo "<li>{$deletedInternships} Sample internships removed</li>\n";
    echo "<li>{$deletedApplications} Sample applications removed</li>\n";
    echo "<li>{$deletedLogs} Sample system logs removed</li>\n";
    echo "<li>{$deletedNotifications} Sample notifications removed</li>\n";
    echo "</ul>\n";
    echo "<p><strong>Preserved data:</strong></p>\n";
    echo "<ul>\n";
    echo "<li>Default system users (admin, uoc, codalyth)</li>\n";
    echo "<li>Any manually created legitimate data</li>\n";
    echo "<li>Core system configurations</li>\n";
    echo "</ul>\n";
    echo "<p><a href='../../pages/admin/analytics.php'>View Analytics Dashboard</a></p>\n";
    echo "<p><a href='seed-sample-data.php'>Generate Sample Data Again</a></p>\n";
    
} catch (Exception $e) {
    $db->rollback();
    echo "<h3 style='color: red;'>❌ Error removing sample data:</h3>\n";
    echo "<p>" . $e->getMessage() . "</p>\n";
    echo "<p>Line: " . $e->getLine() . "</p>\n";
    echo "<p>File: " . $e->getFile() . "</p>\n";
}

// Display current database state
try {
    echo "<h3>Current Database State:</h3>\n";
    $tables = ['users', 'student_profiles', 'company_profiles', 'internships', 'applications', 'system_logs', 'notifications'];
    echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse;'>\n";
    echo "<tr><th>Table</th><th>Record Count</th></tr>\n";
    
    foreach ($tables as $table) {
        $stmt = $db->query("SELECT COUNT(*) FROM {$table}");
        $count = $stmt->fetchColumn();
        echo "<tr><td>{$table}</td><td>{$count}</td></tr>\n";
    }
    echo "</table>\n";
    
} catch (Exception $e) {
    echo "<p>Error getting database state: " . $e->getMessage() . "</p>\n";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Sample Data Removal</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h2, h3 { color: #333; }
        p { margin: 10px 0; }
        ul { margin: 10px 0; }
        a { color: #0077b6; text-decoration: none; }
        a:hover { text-decoration: underline; }
        table { margin: 20px 0; }
        th { background: #f8f9fa; font-weight: 600; }
        td, th { padding: 8px 12px; text-align: left; }
        tr:nth-child(even) { background: #f8f9fa; }
    </style>
</head>
<body>
    <!-- Output will be displayed above -->
</body>
</html>
