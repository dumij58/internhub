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

    function openEditModal(id, firstName, lastName) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_first_name').value = firstName;
        document.getElementById('edit_last_name').value = lastName;
        document.getElementById('editModal').style.display = 'block';
        document.getElementById('edit_first_name').focus();
    }

    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const row = this.closest('tr');
            openEditModal(row.dataset.id, row.dataset.firstname, row.dataset.lastname);
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

    document.getElementById('closeEditModal')?.addEventListener('click', function() {
        document.getElementById('editModal').style.display = 'none';
    });
    document.getElementById('closeAddModal')?.addEventListener('click', function() {
        document.getElementById('addModal').style.display = 'none';
    });
});
