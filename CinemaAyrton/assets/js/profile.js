/**
 * Profile JavaScript functionality
 * Handles user profile management operations
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize profile form submission
    initializeProfileForm();
    
    // Initialize password change form
    initializePasswordForm();
    
    // Initialize profile image preview
    initializeProfileImagePreview();
});

/**
 * Initialize profile form submission
 */
function initializeProfileForm() {
    const profileForm = document.getElementById('profileForm');
    
    if (profileForm) {
        profileForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validate form
            if (!validateForm(this)) {
                return;
            }
            
            // Show loading state
            const submitButton = this.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> A guardar...';
            
            // Get form data
            const formData = new FormData(this);
            
            // Send AJAX request
            fetch('api/profile.php?action=update', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Reset button state
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
                
                if (data.status === 'success') {
                    showToast('Sucesso', data.message, 'success');
                    
                    // Update profile image if it was changed
                    if (data.profile_image) {
                        const profileImages = document.querySelectorAll('.profile-image');
                        profileImages.forEach(img => {
                            img.src = data.profile_image;
                        });
                    }
                    
                    // Update username in navbar if it was changed
                    if (data.username) {
                        const usernameElement = document.querySelector('.user-username');
                        if (usernameElement) {
                            usernameElement.textContent = data.username;
                        }
                    }
                } else {
                    showToast('Erro', data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Error updating profile:', error);
                
                // Reset button state
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
                
                showToast('Erro', 'Erro ao atualizar perfil. Por favor, tente novamente.', 'danger');
            });
        });
    }
}

/**
 * Initialize password change form
 */
function initializePasswordForm() {
    const passwordForm = document.getElementById('passwordForm');
    
    if (passwordForm) {
        passwordForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validate form
            if (!validateForm(this)) {
                return;
            }
            
            // Check if passwords match
            const newPassword = this.querySelector('#newPassword').value;
            const confirmPassword = this.querySelector('#confirmPassword').value;
            
            if (newPassword !== confirmPassword) {
                // Show error
                const confirmField = this.querySelector('#confirmPassword');
                confirmField.classList.add('is-invalid');
                
                // Add error message if it doesn't exist
                let errorDiv = confirmField.nextElementSibling;
                if (!errorDiv || !errorDiv.classList.contains('invalid-feedback')) {
                    errorDiv = document.createElement('div');
                    errorDiv.className = 'invalid-feedback';
                    confirmField.parentNode.insertBefore(errorDiv, confirmField.nextSibling);
                }
                
                errorDiv.textContent = 'As passwords não coincidem.';
                return;
            }
            
            // Show loading state
            const submitButton = this.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> A atualizar...';
            
            // Get form data
            const formData = new FormData(this);
            
            // Send AJAX request
            fetch('api/profile.php?action=change_password', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Reset button state
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
                
                if (data.status === 'success') {
                    showToast('Sucesso', data.message, 'success');
                    passwordForm.reset(); // Clear form
                } else {
                    showToast('Erro', data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Error changing password:', error);
                
                // Reset button state
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
                
                showToast('Erro', 'Erro ao alterar password. Por favor, tente novamente.', 'danger');
            });
        });
    }
}

/**
 * Initialize profile image preview
 */
function initializeProfileImagePreview() {
    const profileImageInput = document.getElementById('profileImage');
    
    if (profileImageInput) {
        profileImageInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const previewContainer = document.getElementById('imagePreview');
                    if (previewContainer) {
                        previewContainer.innerHTML = `
                            <div class="text-center mb-3">
                                <img src="${e.target.result}" class="rounded-circle profile-preview-image" alt="Preview" width="150" height="150">
                                <p class="text-muted small mt-2">Pré-visualização da nova imagem</p>
                            </div>
                        `;
                    }
                };
                
                reader.readAsDataURL(file);
            }
        });
    }
}
