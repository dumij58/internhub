<?php
require_once '../../includes/config.php';
requireLogin(); 

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Begin transaction for data consistency
        $db->beginTransaction();
        
        // Get form data
        $company_name = trim($_POST['company_name']);
        $industry_type = trim($_POST['industry_type']);
        $company_website = !empty($_POST['company_website']) ? trim($_POST['company_website']) : null;
        $official_email = trim($_POST['official_email']);
        $phone_number = trim($_POST['phone_number']);
        $company_address = trim($_POST['company_address']);
        $company_description = trim($_POST['company_description']);

        // Validate required fields
        if (empty($company_name) || empty($industry_type) || empty($official_email) || empty($phone_number)) {
            throw new Exception('Required fields are missing: company name, industry type, official email, and phone number are required.');
        }

        // Validate email format
        if (!filter_var($official_email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Please provide a valid email address.');
        }

        // Handle file upload for company logo
        $logo_path = null;
        if (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../../assets/images/company_logos/';
            
            // Validate file type
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $file_type = $_FILES['company_logo']['type'];
            if (!in_array($file_type, $allowed_types)) {
                throw new Exception('Invalid file type. Only JPEG, PNG, GIF, and WebP images are allowed.');
            }
            
            // Validate file size (5MB max)
            $max_size = 5 * 1024 * 1024; // 5MB
            if ($_FILES['company_logo']['size'] > $max_size) {
                throw new Exception('File size too large. Maximum size is 5MB.');
            }
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['company_logo']['name'], PATHINFO_EXTENSION);
            $logo_filename = 'company_' . $_SESSION['user_id'] . '_' . time() . '.' . $file_extension;
            $logo_path = $upload_dir . $logo_filename;
            
            // Move uploaded file
            if (!move_uploaded_file($_FILES['company_logo']['tmp_name'], $logo_path)) {
                throw new Exception('Failed to upload company logo');
            }
            $logo_path = 'assets/images/company_logos/' . $logo_filename; // Relative path for database
        }

        // Check if company profile already exists
        $check_profile = "SELECT id FROM company_profiles WHERE user_id = ?";
        $stmt = $db->prepare($check_profile);
        $stmt->execute([$_SESSION['user_id']]);
        
        if ($stmt->rowCount() > 0) {
            // Update existing profile
            $update_profile = "UPDATE company_profiles SET 
                company_name = ?, 
                industry_type = ?,
                company_website = ?, 
                company_description = ?, 
                address = ?,
                phone_number = ?";
                
            $params = [
                $company_name,
                $industry_type,
                $company_website,
                $company_description,
                $company_address,
                $phone_number
            ];
            
            // Only update logo if a new one was uploaded
            if ($logo_path !== null) {
                $update_profile .= ", company_logo_path = ?";
                $params[] = $logo_path;
            }
            
            $update_profile .= " WHERE user_id = ?";
            $params[] = $_SESSION['user_id'];
            
            $stmt = $db->prepare($update_profile);
            $result = $stmt->execute($params);
            
            if (!$result) {
                throw new Exception('Failed to update company profile.');
            }
        } else {
            // Insert new profile
            $insert_profile = "INSERT INTO company_profiles 
                (user_id, company_name, industry_type, company_website, company_description, address, phone_number, company_logo_path) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $db->prepare($insert_profile);
            $result = $stmt->execute([
                $_SESSION['user_id'],
                $company_name,
                $industry_type,
                $company_website,
                $company_description,
                $company_address,
                $phone_number,
                $logo_path
            ]);
            
            if (!$result) {
                throw new Exception('Failed to create company profile.');
            }
        }

        // Commit transaction
        $db->commit();
        
        // Set session variable to indicate profile is complete
        $_SESSION['profile_complete'] = true;
        
        echo "<script>
            alert('Company details saved successfully!');
            window.location.href='../../index.php';
        </script>";
        logActivity('Company details saved successfully');

    } catch (Exception $e) {
        // Rollback transaction on error
        if ($db->inTransaction()) {
            $db->rollback();
        }
        
        echo "<script>
            alert('Error saving company details: " . addslashes($e->getMessage()) . "');
            window.location.href='company_details.php';
        </script>";
        logActivity('Error saving company details', $e->getMessage());
    }
} else {
    header("Location: company_details.php");
    exit();
}
?>
