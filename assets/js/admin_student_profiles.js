// Handles AJAX for admin student profiles CRUD

document.addEventListener('DOMContentLoaded', function() {
    // Add Student Profile
    function openAddModal() {
        console.log('Opening Add Modal');
        document.getElementById('addModal').style.display = 'block';
    }

    document.querySelectorAll('.add-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            openAddModal();
        });
    });
    
    document.getElementById('addStudentProfileForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        fetch('student_profiles.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            alert(data.message);
            if (data.success) location.reload();
        });
    });

    function openEditModal(id, studentId, firstName, lastName, phone, university, major, yearOfStudy, gpa, bio, skills, languages, portfolioUrl) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_student_id').value = studentId || '';
        document.getElementById('edit_first_name').value = firstName;
        document.getElementById('edit_last_name').value = lastName;
        document.getElementById('edit_phone').value = phone || '';
        document.getElementById('edit_university').value = university || '';
        document.getElementById('edit_major').value = major || '';
        document.getElementById('edit_year_of_study').value = yearOfStudy || '';
        document.getElementById('edit_gpa').value = gpa || '';
        document.getElementById('edit_bio').value = bio || '';
        document.getElementById('edit_skills').value = skills || '';
        document.getElementById('edit_languages').value = languages || '';
        document.getElementById('edit_portfolio_url').value = portfolioUrl || '';
        document.getElementById('editModal').style.display = 'block';
        document.getElementById('edit_first_name').focus();
    }

    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const row = this.closest('tr');
            openEditModal(
                row.dataset.id, 
                row.dataset.studentid,
                row.dataset.firstname, 
                row.dataset.lastname,
                row.dataset.phone,
                row.dataset.university,
                row.dataset.major,
                row.dataset.yearofstudy,
                row.dataset.gpa,
                row.dataset.bio,
                row.dataset.skills,
                row.dataset.languages,
                row.dataset.portfoliourl
            );
        });
    });

    document.getElementById('editStudentProfileForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        fetch('student_profiles.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            alert(data.message);
            if (data.success) {
                document.getElementById('editModal').style.display = 'none';
                location.reload();
            }
        });
    });

    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            if (confirm('Are you sure you want to delete this student profile?')) {
                fetch('student_profiles.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'delete_id=' + this.dataset.id
                })
                .then(res => res.json())
                .then(data => {
                    alert(data.message);
                    if (data.success) location.reload();
                });
            }
        });
    });

    // Modal close handlers
    document.getElementById('closeEditModal')?.addEventListener('click', function() {
        document.getElementById('editModal').style.display = 'none';
    });
    document.getElementById('closeAddModal')?.addEventListener('click', function() {
        document.getElementById('addModal').style.display = 'none';
    });

    // Close modals when clicking outside
    window.addEventListener('click', function(event) {
        const modals = ['addModal', 'editModal'];
        modals.forEach(modalId => {
            const modal = document.getElementById(modalId);
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
    });
});
