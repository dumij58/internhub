// Handles AJAX for admin admins CRUD

document.addEventListener('DOMContentLoaded', function() {
    // Add Admin
    function openAddModal() {
        document.getElementById('addModal').style.display = 'block';
    }

    document.querySelectorAll('.add-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            openAddModal();
        });
    });
    
    document.getElementById('addAdminForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Basic client-side validation
        const password = this.querySelector('input[name="password"]').value;
        if (password.length < 6) {
            alert('Password must be at least 6 characters long.');
            return;
        }
        
        const formData = new FormData(this);
        fetch('admins.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            alert(data.message);
            if (data.success) {
                location.reload();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while adding the admin.');
        });
    });

    // Edit Admin
    function openEditModal(userId, username, email) {
        document.getElementById('edit_user_id').value = userId;
        document.getElementById('edit_username').value = username;
        document.getElementById('edit_email').value = email;
        document.getElementById('editModal').style.display = 'block';
        document.getElementById('edit_username').focus();
    }

    function closeEditModal() {
        document.getElementById('editModal').style.display = 'none';
    }

    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const row = this.closest('tr');
            openEditModal(row.dataset.userid, row.dataset.username, row.dataset.email);
        });
    });

    document.getElementById('editAdminForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        fetch('admins.php', {
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
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while updating the admin.');
        });
    });

    // Delete Admin
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            if (confirm('Are you sure you want to delete this admin?')) {
                fetch('admins.php', {
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
