-- Create Database
CREATE DATABASE IF NOT EXISTS internhub;
USE internhub;

-- User Types/Roles Table
CREATE TABLE user_types (
    type_id INT AUTO_INCREMENT PRIMARY KEY,
    type_name VARCHAR(50) NOT NULL UNIQUE,
    type_description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Users Table (Main user entity)
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    user_type_id INT NOT NULL,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_type_id) REFERENCES user_types(type_id)
);

-- Student Profiles (Extended info for students)
CREATE TABLE student_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    student_id VARCHAR(20),
    profile_pic_path VARCHAR(255),
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    phone VARCHAR(20),
    university VARCHAR(100),
    major VARCHAR(100),
    year_of_study INT,
    gpa DECIMAL(3,2),
    resume_path VARCHAR(255),
    portfolio_url VARCHAR(255),
    bio TEXT,
    skills TEXT, -- JSON or comma-separated
    languages TEXT, -- JSON or comma-separated
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Company Profiles (For company representatives)
CREATE TABLE company_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    company_logo_path VARCHAR(255),
    company_name VARCHAR(100) NOT NULL,
    industry_type VARCHAR(50),
    company_website VARCHAR(255),
    company_description TEXT,
    address TEXT,
    phone_number VARCHAR(20),
    verified BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Internship Categories (lookup table for internship categories)
CREATE TABLE internship_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Main Internships Table
CREATE TABLE internships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    company_id INT NOT NULL, -- Links to company_profiles
    category_id INT NOT NULL,
    description TEXT NOT NULL,
    requirements TEXT,
    responsibilities TEXT,
    location VARCHAR(100),
    duration_months INT,
    salary DECIMAL(10,2) DEFAULT 0.00,
    application_deadline DATE,
    start_date DATE,
    end_date DATE,
    max_applicants INT DEFAULT 50,
    status ENUM('draft', 'published', 'closed', 'cancelled') DEFAULT 'draft',
    remote_option BOOLEAN DEFAULT FALSE,
    experience_level ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'beginner',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES company_profiles(id),
    FOREIGN KEY (category_id) REFERENCES internship_categories(id),
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);

CREATE TABLE applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    internship_id INT NOT NULL,
    student_id INT NOT NULL, -- Links to users table
    resume_path VARCHAR(255),
    additional_documents TEXT, -- JSON array of file paths
    status ENUM('draft', 'submitted', 'under_review', 'rejected', 'accepted') DEFAULT 'draft',
    application_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_date TIMESTAMP NULL,
    reviewed_by INT NULL,
    interview_scheduled TIMESTAMP NULL,
    interview_notes TEXT,
    FOREIGN KEY (internship_id) REFERENCES internships(id),
    FOREIGN KEY (student_id) REFERENCES users(user_id),
    FOREIGN KEY (reviewed_by) REFERENCES users(user_id)
);

-- System Logs (for audit trail)
CREATE TABLE system_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    details VARCHAR(255),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Notifications Table
CREATE TABLE notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);



-- ====================================
--  INDEXES FOR PERFORMANCE
-- ====================================

-- Users: index for login and lookup
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_user_type_id ON users(user_type_id);

-- Student Profiles: index for user_id
CREATE INDEX idx_student_profiles_user_id ON student_profiles(user_id);

-- Company Profiles: index for user_id
CREATE INDEX idx_company_profiles_user_id ON company_profiles(user_id);
CREATE INDEX idx_company_profiles_company_name ON company_profiles(company_name);

-- Internships: indexes for company, category, and status
CREATE INDEX idx_internships_company_id ON internships(company_id);
CREATE INDEX idx_internships_category_id ON internships(category_id);
CREATE INDEX idx_internships_status ON internships(status);
CREATE INDEX idx_internships_created_by ON internships(created_by);

-- Applications: indexes for internship, student, and status
CREATE INDEX idx_applications_internship_id ON applications(internship_id);
CREATE INDEX idx_applications_student_id ON applications(student_id);
CREATE INDEX idx_applications_status ON applications(status);

