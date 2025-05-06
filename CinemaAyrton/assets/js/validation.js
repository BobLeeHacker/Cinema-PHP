/**
 * Client-side form validation for Cinema DB
 */

document.addEventListener('DOMContentLoaded', function() {
    // Form validation for registration form
    const registerForm = document.getElementById('register-form');
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            if (!validateRegisterForm()) {
                e.preventDefault();
            }
        });
    }
    
    // Form validation for login form
    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            if (!validateLoginForm()) {
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
    
    // Form validation for change password form
    const passwordForm = document.getElementById('password-form');
    if (passwordForm) {
        passwordForm.addEventListener('submit', function(e) {
            if (!validatePasswordForm()) {
                e.preventDefault();
            }
        });
    }
    
    // Form validation for movie form
    const movieForm = document.getElementById('movie-form');
    if (movieForm) {
        movieForm.addEventListener('submit', function(e) {
            if (!validateMovieForm()) {
                e.preventDefault();
            }
        });
    }
});

/**
 * Validate the registration form
 * @return {boolean} Whether the form is valid
 */
function validateRegisterForm() {
    let isValid = true;
    
    // Username validation
    const username = document.getElementById('username');
    if (!username.value.trim()) {
        showError(username, 'Nome de usuário é obrigatório');
        isValid = false;
    } else if (username.value.length < 3 || username.value.length > 50) {
        showError(username, 'Nome de usuário deve ter entre 3 e 50 caracteres');
        isValid = false;
    } else {
        hideError(username);
    }
    
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
    
    // Password validation
    const password = document.getElementById('password');
    if (!password.value) {
        showError(password, 'Senha é obrigatória');
        isValid = false;
    } else if (password.value.length < 6) {
        showError(password, 'Senha deve ter pelo menos 6 caracteres');
        isValid = false;
    } else {
        hideError(password);
    }
    
    // Password confirmation validation
    const passwordConfirm = document.getElementById('password_confirm');
    if (password.value && passwordConfirm.value !== password.value) {
        showError(passwordConfirm, 'As senhas não coincidem');
        isValid = false;
    } else {
        hideError(passwordConfirm);
    }
    
    return isValid;
}

/**
 * Validate the login form
 * @return {boolean} Whether the form is valid
 */
function validateLoginForm() {
    let isValid = true;
    
    // Username/email validation
    const username = document.getElementById('username');
    if (!username.value.trim()) {
        showError(username, 'Nome de usuário ou email é obrigatório');
        isValid = false;
    } else {
        hideError(username);
    }
    
    // Password validation
    const password = document.getElementById('password');
    if (!password.value) {
        showError(password, 'Senha é obrigatória');
        isValid = false;
    } else {
        hideError(password);
    }
    
    return isValid;
}

/**
 * Validate the profile form
 * @return {boolean} Whether the form is valid
 */
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

/**
 * Validate the change password form
 * @return {boolean} Whether the form is valid
 */
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

/**
 * Validate the movie form
 * @return {boolean} Whether the form is valid
 */
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
    
    // Rating validation
    const rating = document.getElementById('rating');
    if (rating.value && !isValidRating(rating.value)) {
        showError(rating, 'Classificação deve ser um número entre 0 e 10');
        isValid = false;
    } else {
        hideError(rating);
    }
    
    return isValid;
}

/**
 * Show error message for an input
 * @param {HTMLElement} input The input element
 * @param {string} message The error message
 */
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

/**
 * Hide error message for an input
 * @param {HTMLElement} input The input element
 */
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

/**
 * Check if an email is valid
 * @param {string} email The email to validate
 * @return {boolean} Whether the email is valid
 */
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

/**
 * Check if a year is valid
 * @param {string|number} year The year to validate
 * @return {boolean} Whether the year is valid
 */
function isValidYear(year) {
    const yearNumber = parseInt(year);
    const currentYear = new Date().getFullYear();
    return !isNaN(yearNumber) && yearNumber >= 1888 && yearNumber <= currentYear + 5;
}

/**
 * Check if a duration is valid
 * @param {string|number} duration The duration to validate
 * @return {boolean} Whether the duration is valid
 */
function isValidDuration(duration) {
    const durationNumber = parseInt(duration);
    return !isNaN(durationNumber) && durationNumber > 0;
}

/**
 * Check if a rating is valid
 * @param {string|number} rating The rating to validate
 * @return {boolean} Whether the rating is valid
 */
function isValidRating(rating) {
    const ratingNumber = parseFloat(rating);
    return !isNaN(ratingNumber) && ratingNumber >= 0 && ratingNumber <= 10;
}
