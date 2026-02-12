// Toggle password visibility
function togglePassword(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('registerForm');
    const firstName = document.getElementById('first_name');
    const lastName = document.getElementById('last_name');
    const email = document.getElementById('email');
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    
    // Variables para controlar la validación de email
    let emailCheckTimeout;
    let isEmailChecking = false;
    let lastEmailChecked = '';
    
    // Función para validar campos y mostrar en verde
    function validateField(field, isValid, message = '') {
        // Remover todos los feedback existentes
        const existingFeedback = field.parentNode.querySelectorAll('.valid-feedback, .invalid-feedback, .text-info');
        existingFeedback.forEach(fb => fb.remove());
        
        if (isValid) {
            field.classList.remove('is-invalid');
            field.classList.add('is-valid');
            
            // Agregar mensaje de éxito si se proporciona
            if (message) {
                let successMsg = document.createElement('div');
                successMsg.className = 'valid-feedback';
                successMsg.textContent = message;
                field.parentNode.appendChild(successMsg);
            }
        } else {
            field.classList.remove('is-valid');
            field.classList.add('is-invalid');
            
            // Agregar mensaje de error si se proporciona
            if (message) {
                let errorMsg = document.createElement('div');
                errorMsg.className = 'invalid-feedback';
                errorMsg.textContent = message;
                field.parentNode.appendChild(errorMsg);
            }
        }
    }
    
    // Función para mostrar estado de verificación
    function showVerifyingState(field, message) {
        // Remover clases de validación y feedback anterior
        field.classList.remove('is-valid', 'is-invalid');
        const existingFeedback = field.parentNode.querySelectorAll('.valid-feedback, .invalid-feedback, .text-info');
        existingFeedback.forEach(fb => fb.remove());
        
        // Crear mensaje de verificación
        const verifyingMsg = document.createElement('div');
        verifyingMsg.className = 'text-info';
        verifyingMsg.innerHTML = message;
        field.parentNode.appendChild(verifyingMsg);
    }
    
    // Función para validar que solo contenga letras, espacios y acentos
    function isValidName(name) {
        // Regex que permite letras (incluye acentos), espacios, apostrofes y guiones
        const nameRegex = /^[a-zA-ZÀ-ÿáéíóúÁÉÍÓÚñÑüÜ\s'\-]+$/;
        return nameRegex.test(name) && name.trim().length >= 2;
    }
    
    // Función para prevenir entrada de caracteres no válidos en nombres
    function preventInvalidChars(event) {
        const char = String.fromCharCode(event.which);
        const validChar = /[a-zA-ZÀ-ÿáéíóúÁÉÍÓÚñÑüÜ\s'\-]/.test(char);
        
        // Permitir teclas especiales (backspace, delete, arrows, etc.)
        const specialKeys = [8, 9, 37, 38, 39, 40, 46];
        
        if (!validChar && !specialKeys.includes(event.which)) {
            event.preventDefault();
        }
    }
    
    // Agregar event listeners para prevenir caracteres inválidos
    firstName.addEventListener('keypress', preventInvalidChars);
    lastName.addEventListener('keypress', preventInvalidChars);
    
    // Validación de nombres
    firstName.addEventListener('input', function() {
        const name = this.value.trim();
        const isValid = isValidName(name);
        
        if (name.length === 0) {
            // Campo vacío - limpiar validación
            this.classList.remove('is-valid', 'is-invalid');
            const existingFeedback = this.parentNode.querySelectorAll('.valid-feedback, .invalid-feedback, .text-info');
            existingFeedback.forEach(fb => fb.remove());
        } else if (isValid) {
            // Solo agregar clase válida, sin mensaje
            this.classList.remove('is-invalid');
            this.classList.add('is-valid');
            // Remover cualquier mensaje de error
            const existingFeedback = this.parentNode.querySelectorAll('.valid-feedback, .invalid-feedback, .text-info');
            existingFeedback.forEach(fb => fb.remove());
        } else {
            // Determinar mensaje de error específico
            const hasInvalidChars = !/^[a-zA-ZÀ-ÿáéíóúÁÉÍÓÚñÑüÜ\s'\-]*$/.test(name);
            const message = hasInvalidChars ? 'Solo se permiten letras, espacios y acentos' : 'Mínimo 2 caracteres';
            validateField(this, false, message);
        }
    });
    
    lastName.addEventListener('input', function() {
        const name = this.value.trim();
        const isValid = isValidName(name);
        
        if (name.length === 0) {
            // Campo vacío - limpiar validación
            this.classList.remove('is-valid', 'is-invalid');
            const existingFeedback = this.parentNode.querySelectorAll('.valid-feedback, .invalid-feedback, .text-info');
            existingFeedback.forEach(fb => fb.remove());
        } else if (isValid) {
            // Solo agregar clase válida, sin mensaje
            this.classList.remove('is-invalid');
            this.classList.add('is-valid');
            // Remover cualquier mensaje de error
            const existingFeedback = this.parentNode.querySelectorAll('.valid-feedback, .invalid-feedback, .text-info');
            existingFeedback.forEach(fb => fb.remove());
        } else {
            // Determinar mensaje de error específico
            const hasInvalidChars = !/^[a-zA-ZÀ-ÿáéíóúÁÉÍÓÚñÑüÜ\s'\-]*$/.test(name);
            const message = hasInvalidChars ? 'Solo se permiten letras, espacios y acentos' : 'Mínimo 2 caracteres';
            validateField(this, false, message);
        }
    });
    
    // Validación de email con verificación en tiempo real
    email.addEventListener('input', function() {
        const emailValue = this.value.trim();
        
        // Limpiar timeout anterior
        if (emailCheckTimeout) {
            clearTimeout(emailCheckTimeout);
        }
        
        // Validación básica de formato
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        const isValidFormat = emailPattern.test(emailValue);
        
        if (!emailValue) {
            // Campo vacío - limpiar todo feedback
            this.classList.remove('is-valid', 'is-invalid');
            const existingFeedback = this.parentNode.querySelectorAll('.valid-feedback, .invalid-feedback, .text-info');
            existingFeedback.forEach(fb => fb.remove());
            return;
        }
        
        if (!isValidFormat) {
            // Formato inválido
            validateField(this, false, 'Formato de email inválido');
            return;
        }
        
        // Si el email no ha cambiado, no hacer nueva consulta
        if (emailValue === lastEmailChecked && !isEmailChecking) {
            return;
        }
        
        // Mostrar estado de verificación
        showVerifyingState(this, '<i class="fas fa-spinner fa-spin me-1"></i>Verificando disponibilidad...');
        
        // Verificar disponibilidad después de 500ms de pausa
        emailCheckTimeout = setTimeout(async () => {
            if (emailValue !== this.value.trim()) return; // El usuario siguió escribiendo
            
            isEmailChecking = true;
            lastEmailChecked = emailValue;
            
            try {
                const response = await fetch('ajax/check-email.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ email: emailValue })
                });
                
                const result = await response.json();
                
                // Verificar que el email no haya cambiado mientras se hacía la consulta
                if (emailValue !== this.value.trim()) return;
                
                if (result.valid && result.available) {
                    validateField(this, true, '✓ Email disponible');
                } else if (result.valid && !result.available) {
                    validateField(this, false, '❌ Este email ya está registrado');
                } else {
                    validateField(this, false, result.message || 'Error de validación');
                }
                
            } catch (error) {
                console.error('Error verificando email:', error);
                validateField(this, false, 'Error verificando disponibilidad');
            } finally {
                isEmailChecking = false;
            }
        }, 500);
    });
    
    // Validación de contraseña con requisitos de seguridad
    password.addEventListener('input', function() {
        validatePasswordStrength(this.value);
        
        // Re-validar confirmación si ya tiene contenido
        if (confirmPassword.value) {
            // Trigger del evento input en confirmPassword para actualizar su validación
            confirmPassword.dispatchEvent(new Event('input'));
        }
    });
    
    // Mostrar/ocultar requisitos cuando se enfoca el campo
    password.addEventListener('focus', function() {
        const requirements = document.getElementById('passwordRequirements');
        requirements.classList.add('show');
    });
    
    password.addEventListener('blur', function() {
        // Solo ocultar si la contraseña está vacía
        if (!this.value.trim()) {
            const requirements = document.getElementById('passwordRequirements');
            requirements.classList.remove('show');
        }
    });
    
    // Función para validar fortaleza de contraseña
    function validatePasswordStrength(password) {
        const passwordField = document.getElementById('password');
        const validationIcon = document.getElementById('passwordValidationIcon');
        const requirements = document.getElementById('passwordRequirements');
        
        // Mostrar requisitos si hay algún contenido
        if (password.length > 0) {
            requirements.classList.add('show');
            validationIcon.classList.add('show');
        } else {
            requirements.classList.remove('show');
            validationIcon.classList.remove('show');
        }
        
        // Definir criterios
        const criteria = {
            length: password.length >= 8,
            uppercase: /[A-Z]/.test(password),
            lowercase: /[a-z]/.test(password),
            number: /[0-9]/.test(password),
            special: /[!@#$%^&*(),.?":{}|<>]/.test(password)
        };
        
        // Actualizar cada requisito visualmente
        updateRequirement('length', criteria.length);
        updateRequirement('uppercase', criteria.uppercase);
        updateRequirement('lowercase', criteria.lowercase);
        updateRequirement('number', criteria.number);
        updateRequirement('special', criteria.special);
        
        // Verificar si todos los criterios se cumplen
        const allValid = Object.values(criteria).every(Boolean);
        
        // Actualizar solo nuestro icono personalizado (NO las clases Bootstrap)
        if (allValid) {
            validationIcon.classList.remove('fa-times');
            validationIcon.classList.add('fa-check');
            validationIcon.classList.add('valid');
        } else {
            validationIcon.classList.remove('fa-check', 'valid');
            validationIcon.classList.add('fa-times');
        }
        
        return allValid;
    }
    
    // Función para actualizar estado de un requisito individual
    function updateRequirement(id, isValid) {
        const requirement = document.getElementById(id);
        const icon = requirement.querySelector('i');
        
        if (isValid) {
            requirement.classList.add('valid');
            icon.classList.remove('fa-times');
            icon.classList.add('fa-check');
        } else {
            requirement.classList.remove('valid');
            icon.classList.remove('fa-check');
            icon.classList.add('fa-times');
        }
    }
    
    // Validación de confirmación de contraseña
    confirmPassword.addEventListener('input', function() {
        const confirmValidationIcon = document.getElementById('confirmPasswordValidationIcon');
        const passwordsMatch = this.value === password.value;
        // Validar que la contraseña principal cumpla con los requisitos de seguridad
        const isPasswordStrong = validatePasswordStrength(password.value);
        const isValid = passwordsMatch && isPasswordStrong && this.value.length > 0;
        
        // Mostrar/ocultar icono según si hay contenido
        if (this.value.length > 0) {
            confirmValidationIcon.classList.add('show');
            
            // Actualizar icono según validación
            if (isValid) {
                confirmValidationIcon.classList.remove('fa-times');
                confirmValidationIcon.classList.add('fa-check');
                confirmValidationIcon.classList.add('valid');
            } else {
                confirmValidationIcon.classList.remove('fa-check', 'valid');
                confirmValidationIcon.classList.add('fa-times');
            }
        } else {
            confirmValidationIcon.classList.remove('show');
        }
        
        // Limpiar cualquier feedback anterior
        const existingFeedback = this.parentNode.parentNode.querySelectorAll('.valid-feedback, .invalid-feedback');
        existingFeedback.forEach(fb => fb.remove());
        
        // Agregar mensaje de feedback solo si hay contenido
        if (this.value.length > 0) {
            const feedbackMsg = document.createElement('div');
            if (isValid) {
                feedbackMsg.className = 'valid-feedback';
                feedbackMsg.textContent = '✓ Las contraseñas coinciden';
            } else {
                feedbackMsg.className = 'invalid-feedback';
                feedbackMsg.textContent = passwordsMatch ? 'La contraseña principal debe cumplir todos los requisitos' : 'Las contraseñas no coinciden';
            }
            this.parentNode.parentNode.appendChild(feedbackMsg);
        }
    });
    
    // Validación del formulario al enviar
    form.addEventListener('submit', async function(e) {
        e.preventDefault(); // Prevenir envío inmediato
        
        let isFormValid = true;
        
        // Validar todos los campos requeridos
        if (!isValidName(firstName.value.trim())) {
            const hasInvalidChars = !/^[a-zA-ZÀ-ÿáéíóúÁÉÍÓÚñÑüÜ\s'\-]*$/.test(firstName.value.trim());
            const message = hasInvalidChars ? 'El nombre solo puede contener letras, espacios y acentos' : 'El nombre es requerido (mínimo 2 caracteres)';
            validateField(firstName, false, message);
            isFormValid = false;
        }
        
        if (!isValidName(lastName.value.trim())) {
            const hasInvalidChars = !/^[a-zA-ZÀ-ÿáéíóúÁÉÍÓÚñÑüÜ\s'\-]*$/.test(lastName.value.trim());
            const message = hasInvalidChars ? 'El apellido solo puede contener letras, espacios y acentos' : 'El apellido es requerido (mínimo 2 caracteres)';
            validateField(lastName, false, message);
            isFormValid = false;
        }
        
        // Validar formato de email
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
            validateField(email, false, 'Email inválido');
            isFormValid = false;
        } else {
            // Verificar disponibilidad de email antes de enviar
            try {
                const response = await fetch('ajax/check-email.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ email: email.value.trim() })
                });
                
                const result = await response.json();
                
                if (!result.available) {
                    validateField(email, false, 'Este email ya está registrado');
                    isFormValid = false;
                }
            } catch (error) {
                console.error('Error verificando email:', error);
                validateField(email, false, 'Error verificando email');
                isFormValid = false;
            }
        }
        
        // Validar fortaleza de contraseña
        const isPasswordStrong = validatePasswordStrength(password.value);
        if (!isPasswordStrong) {
            // Agregar mensaje de error solo si no hay un icono personalizado visible
            const passwordIcon = document.getElementById('passwordValidationIcon');
            if (!passwordIcon.classList.contains('show')) {
                validateField(password, false, 'La contraseña debe cumplir todos los requisitos de seguridad');
            }
            isFormValid = false;
        }
        
        // Validar confirmación de contraseña
        const passwordsMatch = password.value === confirmPassword.value;
        if (!passwordsMatch || !isPasswordStrong) {
            // Agregar mensaje de error solo si no hay un icono personalizado visible
            const confirmIcon = document.getElementById('confirmPasswordValidationIcon');
            if (!confirmIcon.classList.contains('show')) {
                validateField(confirmPassword, false, passwordsMatch ? 'La contraseña principal debe cumplir todos los requisitos' : 'Las contraseñas no coinciden');
            }
            isFormValid = false;
        }
        
        if (!isFormValid) {
            // Scroll al primer campo con error
            const firstError = form.querySelector('.is-invalid');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstError.focus();
            }
            return false;
        } else {
            // Si todo está válido, enviar el formulario
            form.submit();
        }
    });
});