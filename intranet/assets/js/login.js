// ===================================
// LOGIN PAGE FUNCTIONALITY
// ===================================

document.addEventListener('DOMContentLoaded', function () {
    // Toggle password visibility
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');

    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function () {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);

            // Toggle icon
            const icon = this.querySelector('i');
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });
    }

    // Form validation and submission
    const loginForm = document.getElementById('loginForm');

    if (loginForm) {
        loginForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();

            // Basic validation
            if (!username || !password) {
                showAlert('Por favor completa todos los campos', 'warning');
                return;
            }

            // Email validation if username contains @
            if (username.includes('@') && !isValidEmail(username)) {
                showAlert('Por favor ingresa un email válido', 'warning');
                return;
            }

            // Show loading state
            const submitBtn = this.querySelector('.btn-login');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Iniciando sesión...';
            submitBtn.disabled = true;

            // AJAX login request
            fetch('php/auth/login_process.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    username: username,
                    password: password,
                    remember: document.getElementById('remember').checked ? '1' : '0'
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('¡Bienvenido! Redirigiendo...', 'success');
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 1000);
                    } else {
                        showAlert(data.message || 'Error al iniciar sesión', 'danger');
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('Error de conexión. Por favor intenta nuevamente.', 'danger');
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                });
        });
    }

    // Add input animations
    const inputs = document.querySelectorAll('.form-group input');
    inputs.forEach(input => {
        input.addEventListener('focus', function () {
            this.parentElement.classList.add('focused');
        });

        input.addEventListener('blur', function () {
            if (!this.value) {
                this.parentElement.classList.remove('focused');
            }
        });
    });
});

// Helper function to validate email
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Helper function to show alerts
function showAlert(message, type = 'info') {
    // Remove existing alerts
    const existingAlerts = document.querySelectorAll('.alert:not(.alert-info)');
    existingAlerts.forEach(alert => alert.remove());

    // Create new alert
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.innerHTML = `
        <i class="fas fa-${getAlertIcon(type)}"></i>
        <span>${message}</span>
    `;

    // Insert alert at the top of the form
    const form = document.getElementById('loginForm');
    const firstChild = form.firstElementChild;
    form.insertBefore(alert, firstChild);

    // Auto-remove after 5 seconds
    setTimeout(() => {
        alert.style.opacity = '0';
        alert.style.transform = 'translateY(-10px)';
        setTimeout(() => alert.remove(), 300);
    }, 5000);
}

// Helper function to get alert icon
function getAlertIcon(type) {
    const icons = {
        'success': 'check-circle',
        'danger': 'exclamation-circle',
        'warning': 'exclamation-triangle',
        'info': 'info-circle'
    };
    return icons[type] || 'info-circle';
}

// Add smooth transitions to alerts
const style = document.createElement('style');
style.textContent = `
    .alert {
        padding: 14px 18px;
        border-radius: 12px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 0.9rem;
        font-weight: 500;
        animation: slideIn 0.3s ease;
        transition: opacity 0.3s ease, transform 0.3s ease;
    }
    
    .alert i {
        font-size: 1.1rem;
    }
    
    @keyframes slideIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .form-group.focused label {
        color: #4F46E5;
    }
    
    .form-group.focused label i {
        color: #4F46E5;
    }
    
    .alert-success {
        background: rgba(16, 185, 129, 0.1);
        color: #059669;
        border: 1px solid rgba(16, 185, 129, 0.2);
    }
    
    .alert-danger {
        background: rgba(239, 68, 68, 0.1);
        color: #DC2626;
        border: 1px solid rgba(239, 68, 68, 0.2);
    }
    
    .alert-warning {
        background: rgba(245, 158, 11, 0.1);
        color: #D97706;
        border: 1px solid rgba(245, 158, 11, 0.2);
    }
    
    .alert-info {
        background: rgba(6, 182, 212, 0.1);
        color: #0891B2;
        border: 1px solid rgba(6, 182, 212, 0.2);
    }
`;
document.head.appendChild(style);
