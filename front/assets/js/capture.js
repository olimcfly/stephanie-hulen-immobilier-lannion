// Script pour les pages de capture
document.addEventListener('DOMContentLoaded', function() {
    
    const form = document.getElementById('captureForm');
    
    if (!form) return;
    
    // Validation du formulaire
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Réinitialiser les erreurs
        clearErrors();
        
        // Valider les champs
        let isValid = true;
        const formData = new FormData(form);
        
        // Validation email
        const emailField = form.querySelector('input[name="email"]');
        if (emailField && emailField.value) {
            if (!isValidEmail(emailField.value)) {
                showError(emailField, 'Veuillez entrer une adresse email valide');
                isValid = false;
            }
        }
        
        // Validation téléphone
        const telField = form.querySelector('input[name="telephone"]');
        if (telField && telField.value) {
            if (!isValidPhone(telField.value)) {
                showError(telField, 'Veuillez entrer un numéro de téléphone valide');
                isValid = false;
            }
        }
        
        // Validation surface
        const surfaceField = form.querySelector('input[name="surface"]');
        if (surfaceField && surfaceField.value) {
            if (parseFloat(surfaceField.value) <= 0) {
                showError(surfaceField, 'La surface doit être supérieure à 0');
                isValid = false;
            }
        }
        
        // Validation budget
        const budgetField = form.querySelector('input[name="budget"]');
        if (budgetField && budgetField.value) {
            if (parseFloat(budgetField.value) <= 0) {
                showError(budgetField, 'Le budget doit être supérieur à 0');
                isValid = false;
            }
        }
        
        // Validation consentement RGPD
        const consentField = form.querySelector('input[name="consent"]');
        if (consentField && !consentField.checked) {
            showError(consentField, 'Vous devez accepter la politique de confidentialité');
            isValid = false;
        }
        
        // Si tout est valide, soumettre le formulaire
        if (isValid) {
            submitForm(form);
        } else {
            // Scroll vers la première erreur
            const firstError = form.querySelector('.form-group.error');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    });
    
    // Validation en temps réel
    const inputs = form.querySelectorAll('input, textarea, select');
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            validateField(this);
        });
        
        input.addEventListener('input', function() {
            // Enlever l'erreur quand l'utilisateur commence à taper
            const formGroup = this.closest('.form-group');
            if (formGroup && formGroup.classList.contains('error')) {
                formGroup.classList.remove('error');
            }
        });
    });
    
    // Format automatique du téléphone
    const phoneInput = form.querySelector('input[name="telephone"]');
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 0) {
                // Format: 06 12 34 56 78
                value = value.match(/.{1,2}/g).join(' ');
                e.target.value = value.substr(0, 14);
            }
        });
    }
});

/**
 * Soumettre le formulaire en AJAX
 */
function submitForm(form) {
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    
    // Afficher le loading
    submitBtn.classList.add('loading');
    submitBtn.disabled = true;
    
    const formData = new FormData(form);
    
    fetch(form.action, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Succès
            if (data.redirect) {
                // Redirection
                window.location.href = data.redirect;
            } else if (data.message) {
                // Afficher le message
                showSuccessMessage(data.message);
                form.reset();
            }
            
            // Tracking
            if (typeof gtag !== 'undefined') {
                gtag('event', 'conversion', {
                    'event_category': 'Lead',
                    'event_label': 'Capture Form'
                });
            }
            
            if (typeof fbq !== 'undefined') {
                fbq('track', 'Lead');
            }
            
        } else {
            // Erreur
            showErrorMessage(data.message || 'Une erreur est survenue. Veuillez réessayer.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorMessage('Une erreur est survenue. Veuillez réessayer.');
    })
    .finally(() => {
        // Retirer le loading
        submitBtn.classList.remove('loading');
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    });
}

/**
 * Valider un champ
 */
