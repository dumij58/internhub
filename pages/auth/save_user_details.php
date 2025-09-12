<?php
require_once '../../includes/config.php';
requireLogin(); 

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Begin transaction for data consistency
        $db->beginTransaction();
        
        // Get form data
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $university = trim($_POST['university']);
        $degree_program = trim($_POST['degree_program']);
        $year_of_study = !empty($_POST['year_of_study']) ? (int)$_POST['year_of_study'] : null;
        $gpa = !empty($_POST['gpa']) ? (float)$_POST['gpa'] : null;
        $key_skills = trim($_POST['key_skills']);
        $areas_of_interest = trim($_POST['areas_of_interest']);
        $portfolio_links = !empty($_POST['portfolio_links']) ? trim($_POST['portfolio_links']) : null;

        // Split full name into first and last name
        $name_parts = explode(' ', $full_name, 2);
        $first_name = $name_parts[0];
        $last_name = isset($name_parts[1]) ? $name_parts[1] : '';

        // Validate required fields
        if (empty($first_name) || empty($email) || empty($university) || empty($degree_program)) {
            throw new Exception('Required fields are missing: first name, email, university, and degree program are required.');
        }

        // Check if student profile already exists
        $check_profile = "SELECT id FROM student_profiles WHERE user_id = ?";
        $stmt = $db->prepare($check_profile);
        $stmt->execute([$_SESSION['user_id']]);
        
        if ($stmt->rowCount() > 0) {
            // Update existing profile
            $update_profile = "UPDATE student_profiles SET 
                first_name = ?, 
                last_name = ?, 
                phone = ?, 
                university = ?, 
                major = ?, 
                year_of_study = ?, 
                gpa = ?, 
                portfolio_url = ?, 
                skills = ?, 
                bio = ?
                WHERE user_id = ?";
            
            $stmt = $db->prepare($update_profile);
            $result = $stmt->execute([
                $first_name,
                $last_name,
                $phone,
                $university,
                $degree_program,
                $year_of_study,
                $gpa,
                $portfolio_links,
                $key_skills,
                $areas_of_interest,
                $_SESSION['user_id']
            ]);
            
            if (!$result) {
                throw new Exception('Failed to update student profile.');
            }
        } else {
            // Insert new profile
            $insert_profile = "INSERT INTO student_profiles 
                (user_id, first_name, last_name, phone, university, major, year_of_study, gpa, portfolio_url, skills, bio) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $db->prepare($insert_profile);
            $result = $stmt->execute([
                $_SESSION['user_id'],
                $first_name,
                $last_name,
                $phone,
                $university,
                $degree_program,
                $year_of_study,
                $gpa,
                $portfolio_links,
                $key_skills,
                $areas_of_interest
            ]);
            
            if (!$result) {
                throw new Exception('Failed to create student profile.');
            }
        }

        // Commit transaction
        $db->commit();
        
        // Set session variable to indicate profile is complete
        $_SESSION['profile_complete'] = true;
        
        echo "<script>
            alert('User details saved successfully!');
            window.location.href='{$pages_path}/$_SESSION[role]/dashboard.php';
        </script>";
        logActivity('User details saved successfully');

    } catch (Exception $e) {
        // Rollback transaction on error
        if ($db->inTransaction()) {
            $db->rollback();
        }
        
        echo "<script>
            alert('Error saving user details: " . addslashes($e->getMessage()) . "');
            window.location.href='user_details.php';
        </script>";
        logActivity('Error saving user details', $e->getMessage());
    }
} else {
    header("Location: user_details.php");
    exit();
}
?>
