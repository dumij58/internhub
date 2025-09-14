
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
        $student_id = trim($_POST['student_id']);
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $phone = trim($_POST['phone']);
        $university = trim($_POST['university']);
        $major = trim($_POST['major']);
        $year_of_study = intval($_POST['year_of_study']) ?: null;
        $gpa = floatval($_POST['gpa']) ?: null;
        $bio = trim($_POST['bio']);
        $skills = trim($_POST['skills']);
        $languages = trim($_POST['languages']);
        $portfolio_url = trim($_POST['portfolio_url']);
        
        if (!$first_name || !$last_name) {
            echo json_encode(['success' => false, 'message' => 'First and last name required.']);
            exit;
        }
        $stmt = $db->prepare('INSERT INTO student_profiles (student_id, first_name, last_name, phone, university, major, year_of_study, gpa, bio, skills, languages, portfolio_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$student_id, $first_name, $last_name, $phone, $university, $major, $year_of_study, $gpa, $bio, $skills, $languages, $portfolio_url]);
        echo json_encode(['success' => true, 'message' => 'Student profile added successfully.']);
        exit;
    }
    // Edit Student Profile
    if (isset($_POST['edit_id'])) {
        $id = intval($_POST['edit_id']);
        $student_id = trim($_POST['edit_student_id']);
        $first_name = trim($_POST['edit_first_name']);
        $last_name = trim($_POST['edit_last_name']);
        $phone = trim($_POST['edit_phone']);
        $university = trim($_POST['edit_university']);
        $major = trim($_POST['edit_major']);
        $year_of_study = intval($_POST['edit_year_of_study']) ?: null;
        $gpa = floatval($_POST['edit_gpa']) ?: null;
        $bio = trim($_POST['edit_bio']);
        $skills = trim($_POST['edit_skills']);
        $languages = trim($_POST['edit_languages']);
        $portfolio_url = trim($_POST['edit_portfolio_url']);
        
        if (!$first_name || !$last_name) {
            echo json_encode(['success' => false, 'message' => 'First and last name required.']);
            exit;
        }
        $stmt = $db->prepare('UPDATE student_profiles SET student_id = ?, first_name = ?, last_name = ?, phone = ?, university = ?, major = ?, year_of_study = ?, gpa = ?, bio = ?, skills = ?, languages = ?, portfolio_url = ? WHERE id = ?');
        $stmt->execute([$student_id, $first_name, $last_name, $phone, $university, $major, $year_of_study, $gpa, $bio, $skills, $languages, $portfolio_url, $id]);
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
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2>Student Profiles</h2>
    </div>
    <table width="95%" align="center" class="admin-task-table">
        <tr>
            <th>ID</th>
            <th>Student ID</th>
            <th>Name</th>
            <th>University</th>
            <th>Major</th>
            <th>Year</th>
            <th>GPA</th>
            <th>Phone</th>
            <th>Skills</th>
            <th>User Email</th>
            <th>Actions</th>
        </tr>
        <?php
        $stmt = $db->query("
            SELECT sp.id, sp.student_id, sp.first_name, sp.last_name, sp.phone, 
                   sp.university, sp.major, sp.year_of_study, sp.gpa, sp.bio, 
                   sp.skills, sp.languages, sp.portfolio_url, sp.resume_path,
                   u.email, u.username, u.created_at
            FROM student_profiles sp 
            LEFT JOIN users u ON sp.user_id = u.user_id 
            ORDER BY sp.id DESC
        ");
        while ($profile = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $full_name = $profile['first_name'] . ' ' . $profile['last_name'];
            $skills_preview = isset($profile['skills']) && strlen($profile['skills']) > 30 ? 
                substr($profile['skills'], 0, 30) . '...' : 
                $profile['skills'];
            $gpa_display = $profile['gpa'] ? number_format($profile['gpa'], 2) : 'N/A';
            
            echo "<tr data-id='{$profile['id']}' 
                      data-studentid='".escape($profile['student_id'])."'
                      data-firstname='".escape($profile['first_name'])."' 
                      data-lastname='".escape($profile['last_name'])."'
                      data-phone='".escape($profile['phone'])."'
                      data-university='".escape($profile['university'])."'
                      data-major='".escape($profile['major'])."'
                      data-yearofstudy='".escape($profile['year_of_study'])."'
                      data-gpa='".escape($profile['gpa'])."'
                      data-bio='".escape($profile['bio'])."'
                      data-skills='".escape($profile['skills'])."'
                      data-languages='".escape($profile['languages'])."'
                      data-portfoliourl='".escape($profile['portfolio_url'])."'
                      data-resumepath='".escape($profile['resume_path'])."'
                      data-email='".escape($profile['email'])."'
                      align='center' class='table-row'>
                    <td>".escape($profile['id'])."</td>
                    <td>".escape($profile['student_id'] ?: 'N/A')."</td>
                    <td><strong>".escape($full_name)."</strong></td>
                    <td>".escape($profile['university'] ?: 'N/A')."</td>
                    <td>".escape($profile['major'] ?: 'N/A')."</td>
                    <td>".escape($profile['year_of_study'] ?: 'N/A')."</td>
                    <td>{$gpa_display}</td>
                    <td>".escape($profile['phone'] ?: 'N/A')."</td>
                    <td title='".escape($profile['skills'])."'>".escape($skills_preview ?: 'N/A')."</td>
                    <td>".escape($profile['email'] ?: 'N/A')."</td>
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
            <div class="form-grid">
                <label class="admin-form-label">Student ID: <input type="text" name="student_id" placeholder="e.g., UOC001"></label>
                <label class="admin-form-label">First Name: <input type="text" name="first_name" required></label>
                <label class="admin-form-label">Last Name: <input type="text" name="last_name" required></label>
                <label class="admin-form-label">Phone: <input type="tel" name="phone" placeholder="+94701234567"></label>
                <label class="admin-form-label">University: <input type="text" name="university" placeholder="e.g., University of Colombo"></label>
                <label class="admin-form-label">Major: <input type="text" name="major" placeholder="e.g., Computer Science"></label>
                <label class="admin-form-label">Year of Study: <input type="number" name="year_of_study" min="1" max="6" placeholder="1-6"></label>
                <label class="admin-form-label">GPA: <input type="number" name="gpa" min="0" max="4" step="0.01" placeholder="0.00-4.00"></label>
                <label class="admin-form-label">Bio: <textarea name="bio" rows="2" placeholder="Brief biography"></textarea></label>
                <label class="admin-form-label">Skills: <textarea name="skills" rows="2" placeholder="Programming languages, technologies, etc."></textarea></label>
                <label class="admin-form-label">Languages: <input type="text" name="languages" placeholder="e.g., English, Sinhala, Tamil"></label>
                <label class="admin-form-label">Portfolio URL: <input type="url" name="portfolio_url" placeholder="https://portfolio.example.com"></label>
            </div>
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
            <div class="form-grid">
                <label class="admin-form-label">Student ID: <input type="text" name="edit_student_id" id="edit_student_id" placeholder="e.g., UOC001"></label>
                <label class="admin-form-label">First Name: <input type="text" name="edit_first_name" id="edit_first_name" required></label>
                <label class="admin-form-label">Last Name: <input type="text" name="edit_last_name" id="edit_last_name" required></label>
                <label class="admin-form-label">Phone: <input type="tel" name="edit_phone" id="edit_phone" placeholder="+94701234567"></label>
                <label class="admin-form-label">University: <input type="text" name="edit_university" id="edit_university" placeholder="e.g., University of Colombo"></label>
                <label class="admin-form-label">Major: <input type="text" name="edit_major" id="edit_major" placeholder="e.g., Computer Science"></label>
                <label class="admin-form-label">Year of Study: <input type="number" name="edit_year_of_study" id="edit_year_of_study" min="1" max="6" placeholder="1-6"></label>
                <label class="admin-form-label">GPA: <input type="number" name="edit_gpa" id="edit_gpa" min="0" max="4" step="0.01" placeholder="0.00-4.00"></label>
                <label class="admin-form-label">Bio: <textarea name="edit_bio" id="edit_bio" rows="2" placeholder="Brief biography"></textarea></label>
                <label class="admin-form-label">Skills: <textarea name="edit_skills" id="edit_skills" rows="2" placeholder="Programming languages, technologies, etc."></textarea></label>
                <label class="admin-form-label">Languages: <input type="text" name="edit_languages" id="edit_languages" placeholder="e.g., English, Sinhala, Tamil"></label>
                <label class="admin-form-label">Portfolio URL: <input type="url" name="edit_portfolio_url" id="edit_portfolio_url" placeholder="https://portfolio.example.com"></label>
            </div>
            <div class="admin-form-actions">
                <button type="submit" class="btn btn-primary">Update</button>
                <button type="button" id="closeEditModal" class="btn">Cancel</button>
            </div>
        </form>
    </div>

    <!-- View Student Profile Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <h3>Student Profile Details</h3>
            <div id="studentDetails" class="profile-details">
                <!-- Details will be populated by JavaScript -->
            </div>
            <div class="admin-form-actions">
                <button type="button" id="closeViewModal" class="btn">Close</button>
            </div>
        </div>
    </div>
</div>
<script src="../../../assets/js/admin_student_profiles.js"></script>

<?php require_once '../../../includes/footer.php'; ?>