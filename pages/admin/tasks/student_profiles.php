
<?php
require_once '../../../includes/config.php';
//requireAdmin();
$page_title = 'Manage Student Profiles';
$db = getDB();

// Handle AJAX requests for CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    // Add Student Profile
    if (isset($_POST['add_student_profile'])) {
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        if (!$first_name || !$last_name) {
            echo json_encode(['success' => false, 'message' => 'First and last name required.']);
            exit;
        }
        $stmt = $db->prepare('INSERT INTO student_profiles (first_name, last_name) VALUES (?, ?)');
        $stmt->execute([$first_name, $last_name]);
        echo json_encode(['success' => true, 'message' => 'Student profile added successfully.']);
        exit;
    }
    // Edit Student Profile
    if (isset($_POST['edit_id'])) {
        $id = intval($_POST['edit_id']);
        $first_name = trim($_POST['edit_first_name']);
        $last_name = trim($_POST['edit_last_name']);
        if (!$first_name || !$last_name) {
            echo json_encode(['success' => false, 'message' => 'First and last name required.']);
            exit;
        }
        $stmt = $db->prepare('UPDATE student_profiles SET first_name = ?, last_name = ? WHERE id = ?');
        $stmt->execute([$first_name, $last_name, $id]);
        echo json_encode(['success' => true, 'message' => 'Student profile updated successfully.']);
        exit;
    }
    // Delete Student Profile
    if (isset($_POST['delete_id'])) {
        $id = intval($_POST['delete_id']);
        $stmt = $db->prepare('DELETE FROM student_profiles WHERE id = ?');
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Student profile deleted successfully.']);
        exit;
    }
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

require_once '../../../includes/header.php';
?>

<div class="admin-panel-tasks">
    <h2>Student Profiles</h2>
    <button class="add-btn btn btn-primary btn-rg mb-3">Add Student Profile</button>
    <table width="90%" align="center" class="admin-task-table">
        <tr>
            <th>ID</th>
            <th>First Name</th>
            <th>Last Name</th>
            <th>Actions</th>
        </tr>
        <?php
        $stmt = $db->query("SELECT id, first_name, last_name FROM student_profiles");
        while ($profile = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr data-id='{$profile['id']}' data-firstname='".escape($profile['first_name'])."' data-lastname='".escape($profile['last_name'])."' align='center' class='table-row'>
                    <td>".escape($profile['id'])."</td>
                    <td>".escape($profile['first_name'])."</td>
                    <td>".escape($profile['last_name'])."</td>
                    <td>
                        <button class='edit-btn btn btn-primary btn-sm'>Edit</button>
                        <button class='delete-btn btn btn-danger btn-sm' data-id='".escape($profile['id'])."'>Delete</button>
                    </td>
                  </tr>";
        }
        ?>
    </table>

    <!-- Add Student Profile Form -->
    <div id="addModal" class="modal">
        <form id="addStudentProfileForm" method="post">
            <h3>Add Student Profile</h3>
            <input type="hidden" name="add_student_profile" value="1">
            <label class="admin-form-label">First Name: <input type="text" name="first_name" required></label><br>
            <label class="admin-form-label">Last Name: <input type="text" name="last_name" required></label><br>
            <div class="admin-form-actions">
                <button type="submit" class="btn btn-primary">Add</button>
                <button type="button" id="closeAddModal" class="btn">Cancel</button>
            </div>
        </form>
    </div>

    <!-- Edit Student Profile Modal -->
    <div id="editModal" class="modal">
        <form id="editStudentProfileForm" method="post">
            <h3>Edit Student Profile</h3>
            <input type="hidden" name="edit_id" id="edit_id">
            <label class="admin-form-label">First Name: <input type="text" name="edit_first_name" id="edit_first_name" required></label><br>
            <label class="admin-form-label">Last Name: <input type="text" name="edit_last_name" id="edit_last_name" required></label><br>
            <div class="admin-form-actions">
                <button type="submit" class="btn btn-primary">Update</button>
                <button type="button" id="closeEditModal" class="btn">Cancel</button>
            </div>
        </form>
    </div>
</div>
<script src="../../../assets/js/admin_student_profiles.js"></script>

<?php require_once '../../../includes/footer.php'; ?>