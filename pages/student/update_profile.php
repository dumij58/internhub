<?php
require_once '../../includes/config.php';
requireLogin();

// Check if user is a student
if ($_SESSION['role'] !== 'student') {
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
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $student_id = trim($_POST['student_id']) ?: null;
        $phone = trim($_POST['phone']) ?: null;
        $university = trim($_POST['university']) ?: null;
        $major = trim($_POST['major']) ?: null;
        $year_of_study = !empty($_POST['year_of_study']) ? (int)$_POST['year_of_study'] : null;
        $gpa = !empty($_POST['gpa']) ? (float)$_POST['gpa'] : null;
        $portfolio_url = trim($_POST['portfolio_url']) ?: null;
        $bio = trim($_POST['bio']) ?: null;

        // Validate required fields
        if (empty($first_name) || empty($last_name)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'First name and last name are required.']);
            exit;
        }

        // Validate GPA if provided
        if ($gpa !== null && ($gpa < 0 || $gpa > 4.0)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'GPA must be between 0.0 and 4.0.']);
            exit;
        }

        // Validate year of study if provided
        if ($year_of_study !== null && ($year_of_study < 1 || $year_of_study > 8)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Year of study must be between 1 and 8.']);
            exit;
        }

        // Validate portfolio URL if provided
        if ($portfolio_url !== null && !filter_var($portfolio_url, FILTER_VALIDATE_URL)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Please provide a valid portfolio URL.']);
            exit;
        }

        // Check if student profile exists
        $check_query = "SELECT id FROM student_profiles WHERE user_id = ?";
        $stmt = $db->prepare($check_query);
        $stmt->execute([$_SESSION['user_id']]);
        $profile_exists = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($profile_exists) {
            // Update existing profile
            $update_query = "UPDATE student_profiles SET 
                            first_name = ?, 
                            last_name = ?, 
                            student_id = ?, 
                            phone = ?, 
                            university = ?, 
                            major = ?, 
                            year_of_study = ?, 
                            gpa = ?, 
                            portfolio_url = ?, 
                            bio = ?
                            WHERE user_id = ?";
            
            $stmt = $db->prepare($update_query);
            $success = $stmt->execute([
                $first_name,
                $last_name,
                $student_id,
                $phone,
                $university,
                $major,
                $year_of_study,
                $gpa,
                $portfolio_url,
                $bio,
                $_SESSION['user_id']
            ]);
            
            if (!$success) {
                throw new Exception('Failed to update student profile in database.');
            }
        } else {
            // Create new profile
            $insert_query = "INSERT INTO student_profiles 
                            (user_id, first_name, last_name, student_id, phone, university, major, year_of_study, gpa, portfolio_url, bio) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $db->prepare($insert_query);
            $success = $stmt->execute([
                $_SESSION['user_id'],
                $first_name,
                $last_name,
                $student_id,
                $phone,
                $university,
                $major,
                $year_of_study,
                $gpa,
                $portfolio_url,
                $bio
            ]);
            
            if (!$success) {
                throw new Exception('Failed to create student profile in database.');
            }
        }

        // Commit transaction
        $db->commit();

        // Update session variable if it exists
        if (isset($_SESSION['student_name'])) {
            $_SESSION['student_name'] = $first_name . ' ' . $last_name;
        }
        
        logActivity('Student profile updated successfully', "Profile updated for user ID: " . $_SESSION['user_id']);
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Profile updated successfully!']);

    } catch (PDOException $e) {
        // Rollback transaction on database error
        if ($db->inTransaction()) {
            $db->rollback();
        }
        
        logActivity('Database error updating student profile', $e->getMessage() . " - User ID: " . $_SESSION['user_id']);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database error occurred while updating profile.']);
    } catch (Exception $e) {
        // Rollback transaction on any error
        if ($db->inTransaction()) {
            $db->rollback();
        }
        
        logActivity('Error updating student profile', $e->getMessage() . " - User ID: " . $_SESSION['user_id']);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
