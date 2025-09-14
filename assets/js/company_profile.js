
function openEditModal() {
    document.getElementById('editProfileModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editProfileModal').style.display = 'none';
}

function openChangePasswordModal() {
    document.getElementById('changePasswordModal').style.display = 'flex';
}

function closeChangePasswordModal() {
    document.getElementById('changePasswordModal').style.display = 'none';
}

function openDeleteUserModal() {
    document.getElementById('deleteUserModal').style.display = 'flex';
    // Reset confirmation input and button state
    document.getElementById('delete_confirmation').value = '';
    document.getElementById('confirmDeleteBtn').disabled = true;
}

function closeDeleteUserModal() {
    document.getElementById('deleteUserModal').style.display = 'none';
    // Reset confirmation input and button state
    document.getElementById('delete_confirmation').value = '';
    document.getElementById('confirmDeleteBtn').disabled = true;
}

function deleteUserAccount() {
    if (document.getElementById('delete_confirmation').value !== 'DELETE') {
        alert('Please type "DELETE" to confirm account deletion.');
        return;
    }
    
    if (!confirm('Are you absolutely sure you want to delete your company account? This action cannot be undone.')) {
        return;
    }
    
    // Disable the delete button to prevent multiple clicks
    document.getElementById('confirmDeleteBtn').disabled = true;
    document.getElementById('confirmDeleteBtn').textContent = 'Deleting...';
    
    fetch('delete_user.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            // Redirect to home page
            window.location.href = data.redirect;
        } else {
            alert('Error: ' + data.message);
            // Re-enable the button
            document.getElementById('confirmDeleteBtn').disabled = false;
            document.getElementById('confirmDeleteBtn').textContent = 'Delete Account';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while deleting your account.');
        // Re-enable the button
        document.getElementById('confirmDeleteBtn').disabled = false;
        document.getElementById('confirmDeleteBtn').textContent = 'Delete Account';
    });
}

// Close modals when clicking outside
document.getElementById('editProfileModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeEditModal();
    }
});

document.getElementById('changePasswordModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeChangePasswordModal();
    }
});

document.addEventListener('DOMContentLoaded', function() {
    // Add event listener for delete user modal if it exists
    const deleteUserModal = document.getElementById('deleteUserModal');
    if (deleteUserModal) {
        deleteUserModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteUserModal();
            }
        });
        
        // Add event listener for delete confirmation input
        const deleteConfirmationInput = document.getElementById('delete_confirmation');
        if (deleteConfirmationInput) {
            deleteConfirmationInput.addEventListener('input', function() {
                const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
                if (this.value === 'DELETE') {
                    confirmDeleteBtn.disabled = false;
                } else {
                    confirmDeleteBtn.disabled = true;
                }
            });
        }
    }
});

// Handle edit profile form submission
document.getElementById('editProfileForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('update_profile.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Profile updated successfully!');
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating the profile.');
    });
});

// Handle change password form submission
document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (newPassword !== confirmPassword) {
        alert('New password and confirm password do not match.');
        return;
    }
    
    const formData = new FormData(this);
    
    fetch('change_password.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Password changed successfully!');
            closeChangePasswordModal();
            // Clear form
            document.getElementById('changePasswordForm').reset();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while changing the password.');
    });
});