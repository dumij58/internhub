<?php
require_once '../../../includes/config.php';
requireAdmin();
$page_title = 'Manage Admins';
$db = getDB();

// Handle AJAX requests for CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    try {
        // Add Admin
        if (isset($_POST['add_admin'])) {
            $username = trim($_POST['username']);
            $email = trim($_POST['email']);
            $password = $_POST['password'];
            
            if (!$username || !$email || !$password) {
                echo json_encode(['success' => false, 'message' => 'All fields required.']);
                exit;
            }
            
            // Validate email format
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
                exit;
            }
            
            // Check for duplicate
            $stmt = $db->prepare('SELECT user_id FROM users WHERE username = ? OR email = ?');
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Username or email already exists.']);
                exit;
            }
            
            $hash = hashPassword($password);
            $stmt = $db->prepare('INSERT INTO users (username, email, password_hash, user_type_id) VALUES (?, ?, ?, 1)');
            $result = $stmt->execute([$username, $email, $hash]);
            
            if ($result) {
                logActivity('Admin added by admin', "New admin user created: $username ($email)");
                echo json_encode(['success' => true, 'message' => 'Admin added successfully.']);
            } else {
                logActivity('Failed to add admin', "Database error when creating: $username ($email)");
                echo json_encode(['success' => false, 'message' => 'Failed to add admin to database.']);
            }
            exit;
        }
        
        // Edit Admin
        if (isset($_POST['edit_user_id'])) {
            $user_id = intval($_POST['edit_user_id']);
            $username = trim($_POST['edit_username']);
            $email = trim($_POST['edit_email']);
            
            if (!$username || !$email || $user_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'All fields required and valid user ID.']);
                exit;
            }
            
            // Validate email format
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
                exit;
            }
            
            // Check for duplicate (excluding self)
            $stmt = $db->prepare('SELECT user_id FROM users WHERE (username = ? OR email = ?) AND user_id != ?');
            $stmt->execute([$username, $email, $user_id]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Username or email already exists.']);
                exit;
            }
            
            $stmt = $db->prepare('UPDATE users SET username = ?, email = ? WHERE user_id = ? AND user_type_id = 1');
            $result = $stmt->execute([$username, $email, $user_id]);
            
            if ($result && $stmt->rowCount() > 0) {
                logActivity('Admin updated by admin', "Admin user updated: ID $user_id, $username ($email)");
                echo json_encode(['success' => true, 'message' => 'Admin updated successfully.']);
            } else if ($stmt->rowCount() === 0) {
                echo json_encode(['success' => false, 'message' => 'Admin not found or no changes made.']);
            } else {
                logActivity('Failed to update admin', "Database error when updating: ID $user_id");
                echo json_encode(['success' => false, 'message' => 'Failed to update admin.']);
            }
            exit;
        }
        
        // Delete Admin
        if (isset($_POST['delete_user_id'])) {
            $user_id = intval($_POST['delete_user_id']);
            $current_user_id = getCurrentUserId();
            
            if ($user_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid user ID.']);
                exit;
            }
            
            // Prevent admin from deleting themselves
            if ($user_id == $current_user_id) {
                echo json_encode(['success' => false, 'message' => 'You cannot delete your own admin account.']);
                exit;
            }
            
            // Check if this is the last admin
            $stmt = $db->prepare('SELECT COUNT(*) as admin_count FROM users WHERE user_type_id = 1');
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['admin_count'] <= 1) {
                echo json_encode(['success' => false, 'message' => 'Cannot delete the last admin account. At least one admin must remain.']);
                exit;
            }
            
            // Get username for logging before deletion
            $stmt = $db->prepare('SELECT username FROM users WHERE user_id = ? AND user_type_id = 1');
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                echo json_encode(['success' => false, 'message' => 'Admin not found.']);
                exit;
            }
            
            $stmt = $db->prepare('DELETE FROM users WHERE user_id = ? AND user_type_id = 1');
            $result = $stmt->execute([$user_id]);
            
            if ($result && $stmt->rowCount() > 0) {
                logActivity('Admin deleted by admin', "Admin user deleted: ID $user_id, " . $user['username']);
                echo json_encode(['success' => true, 'message' => 'Admin deleted successfully.']);
            } else {
                logActivity('Failed to delete admin', "Database error when deleting: ID $user_id");
                echo json_encode(['success' => false, 'message' => 'Failed to delete admin.']);
            }
            exit;
        }
        
        echo json_encode(['success' => false, 'message' => 'Invalid request.']);
        exit;
        
    } catch (PDOException $e) {
        logActivity('Database error in admin admin management', $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
        exit;
    } catch (Exception $e) {
        logActivity('Error in admin admin management', $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An unexpected error occurred.']);
        exit;
    }
}

require_once '../../../includes/header.php';
?>

<div class="gbt-dash">
    <a href="<?php echo $pages_path; ?>/admin/index.php">← Go Back To Dashboard</a>
</div>

<div class="admin-panel-tasks">
    <h2>Administrators List</h2>
    <button onclick="document.getElementById('addAdminForm').style.display='block'" class="btn btn-primary btn-rg mb-3">Add Admin</button>
    
    <div class="warning-box mb-3">
        <strong>⚠️ Warning:</strong> Admin users have full system access. Only grant admin privileges to trusted personnel.
    </div>
    
    <table width="90%" align="center" class="admin-task-table">
        <tr>
            <th>User ID</th>
            <th>Username</th>
            <th>Email</th>
            <th>Last Login</th>
            <th>Actions</th>
        </tr>
        <?php
        $current_user_id = getCurrentUserId();
        $stmt = $db->query("SELECT user_id, username, email, last_login FROM users WHERE user_type_id = 1 ORDER BY username");
        while ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $is_current_user = ($user['user_id'] == $current_user_id);
            $last_login = $user['last_login'] ? date('Y-m-d H:i', strtotime($user['last_login'])) : 'Never';
            
            echo "<tr data-userid='{$user['user_id']}' data-username='".escape($user['username'])."' data-email='".escape($user['email'])."' align='center' class='table-row'>
                    <td>".escape($user['user_id'])."</td>
                    <td>".escape($user['username']).($is_current_user ? ' <span style="color: #007bff;">(You)</span>' : '')."</td>
                    <td>".escape($user['email'])."</td>
                    <td>$last_login</td>
                    <td>
                        <button class='edit-btn btn btn-primary btn-sm'>Edit</button>";
            
            if (!$is_current_user) {
                echo "<button class='delete-btn btn btn-danger btn-sm' data-userid='".escape($user['user_id'])."'>Delete</button>";
            } else {
                echo "<span class='btn btn-secondary btn-sm disabled' title='Cannot delete your own account'>Delete</span>";
            }
            
            echo "    </td>
                  </tr>";
        }
        ?>
    </table>

    <!-- Add Admin Form -->
    <form id="addAdminForm" style="display:none; margin:20px 0;" method="post">
        <h3>Add New Administrator</h3>
        <input type="hidden" name="add_admin" value="1">
        <div class="form-group">
            <label>Username: <input type="text" name="username" required minlength="3" maxlength="50"></label>
        </div>
        <div class="form-group">
            <label>Email: <input type="email" name="email" required></label>
        </div>
        <div class="form-group">
            <label>Password: <input type="password" name="password" required minlength="6"></label>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Add Admin</button>
            <button type="button" onclick="this.form.style.display='none'; this.form.reset();" class="btn btn-secondary">Cancel</button>
        </div>
    </form>

    <!-- Edit Admin Modal -->
    <div id="editModal" style="display:none; position:fixed; top:20%; left:50%; transform:translate(-50%,0); background:#fff; padding:20px; border:1px solid #ccc; border-radius:8px; box-shadow:0 4px 6px rgba(0,0,0,0.1); z-index:1000; min-width:400px;">
        <form id="editAdminForm" method="post">
            <h3>Edit Administrator</h3>
            <input type="hidden" name="edit_user_id" id="edit_user_id">
            <div class="form-group">
                <label>Username: <input type="text" name="edit_username" id="edit_username" required minlength="3" maxlength="50"></label>
            </div>
            <div class="form-group">
                <label>Email: <input type="email" name="edit_email" id="edit_email" required></label>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Update Admin</button>
                <button type="button" id="closeEditModal" class="btn btn-secondary">Cancel</button>
            </div>
        </form>
    </div>
    
    <!-- Modal Overlay -->
    <div id="modalOverlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:999;"></div>
</div>

<style>
.warning-box {
    background-color: #fff3cd;
    border: 1px solid #ffeaa7;
    color: #856404;
    padding: 12px 16px;
    border-radius: 4px;
    margin: 16px 0;
    border-left: 4px solid #ffc107;
}

.warning-box strong {
    color: #d39e00;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}

.form-group input {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    box-sizing: border-box;
}

.form-actions {
    margin-top: 20px;
    text-align: right;
}

.form-actions button {
    margin-left: 10px;
}

.mb-3 {
    margin-bottom: 1rem;
}

.disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
</style>

<script src="../../../assets/js/admin_admins.js"></script>

<?php require_once '../../../includes/footer.php'; ?>
