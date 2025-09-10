<?php
require_once '../../includes/config.php';
requireLogin();

// Check if user is company
if ($_SESSION['role'] !== 'company') {
    logActivity('Unauthorized Access Attempt', 'User tried to access company internship posting');
    header('Location: ../../pages/error.php?error_message=403 - Access denied');
    exit;
}

$db = getDB();
$user_id = $_SESSION['user_id'];
$page_title = 'Post New Internship';

// Get company profile
$stmt = $db->prepare("SELECT * FROM company_profiles WHERE user_id = ?");
$stmt->execute([$user_id]);
$company_profile = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$company_profile) {
    $_SESSION['error_message'] = 'Please complete your company profile before posting internships.';
    header('Location: update_profile.php');
    exit;
}

// Get internship categories
$categories_stmt = $db->query("SELECT * FROM internship_categories ORDER BY name");
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

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
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $max_applicants = intval($_POST['max_applicants']);
        $remote_option = isset($_POST['remote_option']) ? 1 : 0;
        $experience_level = $_POST['experience_level'];
        $status = $_POST['status']; // 'draft' or 'published'

        // Validation
        $errors = [];

        if (empty($title)) $errors[] = 'Title is required';
        if (empty($category_id)) $errors[] = 'Category is required';
        if (empty($description)) $errors[] = 'Description is required';
        if (empty($location) && !$remote_option) $errors[] = 'Location is required for non-remote positions';
        if ($duration_months <= 0) $errors[] = 'Duration must be positive';
        if (empty($application_deadline)) $errors[] = 'Application deadline is required';
        if (empty($start_date)) $errors[] = 'Start date is required';

        // Date validations
        if (strtotime($application_deadline) <= time()) {
            $errors[] = 'Application deadline must be in the future';
        }
        if (strtotime($start_date) <= strtotime($application_deadline)) {
            $errors[] = 'Start date must be after application deadline';
        }
        if (!empty($end_date) && strtotime($end_date) <= strtotime($start_date)) {
            $errors[] = 'End date must be after start date';
        }

        if (empty($errors)) {
            // Insert internship
            $sql = "INSERT INTO internships (
                title, company_id, category_id, description, requirements, 
                responsibilities, location, duration_months, salary, 
                application_deadline, start_date, end_date, max_applicants, 
                status, remote_option, experience_level, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $db->prepare($sql);
            $stmt->execute([
                $title,
                $company_profile['id'],
                $category_id,
                $description,
                $requirements,
                $responsibilities,
                $location,
                $duration_months,
                $salary,
                $application_deadline,
                $start_date,
                $end_date,
                $max_applicants,
                $status,
                $remote_option,
                $experience_level,
                $user_id
            ]);

            logActivity('Internship Posted', "Posted internship: $title with status: $status");

            $success_message = $status === 'published' ?
                'Internship posted successfully and is now live!' :
                'Internship saved as draft successfully!';

            $_SESSION['success_message'] = $success_message;
            header('Location: dashboard.php');
            exit;
        }
    } catch (Exception $e) {
        $errors[] = 'An error occurred while posting the internship. Please try again.';
        error_log($e->getMessage());
    }
}

require_once '../../includes/header.php';
?>

