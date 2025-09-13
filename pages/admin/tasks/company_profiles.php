
<?php
require_once '../../../includes/config.php';
requireAdmin();
$page_title = 'Manage Company Profiles';
$db = getDB();

// Handle AJAX requests for CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    // Add Company Profile
    if (isset($_POST['add_company_profile'])) {
        $company_name = trim($_POST['company_name']);
        $company_website = trim($_POST['company_website']);
        if (!$company_name) {
            echo json_encode(['success' => false, 'message' => 'Company name required.']);
            exit;
        }
        $stmt = $db->prepare('INSERT INTO company_profiles (company_name, company_website) VALUES (?, ?)');
        $stmt->execute([$company_name, $company_website]);
        echo json_encode(['success' => true, 'message' => 'Company profile added successfully.']);
        exit;
    }
    // Edit Company Profile
    if (isset($_POST['edit_id'])) {
        $id = intval($_POST['edit_id']);
        $company_name = trim($_POST['edit_company_name']);
        $company_website = trim($_POST['edit_company_website']);
        if (!$company_name) {
            echo json_encode(['success' => false, 'message' => 'Company name required.']);
            exit;
        }
        $stmt = $db->prepare('UPDATE company_profiles SET company_name = ?, company_website = ? WHERE id = ?');
        $stmt->execute([$company_name, $company_website, $id]);
        echo json_encode(['success' => true, 'message' => 'Company profile updated successfully.']);
        exit;
    }
    // Delete Company Profile
    if (isset($_POST['delete_id'])) {
        $id = intval($_POST['delete_id']);
        $stmt = $db->prepare('DELETE FROM company_profiles WHERE id = ?');
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Company profile deleted successfully.']);
        exit;
    }
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

require_once '../../../includes/header.php';
?>

<div class="admin-panel-tasks">
    <h2>Company Profiles</h2>
    <button class="add-btn btn btn-primary btn-rg mb-3">Add Company Profile</button>
    <table width="90%" align="center" class="admin-task-table">
        <tr>
            <th>ID</th>
            <th>Company Name</th>
            <th>Website</th>
            <th>Actions</th>
        </tr>
        <?php
        $stmt = $db->query("SELECT id, company_name, company_website FROM company_profiles");
        while ($profile = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr data-id='{$profile['id']}' data-companyname='".escape($profile['company_name'])."' data-companywebsite='".escape($profile['company_website'])."' align='center' class='table-row'>
                    <td>".escape($profile['id'])."</td>
                    <td>".escape($profile['company_name'])."</td>
                    <td>".escape($profile['company_website'])."</td>
                    <td>
                        <button class='edit-btn btn btn-primary btn-sm'>Edit</button>
                        <button class='delete-btn btn btn-danger btn-sm' data-id='".escape($profile['id'])."'>Delete</button>
                    </td>
                  </tr>";
        }
        ?>
    </table>

    <!-- Add Company Profile Form -->
    <div id="addModal" class="modal">
        <form id="addCompanyProfileForm" method="post">
            <h3>Add Company Profile</h3>
            <input type="hidden" name="add_company_profile" value="1">
            <label class="admin-form-label">Company Name: <input type="text" name="company_name" required></label><br>
            <label class="admin-form-label">Website: <input type="text" name="company_website"></label><br>
            <div class="admin-form-actions">
                <button type="submit" class="btn btn-primary">Add</button>
                <button type="button" id="closeAddModal" class="btn">Cancel</button>
            </div>
        </form>
    </div>

    <!-- Edit Company Profile Modal -->
    <div id="editModal" class="modal">
        <form id="editCompanyProfileForm" method="post">
            <h3>Edit Company Profile</h3>
            <input type="hidden" name="edit_id" id="edit_id">
            <label class="admin-form-label">Company Name: <input type="text" name="edit_company_name" id="edit_company_name" required></label><br>
            <label class="admin-form-label">Website: <input type="text" name="edit_company_website" id="edit_company_website"></label><br>
            <div class="admin-form-actions">
                <button type="submit" class="btn btn-primary">Update</button>
                <button type="button" id="closeEditModal" class="btn">Cancel</button>
            </div>
        </form>
    </div>
</div>
<script src="../../../assets/js/admin_company_profiles.js"></script>

<?php require_once '../../../includes/footer.php'; ?>