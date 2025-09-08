# Developer Guide

Stack: Apache + PHP + MySQL + HTML/CSS + Vanilla JS  
Constraints: No frameworks, libraries, or external APIs

## Table of Contents

1. Overview and Scope
2. Architecture and Tech Choices
3. Project Structure
4. Setup and Configuration
5. Database Schema
6. Security Model
7. Authentication & Authorization
8. Admin Features
9. Student Features (Planned)
10. Company Features (Planned)
11. Frontend (CSS/JS)
12. Testing & QA
13. API Structure
14. Time-Savers and Tips

---

## 1) Overview and Scope

InternHub is a comprehensive internship tracking and application management system with three distinct user roles.


**Implemented Features:**

- Multi-role authentication system (Admin, Student, Company)
- Admin dashboard with analytics
- Complete admin CRUD operations for:
  - Students
  - Companies
  - Internships
  - Applications
  - Student Profiles
  - Company Profiles
  - System Logs
- Session-based authentication
- User profile management

**Planned Features:**

- [ ] Browse available internships (Student)
- [ ] Apply for internships (Student)
- [ ] Track application status (Student)
- [ ] Receive notifications (Student)
- [ ] Resume upload (Student)
- [ ] Post new internships (Company)
- [ ] Manage internship listings (Company)
- [ ] Review student applications (Company)
- [ ] Company profile management (Company)
- [x] System analytics (Admin)

**Roles:**

- **Admin**: Full system management including users, companies, internships, applications
- **Student**: Browse and apply for internships, manage profile (partial implementation)
- **Company**: Post internships, manage applications (planned)

**Current Pages:**

- Home page (index.php)
- Login/Registration system
- Admin dashboard with analytics and logs
- Error handling pages
- User details/profile management

---

## 2) Architecture and Tech Choices

- **Server**: Apache (mod_php)
- **Language**: PHP 8.x with:
  - `password_hash`/`password_verify` for secure credentials
  - PDO for MySQL with prepared statements
  - Session-based authentication
- **Database**: MySQL 8.x (InnoDB) - Database name: `internhub`
- **Frontend**: Pure HTML/CSS/JS with AJAX for admin operations
- **Routing**: File-based routing (no frameworks)
- **Security**:
  - Role-based access control via helper functions
  - XSS protection via output escaping
  - SQL injection prevention via prepared statements
  - Activity logging for audit trails

---

## 3) Project Structure

**Current Structure:**

```text
internship-tracker/
  index.php                 # Home page
  
  assets/
    css/
      style.css            # Main stylesheet
    images/
      logo.webp           # Application logo
    js/
      login.js            # Login form switching
      admin_*.js          # AJAX handlers for admin CRUD operations
      
  db/
    schema.sql            # Complete database schema with sample data
    
  includes/
    config.php            # Database config, helper functions, auth
    header.php            # Reusable header with navigation
    footer.php            # Reusable footer
    admin_back_to_dash.php # Admin navigation helper
    
  pages/
    index.php             # Default page
    error.php             # Error handling page
    admin/
      index.php           # Admin dashboard
      login.php           # Admin-specific login
      analytics.php       # System analytics (planned)
      tasks/              # Admin CRUD operations
        students.php
        companies.php
        internships.php
        applications.php
        student_profiles.php
        company_profiles.php
        system_logs.php
    auth/
      login.php           # Main login page
      register.php        # User registration
      logout.php          # Session termination
      user_details.php    # User profile form
      save_user_details.php # Profile save handler
      LoginPage_README.md # Login system documentation

    company/               # Company pages
      index.php           # Company dashboard (redirects to dashboard.php)
      dashboard.php       # Company dashboard (main control center)
      post_internship.php # Post internship (planned)
      manage_internships.php # Manage internships (planned)
      view_applications.php # View applications (planned)
      update_profile.php  # Handle profile updates
      change_password.php # Handle password changes
    
    student/               # Student pages
      index.php           # Student dashboard (partial)
      profile.php         # Student profile (partial)
      manage_applications.php  # Manage applications (partial)
      browse_internships.php # Browse internships (planned)
```

---

## 4) Setup and Configuration

**Database Setup:**

1. Create MySQL database named `internhub`
2. Import `db/schema.sql` which includes:
   - Complete table structure
   - Indexes for performance
   - Default user accounts
3. Default accounts:
   - Admin: admin@example.com / admin123
   - Student: student@example.com / student123
   - Company: company@example.com / company123

**Configuration:**

- Edit `includes/config.php`:
  - Set database credentials (DB_HOST, DB_NAME, DB_USER, DB_PASS)
  - Configure site URL and paths
  - Set timezone (currently Asia/Colombo)

**Apache Setup:**

- DocumentRoot should point to the project root
- Ensure mod_rewrite is enabled
- PHP 8.x+ required

---

## 5) Database Schema

**Core Tables:**

