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

    function openEditModal(id, companyName, industryType, companyWebsite, phoneNumber, address, description, verified) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_company_name').value = companyName;
        document.getElementById('edit_industry_type').value = industryType || '';
        document.getElementById('edit_company_website').value = companyWebsite || '';
        document.getElementById('edit_phone_number').value = phoneNumber || '';
        document.getElementById('edit_address').value = address || '';
        document.getElementById('edit_company_description').value = description || '';
        document.getElementById('edit_verified').checked = verified == '1';
        document.getElementById('editModal').style.display = 'block';
        document.getElementById('edit_company_name').focus();
    }

    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const row = this.closest('tr');
            openEditModal(
                row.dataset.id, 
                row.dataset.companyname, 
                row.dataset.industrytype,
                row.dataset.companywebsite, 
                row.dataset.phonenumber,
                row.dataset.address,
                row.dataset.description,
                row.dataset.verified
            );
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
