
<?php
require_once '../../../includes/config.php';
requireAdmin();
$page_title = 'Manage Applications';
$db = getDB();

// Handle AJAX requests for CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    // Add Application
    if (isset($_POST['add_application'])) {
        $student_id = trim($_POST['student_id']);
        $internship_id = trim($_POST['internship_id']);
        $status = trim($_POST['status']);
        if (!$student_id || !$internship_id) {
            echo json_encode(['success' => false, 'message' => 'Student and internship required.']);
            exit;
        }
        $stmt = $db->prepare('INSERT INTO applications (student_id, internship_id, status) VALUES (?, ?, ?)');
        $stmt->execute([$student_id, $internship_id, $status]);
        echo json_encode(['success' => true, 'message' => 'Application added successfully.']);
        exit;
    }
    // Edit Application
    if (isset($_POST['edit_id'])) {
        $id = intval($_POST['edit_id']);
        $student_id = trim($_POST['edit_student_id']);
        $internship_id = trim($_POST['edit_internship_id']);
        $status = trim($_POST['edit_status']);
        if (!$student_id || !$internship_id) {
            echo json_encode(['success' => false, 'message' => 'Student and internship required.']);
            exit;
        }
        $stmt = $db->prepare('UPDATE applications SET student_id = ?, internship_id = ?, status = ? WHERE application_id = ?');
        $stmt->execute([$student_id, $internship_id, $status, $id]);
        echo json_encode(['success' => true, 'message' => 'Application updated successfully.']);
        exit;
    }
    // Delete Application
    if (isset($_POST['delete_id'])) {
        $id = intval($_POST['delete_id']);
        $stmt = $db->prepare('DELETE FROM applications WHERE application_id = ?');
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Application deleted successfully.']);
        exit;
    }
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

require_once '../../../includes/header.php';
?>

<div class="admin-panel-tasks">
    <h2>Applications</h2>
    <button class="add-btn btn btn-primary btn-rg mb-3">Add Application</button>
    <table width="90%" align="center" class="admin-task-table">
        <tr>
            <th>ID</th>
            <th>Student ID</th>
            <th>Internship ID</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
        <?php
        $stmt = $db->query("SELECT id, student_id, internship_id, status FROM applications");
        while ($application = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr data-id='{$application['id']}' data-studentid='".escape($application['student_id'])."' data-internshipid='".escape($application['internship_id'])."' data-status='".escape($application['status'])."' align='center' class='table-row'>
                    <td>".escape($application['id'])."</td>
                    <td>".escape($application['student_id'])."</td>
                    <td>".escape($application['internship_id'])."</td>
                    <td>".escape($application['status'])."</td>
                    <td>
                        <button class='edit-btn btn btn-primary btn-sm'>Edit</button>
                        <button class='delete-btn btn btn-danger btn-sm' data-id='".escape($application['id'])."'>Delete</button>
                    </td>
                  </tr>";
        }
        ?>
    </table>

    <!-- Add Application Form -->
    <div id="addModal" class="modal">
        <form id="addApplicationForm" method="post">
            <h3>Add Application</h3>
            <input type="hidden" name="add_application" value="1">
            <label class="admin-form-label">Student ID: <input type="text" name="student_id" required></label><br>
            <label class="admin-form-label">Internship ID: <input type="text" name="internship_id" required></label><br>
            <label class="admin-form-label">Status: <input type="text" name="status"></label><br>
            <div class="admin-form-actions">
                <button type="submit" class="btn btn-primary">Add</button>
                <button type="button" id="closeAddModal" class="btn">Cancel</button>
            </div>
        </form>
    </div>

    <!-- Edit Application Modal -->
    <div id="editModal" class="modal">
        <form id="editApplicationForm" method="post">
            <h3>Edit Application</h3>
            <input type="hidden" name="edit_id" id="edit_id">
            <label class="admin-form-label">Student ID: <input type="text" name="edit_student_id" id="edit_student_id" required></label><br>
            <label class="admin-form-label">Internship ID: <input type="text" name="edit_internship_id" id="edit_internship_id" required></label><br>
            <label class="admin-form-label">Status: <input type="text" name="edit_status" id="edit_status"></label><br>
            <div class="admin-form-actions">
                <button type="submit" class="btn btn-primary">Update</button>
                <button type="button" id="closeEditModal" class="btn">Cancel</button>
            </div>
        </form>
    </div>
</div>
<script src="../../../assets/js/admin_applications.js"></script>

<?php require_once '../../../includes/footer.php'; ?>