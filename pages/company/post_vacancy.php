<?php
require_once '../../includes/config.php';

// --- Page-specific variables ---
$page_title = 'Post Job Vacancy';
global $pages_path;

// Check if user is logged in and is a company
if (!isLoggedIn() || $_SESSION['role'] !== 'company') {
    header('Location: ' . $pages_path . '/auth/login.php?msg=Please login as a company to post vacancies');
    exit;
}

$db = getDB();
$success_message = '';
$error_message = '';

// Get company profile information
$user_id = $_SESSION['user_id'];
$company_query = "SELECT cp.*, u.email FROM company_profiles cp 
                  JOIN users u ON cp.user_id = u.user_id 
                  WHERE cp.user_id = :user_id";
$company_stmt = $db->prepare($company_query);
$company_stmt->execute(['user_id' => $user_id]);
$company = $company_stmt->fetch();

if (!$company) {
    header('Location: ' . $pages_path . '/auth/company_details.php?msg=Please complete your company profile first');
    exit;
}

// Get categories for dropdown
$categories_query = "SELECT * FROM internship_categories ORDER BY name";
$categories_stmt = $db->prepare($categories_query);
$categories_stmt->execute();
$categories = $categories_stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate required fields
        $required_fields = [
            'title', 'department', 'description', 'required_skills', 
            'location', 'duration_months', 'start_date', 'application_deadline'
        ];
        
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Please fill in all required fields.");
            }
        }
        
        // Prepare data
        $data = [
            'title' => trim($_POST['title']),
            'department' => trim($_POST['department']),
            'description' => trim($_POST['description']),
            'required_skills' => trim($_POST['required_skills']),
            'preferred_skills' => trim($_POST['preferred_skills'] ?? ''),
            'learning_outcomes' => trim($_POST['learning_outcomes'] ?? ''),
            'education_level' => $_POST['education_level'],
            'degree_fields' => trim($_POST['degree_fields'] ?? ''),
            'year_requirement' => trim($_POST['year_requirement'] ?? ''),
            'gpa_requirement' => !empty($_POST['gpa_requirement']) ? floatval($_POST['gpa_requirement']) : null,
            'location' => trim($_POST['location']),
            'duration_months' => intval($_POST['duration_months']),
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'] ?? null,
            'application_deadline' => $_POST['application_deadline'],
            'stipend' => !empty($_POST['stipend']) ? floatval($_POST['stipend']) : null,
            'internship_type' => $_POST['internship_type'],
            'working_hours' => trim($_POST['working_hours'] ?? ''),
            'documents_required' => trim($_POST['documents_required'] ?? ''),
            'how_to_apply' => trim($_POST['how_to_apply'] ?? ''),
            'remote_option' => isset($_POST['remote_option']) ? 1 : 0,
            'experience_level' => $_POST['experience_level'],
            'max_applicants' => intval($_POST['max_applicants'] ?? 50),
            'category_id' => intval($_POST['category_id']),
            'company_id' => $company['id'],
            'created_by' => $user_id
        ];
        
        // Insert into database
        $insert_sql = "INSERT INTO internships (
            title, department, description, required_skills, preferred_skills, 
            learning_outcomes, education_level, degree_fields, year_requirement, 
            gpa_requirement, location, duration_months, start_date, end_date, 
            application_deadline, stipend, internship_type, working_hours, 
            documents_required, how_to_apply, remote_option, experience_level, 
            max_applicants, category_id, company_id, created_by, status
        ) VALUES (
            :title, :department, :description, :required_skills, :preferred_skills,
            :learning_outcomes, :education_level, :degree_fields, :year_requirement,
            :gpa_requirement, :location, :duration_months, :start_date, :end_date,
            :application_deadline, :stipend, :internship_type, :working_hours,
            :documents_required, :how_to_apply, :remote_option, :experience_level,
            :max_applicants, :category_id, :company_id, :created_by, 'published'
        )";
        
        $stmt = $db->prepare($insert_sql);
        $stmt->execute($data);
        
        logActivity('Vacancy Posted', "Posted vacancy: " . $data['title']);
        $success_message = "Job vacancy posted successfully!";
        
        // Redirect to view posted vacancies
        header('Location: view_vacancies.php?success=1');
        exit;
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// --- Include the header ---
require_once '../../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h3 class="mb-0">Post New Job Vacancy</h3>
                    <p class="text-muted mb-0">Fill in the details below to post a new internship opportunity</p>
                </div>
                <div class="card-body">
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger"><?php echo escape($error_message); ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <!-- Basic Details -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="text-primary border-bottom pb-2">Basic Details</h5>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="title" class="form-label">Internship Title / Position *</label>
                                <input type="text" class="form-control" id="title" name="title" 
                                       placeholder="e.g., Software Development Intern" required>
                            </div>
                            <div class="col-md-6">
                                <label for="department" class="form-label">Department / Team *</label>
                                <input type="text" class="form-control" id="department" name="department" 
                                       placeholder="e.g., HR, Marketing, IT" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="company_name" class="form-label">Company Name</label>
                                <input type="text" class="form-control" value="<?php echo escape($company['company_name']); ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label for="category_id" class="form-label">Category *</label>
                                <select class="form-control" id="category_id" name="category_id" required>
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>">
                                            <?php echo escape($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Internship Description -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="text-primary border-bottom pb-2">Internship Description</h5>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Job Description *</label>
                            <textarea class="form-control" id="description" name="description" rows="4" 
                                      placeholder="Describe the responsibilities, expectations, and daily tasks..." required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="required_skills" class="form-label">Required Skills *</label>
                            <textarea class="form-control" id="required_skills" name="required_skills" rows="3" 
                                      placeholder="e.g., Python, Excel, Communication, Design" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="preferred_skills" class="form-label">Preferred Skills (Optional)</label>
                            <textarea class="form-control" id="preferred_skills" name="preferred_skills" rows="2" 
                                      placeholder="Nice-to-have skills (optional)"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="learning_outcomes" class="form-label">Learning Outcomes / Benefits</label>
                            <textarea class="form-control" id="learning_outcomes" name="learning_outcomes" rows="3" 
                                      placeholder="What will the student gain from this internship?"></textarea>
                        </div>
                        
                        <!-- Eligibility -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="text-primary border-bottom pb-2">Eligibility Requirements</h5>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="education_level" class="form-label">Education Level</label>
                                <select class="form-control" id="education_level" name="education_level">
                                    <option value="any">Any</option>
                                    <option value="undergraduate">Undergraduates</option>
                                    <option value="graduate">Graduates</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="year_requirement" class="form-label">Year of Study</label>
                                <input type="text" class="form-control" id="year_requirement" name="year_requirement" 
                                       placeholder="e.g., 2nd year, 3rd year, Any">
                            </div>
                            <div class="col-md-4">
                                <label for="gpa_requirement" class="form-label">GPA Requirement</label>
                                <input type="number" step="0.01" min="0" max="4" class="form-control" 
                                       id="gpa_requirement" name="gpa_requirement" placeholder="e.g., 3.0">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="degree_fields" class="form-label">Degree Field(s)</label>
                            <input type="text" class="form-control" id="degree_fields" name="degree_fields" 
                                   placeholder="e.g., Computer Science, Business, Engineering">
                        </div>
                        
                        <!-- Internship Details -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="text-primary border-bottom pb-2">Internship Details</h5>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="location" class="form-label">Location *</label>
                                <input type="text" class="form-control" id="location" name="location" 
                                       placeholder="e.g., Colombo, Sri Lanka or Remote" required>
                            </div>
                            <div class="col-md-6">
                                <label for="internship_type" class="form-label">Internship Type</label>
                                <select class="form-control" id="internship_type" name="internship_type">
                                    <option value="full-time">Full-time</option>
                                    <option value="part-time">Part-time</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="duration_months" class="form-label">Duration (Months) *</label>
                                <input type="number" class="form-control" id="duration_months" name="duration_months" 
                                       min="1" max="24" required>
                            </div>
                            <div class="col-md-4">
                                <label for="start_date" class="form-label">Start Date *</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" required>
                            </div>
                            <div class="col-md-4">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="end_date" name="end_date">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="stipend" class="form-label">Stipend / Salary (LKR)</label>
                                <input type="number" class="form-control" id="stipend" name="stipend" 
                                       placeholder="Leave empty for unpaid internship">
                            </div>
                            <div class="col-md-6">
                                <label for="working_hours" class="form-label">Working Hours</label>
                                <input type="text" class="form-control" id="working_hours" name="working_hours" 
                                       placeholder="e.g., 9 AM - 5 PM">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="application_deadline" class="form-label">Application Deadline *</label>
                                <input type="date" class="form-control" id="application_deadline" name="application_deadline" required>
                            </div>
                            <div class="col-md-6">
                                <label for="max_applicants" class="form-label">Max Applicants</label>
                                <input type="number" class="form-control" id="max_applicants" name="max_applicants" 
                                       value="50" min="1" max="1000">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="remote_option" name="remote_option">
                                <label class="form-check-label" for="remote_option">
                                    Remote work option available
                                </label>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="experience_level" class="form-label">Experience Level</label>
                            <select class="form-control" id="experience_level" name="experience_level">
                                <option value="beginner">Beginner</option>
                                <option value="intermediate">Intermediate</option>
                                <option value="advanced">Advanced</option>
                            </select>
                        </div>
                        
                        <!-- Application Information -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="text-primary border-bottom pb-2">Application Information</h5>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="documents_required" class="form-label">Documents Required</label>
                            <textarea class="form-control" id="documents_required" name="documents_required" rows="2" 
                                      placeholder="e.g., Resume, Cover Letter, Portfolio"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="how_to_apply" class="form-label">How to Apply</label>
                            <textarea class="form-control" id="how_to_apply" name="how_to_apply" rows="2" 
                                      placeholder="Additional instructions for applicants"></textarea>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="index.php" class="btn btn-secondary me-md-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">Post Vacancy</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-calculate end date based on start date and duration
document.getElementById('start_date').addEventListener('change', function() {
    const startDate = new Date(this.value);
    const duration = parseInt(document.getElementById('duration_months').value) || 0;
    
    if (startDate && duration > 0) {
        const endDate = new Date(startDate);
        endDate.setMonth(endDate.getMonth() + duration);
        
        const endDateInput = document.getElementById('end_date');
        endDateInput.value = endDate.toISOString().split('T')[0];
    }
});

document.getElementById('duration_months').addEventListener('change', function() {
    const startDate = document.getElementById('start_date').value;
    if (startDate) {
        document.getElementById('start_date').dispatchEvent(new Event('change'));
    }
});
</script>

<?php
// --- Include the footer ---
require_once '../../includes/footer.php';
?>
