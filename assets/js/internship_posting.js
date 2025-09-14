// Internship Posting Form Management
let currentStep = 1;
const totalSteps = 4;

// Initialize form
document.addEventListener('DOMContentLoaded', function() {
    updateProgressIndicator();
    setupFormValidation();
    setupRemoteToggle();
    setupStatusToggle();
    setupPreview();
});

// Step navigation functions
function nextStep() {
    if (validateCurrentStep()) {
        if (currentStep < totalSteps) {
            currentStep++;
            showStep(currentStep);
            updateProgressIndicator();
            
            if (currentStep === 4) {
                updatePreview();
            }
        }
    }
}

function prevStep() {
    if (currentStep > 1) {
        currentStep--;
        showStep(currentStep);
        updateProgressIndicator();
    }
}

function goToStep(step) {
    if (step >= 1 && step <= totalSteps) {
        currentStep = step;
        showStep(currentStep);
        updateProgressIndicator();
        
        if (currentStep === 4) {
            updatePreview();
        }
    }
}

function showStep(step) {
    // Hide all steps
    document.querySelectorAll('.form-step').forEach(stepElement => {
        stepElement.classList.remove('active');
    });
    
    // Show current step
    const currentStepElement = document.getElementById(`step${step}`);
    if (currentStepElement) {
        currentStepElement.classList.add('active');
    }
}

function updateProgressIndicator() {
    const progressSteps = document.querySelectorAll('.progress-step');
    const progressFill = document.getElementById('progressFill');
    
    progressSteps.forEach((step, index) => {
        const stepNumber = index + 1;
        if (stepNumber <= currentStep) {
            step.classList.add('active');
        } else {
            step.classList.remove('active');
        }
        
        if (stepNumber < currentStep) {
            step.classList.add('completed');
        } else {
            step.classList.remove('completed');
        }
    });
    
    // Update progress bar
    const progressPercentage = ((currentStep - 1) / (totalSteps - 1)) * 100;
    progressFill.style.width = `${progressPercentage}%`;
}

// Form validation
function validateCurrentStep() {
    const currentStepElement = document.getElementById(`step${currentStep}`);
    const requiredFields = currentStepElement.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('error');
            isValid = false;
        } else {
            field.classList.remove('error');
        }
    });
    
    // Additional validation for specific steps
    if (currentStep === 1) {
        isValid = validateBasicInfo() && isValid;
    } else if (currentStep === 2) {
        isValid = validateLocationSchedule() && isValid;
    } else if (currentStep === 3) {
        isValid = validateDetails() && isValid;
    }
    
    if (!isValid) {
        showNotification('Please fill in all required fields correctly.', 'error');
    }
    
    return isValid;
}

function validateBasicInfo() {
    const title = document.getElementById('title');
    const category = document.getElementById('category_id');
    const duration = document.getElementById('duration_months');
    
    let isValid = true;
    
    if (title.value.trim().length < 3) {
        title.classList.add('error');
        isValid = false;
    }
    
    if (!category.value) {
        category.classList.add('error');
        isValid = false;
    }
    
    if (parseInt(duration.value) <= 0) {
        duration.classList.add('error');
        isValid = false;
    }
    
    return isValid;
}

function validateLocationSchedule() {
    const remoteOption = document.getElementById('remote_option');
    const location = document.getElementById('location');
    const applicationDeadline = document.getElementById('application_deadline');
    
    let isValid = true;
    
    // Location validation
    if (!remoteOption.checked && !location.value.trim()) {
        location.classList.add('error');
        isValid = false;
    }
    
    // Date validation
    const deadlineDate = new Date(applicationDeadline.value);
    const today = new Date();
    
    if (deadlineDate <= today) {
        applicationDeadline.classList.add('error');
        isValid = false;
    }
    
    return isValid;
}

function validateDetails() {
    const description = document.getElementById('description');
    
    let isValid = true;
    
    if (description.value.trim().length < 50) {
        description.classList.add('error');
        showNotification('Description should be at least 50 characters long.', 'error');
        isValid = false;
    }
    
    return isValid;
}

