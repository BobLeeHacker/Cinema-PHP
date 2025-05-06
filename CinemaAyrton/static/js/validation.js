/**
 * Client-side form validation for Cinema DB
 */

// Helper functions for validation
function showError(input, message) {
    const formGroup = input.closest('.mb-3');
    formGroup.classList.add('has-error');
    
    let errorElement = formGroup.querySelector('.invalid-feedback');
    if (!errorElement) {
        errorElement = document.createElement('div');
        errorElement.className = 'invalid-feedback';
        input.after(errorElement);
    }
    
    input.classList.add('is-invalid');
    errorElement.textContent = message;
    errorElement.style.display = 'block';
}

function hideError(input) {
    const formGroup = input.closest('.mb-3');
    formGroup.classList.remove('has-error');
    
    const errorElement = formGroup.querySelector('.invalid-feedback');
    if (errorElement) {
        errorElement.textContent = '';
        errorElement.style.display = 'none';
    }
    
    input.classList.remove('is-invalid');
    input.classList.add('is-valid');
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function isValidYear(year) {
    const yearNumber = parseInt(year);
    const currentYear = new Date().getFullYear();
    return !isNaN(yearNumber) && yearNumber >= 1888 && yearNumber <= currentYear + 5;
}

function isValidDuration(duration) {
    const durationNumber = parseInt(duration);
    return !isNaN(durationNumber) && durationNumber > 0;
}

function isValidRating(rating) {
    const ratingNumber = parseFloat(rating);
    return !isNaN(ratingNumber) && ratingNumber >= 0 && ratingNumber <= 10;
}

// Movie form validation
function validateMovieForm() {
    let isValid = true;
    
    // Title validation
    const title = document.getElementById('title');
    if (!title.value.trim()) {
        showError(title, 'Título é obrigatório');
        isValid = false;
    } else {
        hideError(title);
    }
    
    // Release year validation
    const releaseYear = document.getElementById('release_year');
    if (releaseYear.value && !isValidYear(releaseYear.value)) {
        showError(releaseYear, 'Ano de lançamento inválido');
        isValid = false;
    } else {
        hideError(releaseYear);
    }
    
    // Duration validation
    const duration = document.getElementById('duration');
    if (duration.value && !isValidDuration(duration.value)) {
        showError(duration, 'Duração deve ser um número positivo');
        isValid = false;
    } else {
        hideError(duration);
    }
    
    // Rating validation (if manual input is used)
    const rating = document.getElementById('rating');
    if (rating.value && !isValidRating(rating.value)) {
        showError(rating, 'Classificação deve ser um número entre 0 e 10');
        isValid = false;
    } else {
        hideError(rating);
    }
    
    return isValid;
}

// Profile form validation
function validateProfileForm() {
    let isValid = true;
    
    // Email validation
    const email = document.getElementById('email');
    if (!email.value.trim()) {
        showError(email, 'Email é obrigatório');
        isValid = false;
    } else if (!isValidEmail(email.value)) {
        showError(email, 'Por favor, forneça um email válido');
        isValid = false;
    } else {
        hideError(email);
    }
    
    return isValid;
}

// Password form validation
function validatePasswordForm() {
    let isValid = true;
    
    // Current password validation
    const currentPassword = document.getElementById('current_password');
    if (!currentPassword.value) {
        showError(currentPassword, 'Senha atual é obrigatória');
        isValid = false;
    } else {
        hideError(currentPassword);
    }
    
    // New password validation
    const newPassword = document.getElementById('new_password');
    if (!newPassword.value) {
        showError(newPassword, 'Nova senha é obrigatória');
        isValid = false;
    } else if (newPassword.value.length < 6) {
        showError(newPassword, 'Nova senha deve ter pelo menos 6 caracteres');
        isValid = false;
    } else {
        hideError(newPassword);
    }
    
    // New password confirmation validation
    const confirmPassword = document.getElementById('confirm_password');
    if (newPassword.value && confirmPassword.value !== newPassword.value) {
        showError(confirmPassword, 'As senhas não coincidem');
        isValid = false;
    } else {
        hideError(confirmPassword);
    }
    
    return isValid;
}

// Initialize forms when the DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Form validation for movie form
    const movieForm = document.getElementById('movie-form');
    if (movieForm) {
        movieForm.addEventListener('submit', function(e) {
            if (!validateMovieForm()) {
                e.preventDefault();
            }
        });
    }
    
    // Form validation for profile form
    const profileForm = document.getElementById('profile-form');
    if (profileForm) {
        profileForm.addEventListener('submit', function(e) {
            if (!validateProfileForm()) {
                e.preventDefault();
            }
        });
    }
    
    // Form validation for password form
    const passwordForm = document.getElementById('password-form');
    if (passwordForm) {
        passwordForm.addEventListener('submit', function(e) {
            if (!validatePasswordForm()) {
                e.preventDefault();
            }
        });
    }
});