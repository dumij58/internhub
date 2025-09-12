<?php
// Admin dashboard page for managing users and functionalities.
require_once '../../includes/config.php';

if (isLoggedIn()) {
    if (isAdmin()) {
        // Redirect to admin dashboard
        logActivity('Redirected to Admin Dashboard', 'Logged-in admin redirected to dashboard.');
        header('Location: ../admin/index.php');
        exit;
    } else {
        // Redirect to user dashboard
        $role = $_SESSION['role'];
        logActivity('Redirected to User Dashboard', "Logged-in user redirected to $role dashboard.");
        header("Location: ../$role/index.php");
        exit;
    }
}

// --- Page-specific variables ---
$page_title = 'Login';
global $pages_path;
global $assets_path;

// --- Include the header ---
require_once '../../includes/header.php';
?>

<style>
    body {
        background: url('<?php echo $assets_path; ?>/images/login_background.jpg') no-repeat center center fixed;
        background-size: cover;
    }
</style>

<div class="login-container">
    <div class="form-box">
        <h1 id="title">Sign In</h1>
        <form method="POST" action="register.php" id="loginForm">
            <div class="input-group">
                <div class="input-field" id="namefield" style="max-height: 0; overflow: hidden;">
                    <input type="text" name="name" placeholder="Name" required>
                </div>
                <div class="input-field" id="usertypefield" style="max-height: 0; overflow: hidden;">
                    <select name="user_type" class="form-control" required>
                        <option value="">Select Account Type</option>
                        <option value="2">Student</option>
                        <option value="3">Company</option>
                    </select>
                </div>
                <div class="input-field">
                    <input type="email" name= "email" placeholder="Email" required>
                </div>

                <div class="input-field">
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <p class="lost-pass">Lost password? <a href="reset_password.php">Click here</a></p>
            </div>
            <div class="btn-field">
                <button type="button" class="disable" id="signupBtn">Sign up</button>
                <button type="submit" id="signinBtn" name="signIn">Sign in</button>
            </div>
        </form>
        <script src="<?php echo $assets_path; ?>/js/login.js"></script>
    </div>
</div>

<?php
// --- Include the footer ---
require_once '../../includes/footer.php';
?>