
<?php
require_once '../../../includes/config.php';
requireAdmin();
$page_title = 'Manage Companies';
$db = getDB();

// Handle AJAX requests for CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    // Add Company
    if (isset($_POST['add_company'])) {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        if (!$username || !$email || !$password) {
            echo json_encode(['success' => false, 'message' => 'All fields required.']);
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
        $stmt = $db->prepare('INSERT INTO users (username, email, password_hash, user_type_id) VALUES (?, ?, ?, 3)');
        $stmt->execute([$username, $email, $hash]);
        echo json_encode(['success' => true, 'message' => 'Company added successfully.']);
        exit;
    }
    // Edit Company
    if (isset($_POST['edit_user_id'])) {
        $user_id = intval($_POST['edit_user_id']);
        $username = trim($_POST['edit_username']);
        $email = trim($_POST['edit_email']);
        if (!$username || !$email) {
            echo json_encode(['success' => false, 'message' => 'All fields required.']);
            exit;
        }
        // Check for duplicate (excluding self)
        $stmt = $db->prepare('SELECT user_id FROM users WHERE (username = ? OR email = ?) AND user_id != ?');
        $stmt->execute([$username, $email, $user_id]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Username or email already exists.']);
            exit;
        }
        $stmt = $db->prepare('UPDATE users SET username = ?, email = ? WHERE user_id = ? AND user_type_id = 3');
        $stmt->execute([$username, $email, $user_id]);
        echo json_encode(['success' => true, 'message' => 'Company updated successfully.']);
        exit;
    }
    // Delete Company
    if (isset($_POST['delete_user_id'])) {
        $user_id = intval($_POST['delete_user_id']);
        $stmt = $db->prepare('DELETE FROM users WHERE user_id = ? AND user_type_id = 3');
        $stmt->execute([$user_id]);
        echo json_encode(['success' => true, 'message' => 'Company deleted successfully.']);
        exit;
    }
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

require_once '../../../includes/header.php';
?>

<div class="admin-panel-tasks">
    <h2>Companies List</h2>
    <button class="add-btn btn btn-primary btn-rg mb-3">Add Company</button>
    <table width="90%" align="center" class="admin-task-table">
        <tr>
            <th>User ID</th>
            <th>Username</th>
            <th>Email</th>
            <th>Actions</th>
        </tr>
        <?php
        $stmt = $db->query("SELECT user_id, username, email FROM users WHERE user_type_id = 3");
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

    <!-- Add Company Form -->
    <div id="addModal" class="modal">
        <form id="addCompanyForm" method="post">
            <h3>Add Company</h3>
            <input type="hidden" name="add_company" value="1">
            <label class="admin-form-label">Username: <input type="text" name="username" required></label><br>
            <label class="admin-form-label">Email: <input type="email" name="email" required></label><br>
            <label class="admin-form-label">Password: <input type="password" name="password" required></label><br>
            <div class="admin-form-actions">
                <button type="submit" class="btn btn-primary">Add</button>
                <button type="button" id="closeAddModal" class="btn">Cancel</button>
            </div>
        </form>
    </div>

    <!-- Edit Company Modal -->
    <div id="editModal" class="modal">
        <form id="editCompanyForm" method="post">
            <h3>Edit Company</h3>
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
<script src="../../../assets/js/admin_companies.js"></script>

<?php require_once '../../../includes/footer.php'; ?>