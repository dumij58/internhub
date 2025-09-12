<?php
require_once '../../includes/config.php';
requireLogin();

if ($_SESSION['role'] !== 'student') {
    logActivity('Unauthorized Access Attempt', 'User tried to access student internship search');
    header('Location: ../../pages/error.php?error_message=403 - Access denied');
    exit;
}

$db = getDB();
$user_id = $_SESSION['user_id'];
$page_title = 'Find Internships';

// Search and filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$location_filter = isset($_GET['location']) ? trim($_GET['location']) : '';
$experience_filter = isset($_GET['experience']) ? $_GET['experience'] : '';
$remote_filter = isset($_GET['remote']) ? $_GET['remote'] : '';
$salary_min = isset($_GET['salary_min']) ? (int)$_GET['salary_min'] : 0;

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Build search conditions
$where_conditions = ["i.status = 'published'", "i.application_deadline > NOW()"];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(i.title LIKE ? OR i.description LIKE ? OR cp.company_name LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

if (!empty($category_filter)) {
    $where_conditions[] = "i.category_id = ?";
    $params[] = $category_filter;
}

if (!empty($location_filter)) {
    $where_conditions[] = "i.location LIKE ?";
    $params[] = "%$location_filter%";
}

if (!empty($experience_filter)) {
    $where_conditions[] = "i.experience_level = ?";
    $params[] = $experience_filter;
}

if ($remote_filter === '1') {
    $where_conditions[] = "i.remote_option = 1";
}

if ($salary_min > 0) {
    $where_conditions[] = "i.salary >= ?";
    $params[] = $salary_min;
}

$where_clause = implode(' AND ', $where_conditions);

// Get total count for pagination
$count_query = "SELECT COUNT(DISTINCT i.id) as total 
                FROM internships i 
                JOIN company_profiles cp ON i.company_id = cp.id
                LEFT JOIN internship_categories ic ON i.category_id = ic.id
                WHERE $where_clause";
$stmt = $db->prepare($count_query);
$stmt->execute($params);
$total_records = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_records / $limit);

// Get internships with search and filters
$internships_query = "SELECT i.*, cp.company_name, cp.industry_type, ic.name as category_name,
                      COUNT(DISTINCT a.id) as application_count,
                      DATEDIFF(i.application_deadline, NOW()) as days_left
                      FROM internships i 
                      JOIN company_profiles cp ON i.company_id = cp.id
                      LEFT JOIN internship_categories ic ON i.category_id = ic.id
                      LEFT JOIN applications a ON i.id = a.internship_id
                      WHERE $where_clause
                      GROUP BY i.id
                      ORDER BY i.created_at DESC
                      LIMIT $limit OFFSET $offset";
$internships_stmt = $db->prepare($internships_query);
$internships_stmt->execute($params);
$internships = $internships_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user's applied internships
$applied_query = "SELECT internship_id, status FROM applications WHERE student_id = ?";
$applied_stmt = $db->prepare($applied_query);
$applied_stmt->execute([$user_id]);
$applied_internships = [];
foreach ($applied_stmt->fetchAll(PDO::FETCH_ASSOC) as $app) {
    $applied_internships[$app['internship_id']] = $app['status'];
}

// Get categories for filter dropdown
$categories_query = "SELECT * FROM internship_categories ORDER BY name";
$categories = $db->query($categories_query)->fetchAll(PDO::FETCH_ASSOC);

require_once '../../includes/header.php';
?>

