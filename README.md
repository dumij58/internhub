# InternHub - Internship Application & Tracking System

A simple full-stack web application for Internship application and tracking, developed using native PHP, MySQL, HTML, CSS, and JavaScript.

## ToDo

- Setup Database
- Login Page
- Admin Page
- Homepage (default)
- Homepage (user logged in)
- Help Page
- Functionalities Page
- Analytical reports
- Feedback forms

## Setup

### 1. Setup the environment

- **Recomended** - Install XAMPP v8.2.4 (All-in-one utility with all the required dependencies)
    1. Download and install XAMPP from [Apache Friends](https://www.apachefriends.org/index.html)
    2. Start Apache and MySQL from the XAMPP control panel

    > [!WARNING]
    > If any other version of PHP, MySQL, or Apache is installed on your machine, please ensure that they are not running to avoid port conflicts.

    > [!WARNING]
    > Using a version of XAMPP other than v8.2.4 may lead to compatibility issues.

### 2. Clone the repository

1. Navigate to the XAMPP installation directory (e.g., `C:\xampp\htdocs` on Windows or `/opt/lampp/htdocs` on Linux/Mac)
    ```bash
    cd /path/to/xampp/htdocs
    ```
2. Clone or download this repository and place it inside the `htdocs` folder (e.g., `C:\xampp\htdocs\internhub` or `/opt/lampp/htdocs/internhub`)
    ```bash
    git clone https://github.com/dumij58/internhub.git
    ```

### 3. Initialize Database

1. Access phpmyadmin through the browser `localhost/phpmyadmin`

2. Go to "Import" tab and choose the `schema.sql` file from the `db` folder of the cloned repository

    - Now your tables are created

3. Seed the database with default users

    - from the **terminal**, navigate to the `db/seeding` folder and execute the `seed-default-users.php` file
    - from the **browser**, navigate to `localhost/internhub/db/seeding/seed-default-users.php`

4. (Optional) FOR TESTING: Seed the database with sample data

    - If you want to test the analytical reports, you can,
        - from the terminal, navigate to the `db/seeding` folder and execute the `seed-analytics-data.php` file
        - from the browser, login as admin and navigate to `localhost/internhub/db/seeding/seed-analytics-data.php`
    - When you are done testing, you can remove the sample data by executing the `unseed-analytics-data.php` file in the same way

### 4. Login

1. Open your browser and navigate to;
    - For Admins:
     `http://localhost/internhub/pages/admin/login.php`
    - For Students/Companies:
     `http://localhost/internhub/pages/login.php`
2. Use the default credentials below to log in

    #### Default User Credentials
        - Admin
            - username: admin
            - password: admin

        - Student
            - email: uoc@example.com
            - password: uoc

        - Company
            - email: hr@codalyth.com
            - password: codalyth