function validateField(field) {
    const formGroup = field.closest('.form-group');
    if (!formGroup) return true;
    
    let isValid = true;
    let errorMessage = '';
    
    // Champ requis
    if (field.hasAttribute('required') && !field.value.trim()) {
        isValid = false;
        errorMessage = 'Ce champ est obligatoire';
    }
    
    // Email
    if (field.type === 'email' && field.value) {
        if (!isValidEmail(field.value)) {
            isValid = false;
            errorMessage = 'Adresse email invalide';
        }
    }
    
    // Téléphone
    if (field.name === 'telephone' && field.value) {
        if (!isValidPhone(field.value)) {
            isValid = false;
            errorMessage = 'Numéro de téléphone invalide';
        }
    }
    
    if (!isValid) {
        showError(field, errorMessage);
    } else {
        clearError(field);
    }
    
    return isValid;
}

/**
 * Afficher une erreur sur un champ
 */
function showError(field, message) {
    const formGroup = field.closest('.form-group');
    if (!formGroup) return;
    
    formGroup.classList.add('error');
    
    let errorDiv = formGroup.querySelector('.form-error');
    if (!errorDiv) {
        errorDiv = document.createElement('div');
        errorDiv.className = 'form-error';
        field.parentNode.appendChild(errorDiv);
    }
    errorDiv.textContent = message;
}

/**
 * Effacer l'erreur d'un champ
 */
function clearError(field) {
    const formGroup = field.closest('.form-group');
    if (!formGroup) return;
    
    formGroup.classList.remove('error');
    
    const errorDiv = formGroup.querySelector('.form-error');
    if (errorDiv) {
        errorDiv.remove();
    }
}

/**
 * Effacer toutes les erreurs
 */
function clearErrors() {
    document.querySelectorAll('.form-group.error').forEach(group => {
        group.classList.remove('error');
    });
    document.querySelectorAll('.form-error').forEach(error => {
        error.remove();
    });
}

/**
 * Afficher un message de succès
 */
function showSuccessMessage(message) {
    const form = document.getElementById('captureForm');
    const successDiv = document.createElement('div');
    successDiv.className = 'form-success-message';
    successDiv.innerHTML = `
        <i class="fas fa-check-circle"></i>
        <strong>Merci !</strong> ${message}
    `;
    
    form.parentNode.insertBefore(successDiv, form);
    form.style.display = 'none';
    
    // Scroll vers le message
    successDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

/**
 * Afficher un message d'erreur
 */
function showErrorMessage(message) {
    const form = document.getElementById('captureForm');
    
    // Supprimer les anciens messages d'erreur
    const oldError = form.parentNode.querySelector('.form-error-message');
    if (oldError) oldError.remove();
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'form-error-message';
    errorDiv.innerHTML = `
        <i class="fas fa-exclamation-circle"></i>
        ${message}
    `;
    
    form.parentNode.insertBefore(errorDiv, form);
    
    // Scroll vers le message
    errorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
    
    // Supprimer après 5 secondes
    setTimeout(() => {
        errorDiv.remove();
    }, 5000);
}

/**
 * Valider une adresse email
 */
function isValidEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

/**
 * Valider un numéro de téléphone français
 */
function isValidPhone(phone) {
    // Retirer tous les espaces, points, tirets
    const cleaned = phone.replace(/[\s.\-]/g, '');
    
    // Vérifier le format français (commence par 0 et 10 chiffres)
    const re = /^0[1-9]\d{8}$/;
    return re.test(cleaned);
}

/**
 * Formater un nombre avec des espaces
 */
function formatNumber(number) {
    return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ");
}

// Animation au scroll
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('animate-in');
        }
    });
}, observerOptions);

// Observer les éléments à animer
document.querySelectorAll('.capture-description, .capture-form').forEach(el => {
    observer.observe(el);
});

// Style pour les messages d'erreur globaux
const style = document.createElement('style');
style.textContent = `
.form-error-message {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.form-error-message i {
    font-size: 1.2rem;
}

.animate-in {
    animation: fadeInUp 0.6s ease-out;
}
`;
document.head.appendChild(style);