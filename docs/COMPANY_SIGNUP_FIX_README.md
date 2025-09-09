# Company Signup Form Fix

## Problem Fixed
The original registration system was hardcoded to only create student accounts (user_type_id = 2) and didn't provide a proper company registration form with company-specific fields.

## Changes Made

### 1. Updated Registration Form (`pages/auth/login.php`)
- Added user type selection dropdown (Student/Company)
- Added JavaScript functionality to show/hide the user type field appropriately

### 2. Updated Registration Logic (`pages/auth/register.php`)
- Modified to accept and validate user type selection
- Updated to create accounts with correct user_type_id (2 for students, 3 for companies)
- Enhanced login logic to check appropriate profile tables based on user type
- Added proper redirection for companies to company profile completion

### 3. Created Company Profile Form (`pages/auth/company_details.php`)
- **Company Information**: Company Name, Industry Type, Website, Logo upload
- **Contact Information**: Official Email, Phone Number, Company Address
- **Company Description**: Brief description of company and mission

### 4. Created Company Profile Handler (`pages/auth/save_company_details.php`)
- Handles form submission and database insertion
- Supports file upload for company logo
- Creates directory structure for company logos
- Proper error handling and validation

### 5. Updated JavaScript (`assets/js/login.js`)
- Added functionality to show/hide user type field during signup
- Proper form validation for required fields

### 6. Database Migration Required
Created migration file: `db/migrations/add_industry_type_to_company_profiles.sql`

## Database Migration Required

**IMPORTANT**: You need to run the following SQL commands to add the missing fields to the company_profiles table:

```sql
-- Add industry_type field to company_profiles table
ALTER TABLE company_profiles 
ADD COLUMN industry_type VARCHAR(50) AFTER company_name;

-- Add phone_number field to company_profiles table
ALTER TABLE company_profiles 
ADD COLUMN phone_number VARCHAR(20) AFTER address;
```

## How It Works Now

### Student Registration Flow:
1. User selects "Student" account type during signup
2. Account created with user_type_id = 2
3. After login, redirected to student profile completion form
4. Student fills out academic details, skills, etc.

### Company Registration Flow:
1. User selects "Company" account type during signup
2. Account created with user_type_id = 3
3. After login, redirected to company profile completion form
4. Company fills out company information, industry, contact details, etc.

## Form Fields Included

### Student Form (existing, enhanced):
- **Basic Information**: Full Name, Email, Phone
- **Academic Details**: University, Degree Program, Year of Study, GPA
- **Skills & Interests**: Key Skills, Areas of Interest
- **Resume/Portfolio**: LinkedIn/GitHub/Portfolio links

### Company Form (new):
- **Company Information**: Company Name, Industry Type, Website, Logo
- **Contact Information**: Official Email, Phone Number, Company Address
- **Company Description**: Mission and company description

## Testing
1. Run the database migration
2. Test student registration and profile completion
3. Test company registration and profile completion
4. Verify proper redirections based on user type
5. Test file upload for company logos

## Files Modified/Created
- `pages/auth/login.php` - Added user type selection
- `pages/auth/register.php` - Updated registration and login logic
- `pages/auth/company_details.php` - New company profile form
- `pages/auth/save_company_details.php` - New company profile handler
- `assets/js/login.js` - Updated JavaScript for form handling
- `db/migrations/add_industry_type_to_company_profiles.sql` - Database migration
