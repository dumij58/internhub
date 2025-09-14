<?php
require_once '../../includes/config.php';
requireLogin();

$page_title = 'User Details';
require_once '../../includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="text-center">Student Details</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="save_user_details.php" enctype="multipart/form-data">
                        <!-- Basic Information -->
                        <h4 class="mb-3">Basic Information</h4>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="full_name" class="form-label">Full Name *</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email Address *</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo $_SESSION['email']; ?>" readonly>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone Number *</label>
                                <input type="tel" class="form-control" id="phone" name="phone" required>
                            </div>
                        </div>

                        <!-- Academic Details -->
                        <h4 class="mb-3 mt-4">Academic Details</h4>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="university" class="form-label">University / College Name *</label>
                                <input type="text" class="form-control" id="university" name="university" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="degree_program" class="form-label">Degree Program *</label>
                                <input type="text" class="form-control" id="degree_program" name="degree_program" placeholder="e.g., BSc in Computer Science" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="year_of_study" class="form-label">Year of Study *</label>
                                <select class="form-control" id="year_of_study" name="year_of_study" required>
                                    <option value="">Select Year</option>
                                    <option value="1">1st Year</option>
                                    <option value="2">2nd Year</option>
                                    <option value="3">3rd Year</option>
                                    <option value="4">4th Year</option>
                                    <option value="5">5th Year</option>
                                    <option value="final">Final Year</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="gpa" class="form-label">GPA / Academic Performance</label>
                                <input type="number" class="form-control" id="gpa" name="gpa" step="0.01" min="0" max="4" placeholder="e.g., 3.5">
                            </div>
                        </div>

                        <!-- Skills & Interests -->
                        <h4 class="mb-3 mt-4">Skills & Interests</h4>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="key_skills" class="form-label">Key Skills *</label>
                                <textarea class="form-control" id="key_skills" name="key_skills" rows="3" placeholder="e.g., programming, marketing, design, etc." required></textarea>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="areas_of_interest" class="form-label">Areas of Interest *</label>
                                <textarea class="form-control" id="areas_of_interest" name="areas_of_interest" rows="3" placeholder="e.g., HR, Data Science, Research, etc." required></textarea>
                            </div>
                        </div>

                        <!-- Resume/Portfolio -->
                        <h4 class="mb-3 mt-4">Resume/Portfolio</h4>
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="portfolio_links" class="form-label">LinkedIn / GitHub / Portfolio links</label>
                                <textarea class="form-control" id="portfolio_links" name="portfolio_links" rows="3" placeholder="Enter your professional profile links"></textarea>
                            </div>
                        </div>

                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">Save User Details</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
