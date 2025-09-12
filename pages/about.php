<?php
require_once '../includes/config.php';

// --- Page-specific variables ---
$page_title = 'About Us';

// --- Include the header ---
require_once '../includes/header.php';
?>

<style>
    body {
        background: url('<?php echo $assets_path; ?>/images/aboutus.jpg') no-repeat center center fixed;
        background-size: cover;
    }
</style>

<section> 
    <div class="container">
        <section class="section">
            <h1>About Us</h1>
            <p>At the core of our platform is a simple but powerful idea: to make professional growth accessible to every student. We created this system to solve the common challenges in the internship application process—a fragmented landscape of different company portals, a lack of transparency, and the difficulty students face in finding opportunities that truly fit their ambitions. Our platform serves as a central hub, connecting talented students with a wide array of internships, from bustling startups to global corporations, all in one place. We're here to simplify the journey and empower the next generation of professionals to gain the real-world experience they need to succeed.</p>
        </section>

        <section class="section">
            <h2>Our Mission</h2>
            <p>Our mission is to bridge the gap between talented students and valuable internship opportunities by providing a centralized, efficient, and transparent platform. We aim to streamline the entire application and tracking process, making it easier for students to find and apply for internships that align with their career goals. By connecting students with a diverse range of companies, we are dedicated to fostering the next generation of professionals and empowering them to gain real-world experience. Our system is designed to remove the traditional barriers and complexities of internship applications, ensuring a smooth and equitable experience for every user.</p>
        </section>

        <section class="section">
            <h2>Our Vision</h2>
            <p>We envision a future where every student, regardless of their background or location, has the opportunity to launch a successful career through meaningful internships. Our vision is to become the leading global platform for internship applications and management, recognized for our commitment to innovation, accessibility, and user-centric design. We strive to create a dynamic ecosystem where educational institutions, employers, and students can collaborate seamlessly, sharing knowledge and creating pathways to professional growth. Ultimately, we seek to redefine the internship landscape, making it a powerful and accessible tool for career development and economic empowerment worldwide.</p>
        </section>

        <section class="section">
            <h2>What We Stand For</h2>
            <ul class="values-list">
                <li><strong>Student Success:</strong> Our primary focus is on empowering students to achieve their academic and professional goals. We are committed to providing the resources and tools they need to secure internships that will shape their future careers.</li>
                <li><strong>Equal Opportunity:</strong> We believe that access to professional development should be equitable. Our platform is built to reduce bias and provide a fair chance for all students to be considered for internships, regardless of their school, major, or socioeconomic background.</li>
                <li><strong>Transparency:</strong> We are dedicated to providing a clear and honest process for all users. From application status updates to employer feedback, we ensure that students and companies have full visibility into the internship journey.</li>
                <li><strong>Innovation:</strong> We are constantly seeking new ways to improve the internship process. Our platform leverages technology to provide a seamless, intuitive, and effective experience for everyone involved.</li>
            </ul>
        </section>
    </div>
</section>


<?php
// --- Include the footer ---
require_once '../includes/footer.php';
?>