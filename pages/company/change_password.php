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
        $current_password = trim($_POST['current_password']);
        $new_password = trim($_POST['new_password']);
        $confirm_password = trim($_POST['confirm_password']);

        // Validate input
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'All fields are required.']);
            exit;
        }

        if ($new_password !== $confirm_password) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'New password and confirm password do not match.']);
            exit;
        }

        if (strlen($new_password) < 6) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters long.']);
            exit;
        }

        // Get current user's password hash
        $stmt = $db->prepare("SELECT password_hash FROM users WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'User not found.']);
            exit;
        }

        // Verify current password
        if (!password_verify($current_password, $user['password_hash'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Current password is incorrect.']);
            exit;
        }

        // Hash new password
        $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);

        // Update password in database
        $update_stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
        $success = $update_stmt->execute([$new_password_hash, $_SESSION['user_id']]);

        if ($success) {
            logActivity('Password changed successfully');
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Password changed successfully!']);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Failed to update password.']);
        }

    } catch (Exception $e) {
        logActivity('Error changing password', $e->getMessage());
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