function setupFormValidation() {
    // Remove error styling when user starts typing
    document.querySelectorAll('input, select, textarea').forEach(field => {
        field.addEventListener('input', function() {
            this.classList.remove('error');
        });
        
        field.addEventListener('change', function() {
            this.classList.remove('error');
        });
    });
}

// Remote work toggle functionality
function setupRemoteToggle() {
    const remoteCheckbox = document.getElementById('remote_option');
    const locationGroup = document.getElementById('location-group');
    const locationInput = document.getElementById('location');
    
    remoteCheckbox.addEventListener('change', function() {
        if (this.checked) {
            locationGroup.style.opacity = '0.6';
            locationInput.required = false;
            locationInput.placeholder = 'Optional - for hybrid positions';
        } else {
            locationGroup.style.opacity = '1';
            locationInput.required = true;
            locationInput.placeholder = 'e.g., Colombo, Kandy, Galle';
        }
    });
}

// Status toggle functionality
function setupStatusToggle() {
    const draftRadio = document.getElementById('save_draft');
    const publishRadio = document.getElementById('publish_now');
    const submitBtn = document.getElementById('submitBtn');
    const submitText = document.getElementById('submitText');
    
    function updateSubmitButton() {
        if (publishRadio.checked) {
            submitBtn.className = 'btn btn-success btn-lg';
            submitText.textContent = 'Publish Internship';
        } else {
            submitBtn.className = 'btn btn-primary btn-lg';
            submitText.textContent = 'Save as Draft';
        }
    }
    
    draftRadio.addEventListener('change', updateSubmitButton);
    publishRadio.addEventListener('change', updateSubmitButton);
}

// Preview functionality
function setupPreview() {
    // Setup real-time preview updates
    const formFields = document.querySelectorAll('input, select, textarea');
    formFields.forEach(field => {
        field.addEventListener('input', debounce(updatePreview, 300));
        field.addEventListener('change', updatePreview);
    });
}