<div class="posting-container">
    <div class="posting-header">
        <h1>
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-plus-circle" viewBox="0 0 16 16">
                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z" />
                <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z" />
            </svg>
            Post New Internship
        </h1>
    </div>

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

    <form id="internshipForm" method="POST" class="internship-form">
        <!-- Step 1: Basic Information -->
        <div class="form-step active" id="step1">
            <div class="step-header">
                <h3>
                    <span class="step-number">1</span>
                    Basic Information
                </h3>
            </div>

            <div class="form-grid">
                <div class="form-group full-width">
                    <label for="title" class="form-label">
                        Internship Title *
                    </label>
                    <input type="text"
                        class="form-control"
                        id="title"
                        name="title"
                        value="<?php echo escape($_POST['title'] ?? ''); ?>"
                        placeholder="e.g., Frontend Developer Intern, Marketing Assistant"
                        required>
                </div>

                <div class="form-group">
                    <label for="category_id" class="form-label">Category *</label>
                    <select class="form-control" id="category_id" name="category_id" required>
                        <option value="">Select a category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>"
                                <?php echo (($_POST['category_id'] ?? '') == $category['id']) ? 'selected' : ''; ?>>
                                <?php echo escape($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="experience_level" class="form-label">Experience Level *</label>
                    <select class="form-control" id="experience_level" name="experience_level" required>
                        <option value="">Select level</option>
                        <option value="beginner" <?php echo (($_POST['experience_level'] ?? '') === 'beginner') ? 'selected' : ''; ?>>
                            Beginner (No experience required)
                        </option>
                        <option value="intermediate" <?php echo (($_POST['experience_level'] ?? '') === 'intermediate') ? 'selected' : ''; ?>>
                            Intermediate (Some experience preferred)
                        </option>
                        <option value="advanced" <?php echo (($_POST['experience_level'] ?? '') === 'advanced') ? 'selected' : ''; ?>>
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
                        value="<?php echo escape($_POST['duration_months'] ?? '3'); ?>"
                        min="1"
                        max="12"
                        required>
                </div>

                <div class="form-group">
                    <label for="salary" class="form-label">
                        Monthly Salary (LKR)
                        <small>Enter 0 if unpaid</small>
                    </label>
                    <input type="number"
                        class="form-control"
                        id="salary"
                        name="salary"
                        value="<?php echo escape($_POST['salary'] ?? '0'); ?>"
                        min="0"
                        step="1000">
                </div>

                <div class="form-group">
                    <label for="max_applicants" class="form-label">Maximum Applicants</label>
                    <input type="number"
                        class="form-control"
                        id="max_applicants"
                        name="max_applicants"
                        value="<?php echo escape($_POST['max_applicants'] ?? '50'); ?>"
                        min="1"
                        max="500">
                </div>
            </div>

            <div class="step-navigation">
                <div><!-- Keep this div for spacing --></div>
                <button type="button" class="btn btn-primary btn-sm btn-icon" onclick="nextStep()">
                    Next Step
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-arrow-right" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Step 2: Location & Schedule -->
        <div class="form-step" id="step2">
            <div class="step-header">
                <h3>
                    <span class="step-number">2</span>
                    Location & Schedule
                </h3>
            </div>

            <div class="form-group">
                <div class="checkbox-group">
                    <label for="remote_option" class="checkbox-label">
                        <div class="label-content">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-house" viewBox="0 0 16 16">
                                <path fill-rule="evenodd" d="M2 13.5V7h1v6.5a.5.5 0 0 0 .5.5h9a.5.5 0 0 0 .5-.5V7h1v6.5a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 2 13.5zm11-11V6l-2-2V2.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5z" />
                                <path fill-rule="evenodd" d="M7.293 1.5a1 1 0 0 1 1.414 0l6.647 6.646a.5.5 0 0 1-.708.708L8 2.207 1.354 8.854a.5.5 0 1 1-.708-.708L7.293 1.5z" />
                            </svg>
                            Remote Work Option Available
                        </div>
                        <input type="checkbox"
                            id="remote_option"
                            name="remote_option"
                            value="1"
                            <?php echo (isset($_POST['remote_option'])) ? 'checked' : ''; ?>>
                    </label>
                </div>
            </div>

            <div class="form-group" id="location-group">
                <label for="location" class="form-label">
                    Office Location *
                    <small>City, District or full address</small>
                </label>
                <input type="text"
                    class="form-control"
                    id="location"
                    name="location"
                    value="<?php echo escape($_POST['location'] ?? ''); ?>"
                    placeholder="e.g., Colombo, Kandy, Galle">
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label for="application_deadline" class="form-label">Application Deadline *</label>
                    <input type="date"
                        class="form-control"
                        id="application_deadline"
                        name="application_deadline"
                        value="<?php echo escape($_POST['application_deadline'] ?? ''); ?>"
                        min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                        required>
                </div>

                <div class="form-group">
                    <label for="start_date" class="form-label">Start Date *</label>
                    <input type="date"
                        class="form-control"
                        id="start_date"
                        name="start_date"
                        value="<?php echo escape($_POST['start_date'] ?? ''); ?>"
                        required>
                </div>

                <div class="form-group">
                    <label for="end_date" class="form-label">
                        End Date
                        <small>Optional - leave blank for flexible duration</small>
                    </label>
                    <input type="date"
                        class="form-control"
                        id="end_date"
                        name="end_date"
                        value="<?php echo escape($_POST['end_date'] ?? ''); ?>">
                </div>
            </div>

            <div class="step-navigation">
                <button type="button" class="btn btn-secondary btn-icon btn-sm" onclick="prevStep()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5a.5.5 0 0 0 .5-.5z" />
                    </svg>
                    Previous
                </button>
                <button type="button" class="btn btn-primary btn-icon btn-sm" onclick="nextStep()">
                    Next Step
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-right" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Step 3: Details -->
        <div class="form-step" id="step3">
            <div class="step-header">
                <h3>
                    <span class="step-number">3</span>
                    Detailed Description
                </h3>
                <p>Provide comprehensive information about the role</p>
            </div>

            <div class="form-group">
                <label for="description" class="form-label">
                    Job Description *
                    <small>Describe the internship opportunity, company culture, and what interns will gain</small>
                </label>
                <textarea class="form-control rich-text"
                    id="description"
                    name="description"
                    rows="6"
                    placeholder="Provide a detailed description of the internship opportunity..."
                    required><?php echo escape($_POST['description'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label for="responsibilities" class="form-label">
                    Key Responsibilities
                    <small>What will the intern be doing day-to-day?</small>
                </label>
                <textarea class="form-control rich-text"
                    id="responsibilities"
                    name="responsibilities"
                    rows="5"
                    placeholder="• List the main tasks and responsibilities&#10;• Use bullet points for clarity&#10;• Include learning opportunities"><?php echo escape($_POST['responsibilities'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label for="requirements" class="form-label">
                    Requirements & Qualifications
                    <small>Skills, education, and experience needed</small>
                </label>
                <textarea class="form-control rich-text"
                    id="requirements"
                    name="requirements"
                    rows="5"
                    placeholder="• Educational qualifications&#10;• Technical skills required&#10;• Soft skills preferred&#10;• Any specific requirements"><?php echo escape($_POST['requirements'] ?? ''); ?></textarea>
            </div>

            <div class="step-navigation">
                <button type="button" class="btn btn-secondary btn-icon btn-sm" onclick="prevStep()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5a.5.5 0 0 0 .5-.5z" />
                    </svg>
                    Previous
                </button>
                <button type="button" class="btn btn-primary btn-icon btn-sm" onclick="nextStep()">
                    Review & Publish
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-right" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Step 4: Review & Publish -->
        <div class="form-step" id="step4">
            <div class="step-header">
                <h3>
                    <span class="step-number">4</span>
                    Review & Publish
                </h3>
            </div>

            <div class="preview-container">
                <div class="preview-header">
                    <h4>Preview</h4>
                    <p>This is how your internship will appear to students</p>
                </div>

                <div class="internship-preview" id="internshipPreview">
                    <!-- Preview content will be populated by JavaScript -->
                </div>
            </div>

            <div class="publish-options">
                <h4>Publishing Options</h4>
                <div class="radio-group">
                    <div class="radio-option">
                        <div class="label-content">
                            <label for="save_draft" class="radio-label">
                                <div class="radio-content">
                                    <h5>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-file-earmark-text" viewBox="0 0 16 16">
                                            <path d="M5.5 7a.5.5 0 0 0 0 1h5a.5.5 0 0 0 0-1h-5zM5 9.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5zm0 2a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5z" />
                                            <path d="M9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V4.5L9.5 0zm0 1v2A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5z" />
                                        </svg>
                                        Save as Draft
                                    </h5>
                                    <p>Save your internship posting to review and publish later</p>
                                </div>
                                <input type="radio" id="save_draft" name="status" value="draft" checked>
                            </label>
                        </div>
                    </div>

                    <div class="radio-option">
                        <div class="label-content">
                            <label for="publish_now" class="radio-label">
                                <div class="radio-content">
                                    <h5>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-rocket-takeoff" viewBox="0 0 16 16">
                                            <path d="M9.752 6.193c.599.63 1.303.929 1.976.929 1.06 0 1.857-.659 1.857-1.54 0-.577-.315-1.077-.793-1.372C12.498 4.055 12 4 11.5 4c-.605 0-1.07.082-1.384.193a4.924 4.924 0 0 0-.364.193zm-2.495-1.83c-.03-.17-.032-.353-.032-.535C7.225 2.025 8.58.778 10.362.778 12.146.778 13.5 2.025 13.5 3.828c0 .182-.002.365-.032.535-.149.835-.66 1.518-1.302 1.518-.641 0-1.153-.683-1.302-1.518zM7.225 5.5c-.03-.17-.032-.353-.032-.535C7.193 3.163 8.548 1.916 10.33 1.916c1.782 0 3.137 1.247 3.137 2.049 0 .182-.002.365-.032.535-.149.835-.66 1.518-1.302 1.518-.641 0-1.153-.683-1.302-1.518z" />
                                            <path d="M9.5 6.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 0 1h-1a.5.5 0 0 1-.5-.5zM6.5 14.5a.5.5 0 0 1-1 0V9a.5.5 0 0 1 1 0v5.5zm3 0a.5.5 0 0 1-1 0V9a.5.5 0 0 1 1 0v5.5z" />
                                        </svg>
                                        Publish Now
                                    </h5>
                                    <p>Make your internship visible to students immediately</p>
                                </div>
                                <input type="radio" id="publish_now" name="status" value="published" size="10px">
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="step-navigation final-step">
                <button type="button" class="btn btn-secondary btn-icon btn-sm" onclick="prevStep()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5a.5.5 0 0 0 .5-.5z" />
                    </svg>
                    Previous
                </button>
                <button type="submit" class="btn btn-success btn-icon btn-rg" id="submitBtn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check-circle" viewBox="0 0 16 16">
                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z" />
                        <path d="M10.97 4.97a.235.235 0 0 0-.02.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.061L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-1.071-1.05z" />
                    </svg>
                    <span id="submitText">Save as Draft</span>
                </button>
            </div>
        </div>
    </form>

    <!-- Progress indicator -->
    <div class="progress-indicator">
        <div class="progress-steps">
            <div class="progress-step" data-step="1">
                <span class="step-num">1</span>
                <span class="step-label">Basic Info</span>
            </div>
            <div class="progress-step" data-step="2">
                <span class="step-num">2</span>
                <span class="step-label">Location</span>
            </div>
            <div class="progress-step" data-step="3">
                <span class="step-num">3</span>
                <span class="step-label">Details</span>
            </div>
            <div class="progress-step" data-step="4">
                <span class="step-num">4</span>
                <span class="step-label">Review</span>
            </div>
        </div>
        <div class="progress-bar">
            <div class="progress-fill" id="progressFill"></div>
        </div>
    </div>
</div>

<script src="../../assets/js/internship_posting.js"></script>

<?php require_once '../../includes/footer.php'; ?>