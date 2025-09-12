<?php
require_once '../../includes/config.php';
requireLogin();

if ($_SESSION['role'] !== 'student') {
    logActivity('Unauthorized Access Attempt', 'User tried to access application form');
    header('Location: ../../pages/error.php?error_message=403 - Access denied');
    exit;
}

$db = getDB();
$user_id = $_SESSION['user_id'];
$internship_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$internship_id) {
    header('Location: find_internships.php?error=Invalid internship ID');
    exit;
}

// Get internship details
$internship_query = "SELECT i.*, cp.company_name, cp.industry_type,
                            ic.name as category_name,
                            DATEDIFF(i.application_deadline, NOW()) as days_left
                     FROM internships i 
                     JOIN company_profiles cp ON i.company_id = cp.id
                     LEFT JOIN internship_categories ic ON i.category_id = ic.id
                     WHERE i.id = ? AND i.status = 'published'";

$stmt = $db->prepare($internship_query);
$stmt->execute([$internship_id]);
$internship = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$internship) {
    $_SESSION['error_message'] = 'Internship not found or no longer available.';
    header('Location: find_internships.php');
    exit;
}

// Check if application deadline has passed
if ($internship['days_left'] <= 0) {
    $_SESSION['error_message'] = 'Application deadline for this internship has passed.';
    header('Location: view_internship.php?id=' . $internship_id);
    exit;
}

// Check if user has already applied
$existing_app_query = "SELECT id, status FROM applications WHERE internship_id = ? AND student_id = ?";
$stmt = $db->prepare($existing_app_query);
$stmt->execute([$internship_id, $user_id]);
$existing_application = $stmt->fetch(PDO::FETCH_ASSOC);

if ($existing_application) {
    $_SESSION['error_message'] = 'You have already applied for this internship.';
    header('Location: my_applications.php');
    exit;
}

// Get student profile
$profile_query = "SELECT * FROM student_profiles WHERE user_id = ?";
$stmt = $db->prepare($profile_query);
$stmt->execute([$user_id]);
$student_profile = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student_profile) {
    $_SESSION['error_message'] = 'Please complete your profile before applying for internships.';
    header('Location: update_profile.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $cover_letter = trim($_POST['cover_letter']);
        $additional_info = trim($_POST['additional_info']);
        $status = isset($_POST['save_draft']) ? 'draft' : 'submitted';

        // Validation
        $errors = [];

        if ($status === 'submitted') {
            if (empty($cover_letter)) {
                $errors[] = 'Cover letter is required for submission';
            }
        }

        if (empty($errors)) {
            // Insert application
            $sql = "INSERT INTO applications (
                internship_id, student_id, cover_letter, additional_info, status, application_date
            ) VALUES (?, ?, ?, ?, ?, NOW())";

            $stmt = $db->prepare($sql);
            $stmt->execute([
                $internship_id,
                $user_id,
                $cover_letter,
                $additional_info,
                $status
            ]);

            $action_text = $status === 'submitted' ? 'submitted' : 'saved as draft';
            logActivity('Application ' . ucfirst($action_text), "Application $action_text for internship: " . $internship['title']);

            $success_message = $status === 'submitted' ?
                'Application submitted successfully! The company will review your application.' :
                'Application saved as draft. You can complete and submit it later.';

            $_SESSION['success_message'] = $success_message;
            
            if ($status === 'submitted') {
                header('Location: my_applications.php');
            } else {
                header('Location: apply_vacancy.php?id=' . $internship_id . '&saved=1');
            }
            exit;
        }
    } catch (Exception $e) {
        $errors[] = 'An error occurred while processing your application. Please try again.';
        error_log($e->getMessage());
    }
}

$page_title = 'Apply for ' . $internship['title'];

require_once '../../includes/header.php';
?>

