<?php
require_once '../../includes/config.php';

// --- Page-specific variables ---
$page_title = 'Apply for Internship';
global $pages_path;

// Check if user is logged in and is a student
if (!isLoggedIn() || $_SESSION['role'] !== 'student') {
    header('Location: ' . $pages_path . '/auth/login.php?msg=Please login as a student to apply for internships');
    exit;
}

$db = getDB();
$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Get vacancy ID from URL
$vacancy_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$vacancy_id) {
    header('Location: find_internships.php?error=Invalid vacancy ID');
    exit;
}

// Get vacancy details
$vacancy_query = "SELECT i.*, cp.company_name, ic.name as category_name 
                  FROM internships i 
                  JOIN company_profiles cp ON i.company_id = cp.id
                  LEFT JOIN internship_categories ic ON i.category_id = ic.id
                  WHERE i.id = :vacancy_id AND i.status = 'published'";
$vacancy_stmt = $db->prepare($vacancy_query);
$vacancy_stmt->execute(['vacancy_id' => $vacancy_id]);
$vacancy = $vacancy_stmt->fetch();

if (!$vacancy) {
    header('Location: find_internships.php?error=Vacancy not found or no longer available');
    exit;
}

// Check if application deadline has passed
if (strtotime($vacancy['application_deadline']) < time()) {
    header('Location: find_internships.php?error=Application deadline has passed');
    exit;
}

// Get student profile
$student_query = "SELECT sp.*, u.email FROM student_profiles sp 
                  JOIN users u ON sp.user_id = u.user_id 
                  WHERE sp.user_id = :user_id";
$student_stmt = $db->prepare($student_query);
$student_stmt->execute(['user_id' => $user_id]);
$student = $student_stmt->fetch();

if (!$student) {
    header('Location: ' . $pages_path . '/auth/user_details.php?msg=Please complete your profile first');
    exit;
}

// Check if student has already applied
$existing_application_query = "SELECT id FROM applications WHERE internship_id = :vacancy_id AND student_id = :user_id";
$existing_stmt = $db->prepare($existing_application_query);
$existing_stmt->execute(['vacancy_id' => $vacancy_id, 'user_id' => $user_id]);
$existing_application = $existing_stmt->fetch();

