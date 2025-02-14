// The script remains the same as in the previous version
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('signinForm');
    const alertBox = document.getElementById('alertBox');
    const togglePassword = document.querySelector('.toggle-password');
    const passwordInput = document.getElementById('password');
    
    togglePassword.addEventListener('click', () => {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        togglePassword.classList.toggle('fa-eye');
        togglePassword.classList.toggle('fa-eye-slash');
    });
    
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        if (!validateForm()) return;
        
        const spinner = document.querySelector('.spinner-border');
        const submitBtn = form.querySelector('button[type="submit"]');
        
        try {
            spinner.classList.remove('d-none');
            submitBtn.disabled = true;
            
            const formData = new FormData();
            formData.append('email', document.getElementById('email').value);
            formData.append('password', passwordInput.value);
            formData.append('remember', document.getElementById('rememberMe').checked);
            
            const response = await fetch(window.location.href, {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                window.location.href = result.redirect;
            } else {
                showAlert(result.message, 'danger');
            }
        } catch (error) {
            showAlert('An error occurred. Please try again.', 'danger');
        } finally {
            spinner.classList.add('d-none');
            submitBtn.disabled = false;
        }
    });
    
    function validateForm() {
        let isValid = true;
        const email = document.getElementById('email');
        const password = document.getElementById('password');
        
        document.querySelectorAll('.error-message').forEach(el => el.textContent = '');
        
        if (!email.value) {
            showError(email, 'Email is required');
            isValid = false;
        } else if (!isValidEmail(email.value)) {
            showError(email, 'Please enter a valid email address');
            isValid = false;
        }
        
        if (!password.value) {
            showError(password, 'Password is required');
            isValid = false;
        }
        
        return isValid;
    }
    
    function showError(input, message) {
        input.classList.add('is-invalid');
        input.nextElementSibling.nextElementSibling.textContent = message;
    }
    
    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }
    
    function showAlert(message, type) {
        alertBox.className = `alert alert-${type}`;
        alertBox.textContent = message;
        alertBox.classList.remove('d-none');
    }
});