- `user_types`: Role definitions (admin, student, company)
- `users`: Main user accounts with authentication
- `student_profiles`: Extended student information
- `company_profiles`: Company details
- `internships`: Internship postings
- `applications`: Student applications to internships
- `system_logs`: Activity audit trail
- `notifications`: User notifications (planned)

**Key Features:**

- Foreign key constraints for data integrity
- Performance indexes on lookup columns
- Activity logging for all user actions
- Support for file uploads (resumes, documents)

---

## 6) Security Model

**Authentication:**

- Email/password with `password_hash()` (PASSWORD_DEFAULT)
- Session-based authentication
- Role-based access control

**Authorization Functions:**

- `requireLogin()`: Ensures user is authenticated
- `requireAdmin()`: Restricts access to admin users
- `isLoggedIn()`: Checks authentication status
- `isAdmin()`: Checks admin privileges

**Data Protection:**

- All database queries use prepared statements
- Output escaping via `escape()` function
- Activity logging with `logActivity()`
- Error handling with proper HTTP status codes

---

## 7) Authentication & Authorization

**Login Flow:**

1. User enters credentials on login page
2. System verifies against database
3. Session variables set on success:
   - `$_SESSION['user_id']`
   - `$_SESSION['username']`
   - `$_SESSION['role']`
4. Role-based redirects to appropriate dashboard

**User Roles:**

- **Type ID 1**: Admin - Full system access
- **Type ID 2**: Student - Personal profile and applications
- **Type ID 3**: Company - Post internships and manage applications

**Profile Management:**

- New users redirected to profile completion
- Student profiles include education, skills, contact info
- Resume upload capability (planned)

---

## 8) Admin Features

**Dashboard (pages/admin/index.php):**

- System analytics overview
- Quick navigation to all admin tasks
- Recent system logs display

**CRUD Operations (pages/admin/tasks/):**

- **Students**: Add/edit/delete student accounts
- **Companies**: Manage company accounts
- **Internships**: Create and manage internship postings
- **Applications**: Track and update application status
- **Student Profiles**: Extended student information
- **Company Profiles**: Company details and verification
- **System Logs**: Complete audit trail

**Features:**

- AJAX-powered interfaces for seamless UX
- Form validation and error handling
- Bulk operations support
- Real-time updates without page refresh

---

## 9) Student Features (Planned/Partial)

**Profile Management:**

- Complete profile form (user_details.php)
- Education and skills tracking
- Contact information
- Resume upload (framework ready)

**Planned Features:**

- Browse available internships
- Apply for internships
- Track application status
- Receive notifications

---

## 10) Company Features (Planned)

**Navigation Ready:**

- Company-specific menu items in header
- Role-based routing structure

**Planned Features:**

- Post new internships
- Manage internship listings
- Review student applications
- Company profile management

---

## 11) Frontend (CSS/JS)

**Styling:**

- Single CSS file: `assets/css/style.css`
- Responsive design
- Clean admin interface with tables and forms
- Logo and branding elements

**JavaScript:**

- `login.js`: Form switching between login/register
- `admin_*.js`: AJAX handlers for each admin CRUD module
- Pure vanilla JS, no frameworks
- Modular structure for maintainability

**Key JS Features:**

- Form validation
- AJAX form submissions
- Dynamic modal management
- Real-time UI updates

---

## 12) Testing & QA

**Test Structure:**

- `test/` directory with isolated testing
- Separate test database setup
- Login system testing

**Security Testing:**

- SQL injection prevention (all queries use prepared statements)
- XSS protection (all output escaped)
- Authentication bypass attempts
- Role-based access control

**Functional Testing:**

- CRUD operations for all admin modules
- Login/logout flows
- Profile management
- Error handling

---

## 13) API Structure

**AJAX Endpoints:**
All admin CRUD operations use POST requests with JSON responses:

```php
// Standard response format
{
    "success": true|false,
    "message": "Operation result message"
}
```

**Operation Patterns:**

- `add_*`: Create new records
- `edit_*`: Update existing records  
- `delete_*`: Remove records

**Validation:**

- Server-side validation for all inputs
- Duplicate checking for unique fields
- Required field validation

---

## 14) Time-Savers and Tips

**Current Patterns:**

- Consistent CRUD structure across all admin modules
- Reusable AJAX patterns in JavaScript files
- Standard form validation and error handling
- Modular header/footer includes

**Development Workflow:**

1. Database changes → Update schema.sql
2. Backend logic → Add to appropriate task file
3. Frontend → Add corresponding JavaScript file
4. Test → Use admin interface for validation

**Code Standards:**

- Use prepared statements for all database operations
- Escape all output with `escape()` function
- Log important actions with `logActivity()`
- Follow existing naming conventions

**Quick Development:**
- Copy existing admin task structure for new modules
- Use provided AJAX patterns for new features
- Leverage existing CSS classes for consistent styling
- Use header role checking for navigation

**Database Helpers:**
- `getDB()`: Get database connection
- `hashPassword()`: Secure password hashing
- `getUserRole()`: Convert type ID to role name
- `logActivity()`: Record user actions