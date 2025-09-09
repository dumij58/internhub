<?php

require_once '../../includes/config.php';
requireAdmin();

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

$db = getDB();

// Arrays for generating realistic sample data
$universities = [
    'University of Colombo', 'University of Peradeniya', 'University of Moratuwa',
    'University of Sri Jayewardenepura', 'University of Kelaniya', 'University of Ruhuna',
    'Sabaragamuwa University', 'University of Jaffna', 'Eastern University',
    'SLIIT', 'NSBM', 'IIT Campus'
];

$majors = [
    'Computer Science', 'Software Engineering', 'Information Technology',
    'Computer Engineering', 'Data Science', 'Cybersecurity',
    'Business Administration', 'Marketing', 'Finance',
    'Mechanical Engineering', 'Civil Engineering', 'Electrical Engineering',
    'Graphic Design', 'Architecture', 'Psychology'
];

$companies = [
    'TechCorp Solutions', 'Digital Innovations Ltd', 'Future Systems',
    'CodeCraft Technologies', 'DataDrive Inc', 'CloudTech Solutions',
    'WebWorks Agency', 'StartupHub', 'Enterprise Solutions',
    'Creative Minds Studio', 'NextGen Technologies', 'InnovateLK',
    'TechPioneer', 'DigitalFirst', 'SmartSolutions'
];

$jobTitles = [
    'Software Developer Intern', 'Web Developer Intern', 'Data Analyst Intern',
    'UI/UX Designer Intern', 'Digital Marketing Intern', 'Business Analyst Intern',
    'Quality Assurance Intern', 'DevOps Intern', 'Mobile App Developer Intern',
    'Cybersecurity Intern', 'Database Administrator Intern', 'Project Management Intern',
    'Content Writer Intern', 'Graphic Designer Intern', 'Sales Intern'
];

$locations = [
    'Colombo', 'Kandy', 'Galle', 'Negombo', 'Kurunegala',
    'Anuradhapura', 'Matara', 'Ratnapura', 'Batticaloa', 'Jaffna'
];

$skills = [
    'PHP', 'JavaScript', 'Python', 'Java', 'React', 'Angular', 'Vue.js',
    'Node.js', 'Laravel', 'MySQL', 'PostgreSQL', 'MongoDB',
    'HTML', 'CSS', 'Bootstrap', 'Tailwind CSS', 'Git', 'Docker',
    'AWS', 'Azure', 'Google Cloud', 'Photoshop', 'Illustrator'
];

// Helper function to get random date within a range
function randomDate($start_date, $end_date) {
    $min = strtotime($start_date);
    $max = strtotime($end_date);
    $random = mt_rand($min, $max);
    return date('Y-m-d H:i:s', $random);
}

// Helper function to get random date (date only)
function randomDateOnly($start_date, $end_date) {
    $min = strtotime($start_date);
    $max = strtotime($end_date);
    $random = mt_rand($min, $max);
    return date('Y-m-d', $random);
}

