<?php

require_once '../../includes/config.php';

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

$db = getDB();

try {
    $db->beginTransaction();
    
    echo "<h2>Creating Default System Users...</h2>\n";

    // Check if default users already exist
    $checkStmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username IN ('admin', 'uoc', 'codalyth')");
    $checkStmt->execute();
    $existingCount = $checkStmt->fetchColumn();
    
    if ($existingCount > 0) {
        echo "<p style='color: orange;'>⚠️ Some default users already exist. Skipping creation to avoid duplicates.</p>\n";
        $db->rollback();
        return;
    }

    // 1. Create Admin User
    echo "<p>Creating admin user...</p>\n";
    $username = "admin";
    $email = "admin@internhub.com";
    $password = password_hash('admin', PASSWORD_DEFAULT); // admin
    $stmt = $db->prepare("INSERT INTO users (username, email, password_hash, user_type_id) VALUES (?, ?, ?, 1)");
    $stmt->execute([$username, $email, $password]);
    
    // 2. Create University User
    echo "<p>Creating university user...</p>\n";
    $username = "uoc";
    $email = "uoc@university.edu";
    $password = password_hash('uoc', PASSWORD_DEFAULT); // uoc
    $stmt = $db->prepare("INSERT INTO users (username, email, password_hash, user_type_id) VALUES (?, ?, ?, 2)");
    $stmt->execute([$username, $email, $password]);

    // 3. Create Company Moderator User
    echo "<p>Creating company moderator user...</p>\n";
    $username = "codalyth";
    $email = "hr@codalyth.com";
    $password = password_hash('codalyth', PASSWORD_DEFAULT); // codalyth
    $stmt = $db->prepare("INSERT INTO users (username, email, password_hash, user_type_id) VALUES (?, ?, ?, 3)");
    $stmt->execute([$username, $email, $password]);
    
    $db->commit();
    echo "<h3 style='color: green;'>✅ Default users created successfully!</h3>\n";
    echo "<p><strong>Default Login Credentials:</strong></p>\n";
    echo "<ul>\n";
    echo "<li><strong>Admin:</strong> admin / admin</li>\n";
    echo "<li><strong>University:</strong> uoc / uoc</li>\n";
    echo "<li><strong>Company Moderator:</strong> codalyth / codalyth</li>\n";
    echo "</ul>\n";
    echo "<p><em>Please change these default passwords after first login for security.</em></p>\n";
    echo "<p><a href='seed-sample-data.php'>Create Sample Analytics Data</a></p>\n";
    echo "<p><a href='../../pages/admin/'>Go to Admin Dashboard</a></p>\n";
    
} catch (Exception $e) {
    $db->rollback();
    echo "<h3 style='color: red;'>❌ Error creating default users:</h3>\n";
    echo "<p>" . $e->getMessage() . "</p>\n";
    echo "<p>Line: " . $e->getLine() . "</p>\n";
    echo "<p>File: " . $e->getFile() . "</p>\n";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Default Users Seeder</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h2, h3 { color: #333; }
        p { margin: 10px 0; }
        ul { margin: 10px 0; }
        li { margin: 5px 0; }
        a { color: #0077b6; text-decoration: none; }
        a:hover { text-decoration: underline; }
        em { color: #666; }
    </style>
</head>
<body>
</body>
</html>
