
<?php
require_once '../../../includes/config.php';
//requireAdmin();
$page_title = 'Manage Students';
$db = getDB();

// Handle AJAX requests for CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    try {
        // Add Student
        if (isset($_POST['add_student'])) {
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
            $stmt = $db->prepare('INSERT INTO users (username, email, password_hash, user_type_id) VALUES (?, ?, ?, 2)');
            $result = $stmt->execute([$username, $email, $hash]);
            
            if ($result) {
                logActivity('Student added by admin', "New student user created: $username ($email)");
                echo json_encode(['success' => true, 'message' => 'Student added successfully.']);
            } else {
                logActivity('Failed to add student', "Database error when creating: $username ($email)");
                echo json_encode(['success' => false, 'message' => 'Failed to add student to database.']);
            }
            exit;
        }
        
        // Edit Student
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
            
            $stmt = $db->prepare('UPDATE users SET username = ?, email = ? WHERE user_id = ? AND user_type_id = 2');
            $result = $stmt->execute([$username, $email, $user_id]);
            
            if ($result && $stmt->rowCount() > 0) {
                logActivity('Student updated by admin', "Student user updated: ID $user_id, $username ($email)");
                echo json_encode(['success' => true, 'message' => 'Student updated successfully.']);
            } else if ($stmt->rowCount() === 0) {
                echo json_encode(['success' => false, 'message' => 'Student not found or no changes made.']);
            } else {
                logActivity('Failed to update student', "Database error when updating: ID $user_id");
                echo json_encode(['success' => false, 'message' => 'Failed to update student.']);
            }
            exit;
        }
        
        // Delete Student
        if (isset($_POST['delete_user_id'])) {
            $user_id = intval($_POST['delete_user_id']);
            
            if ($user_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid user ID.']);
                exit;
            }
            
            // Get username for logging before deletion
            $stmt = $db->prepare('SELECT username FROM users WHERE user_id = ? AND user_type_id = 2');
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                echo json_encode(['success' => false, 'message' => 'Student not found.']);
                exit;
            }
            
            $stmt = $db->prepare('DELETE FROM users WHERE user_id = ? AND user_type_id = 2');
            $result = $stmt->execute([$user_id]);
            
            if ($result && $stmt->rowCount() > 0) {
                logActivity('Student deleted by admin', "Student user deleted: ID $user_id, " . $user['username']);
                echo json_encode(['success' => true, 'message' => 'Student deleted successfully.']);
            } else {
                logActivity('Failed to delete student', "Database error when deleting: ID $user_id");
                echo json_encode(['success' => false, 'message' => 'Failed to delete student.']);
            }
            exit;
        }
        
        echo json_encode(['success' => false, 'message' => 'Invalid request.']);
        exit;
        
    } catch (PDOException $e) {
        logActivity('Database error in admin student management', $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
        exit;
    } catch (Exception $e) {
        logActivity('Error in admin student management', $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An unexpected error occurred.']);
        exit;
    }
}

require_once '../../../includes/header.php';
?>

<div class="admin-panel-tasks">
    <h2>Students List</h2>
    <button class="add-btn btn btn-primary btn-rg mb-3">Add Student</button>
    <table width="90%" align="center" class="admin-task-table">
        <tr>
            <th>User ID</th>
            <th>Username</th>
            <th>Email</th>
            <th>Actions</th>
        </tr>
        <?php
        $stmt = $db->query("SELECT user_id, username, email FROM users WHERE user_type_id = 2");
        while ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr data-userid='{$user['user_id']}' data-username='".escape($user['username'])."' data-email='".escape($user['email'])."' align='center' class='table-row'>
                    <td>".escape($user['user_id'])."</td>
                    <td>".escape($user['username'])."</td>
                    <td>".escape($user['email'])."</td>
                    <td>
                        <button class='edit-btn btn btn-primary btn-sm'>Edit</button>
                        <button class='delete-btn btn btn-danger btn-sm' data-userid='".escape($user['user_id'])."'>Delete</button>
                    </td>
                  </tr>";
        }
        ?>
    </table>

    <!-- Add Student Form -->
    <div id="addModal" class="modal">
        <form id="addStudentForm" method="post">
            <h3>Add Student</h3>
            <input type="hidden" name="add_student" value="1">
            <label class="admin-form-label">Username: <input type="text" name="username" required></label><br>
            <label class="admin-form-label">Email: <input type="email" name="email" required></label><br>
            <label class="admin-form-label">Password: <input type="password" name="password" required></label><br>
            <div class="admin-form-actions">
                <button type="submit" class="btn btn-primary">Add</button>
                <button type="button" id="closeAddModal" class="btn">Cancel</button>
            </div>
        </form>
    </div>

    <!-- Edit Student Modal -->
    <div id="editModal" class="modal">
        <form id="editStudentForm" method="post">
            <h3>Edit Student</h3>
            <input type="hidden" name="edit_user_id" id="edit_user_id">
            <label class="admin-form-label">Username: <input type="text" name="edit_username" id="edit_username" required></label><br>
            <label class="admin-form-label">Email: <input type="email" name="edit_email" id="edit_email" required></label><br>
            <div class="admin-form-actions">
                <button type="submit" class="btn btn-primary">Update</button>
                <button type="button" id="closeEditModal" class="btn">Cancel</button>
            </div>
        </form>
    </div>
</div>
<script src="../../../assets/js/admin_students.js"></script>

<?php require_once '../../../includes/footer.php'; ?>