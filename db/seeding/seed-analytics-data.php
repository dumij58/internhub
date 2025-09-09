<?php
/**
 * DEPRECATED: This file has been split into separate scripts for better organization
 * 
 * Please use the new separated scripts:
 * - seed-default-users.php (for system users)
 * - seed-sample-data.php (for analytics test data)
 */

echo "<!DOCTYPE html>
<html>
<head>
    <title>Deprecated - Use New Seeding Scripts</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; text-align: center; }
        .container { max-width: 600px; margin: 0 auto; }
        h1 { color: #e74c3c; }
        h2 { color: #333; }
        .notice { background: #fff3cd; border: 1px solid #ffeaa7; padding: 20px; border-radius: 5px; margin: 20px 0; }
        .btn { display: inline-block; padding: 12px 24px; margin: 10px; text-decoration: none; 
               background: #3498db; color: white; border-radius: 5px; font-weight: bold; }
        .btn:hover { background: #2980b9; }
        .btn-primary { background: #27ae60; }
        .btn-primary:hover { background: #229954; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>⚠️ Script Deprecated</h1>
        <div class='notice'>
            <h2>This script has been separated for better organization</h2>
            <p>The seeding functionality has been split into two focused scripts:</p>
        </div>
        
        <h2>Choose Your Action:</h2>
        
        <a href='seed-default-users.php' class='btn btn-primary'>
            🔧 Create Default System Users<br>
            <small>(admin, university, company moderator)</small>
        </a>
        
        <a href='seed-sample-data.php' class='btn'>
            📊 Generate Sample Analytics Data<br>
            <small>(students, companies, internships, applications)</small>
        </a>
        
        <div style='margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 5px;'>
            <h3>Recommended Workflow:</h3>
            <ol style='text-align: left; max-width: 400px; margin: 0 auto;'>
                <li>First time: Create default system users</li>
                <li>For testing: Generate sample analytics data</li>
                <li>After testing: Use unseed script to clean up</li>
            </ol>
        </div>
        
        <p style='margin-top: 30px;'>
            <a href='db_seeding_README.md' style='color: #666;'>📖 Read the documentation</a>
        </p>
    </div>
</body>
</html>";
?>