if ($existing_application) {
    header('Location: my_applications.php?msg=You have already applied for this internship');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate required fields
        $required_fields = ['full_name', 'email', 'phone', 'university', 'degree_program'];
        
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Please fill in all required fields.");
            }
        }
        
        // Handle file uploads
        $resume_path = null;
        $cover_letter_path = null;
        
        // Create uploads directory if it doesn't exist
        $upload_dir = '../../uploads/applications/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Handle resume upload
        if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
            $resume_file = $_FILES['resume'];
            $resume_extension = strtolower(pathinfo($resume_file['name'], PATHINFO_EXTENSION));
            
            if (!in_array($resume_extension, ['pdf', 'doc', 'docx'])) {
                throw new Exception("Resume must be a PDF, DOC, or DOCX file.");
            }
            
            $resume_filename = 'resume_' . $user_id . '_' . time() . '.' . $resume_extension;
            $resume_path = $upload_dir . $resume_filename;
            
            if (!move_uploaded_file($resume_file['tmp_name'], $resume_path)) {
                throw new Exception("Failed to upload resume.");
            }
        } else {
            throw new Exception("Resume is required.");
        }
        
        // Handle cover letter upload (optional)
        if (isset($_FILES['cover_letter']) && $_FILES['cover_letter']['error'] === UPLOAD_ERR_OK) {
            $cover_letter_file = $_FILES['cover_letter'];
            $cover_letter_extension = strtolower(pathinfo($cover_letter_file['name'], PATHINFO_EXTENSION));
            
            if (!in_array($cover_letter_extension, ['pdf', 'doc', 'docx'])) {
                throw new Exception("Cover letter must be a PDF, DOC, or DOCX file.");
            }
            
            $cover_letter_filename = 'cover_letter_' . $user_id . '_' . time() . '.' . $cover_letter_extension;
            $cover_letter_path = $upload_dir . $cover_letter_filename;
            
            if (!move_uploaded_file($cover_letter_file['tmp_name'], $cover_letter_path)) {
                throw new Exception("Failed to upload cover letter.");
            }
        }
        
        // Prepare application data
        $application_data = [
            'internship_id' => $vacancy_id,
            'student_id' => $user_id,
            'full_name' => trim($_POST['full_name']),
            'email' => trim($_POST['email']),
            'phone' => trim($_POST['phone']),
            'current_address' => trim($_POST['current_address'] ?? ''),
            'university' => trim($_POST['university']),
            'degree_program' => trim($_POST['degree_program']),
            'year_of_study' => !empty($_POST['year_of_study']) ? intval($_POST['year_of_study']) : null,
            'graduation_year' => !empty($_POST['graduation_year']) ? intval($_POST['graduation_year']) : null,
            'gpa' => !empty($_POST['gpa']) ? floatval($_POST['gpa']) : null,
            'key_skills' => trim($_POST['key_skills'] ?? ''),
            'areas_of_interest' => trim($_POST['areas_of_interest'] ?? ''),
            'resume_path' => $resume_path,
            'cover_letter_path' => $cover_letter_path,
            'cover_letter_text' => trim($_POST['cover_letter_text'] ?? ''),
            'portfolio_links' => trim($_POST['portfolio_links'] ?? '')
        ];
        
        // Insert application into database
        $insert_sql = "INSERT INTO applications (
            internship_id, student_id, full_name, email, phone, current_address,
            university, degree_program, year_of_study, graduation_year, gpa,
            key_skills, areas_of_interest, resume_path, cover_letter_path,
            cover_letter_text, portfolio_links, status
        ) VALUES (
            :internship_id, :student_id, :full_name, :email, :phone, :current_address,
            :university, :degree_program, :year_of_study, :graduation_year, :gpa,
            :key_skills, :areas_of_interest, :resume_path, :cover_letter_path,
            :cover_letter_text, :portfolio_links, 'submitted'
        )";
        
        $stmt = $db->prepare($insert_sql);
        $stmt->execute($application_data);
        
        logActivity('Application Submitted', "Applied for internship: " . $vacancy['title']);
        
        // Redirect to success page
        header('Location: my_applications.php?success=1');
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
            <!-- Vacancy Details Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h4 class="mb-0">Internship Details</h4>
                </div>
                <div class="card-body">
                    <h5><?php echo escape($vacancy['title']); ?></h5>
                    <p class="text-muted mb-2">
                        <strong><?php echo escape($vacancy['company_name']); ?></strong> | 
                        <?php echo escape($vacancy['department']); ?> | 
                        <?php echo escape($vacancy['category_name']); ?>
                    </p>
                    <p><strong>Location:</strong> <?php echo escape($vacancy['location']); ?></p>
                    <p><strong>Duration:</strong> <?php echo $vacancy['duration_months']; ?> months</p>
                    <p><strong>Application Deadline:</strong> 
                        <span class="<?php echo strtotime($vacancy['application_deadline']) < time() ? 'text-danger' : 'text-success'; ?>">
                            <?php echo date('M d, Y', strtotime($vacancy['application_deadline'])); ?>
                        </span>
                    </p>
                </div>
            </div>
            
            <!-- Application Form -->
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Application Form</h4>
                    <p class="text-muted mb-0">Fill in your details to apply for this internship</p>
                </div>
                <div class="card-body">
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger"><?php echo escape($error_message); ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" enctype="multipart/form-data">
                        <!-- Personal Information -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="text-primary border-bottom pb-2">Personal Information</h5>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="full_name" class="form-label">Full Name *</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" 
                                       value="<?php echo escape($student['first_name'] . ' ' . $student['last_name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email Address *</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo escape($student['email']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone Number *</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo escape($student['phone']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="current_address" class="form-label">Current Address</label>
                                <input type="text" class="form-control" id="current_address" name="current_address" 
                                       placeholder="Optional - some companies require location info">
                            </div>
                        </div>
                        
                        <!-- Academic Details -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="text-primary border-bottom pb-2">Academic Details</h5>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="university" class="form-label">University / College *</label>
                                <input type="text" class="form-control" id="university" name="university" 
                                       value="<?php echo escape($student['university']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="degree_program" class="form-label">Degree Program *</label>
                                <input type="text" class="form-control" id="degree_program" name="degree_program" 
                                       value="<?php echo escape($student['major']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="year_of_study" class="form-label">Year of Study</label>
                                <select class="form-control" id="year_of_study" name="year_of_study">
                                    <option value="">Select Year</option>
                                    <option value="1" <?php echo $student['year_of_study'] == 1 ? 'selected' : ''; ?>>1st Year</option>
                                    <option value="2" <?php echo $student['year_of_study'] == 2 ? 'selected' : ''; ?>>2nd Year</option>
                                    <option value="3" <?php echo $student['year_of_study'] == 3 ? 'selected' : ''; ?>>3rd Year</option>
                                    <option value="4" <?php echo $student['year_of_study'] == 4 ? 'selected' : ''; ?>>4th Year</option>
                                    <option value="5">5th Year</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="graduation_year" class="form-label">Graduation Year</label>
                                <input type="number" class="form-control" id="graduation_year" name="graduation_year" 
                                       min="2020" max="2030" placeholder="e.g., 2024">
                            </div>
                            <div class="col-md-4">
                                <label for="gpa" class="form-label">GPA / Academic Performance</label>
                                <input type="number" step="0.01" min="0" max="4" class="form-control" 
                                       id="gpa" name="gpa" value="<?php echo $student['gpa']; ?>" 
                                       placeholder="e.g., 3.5">
                            </div>
                        </div>
                        
                        <!-- Skills & Interests -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="text-primary border-bottom pb-2">Skills & Interests</h5>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="key_skills" class="form-label">Key Skills</label>
                            <textarea class="form-control" id="key_skills" name="key_skills" rows="3" 
                                      placeholder="e.g., Python, Marketing, Photoshop, Communication"><?php echo escape($student['skills']); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="areas_of_interest" class="form-label">Areas of Interest</label>
                            <textarea class="form-control" id="areas_of_interest" name="areas_of_interest" rows="2" 
                                      placeholder="e.g., Data Science, HR, Finance, Web Development"></textarea>
                        </div>
                        
                        <!-- Application Documents -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="text-primary border-bottom pb-2">Application Documents</h5>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="resume" class="form-label">Resume / CV *</label>
                            <input type="file" class="form-control" id="resume" name="resume" 
                                   accept=".pdf,.doc,.docx" required>
                            <div class="form-text">Upload your resume in PDF, DOC, or DOCX format (Max 5MB)</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="cover_letter" class="form-label">Cover Letter (Optional)</label>
                            <input type="file" class="form-control" id="cover_letter" name="cover_letter" 
                                   accept=".pdf,.doc,.docx">
                            <div class="form-text">Upload a cover letter or write one below</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="cover_letter_text" class="form-label">Cover Letter Text (Optional)</label>
                            <textarea class="form-control" id="cover_letter_text" name="cover_letter_text" rows="4" 
                                      placeholder="Write your cover letter here..."></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="portfolio_links" class="form-label">Portfolio Links (Optional)</label>
                            <textarea class="form-control" id="portfolio_links" name="portfolio_links" rows="2" 
                                      placeholder="GitHub, LinkedIn, Personal Website, etc."></textarea>
                        </div>
                        
                        <!-- Vacancy-Specific Information (Auto-filled) -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="text-primary border-bottom pb-2">Application Details</h5>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Company Name</label>
                                <input type="text" class="form-control" value="<?php echo escape($vacancy['company_name']); ?>" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Internship Title</label>
                                <input type="text" class="form-control" value="<?php echo escape($vacancy['title']); ?>" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Application Date</label>
                                <input type="text" class="form-control" value="<?php echo date('M d, Y'); ?>" readonly>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="find_internships.php" class="btn btn-secondary me-md-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">Submit Application</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// --- Include the footer ---
require_once '../../includes/footer.php';
?>
