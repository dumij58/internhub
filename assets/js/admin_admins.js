// Handles AJAX for admin admins CRUD

document.addEventListener('DOMContentLoaded', function() {
    // Add Admin
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
                this.style.display = 'none';
                this.reset();
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
        document.getElementById('modalOverlay').style.display = 'block';
        document.getElementById('edit_username').focus();
    }

    function closeEditModal() {
        document.getElementById('editModal').style.display = 'none';
        document.getElementById('modalOverlay').style.display = 'none';
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
                closeEditModal();
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
            const adminName = this.closest('tr').dataset.username;
            if (confirm(`Are you sure you want to delete admin "${adminName}"? This action cannot be undone.`)) {
                fetch('admins.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'delete_user_id=' + this.dataset.userid
                })
                .then(res => res.json())
                .then(data => {
                    alert(data.message);
                    if (data.success) location.reload();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while deleting the admin.');
                });
            }
        });
    });

    // Close modal events
    document.getElementById('closeEditModal')?.addEventListener('click', closeEditModal);
    document.getElementById('modalOverlay')?.addEventListener('click', closeEditModal);
    
    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeEditModal();
        }
    });
});