-- Notifications: indexes for user and read status
CREATE INDEX idx_notifications_user_id ON notifications(user_id);
CREATE INDEX idx_notifications_is_read ON notifications(is_read);


-- ====================================
--  INSERT DEFAULT DATA
-- ====================================

-- Insert User Types
INSERT INTO user_types (type_name, type_description) VALUES
('admin', 'System administrator with full access'),
('student', 'Student user who can apply for internships'),
('company', 'Company representative who can post internships');

-- Create Default Users (usernames/passwords: admin/admin, uoc/uoc, company/company)
-- Note: These are default passwords - CHANGE THEM AFTER FIRST LOGIN for security!
INSERT INTO users (username, email, password_hash, user_type_id) VALUES
('admin', 'admin@internhub.com', '$2y$10$RINKjF.wPU.jMwzvshFe.OPnSdS2wRBBk.Soaf9NAqSHtm.HWdq3m', 1),
('uoc', 'uoc@university.edu', '$2y$10$V.mAEYf.nNJI7iOSnUGKgu9VFM2WgfBoQsJJagG50PMKGP//xokPe', 2);
-- ('company', 'hr@company.com', '$2y$10$ZvNCGAXp7ctevkzb8hy7aepiohXDbm7fR3o/3DQzbvYfQcWvCyIWu', 3);

-- Create Student Profile for University Representative
INSERT INTO student_profiles (user_id, student_id, first_name, last_name, phone, university, major, year_of_study, gpa, bio) VALUES
(2, 'UOC001', 'Default', 'Student', '+94701234567', 'University of Colombo', 'Computer Science', 3, 3.50, 'Default student account for InternHub.');

-- Create Company Profile for Company Representative  
-- INSERT INTO company_profiles (user_id, company_name, industry_type, company_website, company_description, address, phone_number, verified) VALUES
-- (3, 'InternHub Default Company', 'Technology', 'https://internhub.com', 'Default company account for internship management and application tracking.', '123 Main Street, Colombo 03, Sri Lanka', '+94112345678', 1);


-- Default Internship Categories
INSERT INTO internship_categories (name, description) VALUES
('Software Development', 'Internships focused on software engineering, development and programming'),
('Marketing', 'Internships in marketing, communications, and promotions'),
('Design', 'Internships related to UI/UX, graphic and product design'),
('Data Science', 'Internships in data analysis, machine learning, and data engineering'),
('AI / Machine Learning', 'Internships in artificial intelligence, deep learning, and ML research'),
('DevOps / SRE', 'Internships covering deployment, CI/CD, and site reliability'),
('Quality Assurance', 'Internships in software testing, QA automation and test engineering'),
('Product Management', 'Internships in product planning, roadmaps, and stakeholder coordination'),
('Business Analysis', 'Internships focused on requirements gathering and business processes'),
('Sales', 'Internships in sales, business development, and account management'),
('Human Resources', 'Internships in recruitment, HR operations, and people programs'),
('Finance & Accounting', 'Internships in financial analysis, accounting, and bookkeeping'),
('Customer Support', 'Internships handling customer success, support and helpdesk'),
('Content & Copywriting', 'Internships for content creation, blogging and copywriting'),
('Research & Development', 'Internships in R&D, technical research and prototyping'),
('Cybersecurity', 'Internships in information security, risk and penetration testing'),
('Embedded Systems', 'Internships working on firmware, IoT and embedded hardware'),
('Cloud Engineering', 'Internships for cloud platforms, architecture and operations'),
('Mobile Development', 'Internships for iOS, Android and mobile app development'),
('Web Development', 'Internships in frontend, backend and full-stack web development'),
('Project Management', 'Internships assisting project planning and delivery'),
('Operations', 'Internships in business operations, logistics and process improvement'),
('Healthcare', 'Internships related to healthcare, medical tech and health administration'),
('Environmental / Sustainability', 'Internships focusing on sustainability, environment and policy'),
('Graphics & Multimedia', 'Internships in animation, video, and multimedia production'),
('Blockchain', 'Internships in distributed ledger technology, smart contracts and crypto');