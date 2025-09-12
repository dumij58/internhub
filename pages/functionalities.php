<?php
include_once '../includes/header.php';
?>

<section class="func-container func-card">
    <h1>System Functionalities</h1>
    <p>
        InternHub provides separate functionalities for
        <strong>Students</strong>, <strong>Companies</strong> and <strong>Administrators</strong>.
        Students can explore opportunities and apply, while Companies (or Company Moderators) manage internships.
    </p>
</section>

<!-- Student Functionalities -->
<section class="func-container func-card" id="studentOnly">
    <h2>Student Functionalities</h2>
    <ul>
        <li>Browse available internships with details (company, role, location, deadline).</li>
        <li>Search and filter internships by category, company, or deadline.</li>
        <li>Apply for an internship by filling out an online application form.</li>
        <li>Upload supporting documents (CV, cover letter, certificates).</li>
        <li>View submitted applications on the <strong>Home</strong> page.</li>
        <li>Track application status:
            <span class="badge pending">Pending</span>,
            <span class="badge status-accepted">Approved</span>,
            <span class="badge status-rejected">Rejected</span>.
        </li>
        <li>Edit or withdraw applications before deadlines.</li>
        <li>Receive notifications for updates (status changes, deadlines).</li>
        <li>Save internships to a personal <em>Wishlist</em> for later.</li>
        <li>Logout after completing your actions.</li>
    </ul>
<!--
    <h3>Available Internships</h3>
    <div class="search-bar">
        <label for="searchInternships" class="visually-hidden">Search internships</label>
        <input type="text" id="searchInternships" placeholder="Search internships...">
        <button type="button" onclick="filterInternships()">Search</button>
    </div>
    <div class="grid two" id="internshipList"></div>-->
</section>

<!-- Company Functionalities -->
<section class="func-container func-card" id="companyOnly">
    <h2>Company Functionalities</h2>
    <ul>
        <li>Register and manage company profile.</li>
        <li>Post and manage internship listings (coming soon).</li>
        <li>Review student applications (coming soon).</li>
    </ul>

</section>

<!-- Admin Functionalities -->
<section class="func-container func-card hidden" id="notStudent">
    <h2>Administrator Functionalities</h2>
    <ul>
        <li>Add, edit, and delete internship opportunities.</li>
        <li>Review and manage student applications.</li>
        <li>Update application status (Pending → Approved / Rejected).</li>
        <li>View uploaded student documents (CVs, cover letters).</li>
        <li>Generate reports (internships, applications, approvals).</li>
        <li>Send notifications or announcements to students.</li>
        <li>Monitor statistics and upcoming deadlines.</li>
    </ul>
    <p class="alert">
        Go to the <a href="admin.html" class="link">Admin Page</a> to perform these actions.
    </p>
</section>

<?php include_once '../includes/footer.php'; ?>