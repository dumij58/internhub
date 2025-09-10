-- Migration: Add internship_categories table and link to internships
-- Run in internhub database

USE internhub;

-- 1) Create the internship_categories table
CREATE TABLE IF NOT EXISTS internship_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2) Insert default categories (only if they don't already exist)
INSERT INTO internship_categories (name, description)
SELECT * FROM (SELECT 'Software Development' AS name, 'Internships focused on software engineering, development and programming' AS description) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM internship_categories WHERE name = 'Software Development')
LIMIT 1;

INSERT INTO internship_categories (name, description)
SELECT * FROM (SELECT 'Marketing' AS name, 'Internships in marketing, communications, and promotions' AS description) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM internship_categories WHERE name = 'Marketing')
LIMIT 1;

INSERT INTO internship_categories (name, description)
SELECT * FROM (SELECT 'Design' AS name, 'Internships related to UI/UX, graphic and product design' AS description) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM internship_categories WHERE name = 'Design')
LIMIT 1;

INSERT INTO internship_categories (name, description)
SELECT * FROM (SELECT 'Data Science' AS name, 'Internships in data analysis, machine learning, and data engineering' AS description) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM internship_categories WHERE name = 'Data Science')
LIMIT 1;

-- Additional categories
INSERT INTO internship_categories (name, description)
SELECT * FROM (SELECT 'AI / Machine Learning' AS name, 'Internships in artificial intelligence, deep learning, and ML research' AS description) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM internship_categories WHERE name = 'AI / Machine Learning')
LIMIT 1;

INSERT INTO internship_categories (name, description)
SELECT * FROM (SELECT 'DevOps / SRE' AS name, 'Internships covering deployment, CI/CD, and site reliability' AS description) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM internship_categories WHERE name = 'DevOps / SRE')
LIMIT 1;

INSERT INTO internship_categories (name, description)
SELECT * FROM (SELECT 'Quality Assurance' AS name, 'Internships in software testing, QA automation and test engineering' AS description) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM internship_categories WHERE name = 'Quality Assurance')
LIMIT 1;

INSERT INTO internship_categories (name, description)
SELECT * FROM (SELECT 'Product Management' AS name, 'Internships in product planning, roadmaps, and stakeholder coordination' AS description) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM internship_categories WHERE name = 'Product Management')
LIMIT 1;

INSERT INTO internship_categories (name, description)
SELECT * FROM (SELECT 'Business Analysis' AS name, 'Internships focused on requirements gathering and business processes' AS description) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM internship_categories WHERE name = 'Business Analysis')
LIMIT 1;

INSERT INTO internship_categories (name, description)
SELECT * FROM (SELECT 'Sales' AS name, 'Internships in sales, business development, and account management' AS description) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM internship_categories WHERE name = 'Sales')
LIMIT 1;

INSERT INTO internship_categories (name, description)
SELECT * FROM (SELECT 'Human Resources' AS name, 'Internships in recruitment, HR operations, and people programs' AS description) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM internship_categories WHERE name = 'Human Resources')
LIMIT 1;

INSERT INTO internship_categories (name, description)
SELECT * FROM (SELECT 'Finance & Accounting' AS name, 'Internships in financial analysis, accounting, and bookkeeping' AS description) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM internship_categories WHERE name = 'Finance & Accounting')
LIMIT 1;

INSERT INTO internship_categories (name, description)
SELECT * FROM (SELECT 'Customer Support' AS name, 'Internships handling customer success, support and helpdesk' AS description) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM internship_categories WHERE name = 'Customer Support')
LIMIT 1;

INSERT INTO internship_categories (name, description)
SELECT * FROM (SELECT 'Content & Copywriting' AS name, 'Internships for content creation, blogging and copywriting' AS description) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM internship_categories WHERE name = 'Content & Copywriting')
LIMIT 1;

INSERT INTO internship_categories (name, description)
SELECT * FROM (SELECT 'Research & Development' AS name, 'Internships in R&D, technical research and prototyping' AS description) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM internship_categories WHERE name = 'Research & Development')
LIMIT 1;

INSERT INTO internship_categories (name, description)
SELECT * FROM (SELECT 'Cybersecurity' AS name, 'Internships in information security, risk and penetration testing' AS description) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM internship_categories WHERE name = 'Cybersecurity')
LIMIT 1;

INSERT INTO internship_categories (name, description)
SELECT * FROM (SELECT 'Embedded Systems' AS name, 'Internships working on firmware, IoT and embedded hardware' AS description) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM internship_categories WHERE name = 'Embedded Systems')
LIMIT 1;

INSERT INTO internship_categories (name, description)
SELECT * FROM (SELECT 'Cloud Engineering' AS name, 'Internships for cloud platforms, architecture and operations' AS description) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM internship_categories WHERE name = 'Cloud Engineering')
LIMIT 1;

INSERT INTO internship_categories (name, description)
SELECT * FROM (SELECT 'Mobile Development' AS name, 'Internships for iOS, Android and mobile app development' AS description) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM internship_categories WHERE name = 'Mobile Development')
LIMIT 1;

INSERT INTO internship_categories (name, description)
SELECT * FROM (SELECT 'Web Development' AS name, 'Internships in frontend, backend and full-stack web development' AS description) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM internship_categories WHERE name = 'Web Development')
LIMIT 1;

INSERT INTO internship_categories (name, description)
SELECT * FROM (SELECT 'Project Management' AS name, 'Internships assisting project planning and delivery' AS description) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM internship_categories WHERE name = 'Project Management')
LIMIT 1;

INSERT INTO internship_categories (name, description)
SELECT * FROM (SELECT 'Operations' AS name, 'Internships in business operations, logistics and process improvement' AS description) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM internship_categories WHERE name = 'Operations')
LIMIT 1;

INSERT INTO internship_categories (name, description)
SELECT * FROM (SELECT 'Healthcare' AS name, 'Internships related to healthcare, medical tech and health administration' AS description) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM internship_categories WHERE name = 'Healthcare')
LIMIT 1;

INSERT INTO internship_categories (name, description)
SELECT * FROM (SELECT 'Environmental / Sustainability' AS name, 'Internships focusing on sustainability, environment and policy' AS description) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM internship_categories WHERE name = 'Environmental / Sustainability')
LIMIT 1;

INSERT INTO internship_categories (name, description)
SELECT * FROM (SELECT 'Graphics & Multimedia' AS name, 'Internships in animation, video, and multimedia production' AS description) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM internship_categories WHERE name = 'Graphics & Multimedia')
LIMIT 1;

INSERT INTO internship_categories (name, description)
SELECT * FROM (SELECT 'Blockchain' AS name, 'Internships in distributed ledger technology, smart contracts and crypto' AS description) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM internship_categories WHERE name = 'Blockchain')
LIMIT 1;

-- 3) Add foreign key constraint to internships.category_id referencing internship_categories(id)
-- This assumes internships.category_id column already exists. If not, add it.
ALTER TABLE internships MODIFY COLUMN category_id INT NOT NULL;
ALTER TABLE internships ADD CONSTRAINT fk_internships_category_id FOREIGN KEY (category_id) REFERENCES internship_categories(id) ON DELETE RESTRICT ON UPDATE CASCADE;
