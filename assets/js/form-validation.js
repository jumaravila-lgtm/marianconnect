/**
 * MARIANCONNECT - Form Validation JavaScript
 * Handles client-side form validation with real-time feedback
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // ==========================================
    // VALIDATION RULES
    // ==========================================
    
    const validationRules = {
        required: {
            validate: (value) => value.trim() !== '',
            message: 'This field is required'
        },
        email: {
            validate: (value) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value),
            message: 'Please enter a valid email address'
        },
        phone: {
            validate: (value) => /^[\d\s\-\(\)]+$/.test(value) && value.replace(/\D/g, '').length >= 10,
            message: 'Please enter a valid phone number'
        },
        minLength: {
            validate: (value, min) => value.length >= min,
            message: (min) => `Must be at least ${min} characters`
        },
        maxLength: {
            validate: (value, max) => value.length <= max,
            message: (max) => `Must be no more than ${max} characters`
        },
        number: {
            validate: (value) => !isNaN(value) && value.trim() !== '',
            message: 'Please enter a valid number'
        },
        url: {
            validate: (value) => /^https?:\/\/.+\..+/.test(value),
            message: 'Please enter a valid URL'
        },
        match: {
            validate: (value, matchField) => {
                const matchElement = document.querySelector(`[name="${matchField}"]`);
                return value === matchElement?.value;
            },
            message: (matchField) => `Must match ${matchField}`
        }
    };
    
    
    // ==========================================
    // SHOW ERROR MESSAGE
    // ==========================================
    
    function showError(field, message) {
        field.classList.add('error', 'invalid');
        field.classList.remove('valid');
        
        // Remove existing error
        const existingError = field.parentElement.querySelector('.error-message');
        if (existingError) existingError.remove();
        
        // Create error message
        const error = document.createElement('div');
        error.className = 'error-message';
        error.textContent = message;
        error.style.cssText = `
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 0.25rem;
            display: block;
        `;
        
        field.parentElement.appendChild(error);
    }
    
    
    // ==========================================
    // REMOVE ERROR MESSAGE
    // ==========================================
    
    function removeError(field) {
        field.classList.remove('error', 'invalid');
        field.classList.add('valid');
        
        const error = field.parentElement.querySelector('.error-message');
        if (error) error.remove();
    }
    
    
    // ==========================================
    // VALIDATE FIELD
    // ==========================================
    
    function validateField(field) {
        const value = field.value;
        const rules = field.dataset.validate?.split('|') || [];
        
        // Required check
        if (field.hasAttribute('required') || rules.includes('required')) {
            if (!validationRules.required.validate(value)) {
                showError(field, validationRules.required.message);
                return false;
            }
        }
        
        // Skip other validations if field is empty and not required
        if (value.trim() === '' && !field.hasAttribute('required')) {
            removeError(field);
            return true;
        }
        
        // Email validation
        if (field.type === 'email' || rules.includes('email')) {
            if (!validationRules.email.validate(value)) {
                showError(field, validationRules.email.message);
                return false;
            }
        }
        
        // Phone validation
        if (field.type === 'tel' || rules.includes('phone')) {
            if (!validationRules.phone.validate(value)) {
                showError(field, validationRules.phone.message);
                return false;
            }
        }
        
        // Number validation
        if (field.type === 'number' || rules.includes('number')) {
            if (!validationRules.number.validate(value)) {
                showError(field, validationRules.number.message);
                return false;
            }
        }
        
        // URL validation
        if (field.type === 'url' || rules.includes('url')) {
            if (!validationRules.url.validate(value)) {
                showError(field, validationRules.url.message);
                return false;
            }
        }
        
        // Min length
        const minLength = field.getAttribute('minlength') || field.dataset.minlength;
        if (minLength && !validationRules.minLength.validate(value, minLength)) {
            showError(field, validationRules.minLength.message(minLength));
            return false;
        }
        
        // Max length
        const maxLength = field.getAttribute('maxlength') || field.dataset.maxlength;
        if (maxLength && !validationRules.maxLength.validate(value, maxLength)) {
            showError(field, validationRules.maxLength.message(maxLength));
            return false;
        }
        
        // Password match
        if (field.dataset.match) {
            if (!validationRules.match.validate(value, field.dataset.match)) {
                showError(field, validationRules.match.message(field.dataset.match));
                return false;
            }
        }
        
        // Custom pattern
        if (field.pattern) {
            const regex = new RegExp(field.pattern);
            if (!regex.test(value)) {
                showError(field, field.dataset.patternMessage || 'Invalid format');
                return false;
            }
        }
        
        removeError(field);
        return true;
    }
    
    
    // ==========================================
    // REAL-TIME VALIDATION
    // ==========================================
    
    const formFields = document.querySelectorAll('input, textarea, select');
    
    formFields.forEach(field => {
        // Validate on blur
        field.addEventListener('blur', function() {
            if (this.value) {
                validateField(this);
            }
        });
        
        // Remove error on input
        field.addEventListener('input', function() {
            if (this.classList.contains('error')) {
                validateField(this);
            }
        });
        
        // Validate on change (for select, checkbox, radio)
        field.addEventListener('change', function() {
            if (this.value) {
                validateField(this);
            }
        });
    });
    
    
    // ==========================================
    // FORM SUBMISSION VALIDATION
    // ==========================================
    
    const forms = document.querySelectorAll('form[data-validate], form.validate-form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            let isValid = true;
            let firstInvalidField = null;
            
            // Validate all fields
            const fields = this.querySelectorAll('input:not([type="hidden"]), textarea, select');
            fields.forEach(field => {
                if (!validateField(field)) {
                    isValid = false;
                    if (!firstInvalidField) {
                        firstInvalidField = field;
                    }
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                
                // Scroll to first error
                if (firstInvalidField) {
                    firstInvalidField.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                    firstInvalidField.focus();
                }
                
                // Show error message
                showFormError(this, 'Please fix the errors above before submitting.');
            } else {
                // Show loading state
                const submitBtn = this.querySelector('[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.dataset.originalText = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
                }
            }
        });
    });
    
    
    // ==========================================
    // SHOW FORM ERROR
    // ==========================================
    
    function showFormError(form, message) {
        let errorDiv = form.querySelector('.form-error');
        
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'form-error';
            errorDiv.style.cssText = `
                background: #f8d7da;
                border: 1px solid #f5c6cb;
                color: #721c24;
                padding: 1rem;
                border-radius: 8px;
                margin-bottom: 1rem;
                display: flex;
                align-items: center;
                gap: 0.5rem;
            `;
            form.insertBefore(errorDiv, form.firstChild);
        }
        
        errorDiv.innerHTML = `
            <i class="fas fa-exclamation-circle"></i>
            <span>${message}</span>
        `;
        
        errorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    
    
    // ==========================================
    // PASSWORD STRENGTH INDICATOR
    // ==========================================
    
    const passwordFields = document.querySelectorAll('input[type="password"][data-strength]');
    
    passwordFields.forEach(field => {
        const strengthBar = document.createElement('div');
        strengthBar.className = 'password-strength';
        strengthBar.innerHTML = `
            <div class="strength-bar"></div>
            <span class="strength-text"></span>
        `;
        strengthBar.style.cssText = `
            margin-top: 0.5rem;
        `;
        field.parentElement.appendChild(strengthBar);
        
        field.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            if (password.length >= 8) strength++;
            if (password.length >= 12) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^a-zA-Z0-9]/.test(password)) strength++;
            
            const bar = strengthBar.querySelector('.strength-bar');
            const text = strengthBar.querySelector('.strength-text');
            
            bar.style.width = (strength * 20) + '%';
            
            if (strength < 2) {
                bar.style.background = '#dc3545';
                text.textContent = 'Weak';
                text.style.color = '#dc3545';
            } else if (strength < 4) {
                bar.style.background = '#ffc107';
                text.textContent = 'Medium';
                text.style.color = '#ffc107';
            } else {
                bar.style.background = '#28a745';
                text.textContent = 'Strong';
                text.style.color = '#28a745';
            }
        });
    });
    
    
    // ==========================================
    // PASSWORD VISIBILITY TOGGLE
    // ==========================================
    
    document.querySelectorAll('input[type="password"]').forEach(field => {
        const wrapper = document.createElement('div');
        wrapper.style.position = 'relative';
        field.parentNode.insertBefore(wrapper, field);
        wrapper.appendChild(field);
        
        const toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.className = 'password-toggle';
        toggle.innerHTML = '<i class="fas fa-eye"></i>';
        toggle.style.cssText = `
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #6c757d;
        `;
        wrapper.appendChild(toggle);
        
        toggle.addEventListener('click', function() {
            if (field.type === 'password') {
                field.type = 'text';
                this.innerHTML = '<i class="fas fa-eye-slash"></i>';
            } else {
                field.type = 'password';
                this.innerHTML = '<i class="fas fa-eye"></i>';
            }
        });
    });
    
    
    // ==========================================
    // FILE UPLOAD VALIDATION
    // ==========================================
    
    const fileInputs = document.querySelectorAll('input[type="file"]');
    
    fileInputs.forEach(input => {
        input.addEventListener('change', function() {
            const file = this.files[0];
            if (!file) return;
            
            // Max size validation (in MB)
            const maxSize = parseInt(this.dataset.maxSize) || 5;
            if (file.size > maxSize * 1024 * 1024) {
                showError(this, `File size must be less than ${maxSize}MB`);
                this.value = '';
                return;
            }
            
            // File type validation
            const allowedTypes = this.dataset.allowedTypes?.split(',') || [];
            if (allowedTypes.length > 0 && !allowedTypes.includes(file.type)) {
                showError(this, 'Invalid file type');
                this.value = '';
                return;
            }
            
            removeError(this);
        });
    });
    
    
    // ==========================================
    // ADD DEFAULT VALIDATION STYLES
    // ==========================================
    
    const style = document.createElement('style');
    style.textContent = `
        .error, .invalid {
            border-color: #dc3545 !important;
            background-color: #fff5f5 !important;
        }
        
        .valid {
            border-color: #28a745 !important;
        }
        
        .error-message {
            animation: shake 0.3s ease;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        .password-strength {
            height: 4px;
            background: #e9ecef;
            border-radius: 2px;
            overflow: hidden;
            margin-top: 0.5rem;
        }
        
        .strength-bar {
            height: 100%;
            width: 0;
            transition: all 0.3s ease;
        }
        
        .strength-text {
            font-size: 0.75rem;
            margin-top: 0.25rem;
            display: block;
        }
    `;
    document.head.appendChild(style);
    
    
    console.log('✅ Form-validation.js loaded successfully');
});