<div class="profile-container">
    <!-- Search Header -->
    <div class="card">
        <div class="card-body">
            <div class="search-header">
                <h1>
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16">
                        <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                    </svg>
                    Find Internships
                </h1>
                <div class="results-info">
                    <span class="results-count"><?php echo $total_records; ?> opportunities found</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="card">
        <div class="card-body">
            <form method="GET" class="search-form">
                <!-- Main Search Bar -->
                <div class="search-bar">
                    <div class="search-input-group">
                        <input type="text" 
                               name="search" 
                               class="form-control search-input" 
                               placeholder="Search by job title, company, or keywords..."
                               value="<?php echo escape($search); ?>">
                        <button type="submit" class="btn btn-primary search-btn">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16">
                                <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                            </svg>
                            Search
                        </button>
                    </div>
                </div>

                <!-- Advanced Filters -->
                <div class="filters-section">
                    <div class="filters-toggle">
                        <button type="button" onclick="toggleFilters()" class="btn btn-outline-secondary btn-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-funnel" viewBox="0 0 16 16">
                                <path d="M1.5 1.5A.5.5 0 0 1 2 1h12a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.128.334L10 8.692V13.5a.5.5 0 0 1-.342.474l-3 1A.5.5 0 0 1 6 14.5V8.692L1.628 3.834A.5.5 0 0 1 1.5 3.5v-2z"/>
                            </svg>
                            Advanced Filters
                        </button>
                        <?php if (!empty($category_filter) || !empty($location_filter) || !empty($experience_filter) || $remote_filter === '1' || $salary_min > 0): ?>
                            <a href="find_internships.php" class="btn btn-link btn-sm">Clear All Filters</a>
                        <?php endif; ?>
                    </div>

                    <div id="advancedFilters" class="filters-grid" style="display: none;">
                        <div class="filter-group">
                            <label class="filter-label">Category</label>
                            <select name="category" class="form-control">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" 
                                            <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                        <?php echo escape($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label class="filter-label">Location</label>
                            <input type="text" name="location" class="form-control" 
                                   placeholder="City or region" value="<?php echo escape($location_filter); ?>">
                        </div>

                        <div class="filter-group">
                            <label class="filter-label">Experience Level</label>
                            <select name="experience" class="form-control">
                                <option value="">All Levels</option>
                                <option value="beginner" <?php echo $experience_filter === 'beginner' ? 'selected' : ''; ?>>Beginner</option>
                                <option value="intermediate" <?php echo $experience_filter === 'intermediate' ? 'selected' : ''; ?>>Intermediate</option>
                                <option value="advanced" <?php echo $experience_filter === 'advanced' ? 'selected' : ''; ?>>Advanced</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label class="filter-label">Minimum Salary (LKR)</label>
                            <input type="number" name="salary_min" class="form-control" 
                                   placeholder="0" min="0" step="1000" value="<?php echo $salary_min > 0 ? $salary_min : ''; ?>">
                        </div>

                        <div class="filter-group checkbox-filter">
                            <label class="checkbox-label">
                                <input type="checkbox" name="remote" value="1" <?php echo $remote_filter === '1' ? 'checked' : ''; ?>>
                                Remote Work Available
                            </label>
                        </div>

                        <div class="filter-actions">
                            <button type="submit" class="btn btn-primary btn-sm">Apply Filters</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Results Section -->
    <div class="card">
        <div class="card-body">
            <?php if (empty($internships)): ?>
                <div class="empty-state">
                    <div class="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16">
                            <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                        </svg>
                    </div>
                    <h3>No Internships Found</h3>
                    <p>Try adjusting your search criteria or check back later for new opportunities.</p>
                    <a href="find_internships.php" class="btn btn-primary">Reset Search</a>
                </div>
            <?php else: ?>
                <div class="internships-grid">
                    <?php foreach ($internships as $internship): ?>
                        <div class="find-internship-card">
                            <div class="internship-header">
                                <div class="company-info">
                                    <div class="company-logo">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-building" viewBox="0 0 16 16">
                                            <path fill-rule="evenodd" d="M14.763.075A.5.5 0 0 1 15 .5v15a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5V14h-1v1.5a.5.5 0 0 1-.5.5h-9a.5.5 0 0 1-.5-.5V10a.5.5 0 0 1 .342-.474L6 7.64V4.5a.5.5 0 0 1 .276-.447l8-4a.5.5 0 0 1 .487.022ZM6 8.694 1 10.36V15h5V8.694ZM7 15h2v-1.5a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 .5.5V15h2V1.309l-7 3.5V15Z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="internship-title"><?php echo escape($internship['title']); ?></h3>
                                        <p class="company-name"><?php echo escape($internship['company_name']); ?></p>
                                    </div>
                                </div>
                                
                                <?php if (isset($applied_internships[$internship['id']])): ?>
                                    <span class="status-badge applied">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-check-circle" viewBox="0 0 16 16">
                                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                            <path d="M10.97 4.97a.235.235 0 0 0-.02.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.061L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-1.071-1.05z"/>
                                        </svg>
                                        <?php echo ucfirst(str_replace('_', ' ', $applied_internships[$internship['id']])); ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="internship-meta">
                                <div class="meta-item">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-geo-alt" viewBox="0 0 16 16">
                                        <path d="M12.166 8.94c-.524 1.062-1.234 2.12-1.96 3.07A31.493 31.493 0 0 1 8 14.58a31.481 31.481 0 0 1-2.206-2.57c-.726-.95-1.436-2.008-1.96-3.07C3.304 7.867 3 6.862 3 6a5 5 0 0 1 10 0c0 .862-.305 1.867-.834 2.94zM8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10z"/>
                                        <path d="M8 8a2 2 0 1 1 0-4 2 2 0 0 1 0 4zm0 1a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
                                    </svg>
                                    <?php echo escape($internship['location']); ?>
                                    <?php if ($internship['remote_option']): ?>
                                        <span class="remote-badge">Remote</span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="meta-item">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-clock" viewBox="0 0 16 16">
                                        <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/>
                                        <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/>
                                    </svg>
                                    <?php echo $internship['duration_months']; ?> months
                                </div>

                                <?php if ($internship['salary'] > 0): ?>
                                    <div class="meta-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-currency-dollar" viewBox="0 0 16 16">
                                            <path d="M4 10.781c.148 1.667 1.513 2.85 3.591 3.003V15h1.043v-1.216c2.27-.179 3.678-1.438 3.678-3.3 0-1.59-.947-2.51-2.956-3.028l-.722-.187V3.467c1.122.11 1.879.714 2.07 1.616h1.47c-.166-1.6-1.54-2.748-3.54-2.875V1H7.591v1.233c-1.939.23-3.27 1.472-3.27 3.156 0 1.454.966 2.483 2.661 2.917l.61.162v4.031c-1.149-.17-1.94-.8-2.131-1.718H4zm3.391-3.836c-1.043-.263-1.6-.825-1.6-1.616 0-.944.704-1.641 1.8-1.828v3.495l-.2-.05zm1.591 1.872c1.287.323 1.852.859 1.852 1.769 0 1.097-.826 1.828-2.2 1.939V8.73l.348.086z"/>
                                        </svg>
                                        LKR <?php echo number_format($internship['salary']); ?>/month
                                    </div>
                                <?php endif; ?>

                                <div class="meta-item">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-tag" viewBox="0 0 16 16">
                                        <path d="M6 4.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-1 0a.5.5 0 1 0-1 0 .5.5 0 0 0 1 0z"/>
                                        <path d="M2 1h4.586a1 1 0 0 1 .707.293L15.707 9.707a1 1 0 0 1 0 1.414L10.414 16.414a1 1 0 0 1-1.414 0L.586 8a1 1 0 0 1-.293-.707V3a2 2 0 0 1 2-2zm0 1a1 1 0 0 0-1 1v4.293L9.293 15.707a.5.5 0 0 0 .707 0L14.293 11.414a.5.5 0 0 0 0-.707L6 2.586V2z"/>
                                    </svg>
                                    <?php echo escape($internship['category_name'] ?? 'General'); ?>
                                </div>
                            </div>

                            <div class="internship-description">
                                <p><?php echo substr(strip_tags($internship['description']), 0, 150); ?>...</p>
                            </div>

                            <div class="internship-footer">
                                <div class="footer-info">
                                    <span class="experience-level level-<?php echo $internship['experience_level']; ?>">
                                        <?php echo ucfirst($internship['experience_level']); ?>
                                    </span>
                                    <?php if ($internship['days_left'] > 0): ?>
                                        <span class="deadline-info">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" class="bi bi-calendar-x" viewBox="0 0 16 16">
                                                <path d="M6.146 7.146a.5.5 0 0 1 .708 0L8 8.293l1.146-1.147a.5.5 0 1 1 .708.708L8.707 9l1.147 1.146a.5.5 0 0 1-.708.708L8 9.707l-1.146 1.147a.5.5 0 0 1-.708-.708L7.293 9 6.146 7.854a.5.5 0 0 1 0-.708z"/>
                                                <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/>
                                            </svg>
                                            <?php echo $internship['days_left']; ?> days left
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <div class="action-buttons">
                                    <a href="view_internship.php?id=<?php echo $internship['id']; ?>" class="btn btn-outline-secondary btn-sm">
                                        View Details
                                    </a>
                                    <?php if (isset($applied_internships[$internship['id']])): ?>
                                        <a href="my_applications.php" class="btn btn-primary btn-sm">
                                            View Application
                                        </a>
                                    <?php else: ?>
                                        <a href="apply_vacancy.php?id=<?php echo $internship['id']; ?>" class="btn btn-primary btn-sm">
                                            Apply Now
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination-container">
                        <div class="pagination">
                            <?php 
                            $query_params = $_GET;
                            unset($query_params['page']);
                            $query_string = !empty($query_params) ? '&' . http_build_query($query_params) : '';
                            ?>
                            
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?><?php echo $query_string; ?>" class="btn btn-outline-primary btn-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chevron-left" viewBox="0 0 16 16">
                                        <path fill-rule="evenodd" d="M11.354 1.646a.5.5 0 0 1 0 .708L5.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0z"/>
                                    </svg>
                                    Previous
                                </a>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <a href="?page=<?php echo $i; ?><?php echo $query_string; ?>" 
                                   class="btn btn-sm <?php echo $i === $page ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?><?php echo $query_string; ?>" class="btn btn-outline-primary btn-sm">
                                    Next
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chevron-right" viewBox="0 0 16 16">
                                        <path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z"/>
                                    </svg>
                                </a>
                            <?php endif; ?>
                        </div>
                        
                        <div class="pagination-info">
                            Showing <?php echo ($offset + 1); ?>-<?php echo min($offset + $limit, $total_records); ?> of <?php echo $total_records; ?> internships
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.search-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0;
}