<div class="profile-container">
    <!-- Breadcrumb -->
    <div class="breadcrumb-nav">
        <a href="find_internships.php" class="breadcrumb-link">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16">
                <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
            </svg>
            Find Internships
        </a>
        <span class="breadcrumb-separator">/</span>
        <a href="view_internship.php?id=<?php echo $internship_id; ?>" class="breadcrumb-link">
            <?php echo escape($internship['title']); ?>
        </a>
        <span class="breadcrumb-separator">/</span>
        <span class="breadcrumb-current">Apply</span>
    </div>

    <?php if (isset($_GET['saved'])): ?>
        <div class="alert alert-success">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check-circle" viewBox="0 0 16 16">
                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                <path d="M10.97 4.97a.235.235 0 0 0-.02.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.061L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-1.071-1.05z"/>
            </svg>
            Application saved successfully! You can continue editing or submit when ready.
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

    <!-- Application Header -->
    <div class="card">
        <div class="card-body">
            <div class="application-header">
                <div class="internship-info">
                    <div class="company-logo">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-building" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M14.763.075A.5.5 0 0 1 15 .5v15a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5V14h-1v1.5a.5.5 0 0 1-.5.5h-9a.5.5 0 0 1-.5-.5V10a.5.5 0 0 1 .342-.474L6 7.64V4.5a.5.5 0 0 1 .276-.447l8-4a.5.5 0 0 1 .487.022ZM6 8.694 1 10.36V15h5V8.694ZM7 15h2v-1.5a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 .5.5V15h2V1.309l-7 3.5V15Z"/>
                        </svg>
                    </div>
                    <div>
                        <h1>Apply for <?php echo escape($internship['title']); ?></h1>
                        <p class="company-name"><?php echo escape($internship['company_name']); ?></p>
                        <div class="internship-meta">
                            <span><?php echo escape($internship['location']); ?></span>
                            <span>•</span>
                            <span><?php echo escape($internship['category_name']); ?></span>
                            <span>•</span>
                            <span><?php echo $internship['duration_months']; ?> months</span>
                        </div>
                    </div>
                </div>
                <div class="deadline-info">
                    <div class="deadline-warning">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-clock" viewBox="0 0 16 16">
                            <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/>
                            <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/>
                        </svg>
                        <?php echo $internship['days_left']; ?> days left to apply
                    </div>
                    <small>Deadline: <?php echo date('M j, Y', strtotime($internship['application_deadline'])); ?></small>
                </div>
            </div>
        </div>
    </div>

    <!-- Progress Indicator -->
    <div class="progress-indicator">
        <div class="progress-steps">
            <div class="progress-step active" data-step="1">
                <span class="step-num">1</span>
                <span class="step-label">Profile</span>
            </div>
            <div class="progress-step" data-step="2">
                <span class="step-num">2</span>
                <span class="step-label">Cover Letter</span>
            </div>
            <div class="progress-step" data-step="3">
                <span class="step-num">3</span>
                <span class="step-label">Submit</span>
            </div>
        </div>
        <div class="progress-bar">
            <div class="progress-fill" id="progressFill"></div>
        </div>
    </div>

    <!-- Application Form -->
    <form id="applicationForm" method="POST" class="application-form">
        <!-- Step 1: Personal Information Review -->
        <div class="form-step active" id="step1">
            <div class="card">
                <div class="card-header">
                    <h3>
                        <span class="step-number">1</span>
                        Review Your Profile
                    </h3>
                    <p>Ensure your information is complete and up-to-date</p>
                </div>
                <div class="card-body">
                    <div class="profile-review">
                        <div class="profile-section">
                            <h4>Personal Information</h4>
                            <div class="info-grid">
                                <div class="info-item">
                                    <strong>Name:</strong>
                                    <span><?php echo escape($student_profile['first_name'] . ' ' . $student_profile['last_name']); ?></span>
                                </div>
                                <div class="info-item">
                                    <strong>Student ID:</strong>
                                    <span><?php echo escape($student_profile['student_id'] ?? 'Not provided'); ?></span>
                                </div>
                                <div class="info-item">
                                    <strong>Phone:</strong>
                                    <span><?php echo escape($student_profile['phone'] ?? 'Not provided'); ?></span>
                                </div>
                                <div class="info-item">
                                    <strong>University:</strong>
                                    <span><?php echo escape($student_profile['university'] ?? 'Not provided'); ?></span>
                                </div>
                                <div class="info-item">
                                    <strong>Major:</strong>
                                    <span><?php echo escape($student_profile['major'] ?? 'Not provided'); ?></span>
                                </div>
                                <div class="info-item">
                                    <strong>Year of Study:</strong>
                                    <span><?php echo $student_profile['year_of_study'] ? 'Year ' . $student_profile['year_of_study'] : 'Not provided'; ?></span>
                                </div>
                                <?php if ($student_profile['gpa']): ?>
                                <div class="info-item">
                                    <strong>GPA:</strong>
                                    <span><?php echo escape($student_profile['gpa']); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if ($student_profile['bio'] || $student_profile['skills'] || $student_profile['portfolio_url']): ?>
                        <div class="profile-section">
                            <h4>Additional Information</h4>
                            <?php if ($student_profile['bio']): ?>
                                <div class="bio-section">
                                    <strong>Bio:</strong>
                                    <p><?php echo nl2br(escape($student_profile['bio'])); ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($student_profile['skills']): ?>
                                <div class="skills-section">
                                    <strong>Skills:</strong>
                                    <p><?php echo escape($student_profile['skills']); ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($student_profile['portfolio_url']): ?>
                                <div class="portfolio-section">
                                    <strong>Portfolio:</strong>
                                    <a href="<?php echo escape($student_profile['portfolio_url']); ?>" target="_blank">
                                        <?php echo escape($student_profile['portfolio_url']); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="step-navigation">
                        <div></div>
                        <button type="button" class="btn btn-primary btn-icon" onclick="nextStep()">
                            Continue to Cover Letter
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-right" viewBox="0 0 16 16">
                                <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 2: Cover Letter -->
        <div class="form-step" id="step2">
            <div class="card">
                <div class="card-header">
                    <h3>
                        <span class="step-number">2</span>
                        Cover Letter
                    </h3>
                    <p>Tell the company why you're interested and what you can bring to the role</p>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="cover_letter" class="form-label">
                            Cover Letter *
                            <small>Explain your interest in this internship and how your skills align with the requirements</small>
                        </label>
                        <textarea id="cover_letter" 
                                  name="cover_letter" 
                                  class="form-control" 
                                  rows="12"
                                  placeholder="Dear Hiring Manager,&#10;&#10;I am writing to express my strong interest in the <?php echo escape($internship['title']); ?> position at <?php echo escape($internship['company_name']); ?>...&#10;&#10;Please consider explaining:&#10;• Why you're interested in this specific role and company&#10;• Relevant coursework, projects, or experience&#10;• Skills that match the job requirements&#10;• What you hope to learn and contribute&#10;&#10;Thank you for considering my application.&#10;&#10;Sincerely,&#10;<?php echo escape($student_profile['first_name'] . ' ' . $student_profile['last_name']); ?>"
                                  required><?php echo escape($_POST['cover_letter'] ?? ''); ?></textarea>
                        <div class="character-count">
                            <small><span id="charCount">0</span> characters</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="additional_info" class="form-label">
                            Additional Information
                            <small>Any other information you'd like to share? (Optional)</small>
                        </label>
                        <textarea id="additional_info" 
                                  name="additional_info" 
                                  class="form-control" 
                                  rows="4"
                                  placeholder="• Relevant certifications or courses&#10;• Languages spoken&#10;• Part-time/full-time preferences&#10;• Transportation arrangements&#10;• Any questions about the role"><?php echo escape($_POST['additional_info'] ?? ''); ?></textarea>
                    </div>

                    <div class="step-navigation">
                        <button type="button" class="btn btn-secondary btn-icon" onclick="prevStep()">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left" viewBox="0 0 16 16">
                                <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5a.5.5 0 0 0 .5-.5z"/>
                            </svg>
                            Previous
                        </button>
                        <button type="button" class="btn btn-primary btn-icon" onclick="nextStep()">
                            Review Application
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-right" viewBox="0 0 16 16">
                                <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 3: Review & Submit -->
        <div class="form-step" id="step3">
            <div class="card">
                <div class="card-header">
                    <h3>
                        <span class="step-number">3</span>
                        Review & Submit
                    </h3>
                    <p>Review your application before submitting</p>
                </div>
                <div class="card-body">
                    <div class="application-preview">
                        <div class="preview-section">
                            <h4>Cover Letter</h4>
                            <div class="preview-content" id="coverLetterPreview">
                                <!-- Will be populated by JavaScript -->
                            </div>

                            <h4>Additional Information</h4>
                            <div class="preview-content" id="additionalInfoPreview">
                                Not provided
                            </div>
                        </div>
                    </div>

                    <div class="submission-options">
                        <div class="option-cards">
                            <div class="option-card draft">
                                <div class="option-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-file-earmark" viewBox="0 0 16 16">
                                        <path d="M14 4.5V14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h5.5L14 4.5zm-3 0A1.5 1.5 0 0 1 9.5 3V1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V4.5h-2z"/>
                                    </svg>
                                </div>
                                <h5>Save as Draft</h5>
                                <p>Save your progress and continue later. You can edit and submit anytime before the deadline.</p>
                                <button type="submit" name="save_draft" class="btn btn-outline-secondary">
                                    Save Draft
                                </button>
                            </div>

                            <div class="option-card submit">
                                <div class="option-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-send" viewBox="0 0 16 16">
                                        <path d="M15.854.146a.5.5 0 0 1 .11.54L13.026 8.28a.5.5 0 0 1-.078.115L9.793 11.55a.5.5 0 0 1-.707 0L5.93 8.393a.5.5 0 0 1-.078-.115L2.914.686a.5.5 0 0 1 .11-.54C3.167-.095 3.537-.027 3.702.17L8 5.998 12.298.17c.165-.197.535-.265.678-.024z"/>
                                    </svg>
                                </div>
                                <h5>Submit Application</h5>
                                <p>Submit your application now. The company will be notified and you'll receive a confirmation.</p>
                                <button type="submit" class="btn btn-primary btn-lg">
                                    Submit Application
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="step-navigation">
                        <button type="button" class="btn btn-secondary btn-icon" onclick="prevStep()">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left" viewBox="0 0 16 16">
                                <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5a.5.5 0 0 0 .5-.5z"/>
                            </svg>
                            Previous
                        </button>
                        <div></div>
                    </div>
                </div>
            </div>
        </div>
    </form>

