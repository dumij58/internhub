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
        // Begin transaction for data consistency
        $db->beginTransaction();
        
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

        // Validate website URL if provided
        if ($company_website !== null && !filter_var($company_website, FILTER_VALIDATE_URL)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Please provide a valid website URL.']);
            exit;
        }

        // Validate industry type against allowed values
        $allowed_industries = [
            'IT', 'Finance', 'Marketing', 'Engineering', 'Healthcare', 
            'Education', 'Retail', 'Manufacturing', 'Consulting', 'Non-profit', 'Other'
        ];
        if (!in_array($industry_type, $allowed_industries)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Please select a valid industry type.']);
            exit;
        }

        // Check if company profile exists
        $check_query = "SELECT id FROM company_profiles WHERE user_id = ?";
        $stmt = $db->prepare($check_query);
        $stmt->execute([$_SESSION['user_id']]);
        $profile_exists = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$profile_exists) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Company profile not found. Please contact support.']);
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

        if (!$success) {
            throw new Exception('Failed to update company profile in database.');
        }

        if ($stmt->rowCount() === 0) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'No changes were made to the profile.']);
            exit;
        }

        // Commit transaction
        $db->commit();
        
        logActivity('Company profile updated successfully', "Profile updated for user ID: " . $_SESSION['user_id']);
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Profile updated successfully!']);

    } catch (PDOException $e) {
        // Rollback transaction on database error
        if ($db->inTransaction()) {
            $db->rollback();
        }
        
        logActivity('Database error updating company profile', $e->getMessage() . " - User ID: " . $_SESSION['user_id']);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database error occurred while updating profile.']);
    } catch (Exception $e) {
        // Rollback transaction on any error
        if ($db->inTransaction()) {
            $db->rollback();
        }
        
        logActivity('Error updating company profile', $e->getMessage() . " - User ID: " . $_SESSION['user_id']);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
