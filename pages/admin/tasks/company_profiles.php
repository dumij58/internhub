
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
        $industry_type = trim($_POST['industry_type']);
        $company_website = trim($_POST['company_website']);
        $phone_number = trim($_POST['phone_number']);
        $address = trim($_POST['address']);
        $company_description = trim($_POST['company_description']);
        $verified = isset($_POST['verified']) ? 1 : 0;
        
        if (!$company_name) {
            echo json_encode(['success' => false, 'message' => 'Company name required.']);
            exit;
        }
        // For admin-created profiles, we'll use 0 as a placeholder user_id or create without user_id requirement
        $stmt = $db->prepare('INSERT INTO company_profiles (company_name, industry_type, company_website, phone_number, address, company_description, verified) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$company_name, $industry_type, $company_website, $phone_number, $address, $company_description, $verified]);
        echo json_encode(['success' => true, 'message' => 'Company profile added successfully.']);
        exit;
    }
    // Edit Company Profile
    if (isset($_POST['edit_id'])) {
        $id = intval($_POST['edit_id']);
        $company_name = trim($_POST['edit_company_name']);
        $industry_type = trim($_POST['edit_industry_type']);
        $company_website = trim($_POST['edit_company_website']);
        $phone_number = trim($_POST['edit_phone_number']);
        $address = trim($_POST['edit_address']);
        $company_description = trim($_POST['edit_company_description']);
        $verified = isset($_POST['edit_verified']) ? 1 : 0;
        
        if (!$company_name) {
            echo json_encode(['success' => false, 'message' => 'Company name required.']);
            exit;
        }
        $stmt = $db->prepare('UPDATE company_profiles SET company_name = ?, industry_type = ?, company_website = ?, phone_number = ?, address = ?, company_description = ?, verified = ? WHERE id = ?');
        $stmt->execute([$company_name, $industry_type, $company_website, $phone_number, $address, $company_description, $verified, $id]);
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
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2>Company Profiles</h2>
    </div>
    <table width="95%" align="center" class="admin-task-table">
        <tr>
            <th>ID</th>
            <th>Company Name</th>
            <th>Industry</th>
            <th>Website</th>
            <th>Phone</th>
            <th>Address</th>
            <th>Verified</th>
            <th>User Email</th>
            <th>Actions</th>
        </tr>
        <?php
        $stmt = $db->query("
            SELECT cp.id, cp.company_name, cp.industry_type, cp.company_website, 
                   cp.phone_number, cp.address, cp.verified,
                   u.email, u.username, u.created_at
            FROM company_profiles cp 
            LEFT JOIN users u ON cp.user_id = u.user_id 
            ORDER BY cp.id DESC
        ");
        while ($profile = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $verified_status = $profile['verified'] ? '<span class="badge badge-success">✓ Verified</span>' : '<span class="badge badge-warning">⚠ Unverified</span>';
            
            echo "<tr data-id='{$profile['id']}' 
                      data-companyname='".escape($profile['company_name'])."' 
                      data-industrytype='".escape($profile['industry_type'])."'
                      data-companywebsite='".escape($profile['company_website'])."'
                      data-phonenumber='".escape($profile['phone_number'])."'
                      data-address='".escape($profile['address'])."'
                      data-verified='".escape($profile['verified'])."'
                      align='center' class='table-row'>
                    <td>".escape($profile['id'])."</td>
                    <td><strong>".escape($profile['company_name'])."</strong></td>
                    <td>".escape($profile['industry_type'] ?: 'N/A')."</td>
                    <td>".($profile['company_website'] ? '<a href="'.escape($profile['company_website']).'" target="_blank">'.escape($profile['company_website']).'</a>' : 'N/A')."</td>
                    <td>".escape($profile['phone_number'] ?: 'N/A')."</td>
                    <td>".escape($profile['address'] ?: 'N/A')."</td>
                    <td>{$verified_status}</td>
                    <td>".escape($profile['email'] ?: 'N/A')."</td>
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
            <div class="form-grid">
                <label class="admin-form-label">Company Name: <input type="text" name="company_name" required></label>
                <label class="admin-form-label">Industry Type: <input type="text" name="industry_type" placeholder="e.g., Technology, Healthcare, Finance"></label>
                <label class="admin-form-label">Website: <input type="url" name="company_website" placeholder="https://example.com"></label>
                <label class="admin-form-label">Phone Number: <input type="tel" name="phone_number" placeholder="+94701234567"></label>
                <label class="admin-form-label">Address: <textarea name="address" rows="2" placeholder="Company address"></textarea></label>
                <label class="admin-form-label">Description: <textarea name="company_description" rows="3" placeholder="Brief company description"></textarea></label>
                <label class="admin-form-label">
                    <input type="checkbox" name="verified"> Verified Company
                </label>
            </div>
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
            <div class="form-grid">
                <label class="admin-form-label">Company Name: <input type="text" name="edit_company_name" id="edit_company_name" required></label>
                <label class="admin-form-label">Industry Type: <input type="text" name="edit_industry_type" id="edit_industry_type" placeholder="e.g., Technology, Healthcare, Finance"></label>
                <label class="admin-form-label">Website: <input type="url" name="edit_company_website" id="edit_company_website" placeholder="https://example.com"></label>
                <label class="admin-form-label">Phone Number: <input type="tel" name="edit_phone_number" id="edit_phone_number" placeholder="+94701234567"></label>
                <label class="admin-form-label">Address: <textarea name="edit_address" id="edit_address" rows="2" placeholder="Company address"></textarea></label>
                <label class="admin-form-label">Description: <textarea name="edit_company_description" id="edit_company_description" rows="3" placeholder="Brief company description"></textarea></label>
                <label class="admin-form-label">
                    <input type="checkbox" name="edit_verified" id="edit_verified"> Verified Company
                </label>
            </div>
            <div class="admin-form-actions">
                <button type="submit" class="btn btn-primary">Update</button>
                <button type="button" id="closeEditModal" class="btn">Cancel</button>
            </div>
        </form>
    </div>
</div>
<script src="../../../assets/js/admin_company_profiles.js"></script>

<?php require_once '../../../includes/footer.php'; ?>