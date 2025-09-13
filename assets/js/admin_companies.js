// Handles AJAX for admin companies CRUD

document.addEventListener('DOMContentLoaded', function() {
    // Add Company
    function openAddModal() {
        document.getElementById('addModal').style.display = 'block';
    }

    document.querySelectorAll('.add-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            openAddModal();
        });
    });
    
    document.getElementById('addCompanyForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        fetch('companies.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            alert(data.message);
            if (data.success) location.reload();
        });
    });

    // Edit Company
    function openEditModal(userId, username, email) {
        document.getElementById('edit_user_id').value = userId;
        document.getElementById('edit_username').value = username;
        document.getElementById('edit_email').value = email;
        document.getElementById('editModal').style.display = 'block';
        document.getElementById('edit_username').focus();
    }

    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const row = this.closest('tr');
            openEditModal(row.dataset.userid, row.dataset.username, row.dataset.email);
        });
    });

    document.getElementById('editCompanyForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        fetch('companies.php', {
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

    // Delete Company
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            if (confirm('Are you sure you want to delete this company?')) {
                fetch('companies.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'delete_user_id=' + this.dataset.userid
                })
                .then(res => res.json())
                .then(data => {
                    alert(data.message);
                    if (data.success) location.reload();
                });
            }
        });
    });

    // Close modal
    document.getElementById('closeEditModal')?.addEventListener('click', function() {
        document.getElementById('editModal').style.display = 'none';
    });
    document.getElementById('closeAddModal')?.addEventListener('click', function() {
        document.getElementById('addModal').style.display = 'none';
    });
});
