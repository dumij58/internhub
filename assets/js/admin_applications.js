// Handles AJAX for admin applications CRUD

document.addEventListener('DOMContentLoaded', function() {
    // Add Application
    function openAddModal() {
        document.getElementById('addModal').style.display = 'block';
    }

    document.querySelectorAll('.add-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            openAddModal();
        });
    });
    
    document.getElementById('addApplicationForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        fetch('applications.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            alert(data.message);
            if (data.success) location.reload();
        });
    });

    function openEditModal(id, studentId, internshipId, status) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_student_id').value = studentId;
        document.getElementById('edit_internship_id').value = internshipId;
        document.getElementById('edit_status').value = status;
        document.getElementById('editModal').style.display = 'block';
        document.getElementById('edit_student_id').focus();
    }

    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const row = this.closest('tr');
            openEditModal(row.dataset.id, row.dataset.studentid, row.dataset.internshipid, row.dataset.status);
        });
    });

    document.getElementById('editApplicationForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        fetch('applications.php', {
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
            if (confirm('Are you sure you want to delete this application?')) {
                fetch('applications.php', {
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
