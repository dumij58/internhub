-- Enhance the existing internships table to match vacancy posting requirements
-- Add missing columns for comprehensive vacancy posting

ALTER TABLE internships 
ADD COLUMN department VARCHAR(100) AFTER title,
ADD COLUMN required_skills TEXT AFTER requirements,
ADD COLUMN preferred_skills TEXT AFTER required_skills,
ADD COLUMN learning_outcomes TEXT AFTER preferred_skills,
ADD COLUMN education_level ENUM('undergraduate', 'graduate', 'any') DEFAULT 'any' AFTER learning_outcomes,
ADD COLUMN degree_fields TEXT AFTER education_level,
ADD COLUMN year_requirement VARCHAR(50) AFTER degree_fields,
ADD COLUMN gpa_requirement DECIMAL(3,2) AFTER year_requirement,
ADD COLUMN internship_type ENUM('full-time', 'part-time') DEFAULT 'full-time' AFTER gpa_requirement,
ADD COLUMN working_hours VARCHAR(50) AFTER internship_type,
ADD COLUMN documents_required TEXT AFTER working_hours,
ADD COLUMN how_to_apply TEXT AFTER documents_required;

-- Enhance the applications table to match application form requirements
ALTER TABLE applications
ADD COLUMN full_name VARCHAR(100) AFTER student_id,
ADD COLUMN email VARCHAR(100) AFTER full_name,
ADD COLUMN phone VARCHAR(20) AFTER email,
ADD COLUMN current_address TEXT AFTER phone,
ADD COLUMN university VARCHAR(100) AFTER current_address,
ADD COLUMN degree_program VARCHAR(100) AFTER university,
ADD COLUMN year_of_study INT AFTER degree_program,
ADD COLUMN graduation_year INT AFTER year_of_study,
ADD COLUMN gpa DECIMAL(3,2) AFTER graduation_year,
ADD COLUMN key_skills TEXT AFTER gpa,
ADD COLUMN areas_of_interest TEXT AFTER key_skills,
ADD COLUMN cover_letter_path VARCHAR(255) AFTER resume_path,
ADD COLUMN portfolio_links TEXT AFTER cover_letter_path,
ADD COLUMN cover_letter_text TEXT AFTER portfolio_links;

-- Create a table for application documents (for multiple file uploads)
CREATE TABLE application_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    document_type ENUM('resume', 'cover_letter', 'portfolio', 'other') NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    file_size INT NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
);

-- Create indexes for better performance
CREATE INDEX idx_application_documents_application_id ON application_documents(application_id);
CREATE INDEX idx_application_documents_document_type ON application_documents(document_type);

-- Create a table for internship categories (if not exists)
CREATE TABLE IF NOT EXISTS internship_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default categories
INSERT IGNORE INTO internship_categories (name, description) VALUES
('Technology', 'Software development, IT, and technology-related internships'),
('Business', 'Marketing, finance, HR, and business operations'),
('Engineering', 'Mechanical, electrical, civil, and other engineering fields'),
('Healthcare', 'Medical, pharmaceutical, and healthcare-related internships'),
('Education', 'Teaching, research, and educational administration'),
('Media & Communications', 'Journalism, public relations, and media production'),
('Design', 'Graphic design, UX/UI, and creative design fields'),
('Data Science', 'Analytics, machine learning, and data analysis'),
('Sales', 'Sales, customer service, and business development'),
('Other', 'Other internship categories not listed above');

-- Update the internships table to reference categories properly
-- First, let's add a default category_id if it doesn't exist
UPDATE internships SET category_id = 1 WHERE category_id IS NULL OR category_id = 0;

-- Add foreign key constraint for category_id
ALTER TABLE internships 
ADD CONSTRAINT fk_internships_category 
FOREIGN KEY (category_id) REFERENCES internship_categories(id);
