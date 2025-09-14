<?php
require_once '../../includes/config.php';
requireLogin();

// Check if user is company
if ($_SESSION['role'] !== 'company') {
    logActivity('Unauthorized Access Attempt', 'User tried to access company internship editing');
    header('Location: ../../pages/error.php?error_message=403 - Access denied');
    exit;
}

$db = getDB();
$user_id = $_SESSION['user_id'];
$internship_id = intval($_GET['id'] ?? 0);

if (!$internship_id) {
    $_SESSION['error_message'] = 'Invalid internship ID.';
    header('Location: manage_internships.php');
    exit;
}

// Get company profile
$stmt = $db->prepare("SELECT * FROM company_profiles WHERE user_id = ?");
$stmt->execute([$user_id]);
$company_profile = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$company_profile) {
    $_SESSION['error_message'] = 'Company profile not found.';
    header('Location: dashboard.php');
    exit;
}

// Get internship details and verify ownership
$stmt = $db->prepare("SELECT i.*, ic.name as category_name 
                      FROM internships i 
                      LEFT JOIN internship_categories ic ON i.category_id = ic.id 
                      WHERE i.id = ? AND i.company_id = ?");
$stmt->execute([$internship_id, $company_profile['id']]);
$internship = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$internship) {
    $_SESSION['error_message'] = 'Internship not found or access denied.';
    header('Location: manage_internships.php');
    exit;
}

// Get internship categories
$categories_stmt = $db->query("SELECT * FROM internship_categories ORDER BY name");
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if internship has applications
$app_check_stmt = $db->prepare("SELECT COUNT(*) FROM applications WHERE internship_id = ?");
$app_check_stmt->execute([$internship_id]);
$has_applications = $app_check_stmt->fetchColumn() > 0;

$page_title = 'Edit Internship - ' . $internship['title'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate and sanitize input
        $title = trim($_POST['title']);
        $category_id = intval($_POST['category_id']);
        $description = trim($_POST['description']);
        $requirements = trim($_POST['requirements']);
        $responsibilities = trim($_POST['responsibilities']);
        $location = trim($_POST['location']);
        $duration_months = intval($_POST['duration_months']);
        $salary = floatval($_POST['salary']);
        $application_deadline = $_POST['application_deadline'];
        $max_applicants = intval($_POST['max_applicants']);
        $remote_option = isset($_POST['remote_option']) ? 1 : 0;
        $experience_level = $_POST['experience_level'];
        $status = $_POST['status'];

        // Validation
        $errors = [];
        
        if (empty($title)) $errors[] = 'Title is required';
        if (empty($category_id)) $errors[] = 'Category is required';
        if (empty($description)) $errors[] = 'Description is required';
        if (empty($location) && !$remote_option) $errors[] = 'Location is required for non-remote positions';
        if ($duration_months <= 0) $errors[] = 'Duration must be positive';
        if (empty($application_deadline)) $errors[] = 'Application deadline is required';
        
        // Date validations - only if changing dates and internship hasn't started
        // Since start_date is removed, we only validate application deadline
        if (strtotime($application_deadline) <= time()) {
            $errors[] = 'Application deadline must be in the future';
        }

        // If has applications, restrict certain changes
        if ($has_applications) {
            // Check if critical fields are being changed
            if ($category_id != $internship['category_id']) {
                $errors[] = 'Cannot change category when applications exist';
            }
            if ($duration_months != $internship['duration_months']) {
                $errors[] = 'Cannot change duration when applications exist';
            }
            // Allow other changes but warn user
        }

        if (empty($errors)) {
            // Update internship
            $sql = "UPDATE internships SET 
                    title = ?, category_id = ?, description = ?, requirements = ?, 
                    responsibilities = ?, location = ?, duration_months = ?, salary = ?, 
                    application_deadline = ?, max_applicants = ?, 
                    status = ?, remote_option = ?, experience_level = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE id = ? AND company_id = ?";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $title, $category_id, $description, $requirements, 
                $responsibilities, $location, $duration_months, $salary, 
                $application_deadline, $max_applicants, 
                $status, $remote_option, $experience_level, $internship_id, $company_profile['id']
            ]);

            logActivity('Internship Updated', "Updated internship: $title (ID: $internship_id)");
            
            $_SESSION['success_message'] = 'Internship updated successfully!';
            header('Location: manage_internships.php');
            exit;
        }
    } catch (Exception $e) {
        $errors[] = 'An error occurred while updating the internship. Please try again.';
        error_log($e->getMessage());
    }
}

require_once '../../includes/header.php';
?>

