# Database Seeding Scripts

This directory contains scripts to generate sample data for testing the analytics dashboard. Default system users and their profiles are automatically created when the database schema is imported.

## Files

- **`seed-sample-data.php`** - Generates comprehensive sample data for analytics testing
- **`unseed-analytics-data.php`** - Removes all sample data and restores the database to its clean state

## Default Users (Automatically Created)

When you import the `schema.sql` file, the following default users and profiles are automatically created:

### System Users & Profiles
- **Admin** (`admin/admin`) - System administrator with full access
- **University Representative** (`uoc/uoc`) - Student profile: University of Colombo, Computer Science, 3rd year
- **Company Representative** (`company/company`) - Company profile: InternHub Default Company (verified)

⚠️ **Change these default passwords after first login for security!**

## Usage

### 1. Import Database Schema (Automatic Setup)

Import the database schema which automatically creates default users:
```sql
mysql -u your_username -p your_database < /path/to/schema.sql
```

Or use your preferred database management tool to import `db/schema.sql`.

The default users and profiles are created automatically during schema import.

### 2. Generate Sample Data (Optional)

Navigate to: `http://localhost/internship-tracker/db/seeding/seed-sample-data.php`

This generates realistic sample data for testing and demonstration purposes.

### 3. Remove Sample Data (When Done Testing)

Navigate to: `http://localhost/internship-tracker/db/seeding/unseed-analytics-data.php`

This removes all sample data while preserving the default system users and any manually created legitimate data.

## Sample Data Details

### Default System Users (3 users with profiles) (`username/password`)
- **Admin User**: Full system access, user management, analytics (`admin/admin`) - No profile needed
- **University Representative**: Student profile with University of Colombo details (`uoc/uoc`)
- **Company Representative**: Verified company profile for InternHub Default Company (`company/company`)

### Students (40 sample users)
- Usernames: student001 to student040
- Email: student1@university.edu to student40@university.edu
- Password: student123 (for all sample users)
- Profiles include: various universities, majors, GPAs, skills
- Registration dates: spread over the last 8 months

### Companies (15 sample users)
- Usernames: company001 to company015
- Email: hr@company1.com to hr@company15.com
- Password: company123 (for all sample users)
- Profiles include: realistic company names, websites, addresses
- Random verification status

### Internships (25 sample postings)
- Various job titles (Software Developer, UI/UX Designer, etc.)
- Different statuses (draft, published, closed, cancelled)
- Random locations across Sri Lanka
- Mixed paid/unpaid positions
- Different experience levels and duration

### Applications (80 sample applications)
- Random combinations of students and internships
- Various statuses (submitted, under_review, rejected, accepted)
- Realistic application dates
- No duplicate applications per student-internship pair

### System Logs (50 sample entries)
- Various user actions (login, logout, profile updates, etc.)
- Distributed across all user types
- Realistic timestamps

### Notifications (30 sample entries)
- Different types (info, success, warning, error)
- Various titles and messages
- Random read/unread status

## Usage Workflow

### For New Setup
1. **Import Schema**: Import `db/schema.sql` to create database structure and default users
2. **For Testing**: Run `seed-sample-data.php` to generate analytics test data  
3. **After Testing**: Run `unseed-analytics-data.php` to clean up sample data
4. **IMPORTANT**: Change default passwords immediately after first login for security

### For Development/Testing Cycles
1. Generate sample data with `seed-sample-data.php`
2. Test your analytics features
3. Clean up with `unseed-analytics-data.php`
4. Repeat as needed

Note: Default users are automatically available after schema import - no additional setup required.

## Troubleshooting

If you encounter errors:

1. Check database connection settings in `includes/config.php`
2. Ensure you're logged in as an admin user
3. Check PHP error logs for detailed error messages
4. Verify database permissions for creating/deleting records

