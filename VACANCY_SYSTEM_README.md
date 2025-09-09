# Job Vacancy System - Implementation Guide

## Overview
This document describes the comprehensive job vacancy system implemented for the InternHub platform, allowing companies to post internship opportunities and students to apply for them.

## Features Implemented

### For Companies
1. **Post Job Vacancies** (`pages/company/post_vacancy.php`)
   - Comprehensive form with all required fields
   - Basic details (title, department, company info)
   - Internship description and requirements
   - Eligibility criteria (education level, skills, GPA)
   - Internship details (location, duration, stipend, working hours)
   - Application information and deadlines

2. **Manage Vacancies** (`pages/company/view_vacancies.php`)
   - View all posted vacancies
   - Update vacancy status (publish, close, delete)
   - See application counts for each vacancy
   - Quick access to applications

3. **Application Management** (`pages/company/view_applications.php`)
   - View all applications for company's vacancies
   - Review applicant details and documents
   - Update application status (shortlist, accept, reject)
   - Download resumes and cover letters

### For Students
1. **Find Internships** (`pages/student/find_internships.php`)
   - Browse available internship opportunities
   - Search and filter by category, location, type
   - View detailed vacancy information
   - Check application status

2. **Apply for Internships** (`pages/student/apply_vacancy.php`)
   - Comprehensive application form with:
     - Personal information (name, email, phone, address)
     - Academic details (university, degree, year, GPA)
     - Skills and interests
     - Document uploads (resume, cover letter)
     - Portfolio links
   - Auto-filled vacancy information
   - File upload validation

3. **Track Applications** (`pages/student/my_applications.php`)
   - View all submitted applications
   - Check application status
   - Download submitted documents
   - See application timeline

## Database Enhancements

### New Tables
- `internship_categories` - Categories for organizing internships
- `application_documents` - Multiple file uploads for applications

### Enhanced Tables
- `internships` - Added fields for comprehensive vacancy posting
- `applications` - Added fields for detailed application information

### Migration File
Run `db/migrations/enhance_vacancy_system.sql` to apply database changes.

## File Structure

```
pages/
├── company/
│   ├── post_vacancy.php          # Company vacancy posting form
│   ├── view_vacancies.php        # Company vacancy management
│   └── view_applications.php     # Company application management
└── student/
    ├── find_internships.php      # Student internship browsing
    ├── apply_vacancy.php         # Student application form
    └── my_applications.php       # Student application tracking

db/
└── migrations/
    └── enhance_vacancy_system.sql # Database enhancements
```

## Key Features

### Company Vacancy Posting Form
- **Basic Details**: Title, department, company name, category
- **Description**: Job description, required/preferred skills, learning outcomes
- **Eligibility**: Education level, degree fields, year requirements, GPA
- **Details**: Location, type, duration, dates, stipend, working hours
- **Application Info**: Deadline, required documents, instructions

### Student Application Form
- **Personal Info**: Full name, email, phone, address
- **Academic**: University, degree, year, graduation, GPA
- **Skills**: Key skills, areas of interest
- **Documents**: Resume (required), cover letter (optional), portfolio links
- **Auto-filled**: Company name, internship title, application date

### Application Management
- **Status Tracking**: Submitted → Under Review → Shortlisted → Accepted/Rejected
- **Document Access**: Download resumes and cover letters
- **Bulk Actions**: Update multiple application statuses
- **Timeline**: Track application and review dates

## Security Features
- File upload validation (type, size)
- SQL injection prevention with prepared statements
- XSS protection with output escaping
- Access control based on user roles
- Session management and authentication

## Usage Instructions

### For Companies
1. Complete company profile setup
2. Navigate to "Post New Vacancy" from dashboard
3. Fill in comprehensive vacancy details
4. Publish the vacancy
5. Monitor applications in "View Applications"
6. Update application statuses as needed

### For Students
1. Complete student profile setup
2. Browse internships in "Find Internships"
3. Click "Apply Now" on desired positions
4. Fill in application form with documents
5. Submit application
6. Track status in "My Applications"

## Technical Notes
- Uses Bootstrap for responsive UI
- Font Awesome icons for better UX
- File uploads stored in `uploads/applications/` directory
- Database uses proper foreign key relationships
- Activity logging for audit trail
- Error handling and user feedback

## Future Enhancements
- Email notifications for application status changes
- Advanced search and filtering options
- Application deadline reminders
- Interview scheduling system
- Company rating and review system
- Analytics dashboard for companies