.search-header h1 {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin: 0;
}

.results-count {
    color: #666;
    font-size: 0.9rem;
}

.search-form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.search-bar {
    width: 100%;
}

.search-input-group {
    display: flex;
    gap: 0.5rem;
    max-width: 600px;
}

.search-input {
    flex: 1;
}

.search-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
}

.filters-section {
    border-top: 1px solid #eee;
    padding-top: 1rem;
}

.filters-toggle {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.filters-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    align-items: end;
}

.filter-group {
    display: flex;
    flex-direction: column;
}

.filter-label {
    font-size: 0.9rem;
    font-weight: 500;
    margin-bottom: 0.25rem;
    color: #333;
}

.checkbox-filter {
    justify-content: center;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
    cursor: pointer;
}

.filter-actions {
    display: flex;
    align-items: end;
}

.internships-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: 1.5rem;
}

.find-internship-card {
    border: 1px solid #ddd;
    border-radius: 0.5rem;
    padding: 1.5rem;
    transition: all 0.2s ease;
    background: white;
}

.internship-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    border-color: #007bff;
}

.internship-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.company-info {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    flex: 1;
}

.company-logo {
    background: #f8f9fa;
    border-radius: 0.375rem;
    padding: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

.internship-title {
    margin: 0 0 0.25rem 0;
    font-size: 1.1rem;
    font-weight: 600;
    color: #333;
    line-height: 1.3;
}

.company-name {
    margin: 0;
    color: #666;
    font-size: 0.9rem;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.8rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.status-badge.applied {
    background: #d4edda;
    color: #155724;
}

.internship-meta {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 0.75rem;
    margin-bottom: 1rem;
    font-size: 0.85rem;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #666;
}

.remote-badge {
    background: #e3f2fd;
    color: #1976d2;
    padding: 0.1rem 0.4rem;
    border-radius: 0.25rem;
    font-size: 0.7rem;
    font-weight: 500;
}

.internship-description {
    margin-bottom: 1rem;
}

.internship-description p {
    margin: 0;
    color: #666;
    line-height: 1.5;
    font-size: 0.9rem;
}

.internship-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-top: 1px solid #eee;
    padding-top: 1rem;
}

.footer-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.experience-level {
    padding: 0.2rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 500;
    text-transform: capitalize;
}

.level-beginner { background: #d4edda; color: #155724; }
.level-intermediate { background: #fff3cd; color: #856404; }
.level-advanced { background: #f8d7da; color: #721c24; }

.deadline-info {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.75rem;
    color: #dc3545;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
}

.pagination-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 2rem;
    padding-top: 1rem;
    border-top: 1px solid #eee;
}

.pagination {
    display: flex;
    gap: 0.5rem;
}

.pagination-info {
    font-size: 0.9rem;
    color: #666;
}

@media (max-width: 768px) {
    .internships-grid {
        grid-template-columns: 1fr;
    }
    
    .search-input-group {
        flex-direction: column;
    }
    
    .filters-grid {
        grid-template-columns: 1fr;
    }
    
    .pagination-container {
        flex-direction: column;
        gap: 1rem;
    }
}
</style>


<script src="<?php echo $assets_path . '/js/find_internships.js'; ?>"></script>

<?php
// --- Include the footer ---
require_once '../../includes/footer.php';
?>