<div class="edit-container">
    <div class="edit-header">
        <div class="header-content">
            <h1>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-pencil-square" viewBox="0 0 16 16">
                    <path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z"/>
                    <path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5v11z"/>
                </svg>
                Edit Internship
            </h1>
            <p>Update your internship posting details</p>
        </div>
        <div class="header-actions">
            <a href="manage_internships.php" class="btn btn-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5a.5.5 0 0 0 .5-.5z"/>
                </svg>
                Back to Manage
            </a>
        </div>
    </div>

    <?php if ($has_applications): ?>
        <div class="alert alert-warning">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-exclamation-triangle" viewBox="0 0 16 16">
                <path d="M7.938 2.016A.13.13 0 0 1 8.002 2a.13.13 0 0 1 .063.016.146.146 0 0 1 .054.057l6.857 11.667c.036.06.035.124.002.183a.163.163 0 0 1-.054.06.116.116 0 0 1-.066.017H1.146a.115.115 0 0 1-.066-.017.163.163 0 0 1-.054-.06.176.176 0 0 1 .002-.183L7.884 2.073a.147.147 0 0 1 .054-.057zm1.044-.45a1.13 1.13 0 0 0-2.008 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566z"/>
                <path d="M7.002 12a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 5.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995z"/>
            </svg>
            <strong>Notice:</strong> This internship has received applications. Some fields like category and duration cannot be changed to maintain fairness to applicants.
        </div>
    <?php endif; ?>

    <?php if (isset($errors) && !empty($errors)): ?>
        <div class="alert alert-danger">
            <h4>Please fix the following errors:</h4>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo escape($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form id="editInternshipForm" method="POST" class="edit-form">
        <div class="form-section">
            <h3>Basic Information</h3>
            
            <div class="form-grid">
                <div class="form-group full-width">
                    <label for="title" class="form-label">
                        Internship Title *
                    </label>
                    <input type="text" 
                           class="form-control" 
                           id="title" 
                           name="title" 
                           value="<?php echo escape($_POST['title'] ?? $internship['title']); ?>" 
                           required>
                </div>

                <div class="form-group">
                    <label for="category_id" class="form-label">Category *</label>
                    <select class="form-control" id="category_id" name="category_id" required <?php echo $has_applications ? 'disabled' : ''; ?>>
                        <option value="">Select a category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" 
                                    <?php echo (($_POST['category_id'] ?? $internship['category_id']) == $category['id']) ? 'selected' : ''; ?>>
                                <?php echo escape($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($has_applications): ?>
                        <input type="hidden" name="category_id" value="<?php echo $internship['category_id']; ?>">
                        <small class="form-text text-muted">Cannot be changed due to existing applications</small>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="experience_level" class="form-label">Experience Level *</label>
                    <select class="form-control" id="experience_level" name="experience_level" required>
                        <option value="">Select level</option>
                        <option value="beginner" <?php echo (($_POST['experience_level'] ?? $internship['experience_level']) === 'beginner') ? 'selected' : ''; ?>>
                            Beginner (No experience required)
                        </option>
                        <option value="intermediate" <?php echo (($_POST['experience_level'] ?? $internship['experience_level']) === 'intermediate') ? 'selected' : ''; ?>>
                            Intermediate (Some experience preferred)
                        </option>
                        <option value="advanced" <?php echo (($_POST['experience_level'] ?? $internship['experience_level']) === 'advanced') ? 'selected' : ''; ?>>
                            Advanced (Significant experience required)
                        </option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="duration_months" class="form-label">Duration (months) *</label>
                    <input type="number" 
                           class="form-control" 
                           id="duration_months" 
                           name="duration_months" 
                           value="<?php echo escape($_POST['duration_months'] ?? $internship['duration_months']); ?>" 
                           min="1" 
                           max="12" 
                           required
                           <?php echo $has_applications ? 'readonly' : ''; ?>>
                    <?php if ($has_applications): ?>
                        <small class="form-text text-muted">Cannot be changed due to existing applications</small>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="salary" class="form-label">Monthly Salary (LKR)</label>
                    <input type="number" 
                           class="form-control" 
                           id="salary" 
                           name="salary" 
                           value="<?php echo escape($_POST['salary'] ?? $internship['salary']); ?>" 
                           min="0" 
                           step="1000">
                </div>

                <div class="form-group">
                    <label for="max_applicants" class="form-label">Maximum Applicants</label>
                    <input type="number" 
                           class="form-control" 
                           id="max_applicants" 
                           name="max_applicants" 
                           value="<?php echo escape($_POST['max_applicants'] ?? $internship['max_applicants']); ?>" 
                           min="1" 
                           max="500">
                </div>
            </div>
        </div>

        <div class="form-section">
            <h3>Location & Schedule</h3>
            
            <div class="form-group">
                <div class="checkbox-group">
                    <input type="checkbox" 
                           id="remote_option" 
                           name="remote_option" 
                           value="1" 
                           <?php echo (($_POST['remote_option'] ?? $internship['remote_option']) ? 'checked' : ''); ?>>
                    <label for="remote_option" class="checkbox-label">
                        Remote Work Option Available
                    </label>
                </div>
            </div>

            <div class="form-group" id="location-group">
                <label for="location" class="form-label">Office Location *</label>
                <input type="text" 
                       class="form-control" 
                       id="location" 
                       name="location" 
                       value="<?php echo escape($_POST['location'] ?? $internship['location']); ?>" 
                       placeholder="e.g., Colombo, Kandy, Galle">
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label for="application_deadline" class="form-label">Application Deadline *</label>
                    <input type="date" 
                           class="form-control" 
                           id="application_deadline" 
                           name="application_deadline" 
                           value="<?php echo escape($_POST['application_deadline'] ?? $internship['application_deadline']); ?>" 
                           required>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h3>Detailed Description</h3>
            
            <div class="form-group">
                <label for="description" class="form-label">Job Description *</label>
                <textarea class="form-control" 
                          id="description" 
                          name="description" 
                          rows="6" 
                          required><?php echo escape($_POST['description'] ?? $internship['description']); ?></textarea>
            </div>

            <div class="form-group">
                <label for="responsibilities" class="form-label">Key Responsibilities</label>
                <textarea class="form-control" 
                          id="responsibilities" 
                          name="responsibilities" 
                          rows="5"><?php echo escape($_POST['responsibilities'] ?? $internship['responsibilities']); ?></textarea>
            </div>

            <div class="form-group">
                <label for="requirements" class="form-label">Requirements & Qualifications</label>
                <textarea class="form-control" 
                          id="requirements" 
                          name="requirements" 
                          rows="5"><?php echo escape($_POST['requirements'] ?? $internship['requirements']); ?></textarea>
            </div>
        </div>

        <div class="form-section">
            <h3>Publishing Status</h3>
            
            <div class="radio-group">
                <div class="radio-option">
                    <input type="radio" id="status_draft" name="status" value="draft" 
                           <?php echo (($_POST['status'] ?? $internship['status']) === 'draft') ? 'checked' : ''; ?>>
                    <label for="status_draft" class="radio-label">
                        <div class="radio-content">
                            <h5>Draft</h5>
                            <p>Save changes but keep internship as draft</p>
                        </div>
                    </label>
                </div>
                
                <div class="radio-option">
                    <input type="radio" id="status_published" name="status" value="published" 
                           <?php echo (($_POST['status'] ?? $internship['status']) === 'published') ? 'checked' : ''; ?>>
                    <label for="status_published" class="radio-label">
                        <div class="radio-content">
                            <h5>Published</h5>
                            <p>Make internship visible to students</p>
                        </div>
                    </label>
                </div>
                
                <div class="radio-option">
                    <input type="radio" id="status_closed" name="status" value="closed" 
                           <?php echo (($_POST['status'] ?? $internship['status']) === 'closed') ? 'checked' : ''; ?>>
                    <label for="status_closed" class="radio-label">
                        <div class="radio-content">
                            <h5>Closed</h5>
                            <p>Close internship to new applications</p>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <a href="manage_internships.php" class="btn btn-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x-circle" viewBox="0 0 16 16">
                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                    <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                </svg>
                Cancel
            </a>
            <button type="submit" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check-circle" viewBox="0 0 16 16">
                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                    <path d="M10.97 4.97a.235.235 0 0 0-.02.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.061L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-1.071-1.05z"/>
                </svg>
                Save Changes
            </button>
        </div>
    </form>
</div>

<script>
// Remote work toggle functionality
document.addEventListener('DOMContentLoaded', function() {
    const remoteCheckbox = document.getElementById('remote_option');
    const locationGroup = document.getElementById('location-group');
    const locationInput = document.getElementById('location');
    
    function updateLocationField() {
        if (remoteCheckbox.checked) {
            locationGroup.style.opacity = '0.6';
            locationInput.required = false;
            locationInput.placeholder = 'Optional - for hybrid positions';
        } else {
            locationGroup.style.opacity = '1';
            locationInput.required = true;
            locationInput.placeholder = 'e.g., Colombo, Kandy, Galle';
        }
    }
    
    updateLocationField(); // Set initial state
    remoteCheckbox.addEventListener('change', updateLocationField);
});
</script>

<?php require_once '../../includes/footer.php'; ?>
