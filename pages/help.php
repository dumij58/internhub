<?php
include_once '../includes/header.php';
?>

<style>
    body {
        background: url('<?php echo $assets_path; ?>/images/help.jpg') no-repeat center center fixed;
        background-size: cover;
    }
</style>

<div class="help-container">
    <div class="help-section">
        <p class="welcome-text">Welcome to <span class="brand-name">InternHub</span>!</p>
        <p>Whether you're a student, recent graduate, or exploring new
            internship opportunities, we're excited to have you here.</p>
        <p>Click on the expandable sections below for a guide to get started.
            By following these steps, you will be well on your way to utilizing
            InternHub to effectively kickstart your career!</p>

        <details>
            <summary class="highlight">For Students</summary>

            <details>
                <summary>Getting Started with InternHub</summary>
                <div class="step">
                    <p><b>Registration:</b> Click "Register" button on the login page and
                        fill your student details.</p>
                    <p><b>Login:</b> Click "Login" button and use your username and password
                        to access the system.</p>
                </div>
            </details>

            <details>
                <summary>Find & Apply for Internships</summary>
                <div class="step">
                    <p><b>Searching:</b> Use search bar to find internships by
                        company,location or field of study.</p>
                    <p><b>Applying:</b> Click "Apply" button on any internship listing, then
                        upload your resume and submit your application.</p>
                </div>
            </details>

            <details>
                <summary>Tracking Applications</summary>
                <div class="step">
                    <p><b>Status:</b> Check your application status in "My Applications"
                        section.</p>
                    <p><b>Updates:</b> You'll recieve an e-mail when your application status
                        changes.</p>
                </div>
            </details>

        </details>

        <details>
            <summary class="highlight">For Administration</summary>

            <details>
                <summary>System Management</summary>
                <div class="step">
                    <p><b>Login:</b> Use admin credentials to access the system.</p>
                    <p><b>User Management:</b> Add,edit or disable user accounts.</p>
                </div>
            </details>

            <details>
                <summary>Internship Management</summary>
                <div class="step">
                    <p><b>Add Internships:</b> Click "Add New" in the internship section to
                        create new internship listings.</p>
                    <p><b>Internship Listing Management:</b> Edit or remove internship
                        opportunities.</p>
                </div>
            </details>

            <details>
                <summary>Reports</summary>
                <div class="step">
                    <p><b>Generate Reports:</b> Make reports using application statistics
                        and other relavant information.</p>
                    <p><b>Exporting Data:</b> Export reports as csv or pdf for further
                        analysis.</p>
                </div>
            </details>

        </details>
    </div>

    <div class="help-section">
        <h7><u>FAQ</u></h7>

        <div class="faq_question">What if I forgot password?</div>
        <div class="faq_answer"><i>Click on "Forgot Password" link on login page and reset
                password using your e-mail address.</i></div>

        <div class="faq_question">How can I update my profile?</div>
        <div class="faq_answer"><i>Go to "My Profile" and edit personal information and upload
                relavant documents.</i></div>

        <div class="faq_question">Can I apply to as many internship vacancies I want?</div>
        <div class="faq_answer"><i>There is no limit to how many internship vacancies you can
                apply to but ensure that you provide a specific cover letter for each
                position you apply to.</i></div>

        <div class="faq_question">After submitting my online internship application,do I need to
            take any other action to ensure that my application is reviewed by specific
            company that I am interested in?</div>
        <div class="faq_answer"><i>You don't need to take any further action after submitting
                the application.You will recieve an e-mail notification as well as you
                can also check the status in your dashboard when your application status
                changes.</i></div>

        <div class="faq_question">Can I delete an application after submitting it?</div>
        <div class="faq_answer"><i>You can but within 24 hours you should delete the submitted
                application or contact support assistance.</i></div>

    </div>

    <div class="help-section">
        <h2 align="center" >Contact Us</h2>

        <form class="contact-us-form">
            <div class="contact-us-input">
                <table border="0" width="90%" align="center">
                    <tr>
                        <td colspan="1">Name: </td>
                        <td colspan="3"> <input type="text" name="name"> </td>
                    </tr>
                    <tr>
                        <td colspan="1">Email Address: </td>
                        <td colspan="3"> <input type="email" name="email"> </td>
                    </tr>
                    <tr>
                        <td>Subject: </td>
                        <td colspan="3"> <input type="text" name="subject"> </td>
                    </tr>
                    <tr>
                        <td colspan="4"><label for="message">Message: </label></td>
                    </tr>
                    <tr align="center">
                        <td colspan="4"><textarea id="message" name="message" required> </textarea></td>
                    </tr>
                    <tr align="center">
                        <td colspan="4"><button class="contact-submit-button" type="submit">Submit</button></td>
                    </tr>

                </table>
            </div>
        </form>
    </div>

    <div class="contact_info">
        <h9><b>Connect with us on</b></h9>
        <p><b>Email:</b> support@InternHub.com</p>
        <p><b>Phone:</b> 011-2159876</p>
        <p><b>Hours:</b> Weekdays, 9.00 AM - 5.00 PM</p>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>