</div>

<style>
.breadcrumb-nav {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
    font-size: 0.9rem;
}

.breadcrumb-link {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #007bff;
    text-decoration: none;
}

.breadcrumb-link:hover {
    text-decoration: underline;
}

.breadcrumb-separator {
    color: #666;
}

.breadcrumb-current {
    color: #666;
    font-weight: 500;
}

.application-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
}

.internship-info {
    display: flex;
    gap: 1rem;
    flex: 1;
}

.company-logo {
    background: #f8f9fa;
    border-radius: 0.375rem;
    padding: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.application-header h1 {
    margin: 0 0 0.25rem 0;
    font-size: 1.5rem;
    color: #333;
}

.company-name {
    margin: 0 0 0.5rem 0;
    color: #666;
    font-size: 1.1rem;
}

.internship-meta {
    color: #666;
    font-size: 0.9rem;
}

.deadline-info {
    text-align: right;
}

.deadline-warning {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #dc3545;
    font-weight: 500;
    margin-bottom: 0.25rem;
}

.form-step {
    display: none;
    margin-bottom: 2rem;
}

.form-step.active {
    display: block;
}

.step-number {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    background: #007bff;
    color: white;
    border-radius: 50%;
    font-weight: 600;
    margin-right: 0.75rem;
}

.card-header h3 {
    display: flex;
    align-items: center;
    margin: 0 0 0.5rem 0;
    font-size: 1.25rem;
}

.card-header p {
    margin: 0;
    color: #666;
    font-size: 0.9rem;
}

.profile-review {
    margin-bottom: 1.5rem;
}

.profile-section {
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #eee;
}

.profile-section:last-child {
    border-bottom: none;
}

.profile-section h4 {
    margin: 0 0 1rem 0;
    color: #333;
    font-size: 1.1rem;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 0.75rem;
}

.info-item {
    display: flex;
    justify-content: space-around;
    align-items: center;
    padding: 0.5rem 0;
    font-size: 0.9rem;
}

.info-item strong {
    color: #333;
    min-width: 120px;
}

.bio-section, .skills-section, .portfolio-section {
    margin-bottom: 1rem;
}

.bio-section p, .skills-section p {
    margin: 0.25rem 0 0 0;
    line-height: 1.5;
    color: #555;
}

.portfolio-section a {
    color: #007bff;
    text-decoration: none;
}

.portfolio-section a:hover {
    text-decoration: underline;
}

.profile-actions {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #eee;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
}

.character-count {
    text-align: right;
    margin-top: 0.25rem;
}

.step-navigation {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 2rem;
    padding-top: 1rem;
    border-top: 1px solid #eee;
}

.application-preview {
    margin-bottom: 2rem;
}

.preview-section {
    margin-bottom: 1.5rem;
    padding: 1rem;
    border: 1px solid #e9ecef;
    border-radius: 0.375rem;
    background: #f8f9fa;
}

.preview-section h4 {
    margin: 0 0 0.75rem 0;
    color: #333;
    font-size: 1rem;
}

.preview-content {
    line-height: 1.6;
    color: #555;
}

.preview-item {
    display: flex;
    gap: 1rem;
    margin-bottom: 0.5rem;
}

.preview-item strong {
    min-width: 100px;
    color: #333;
}

.submission-options {
    margin-bottom: 2rem;
}

.option-cards {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}

.option-card {
    text-align: center;
    padding: 1.5rem;
    border: 2px solid #e9ecef;
    border-radius: 0.5rem;
    transition: all 0.2s;
}

.option-card:hover {
    border-color: #007bff;
    box-shadow: 0 2px 8px rgba(0,123,255,0.1);
}

.option-card.submit {
    border-color: #007bff;
    background: linear-gradient(135deg, #f8f9ff, #e3f2fd);
}

.option-icon {
    margin-bottom: 1rem;
    color: #007bff;
}

.option-card h5 {
    margin: 0 0 0.5rem 0;
    color: #333;
}

.option-card p {
    margin: 0 0 1rem 0;
    color: #666;
    font-size: 0.9rem;
    line-height: 1.4;
}

.step-num {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.5rem;
    height: 1.5rem;
    background: #e9ecef;
    color: #666;
    border-radius: 50%;
    font-size: 0.8rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.step-label {
    font-size: 0.7rem;
    color: #666;
}

.progress-bar {
    height: 4px;
    background: #e9ecef;
    border-radius: 2px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: #007bff;
    border-radius: 2px;
    transition: width 0.3s ease;
    width: 25%;
}

.alert {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    border-radius: 0.375rem;
    margin-bottom: 1rem;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-danger {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.alert ul {
    margin: 0.5rem 0 0 0;
    padding-left: 1rem;
}

@media (max-width: 768px) {
    .application-header {
        flex-direction: column;
        gap: 1rem;
    }
    
    .deadline-info {
        text-align: left;
    }
    
    .option-cards {
        grid-template-columns: 1fr;
    }
    
    .progress-indicator {
        position: static;
        margin-top: 2rem;
    }
    
    .info-grid {
        grid-template-columns: 1fr;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
let currentStep = 1;
const totalSteps = 3;

function showStep(step) {
    // Hide all steps
    document.querySelectorAll('.form-step').forEach(s => s.classList.remove('active'));
    
    // Show current step
    document.getElementById(`step${step}`).classList.add('active');
    
    // Update progress indicator
    updateProgressIndicator(step);
    
    // Update progress steps
    document.querySelectorAll('.progress-step').forEach((s, index) => {
        s.classList.remove('active', 'completed');
        if (index + 1 === step) {
            s.classList.add('active');
        } else if (index + 1 < step) {
            s.classList.add('completed');
        }
    });
}

function nextStep() {
    if (currentStep < totalSteps) {
        // Validate current step before proceeding
        if (validateStep(currentStep)) {
            currentStep++;
            showStep(currentStep);
            
            // Update preview if on review step
            if (currentStep === 3) {
                updatePreview();
            }
        }
    }
}

function prevStep() {
    if (currentStep > 1) {
        currentStep--;
        showStep(currentStep);
    }
}

function validateStep(step) {
    switch(step) {
        case 2:
            const coverLetter = document.getElementById('cover_letter').value.trim();
            if (!coverLetter) {
                alert('Please write a cover letter before proceeding.');
                return false;
            }
            break;
    }
    return true;
}

function updateProgressIndicator(step) {
    const progressFill = document.getElementById('progressFill');
    const progress = (step / totalSteps) * 100;
    progressFill.style.width = progress + '%';
}

function updatePreview() {
    // Cover letter preview
    const coverLetter = document.getElementById('cover_letter').value;
    const coverLetterPreview = document.getElementById('coverLetterPreview');
    coverLetterPreview.innerHTML = coverLetter ? 
        coverLetter.replace(/\n/g, '<br>') : 
        '<em>No cover letter written</em>';
    
    // Additional info preview
    const additionalInfo = document.getElementById('additional_info').value;
    const additionalInfoPreview = document.getElementById('additionalInfoPreview');
    additionalInfoPreview.innerHTML = additionalInfo ? 
        additionalInfo.replace(/\n/g, '<br>') : 
        '<em>Not provided</em>';
}

// Character count for cover letter
document.getElementById('cover_letter').addEventListener('input', function() {
    const charCount = this.value.length;
    document.getElementById('charCount').textContent = charCount;
});

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    showStep(1);
    
    // Update character count on load
    const coverLetter = document.getElementById('cover_letter');
    if (coverLetter.value) {
        document.getElementById('charCount').textContent = coverLetter.value.length;
    }
});

// Form submission validation
document.getElementById('applicationForm').addEventListener('submit', function(e) {
    const submitButton = e.submitter;
    const isDraft = submitButton.name === 'save_draft';
    
    if (!isDraft) {
        // Validate required fields for submission
        const coverLetter = document.getElementById('cover_letter').value.trim();
        
        if (!coverLetter) {
            e.preventDefault();
            alert('Cover letter is required for submission.');
            return;
        }
        
        // Confirm submission
        if (!confirm('Are you sure you want to submit your application? You won\'t be able to edit it after submission.')) {
            e.preventDefault();
            return;
        }
    }
});
</script>

<?php require_once '../../includes/footer.php'; ?>