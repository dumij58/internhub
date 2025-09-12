<?php
require_once '../includes/config.php';

// --- Page-specific variables ---
$page_title = 'Home';

if (isLoggedIn()) {
    if (isAdmin()) {
        // Redirect to admin dashboard
        header('Location: pages/admin/index.php');
        exit;
    } else {
        // Redirect to user dashboard
        $role = $_SESSION['role'];
        header("Location: pages/$role/index.php");
        exit;
    }
}

// --- Include the header ---
require_once '../includes/header.php';
?>

<style>
    .internship {
        background: #fff;
        border-radius: 8px;
        margin-bottom: 20px;
        padding: 20px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        transition: 0.3s ease;
    }

    .internship:hover {
        transform: translateY(-3px);
    }

    .internship h2 {
        margin-top: 0;
        color: #003366;
    }

    .role {
        font-weight: bold;
        color: #0055aa;
    }

    .hidden {
        display: none;
        margin-top: 15px;
        animation: slideDown 0.5s ease forwards;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .btn {
        display: inline-block;
        margin-top: 10px;
        padding: 8px 15px;
        background: #0055aa;
        color: #fff;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        text-decoration: none;
        font-size: 14px;
    }

    .btn:hover {
        background: #003366;
    }

    ul {
        margin: 8px 0;
        padding-left: 20px;
    }

    .perks {
        color: #008000;
        font-weight: bold;
    }
</style>
</head>

<body>

    <h1>Available Internship Opportunities</h1>

    <div class="internship">
        <h2>Software Engineer Internship <span class="role">- Tech Innovators Inc. (New York, NY)</span></h2>
        <p><strong>Role Overview:</strong>Join our dynamic engineering team to build cutting-edge web applications and contribute to our
            platform's growth. You'll work alongside senior developers on real projects that impact thousands of users.</p>
        <button class="btn" onclick="toggleDetails('job1')">Show More</button>
        <div id="job1" class="hidden">
            <p><strong>Key Responsibilities:</strong></p>
            <ul>
                <li>Develop and maintain web applications using JavaScript, TypeScript, and React</li>
                <li>Work with Python backend services and REST APIs</li>
                <li>Participate in code reviews and agile development processes</li>
                <li>Write unit tests and contribute to our testing framework</li>
                <li>Collaborate with product managers and designers</li>
            </ul>
            <p><strong>Requirements:</strong></p>
            <ul>
                <li>Computer Science or related field (current student or recent graduate)</li>
                <li>Basic knowledge of JavaScript, HTML, and CSS</li>
                <li>Familiarity with Git version control</li>
                <li>Strong problem-solving and analytical skills</li>
                <li>Excellent communication and teamwork abilities</li>
            </ul>
            <p><strong>Perks & Benefits:</strong></p>
            <ul>
                <li>1:1 mentorship with senior engineers</li>
                <li>$500 monthly learning budget for courses/books</li>
                <li>$2,000 monthly salary</li>
                <li>Flexible work hours and remote options</li>
                <li>Company laptop and development setup</li>
            </ul>
        </div>
    </div>

    <div class="internship">
        <h2>Marketing Intern <span class="role"> Creative Minds Ltd. (Los Angeles, CA)</span></h2>
        <p><strong>Role Overview:</strong> Dive into the world of digital marketing and brand management. You'll support our marketing team in creating compelling campaigns, managing social media presence, and analyzing performance metrics to drive business growth.</p>
        <button class="btn" onclick="toggleDetails('job2')">Show More</button>
        <div id="job2" class="hidden">
            <p><strong>Key Responsibilities:</strong></p>
            <ul>
                <li>Create engaging social media content and manage posting schedules</li>
                <li>Draft marketing emails, blog posts, and landing page copy</li>
                <li>Assist with market research and competitor analysis</li>
                <li>Track and report on campaign performance using analytics tools</li>
                <li>Support event planning and promotional activities</li>
            </ul>

            <p><strong>Requirements:</strong></p>
            <ul>
                <li>Marketing, Business, or Communications major (current student)</li>
                <li>Strong written and verbal communication skills</li>
                <li>Basic knowledge of social media platforms</li>
                <li>Experience with Canva, Adobe Creative Suite, or similar tools</li>
                <li>Creative thinking and attention to detail</li>
            </ul>

            <p><strong>Perks & Benefits:</strong></p>
            <ul>
                <li>Mentorship from senior marketing professionals</li>
                <li>Certificate of completion</li>
                <li>$1,500 monthly salary</li>
                <li>Access to premium marketing tools and software</li>
                <li>Networking opportunities with industry professionals</li>
            </ul>
        </div>
    </div>

    <div class="internship">
        <h2>Data Analyst Intern <span class="role">- DataTech Solutions (San Francisco, CA)</span></h2>
        <p><strong>Role Overview:</strong> Transform raw data into actionable insights that drive business decisions. You'll work with large datasets, create visualizations, and help our team understand customer behavior and market trends through data analysis.</p>
        <button class="btn" onclick="toggleDetails('job3')">Show More</button>
        <div id="job3" class="hidden">

            <p><strong>Key Responsibilities:</strong></p>
            <ul>
                <li>Clean, process, and analyze large datasets using SQL and Python</li>
                <li>Create interactive dashboards and reports using Tableau/Power BI</li>
                <li>Perform statistical analysis and identify trends and patterns</li>
                <li>Collaborate with cross-functional teams to understand data needs</li>
                <li>Present findings to stakeholders through clear visualizations</li>
            </ul>

            <p><strong>Requirements:</strong></p>
            <ul>
                <li>Statistics, Mathematics, Computer Science, or related field</li>
                <li>Basic knowledge of SQL and Python/R</li>
                <li>Experience with Excel and data visualization tools</li>
                <li>Strong analytical and critical thinking skills</li>
                <li>Ability to communicate complex data insights clearly</li>
            </ul>

            <p><strong>Perks & Benefits:</strong></p>
            <ul>
                <li>Hands-on experience with real business data</li>
                <li>Training on advanced analytics tools</li>
                <li>$2,200 monthly salary</li>
                <li>Access to premium data science courses</li>
                <li>Potential for full-time offer upon graduation</li>
            </ul>
        </div>
    </div>

    <script>
        function toggleDetails(id) {
            var section = document.getElementById(id);
            section.classList.toggle('hidden');
            if (section.classList.contains('hidden')) {
                event.target.textContent = 'Show More';
            } else {
                event.target.textContent = 'Show Less';
            }
        }
    </script>

    <?php
    // --- Include the footer ---
    require_once '../includes/footer.php';
    ?>