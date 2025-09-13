// Handles AJAX for admin company profiles CRUD

document.addEventListener('DOMContentLoaded', function() {
    // Add Company Profile
    function openAddModal() {
        document.getElementById('addModal').style.display = 'block';
    }

    document.querySelectorAll('.add-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            openAddModal();
        });
    });
    
    document.getElementById('addCompanyProfileForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        fetch('company_profiles.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            alert(data.message);
            if (data.success) location.reload();
        });
    });

    function openEditModal(id, companyName, companyWebsite) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_company_name').value = companyName;
        document.getElementById('edit_company_website').value = companyWebsite;
        document.getElementById('editModal').style.display = 'block';
        document.getElementById('edit_company_name').focus();
    }

    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const row = this.closest('tr');
            openEditModal(row.dataset.id, row.dataset.companyname, row.dataset.companywebsite);
        });
    });

    document.getElementById('editCompanyProfileForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        fetch('company_profiles.php', {
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
            if (confirm('Are you sure you want to delete this company profile?')) {
                fetch('company_profiles.php', {
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