function updatePreview() {
    const preview = document.getElementById('internshipPreview');
    if (!preview) return;
    
    const formData = getFormData();
    
    preview.innerHTML = `
        <div class="preview-internship">
            <div class="preview-header">
                <h3>${formData.title || 'Internship Title'}</h3>
                <div class="preview-meta">
                    <span class="meta-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-building" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M14.763.075A.5.5 0 0 1 15 .5v15a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5V14h-1v1.5a.5.5 0 0 1-.5.5h-9a.5.5 0 0 1-.5-.5V10a.5.5 0 0 1 .342-.474L6 7.64V4.5a.5.5 0 0 1 .276-.447l8-4a.5.5 0 0 1 .487.022ZM6 8.694 1 10.36V15h5V8.694ZM7 15h2v-1.5a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 .5.5V15h2V1.309l-7 3.5V15Z"/>
                            <path d="M2 11h1v1H2v-1Zm2 0h1v1H4v-1Zm-2 2h1v1H2v-1Zm2 0h1v1H4v-1Zm4-4h1v1H8V9Zm2 0h1v1h-1V9Zm-2 2h1v1H8v-1Zm2 0h1v1h-1v-1Zm2-2h1v1h-1V9Zm0 2h1v1h-1v-1ZM8 7h1v1H8V7Zm2 0h1v1h-1V7Zm2 0h1v1h-1V7ZM8 5h1v1H8V5Zm2 0h1v1h-1V5Zm2 0h1v1h-1V5Zm0-2h1v1h-1V3Z"/>
                        </svg>
                        ${document.querySelector('meta[name="company-name"]')?.content || 'Your Company'}
                    </span>
                    ${formData.location ? `
                        <span class="meta-item">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-geo-alt" viewBox="0 0 16 16">
                                <path d="M12.166 8.94c-.524 1.062-1.234 2.12-1.96 3.07A31.493 31.493 0 0 1 8 14.58a31.481 31.481 0 0 1-2.206-2.57c-.726-.95-1.436-2.008-1.96-3.07C3.304 7.867 3 6.862 3 6a5 5 0 0 1 10 0c0 .862-.305 1.867-.834 2.94zM8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10z"/>
                                <path d="M8 8a2 2 0 1 1 0-4 2 2 0 0 1 0 4zm0 1a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
                            </svg>
                            ${formData.location}${formData.remote_option ? ' (Remote Option Available)' : ''}
                        </span>
                    ` : ''}
                    ${formData.salary && parseFloat(formData.salary) > 0 ? `
                        <span class="meta-item">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-currency-dollar" viewBox="0 0 16 16">
                                <path d="M4 10.781c.148 1.667 1.513 2.85 3.591 3.003V15h1.043v-1.216c2.27-.179 3.678-1.438 3.678-3.3 0-1.59-.947-2.51-2.956-3.028l-.722-.187V3.467c1.122.11 1.879.714 2.07 1.616h1.47c-.166-1.6-1.54-2.748-3.540-2.875V1H7.591v1.233c-1.939.23-3.27 1.472-3.27 3.156 0 1.454.966 2.483 2.661 2.917l.61.162v4.031c-1.149-.17-1.94-.8-2.131-1.718H4zm3.391-3.836c-1.043-.263-1.6-.825-1.6-1.616 0-.944.704-1.641 1.8-1.828v3.495l-.2-.05zm1.591 1.872c1.287.323 1.852.859 1.852 1.769 0 1.097-.826 1.828-2.2 1.939V8.73l.348.086z"/>
                            </svg>
                            LKR ${parseFloat(formData.salary).toLocaleString()}/month
                        </span>
                    ` : ''}
                    <span class="meta-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-clock" viewBox="0 0 16 16">
                            <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/>
                            <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/>
                        </svg>
                        ${formData.duration_months || '3'} month${(formData.duration_months != 1) ? 's' : ''}
                    </span>
                </div>
            </div>
            
            <div class="preview-content">
                ${formData.description ? `
                    <div class="preview-section">
                        <h4>About This Internship</h4>
                        <div class="ps-content">${formData.description.replace(/\n/g, '<br>')}</div>
                    </div>
                ` : ''}
                
                ${formData.responsibilities ? `
                    <div class="preview-section">
                        <h4>Key Responsibilities</h4>
                        <div class="ps-content">${formData.responsibilities.replace(/\n/g, '<br>')}</div>
                    </div>
                ` : ''}
                
                ${formData.requirements ? `
                    <div class="preview-section">
                        <h4>Requirements</h4>
                        <div class="ps-content">${formData.requirements.replace(/\n/g, '<br>')}</div>
                    </div>
                ` : ''}
                
                <div class="preview-section">
                    <h4>Application Details</h4>
                    <div class="application-details">
                        ${formData.application_deadline ? `
                            <p><strong>Application Deadline:</strong> ${formatDate(formData.application_deadline)}</p>
                        ` : ''}
                        <p><strong>Experience Level:</strong> ${formData.experience_level ? capitalizeFirst(formData.experience_level) : 'Not specified'}</p>
                        <p><strong>Maximum Applicants:</strong> ${formData.max_applicants || '50'}</p>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function getFormData() {
    const form = document.getElementById('internshipForm');
    const formData = new FormData(form);
    const data = {};
    
    for (let [key, value] of formData.entries()) {
        data[key] = value;
    }
    
    // Handle checkboxes
    data.remote_option = document.getElementById('remote_option').checked;
    
    return data;
}

// Utility functions
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function formatDate(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

function capitalizeFirst(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <span class="notification-message">${message}</span>
            <button class="notification-close" onclick="this.parentElement.parentElement.remove()">×</button>
        </div>
    `;
    
    // Add to page
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}

// Make functions globally available
window.nextStep = nextStep;
window.prevStep = prevStep;
window.goToStep = goToStep;

// Progress step click handlers
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.progress-step').forEach((step, index) => {
        step.addEventListener('click', function() {
            if (index + 1 <= currentStep || validateStepsUpTo(index + 1)) {
                goToStep(index + 1);
            }
        });
    });
});

function validateStepsUpTo(targetStep) {
    for (let step = 1; step < targetStep; step++) {
        const originalStep = currentStep;
        currentStep = step;
        if (!validateCurrentStep()) {
            currentStep = originalStep;
            return false;
        }
    }
    currentStep = targetStep;
    return true;
}
