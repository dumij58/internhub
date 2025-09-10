<?php
require_once '../../includes/config.php';

// Check if user is already logged in
if (isLoggedIn()) {
    $role = $_SESSION['role'];
    header("Location: ../$role/index.php");
    exit;
}

$db = getDB();
$message = '';
$message_type = '';

// Handle password reset request
if (isset($_POST['request_reset'])) {
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        $message = 'Please enter your email address.';
        $message_type = 'error';
    } else {
        // Check if email exists in database
        $sql = "SELECT user_id, username, email FROM users WHERE email = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch();
            
            // Generate a unique reset token
            $reset_token = bin2hex(random_bytes(32));
            $token_expiry = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token expires in 1 hour
            
            // Store reset token in database (we'll need to create a password_resets table)
            // For now, let's create a simple approach using the system_logs table to store tokens
            $reset_url = SITE_URL . "pages/auth/reset_password_form.php?token=" . $reset_token . "&email=" . urlencode($email);
            
            // Log the reset request with token
            $details = json_encode([
                'reset_token' => $reset_token,
                'token_expiry' => $token_expiry,
                'email' => $email,
                'reset_url' => $reset_url
            ]);
            
            $log_sql = "INSERT INTO system_logs (user_id, action, details, created_at) VALUES (?, ?, ?, NOW())";
            $log_stmt = $db->prepare($log_sql);
            $log_stmt->execute([$user['user_id'], 'PASSWORD_RESET_REQUEST', $details]);
            
            // In a real application, you would send an email here
            // For development purposes, we'll show the reset link
            $message = "Password reset request successful! <br><br>
                       <strong>Development Mode:</strong> Use this link to reset your password:<br>
                       <a href='$reset_url' target='_blank'>Reset Password</a><br><br>
                       <small>In production, this link would be sent to your email address.</small>";
            $message_type = 'success';
            
            // Log activity
            logActivity('Password Reset Requested', "Reset token generated for email: $email");
            
        } else {
            // Don't reveal if email exists or not for security
            $message = "If the email address exists in our system, you will receive a password reset link.";
            $message_type = 'info';
        }
    }
}

// Page variables
$page_title = 'Reset Password';
global $pages_path, $assets_path;

// Include header
require_once '../../includes/header.php';
?>

<div class="login-container">
    <div class="form-box">
        <h1>Reset Password</h1>
        
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="input-group">
                <div class="input-field">
                    <input type="email" name="email" placeholder="Enter your email address" required 
                           value="<?php echo isset($_POST['email']) ? escape($_POST['email']) : ''; ?>">
                </div>
            </div>
            
            <div class="btn-field-center">
                <button type="submit" name="request_reset" class="btn-primary">Send Reset Link</button>
            </div>
        </form>
        
        <div class="form-links">
            <p><a href="login.php">← Back to Login</a></p>
            <p>Don't have an account? <a href="login.php">Sign up here</a></p>
        </div>
    </div>
</div>

<?php
// Include footer
require_once '../../includes/footer.php';
?>