try {
    $db->beginTransaction();
    
    echo "<h2>Starting Sample Analytics Data Generation...</h2>\n";

    // Check if sample data already exists
    $checkStmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username LIKE 'student%' OR username LIKE 'company%'");
    $checkStmt->execute();
    $existingCount = $checkStmt->fetchColumn();
    
    if ($existingCount > 0) {
        echo "<p style='color: orange;'>⚠️ Sample data already exists. Please run unseed-analytics-data.php first to remove existing sample data.</p>\n";
        $db->rollback();
        echo "<p><a href='unseed-analytics-data.php'>Remove Existing Sample Data</a></p>\n";
        return;
    }
    
    // 1. Generate Student Users and Profiles (40 students)
    echo "<p>Creating 40 student users and profiles...</p>\n";
    $studentUserIds = [];
    
    for ($i = 1; $i <= 40; $i++) {
        $username = "student" . sprintf("%03d", $i);
        $email = "student{$i}@university.edu";
        $password = password_hash('student123', PASSWORD_DEFAULT); // student123
        $created_at = randomDate('2024-01-01', '2025-08-31');
        
        $stmt = $db->prepare("INSERT INTO users (username, email, password_hash, user_type_id, created_at) VALUES (?, ?, ?, 2, ?)");
        $stmt->execute([$username, $email, $password, $created_at]);
        $userId = $db->lastInsertId();
        $studentUserIds[] = $userId;
        
        // Create student profile
        $firstName = "Student";
        $lastName = "User" . $i;
        $university = $universities[array_rand($universities)];
        $major = $majors[array_rand($majors)];
        $yearOfStudy = rand(1, 4);
        $gpa = round(2.0 + (rand(0, 200) / 100), 2); // GPA between 2.0 and 4.0
        $phone = "077" . rand(1000000, 9999999);
        $studentId = "STU" . sprintf("%03d", $i);
        $bio = "Passionate {$major} student from {$university} looking for internship opportunities.";
        
        // Random skills (3-6 skills per student)
        $studentSkills = array_rand(array_flip($skills), rand(3, 6));
        $skillsJson = json_encode($studentSkills);
        
        $stmt = $db->prepare("
            INSERT INTO student_profiles (user_id, student_id, first_name, last_name, phone, university, major, year_of_study, gpa, bio, skills) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $studentId, $firstName, $lastName, $phone, $university, $major, $yearOfStudy, $gpa, $bio, $skillsJson]);
    }
    
    // 2. Generate Company Users and Profiles (15 companies)
    echo "<p>Creating 15 company users and profiles...</p>\n";
    $companyUserIds = [];
    $companyProfileIds = [];
    
    for ($i = 1; $i <= 15; $i++) {
        $username = "company" . sprintf("%03d", $i);
        $email = "hr@company{$i}.com";
        $password = password_hash('company123', PASSWORD_DEFAULT); // company123
        $created_at = randomDate('2024-01-01', '2025-08-31');
        
        $stmt = $db->prepare("INSERT INTO users (username, email, password_hash, user_type_id, created_at) VALUES (?, ?, ?, 3, ?)");
        $stmt->execute([$username, $email, $password, $created_at]);
        $userId = $db->lastInsertId();
        $companyUserIds[] = $userId;
        
        // Create company profile
        $companyName = $companies[$i - 1];
        $website = "https://www." . strtolower(str_replace(' ', '', $companyName)) . ".com";
        $description = "Leading technology company specializing in innovative solutions and digital transformation.";
        $address = rand(1, 500) . " Main Street, " . $locations[array_rand($locations)] . ", Sri Lanka";
        $verified = rand(0, 1); // Random verification status
        
        $stmt = $db->prepare("
            INSERT INTO company_profiles (user_id, company_name, company_website, company_description, address, verified) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $companyName, $website, $description, $address, $verified]);
        $companyProfileIds[] = $db->lastInsertId();
    }
    
    // 3. Generate Internships (25 internships)
    echo "<p>Creating 25 internship postings...</p>\n";
    $internshipIds = [];
    $statuses = ['draft', 'published', 'closed', 'cancelled'];
    $experienceLevels = ['beginner', 'intermediate', 'advanced'];
    
    for ($i = 1; $i <= 25; $i++) {
        $title = $jobTitles[array_rand($jobTitles)];
        $companyId = $companyProfileIds[array_rand($companyProfileIds)];
        $createdBy = $companyUserIds[array_rand($companyUserIds)];
        $categoryId = rand(1, 5); // Assuming 5 categories
        $description = "Exciting internship opportunity to gain hands-on experience in {$title} role.";
        $requirements = "Currently pursuing degree in relevant field. Strong communication skills. Eager to learn.";
        $responsibilities = "Assist with daily operations, participate in team meetings, complete assigned projects.";
        $location = $locations[array_rand($locations)];
        $durationMonths = rand(3, 12);
        $status = $statuses[array_rand($statuses)];
        $remoteOption = rand(0, 1);
        $experienceLevel = $experienceLevels[array_rand($experienceLevels)];
        $maxApplicants = rand(10, 100);
        $stipend = rand(0, 1) ? rand(15000, 50000) : null; // Some internships have stipend, others don't
        
        $applicationDeadline = randomDateOnly('2025-09-01', '2025-12-31');
        $startDate = randomDateOnly('2025-10-01', '2026-01-31');
        $endDate = date('Y-m-d', strtotime($startDate . " + {$durationMonths} months"));
        $created_at = randomDate('2024-06-01', '2025-08-31');
        
        $stmt = $db->prepare("
            INSERT INTO internships (title, company_id, category_id, description, requirements, responsibilities, 
                                   location, duration_months, stipend, application_deadline, start_date, end_date, 
                                   max_applicants, status, remote_option, experience_level, created_by, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $title, $companyId, $categoryId, $description, $requirements, $responsibilities,
            $location, $durationMonths, $stipend, $applicationDeadline, $startDate, $endDate,
            $maxApplicants, $status, $remoteOption, $experienceLevel, $createdBy, $created_at
        ]);
        $internshipIds[] = $db->lastInsertId();
    }
    
    // 4. Generate Applications (80 applications)
    echo "<p>Creating 80 applications...</p>\n";
    $applicationStatuses = ['draft', 'submitted', 'under_review', 'rejected', 'accepted'];

    for ($i = 1; $i <= 80; $i++) {
        $internshipId = $internshipIds[array_rand($internshipIds)];
        $studentId = $studentUserIds[array_rand($studentUserIds)];
        $status = $applicationStatuses[array_rand($applicationStatuses)];
        $applicationDate = randomDate('2024-07-01', '2025-08-31');
        
        // Avoid duplicate applications
        $checkStmt = $db->prepare("SELECT COUNT(*) FROM applications WHERE internship_id = ? AND student_id = ?");
        $checkStmt->execute([$internshipId, $studentId]);
        if ($checkStmt->fetchColumn() > 0) {
            continue; // Skip if application already exists
        }
        
        $resumePath = "/uploads/resumes/student{$studentId}_resume.pdf";
        $reviewedDate = in_array($status, ['under_review', 'rejected', 'accepted']) ? 
            randomDate($applicationDate, '2025-09-01') : null;
        $reviewedBy = $reviewedDate ? $companyUserIds[array_rand($companyUserIds)] : null;
        
        $stmt = $db->prepare("
            INSERT INTO applications (internship_id, student_id, resume_path, status, application_date, reviewed_date, reviewed_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$internshipId, $studentId, $resumePath, $status, $applicationDate, $reviewedDate, $reviewedBy]);
    }
    
    // 5. Generate System Logs (50 log entries)
    echo "<p>Creating 50 system log entries...</p>\n";
    $actions = [
        'User Login', 'User Logout', 'Profile Updated', 'Application Submitted',
        'Application Reviewed', 'Internship Posted', 'Internship Updated',
        'Password Changed', 'Account Created', 'File Uploaded'
    ];
    
    $allUserIds = array_merge($studentUserIds, $companyUserIds);
    
    for ($i = 1; $i <= 50; $i++) {
        $userId = $allUserIds[array_rand($allUserIds)];
        $action = $actions[array_rand($actions)];
        $details = "System action performed by user";
        $created_at = randomDate('2024-06-01', '2025-09-01');
        
        $stmt = $db->prepare("INSERT INTO system_logs (user_id, action, details, created_at) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $action, $details, $created_at]);
    }
    
    // 6. Generate Notifications (30 notifications)
    echo "<p>Creating 30 notifications...</p>\n";
    $notificationTypes = ['info', 'success', 'warning', 'error'];
    $notificationTitles = [
        'Application Status Update', 'New Internship Posted', 'Profile Incomplete',
        'Application Deadline Reminder', 'Interview Scheduled', 'Welcome Message'
    ];
    
    for ($i = 1; $i <= 30; $i++) {
        $userId = $studentUserIds[array_rand($studentUserIds)];
        $title = $notificationTitles[array_rand($notificationTitles)];
        $message = "This is a sample notification message for testing purposes.";
        $type = $notificationTypes[array_rand($notificationTypes)];
        $isRead = rand(0, 1);
        $created_at = randomDate('2024-08-01', '2025-09-01');
        $readAt = $isRead ? randomDate($created_at, '2025-09-01') : null;
        
        $stmt = $db->prepare("
            INSERT INTO notifications (user_id, title, message, type, is_read, created_at, read_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $title, $message, $type, $isRead, $created_at, $readAt]);
    }
    
    $db->commit();
    echo "<h3 style='color: green;'>✅ Sample analytics data generation completed successfully!</h3>\n";
    echo "<p><strong>Summary:</strong></p>\n";
    echo "<ul>\n";
    echo "<li>40 Student users and profiles created</li>\n";
    echo "<li>15 Company users and profiles created</li>\n";
    echo "<li>25 Internship postings created</li>\n";
    echo "<li>80 Applications created</li>\n";
    echo "<li>50 System log entries created</li>\n";
    echo "<li>30 Notifications created</li>\n";
    echo "</ul>\n";
    echo "<p><a href='../../pages/admin/analytics.php'>View Analytics Dashboard</a></p>\n";
    echo "<p><a href='unseed-analytics-data.php'>Remove Sample Data</a></p>\n";
    
} catch (Exception $e) {
    $db->rollback();
    echo "<h3 style='color: red;'>❌ Error generating sample data:</h3>\n";
    echo "<p>" . $e->getMessage() . "</p>\n";
    echo "<p>Line: " . $e->getLine() . "</p>\n";
    echo "<p>File: " . $e->getFile() . "</p>\n";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Sample Analytics Data Generator</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h2, h3 { color: #333; }
        p { margin: 10px 0; }
        ul { margin: 10px 0; }
        a { color: #0077b6; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
</body>
</html>
