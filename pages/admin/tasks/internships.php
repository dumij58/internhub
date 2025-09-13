
<?php
require_once '../../../includes/config.php';
requireAdmin();
$page_title = 'Manage Internships';
$db = getDB();

// Handle AJAX requests for CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    // Add Internship
    if (isset($_POST['add_internship'])) {
        $title = trim($_POST['title']);
        $status = trim($_POST['status']);
        if (!$title) {
            echo json_encode(['success' => false, 'message' => 'Title required.']);
            exit;
        }
        $stmt = $db->prepare('INSERT INTO internships (title, status) VALUES (?, ?)');
        $stmt->execute([$title, $status]);
        echo json_encode(['success' => true, 'message' => 'Internship added successfully.']);
        exit;
    }
    // Edit Internship
    if (isset($_POST['edit_id'])) {
        $id = intval($_POST['edit_id']);
        $title = trim($_POST['edit_title']);
        $status = trim($_POST['edit_status']);
        if (!$title) {
            echo json_encode(['success' => false, 'message' => 'Title required.']);
            exit;
        }
        $stmt = $db->prepare('UPDATE internships SET title = ?, status = ? WHERE internship_id = ?');
        $stmt->execute([$title, $status, $id]);
        echo json_encode(['success' => true, 'message' => 'Internship updated successfully.']);
        exit;
    }
    // Delete Internship
    if (isset($_POST['delete_id'])) {
        $id = intval($_POST['delete_id']);
        $stmt = $db->prepare('DELETE FROM internships WHERE internship_id = ?');
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Internship deleted successfully.']);
        exit;
    }
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

require_once '../../../includes/header.php';
?>

<div class="admin-panel-tasks">
    <h2>Internships</h2>
    <button class="add-btn btn btn-primary btn-rg mb-3">Add Internship</button>
    <table width="90%" align="center" class="admin-task-table">
        <tr>
            <th>ID</th>
            <th>Title</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
        <?php
        $stmt = $db->query("SELECT id, title, status FROM internships");
        while ($internship = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr data-id='{$internship['id']}' data-title='".escape($internship['title'])."' data-status='".escape($internship['status'])."' align='center' class='table-row'>
                    <td>".escape($internship['id'])."</td>
                    <td>".escape($internship['title'])."</td>
                    <td>".escape($internship['status'])."</td>
                    <td>
                        <button class='edit-btn btn btn-primary btn-sm'>Edit</button>
                        <button class='delete-btn btn btn-danger btn-sm' data-id='".escape($internship['id'])."'>Delete</button>
                    </td>
                  </tr>";
        }
        ?>
    </table>

    <!-- Add Internship Form -->
    <div id="addModal" class="modal">
        <form id="addInternshipForm" method="post">
            <h3>Add Internship</h3>
            <input type="hidden" name="add_internship" value="1">
            <label class="admin-form-label">Title: <input type="text" name="title" required></label><br>
            <label class="admin-form-label">Status: <input type="text" name="status"></label><br>
            <div class="admin-form-actions">
                <button type="submit" class="btn btn-primary">Add</button>
                <button type="button" id="closeAddModal" class="btn">Cancel</button>
            </div>
        </form>
    </div>

    <!-- Edit Internship Modal -->
    <div id="editModal" class="modal">
        <form id="editInternshipForm" method="post">
            <h3>Edit Internship</h3>
            <input type="hidden" name="edit_id" id="edit_id">
            <label class="admin-form-label">Title: <input type="text" name="edit_title" id="edit_title" required></label><br>
            <label class="admin-form-label">Status: <input type="text" name="edit_status" id="edit_status"></label><br>
            <div class="admin-form-actions">
                <button type="submit" class="btn btn-primary">Update</button>
                <button type="button" id="closeEditModal" class="btn">Cancel</button>
            </div>
        </form>
    </div>
</div>
<script src="../../../assets/js/admin_internships.js"></script>

<?php require_once '../../../includes/footer.php'; ?>