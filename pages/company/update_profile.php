<?php
require_once '../../includes/config.php';
requireLogin();

// Check if user is a company
if ($_SESSION['role'] !== 'company') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get form data
        $company_name = trim($_POST['company_name']);
        $industry_type = trim($_POST['industry_type']);
        $company_website = trim($_POST['company_website']) ?: null;
        $phone_number = trim($_POST['phone_number']) ?: null;
        $address = trim($_POST['address']) ?: null;
        $company_description = trim($_POST['company_description']) ?: null;

        // Validate required fields
        if (empty($company_name) || empty($industry_type)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Company name and industry type are required.']);
            exit;
        }

        // Update company profile
        $update_query = "UPDATE company_profiles SET 
                        company_name = ?, 
                        industry_type = ?, 
                        company_website = ?, 
                        phone_number = ?, 
                        address = ?, 
                        company_description = ?
                        WHERE user_id = ?";
        
        $stmt = $db->prepare($update_query);
        $success = $stmt->execute([
            $company_name,
            $industry_type,
            $company_website,
            $phone_number,
            $address,
            $company_description,
            $_SESSION['user_id']
        ]);

        if ($success) {
            logActivity('Company profile updated successfully');
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Profile updated successfully!']);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Failed to update profile.']);
        }

    } catch (Exception $e) {
        logActivity('Error updating company profile', $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
