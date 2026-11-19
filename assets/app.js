import './stimulus_bootstrap.js';
import './styles/app.css';
import { initCharacterPreview } from './services/PersonnageRenderer.js';

// ============================================================================
// INITIALISATION PRINCIPALE
// ============================================================================

function onReady(fn) {
    document.addEventListener('DOMContentLoaded', fn);
    document.addEventListener('turbo:load', fn);
}

// Reset flags avant chaque navigation Turbo
document.addEventListener('turbo:before-render', function() {
    _flags = {};
});

// Flags d'initialisation (réinitialisés par turbo:before-render)
var _flags = {};

// ============================================================================
// TURBO ERROR HANDLER
// ============================================================================
document.addEventListener('turbo:submit-end', function(event) {
    var reponse = event.detail.fetchResponse;
    // Erreur de saisie (422…) : la page renvoyée par le serveur affiche déjà le message.
    if (event.detail.success || (reponse && reponse.clientError)) {
        return;
    }
    showToast(reponse && reponse.serverError
        ? 'Erreur du serveur. Réessayez dans un instant.'
        : 'Impossible d\'envoyer le formulaire. Vérifiez votre connexion ou réessayez.', 'danger');
});

document.addEventListener('turbo:fetch-request-error', function() {
    showToast('La requête a été bloquée. Votre antivirus ou pare-feu pourrait bloquer la connexion.', 'danger');
});

// ============================================================================
// TOAST NOTIFICATIONS
// ============================================================================
function showToast(message, type) {
    type = type || 'danger';
    var container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
    }

    var id = 'toast-' + Date.now();
    container.insertAdjacentHTML('beforeend',
        '<div id="' + id + '" class="toast align-items-center text-white bg-' + type + ' border-0" role="alert" aria-live="assertive" aria-atomic="true">' +
            '<div class="d-flex">' +
                '<div class="toast-body">' + message + '</div>' +
                '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>' +
            '</div>' +
        '</div>'
    );

    var el = document.getElementById(id);
    var toast = new bootstrap.Toast(el, { delay: 4000 });
    toast.show();
    el.addEventListener('hidden.bs.toast', function() { el.remove(); });
}

// ============================================================================
// PASSWORD TOGGLE (login, register, change_password)
// ============================================================================
function initPasswordToggle() {
    var toggleBtn = document.getElementById('togglePassword');
    if (!toggleBtn || toggleBtn.dataset.bound) return;
    toggleBtn.dataset.bound = '1';

    toggleBtn.addEventListener('click', function() {
        // Cherche le champ password dans le même input-group
        var group = this.closest('.input-group');
        var input = group ? group.querySelector('input[type="password"], input[type="text"]') : null;
        if (!input) return;

        var type = input.getAttribute('type') === 'password' ? 'text' : 'password';
        input.setAttribute('type', type);
        var icon = this.querySelector('i');
        icon.classList.toggle('bi-eye');
        icon.classList.toggle('bi-eye-slash');
    });
}

// ============================================================================
// PASSWORD STRENGTH INDICATOR (register, change_password)
// ============================================================================
function initPasswordStrength() {
    if (_flags.passwordStrength) return;

    var strengthBar = document.getElementById('passwordStrengthBar');
    var strengthText = document.getElementById('passwordStrengthText');
    if (!strengthBar || !strengthText) return;

    var progressBar = strengthBar.closest('.progress');
    // Le champ password peut être #password (register) ou #new_password (change_password)
    var passwordInput = document.getElementById('password') || document.getElementById('new_password');
    var confirmInput = document.getElementById('confirmPassword') || document.getElementById('confirm_password');
    var confirmFeedback = document.getElementById('confirmPasswordFeedback');

    if (!passwordInput) return;
    _flags.passwordStrength = true;

    var criteria = {
        length:    { el: document.getElementById('criteria-length'),    test: function(p) { return p.length >= 12; } },
        uppercase: { el: document.getElementById('criteria-uppercase'), test: function(p) { return /[A-Z]/.test(p); } },
        lowercase: { el: document.getElementById('criteria-lowercase'), test: function(p) { return /[a-z]/.test(p); } },
        digit:     { el: document.getElementById('criteria-digit'),     test: function(p) { return /[0-9]/.test(p); } },
        special:   { el: document.getElementById('criteria-special'),   test: function(p) { return /[^A-Za-z0-9]/.test(p); } }
    };

    function updateStrength() {
        var password = passwordInput.value;
        var score = 0;
        var total = Object.keys(criteria).length;

        for (var key in criteria) {
            var passed = criteria[key].test(password);
            score += passed ? 1 : 0;
            var li = criteria[key].el;
            if (!li) continue;
            var icon = li.querySelector('i');
            if (passed) {
                li.classList.remove('text-secondary', 'text-danger');
                li.classList.add('text-success');
                icon.classList.remove('bi-x-circle');
                icon.classList.add('bi-check-circle-fill');
            } else {
                li.classList.remove('text-success');
                li.classList.add('text-secondary');
                icon.classList.remove('bi-check-circle-fill');
                icon.classList.add('bi-x-circle');
            }
        }

        var percent = (score / total) * 100;
        strengthBar.style.width = percent + '%';
        progressBar.setAttribute('aria-valuenow', percent);

        if (password.length === 0) {
            strengthBar.className = 'progress-bar';
            strengthText.textContent = '';
        } else if (score <= 2) {
            strengthBar.className = 'progress-bar bg-danger';
            strengthText.textContent = 'Mot de passe faible';
            strengthText.className = 'text-danger mt-1 d-block small';
        } else if (score <= 3) {
            strengthBar.className = 'progress-bar bg-warning';
            strengthText.textContent = 'Mot de passe moyen';
            strengthText.className = 'text-warning mt-1 d-block small';
        } else if (score <= 4) {
            strengthBar.className = 'progress-bar bg-info';
            strengthText.textContent = 'Mot de passe bon';
            strengthText.className = 'text-info mt-1 d-block small';
        } else {
            strengthBar.className = 'progress-bar bg-success';
            strengthText.textContent = 'Mot de passe excellent';
            strengthText.className = 'text-success mt-1 d-block small';
        }

        updateConfirmMatch();
    }

    function updateConfirmMatch() {
        if (!confirmInput || !confirmFeedback) return;
        var val = confirmInput.value;
        if (val.length === 0) {
            confirmFeedback.textContent = '';
            confirmInput.classList.remove('is-valid', 'is-invalid');
            return;
        }
        if (val === passwordInput.value) {
            confirmFeedback.textContent = 'Les mots de passe correspondent.';
            confirmFeedback.className = 'form-text text-success';
            confirmInput.classList.remove('is-invalid');
            confirmInput.classList.add('is-valid');
        } else {
            confirmFeedback.textContent = 'Les mots de passe ne correspondent pas.';
            confirmFeedback.className = 'form-text text-danger';
            confirmInput.classList.remove('is-valid');
            confirmInput.classList.add('is-invalid');
        }
    }

    passwordInput.addEventListener('input', updateStrength);
    if (confirmInput) {
        confirmInput.addEventListener('input', updateConfirmMatch);
    }
}

// ============================================================================
// COOKIE CONSENT BANNER (footer)
// ============================================================================
function initCookieConsent() {
    var banner = document.getElementById('cookie-banner');
    if (!banner) return;

    var consent = localStorage.getItem('cookie_consent');
    if (!consent) {
        banner.style.display = 'block';
    }

    var acceptBtn = document.getElementById('cookie-accept');
    var refuseBtn = document.getElementById('cookie-refuse');

    if (acceptBtn && !acceptBtn.dataset.bound) {
        acceptBtn.dataset.bound = '1';
        acceptBtn.addEventListener('click', function() {
            localStorage.setItem('cookie_consent', 'accepted');
            banner.style.display = 'none';
        });
    }
    if (refuseBtn && !refuseBtn.dataset.bound) {
        refuseBtn.dataset.bound = '1';
        refuseBtn.addEventListener('click', function() {
            localStorage.setItem('cookie_consent', 'refused');
            banner.style.display = 'none';
        });
    }
}

// ============================================================================
// STAR RATING - Personnage detail page (review form)
// ============================================================================
function initStarRating() {
    if (_flags.starRating) return;

    // Formulaire d'avis (detail.html.twig) : les étoiles cochent les boutons
    // radio cachés du champ « note »
    var form = document.getElementById('commentaire-form');
    if (!form) return;
    _flags.starRating = true;

    var radioStars = form.querySelectorAll('.star-icon');
    var ratingInputs = form.querySelectorAll('input[type="radio"][name*="note"]');
    var ratingText = document.getElementById('rating-text');
    var ratingError = document.getElementById('rating-error');
    var textarea = form.querySelector('textarea');
    var commentaireError = document.getElementById('commentaire-error');
    var selectedRating = 0;

    // Check if this is an edit form (has pre-selected rating)
    ratingInputs.forEach(function(input) {
        if (input.checked) selectedRating = parseInt(input.value);
    });

    var ratingLabels = {
        1: '1 - Décevant',
        2: '2 - Moyen',
        3: '3 - Bien',
        4: '4 - Très bien',
        5: '5 - Excellent'
    };

    function updateRadioStars(rating) {
        radioStars.forEach(function(star, index) {
            star.style.color = index < rating ? '#ffc107' : '#6c757d';
        });
    }

    if (selectedRating > 0) updateRadioStars(selectedRating);

    radioStars.forEach(function(star, index) {
        star.addEventListener('mouseenter', function() {
            var rating = index + 1;
            updateRadioStars(rating);
            if (ratingText) ratingText.textContent = ratingLabels[rating];
        });

        star.addEventListener('click', function(e) {
            e.preventDefault();
            selectedRating = index + 1;
            updateRadioStars(selectedRating);
            if (ratingText) ratingText.textContent = ratingLabels[selectedRating];
            if (ratingError) ratingError.style.display = 'none';
            ratingInputs.forEach(function(input) {
                if (parseInt(input.value) === selectedRating) input.checked = true;
            });
        });
    });

    var starRatingEl = form.querySelector('.star-rating');
    if (starRatingEl) {
        starRatingEl.addEventListener('mouseleave', function() {
            if (selectedRating > 0) {
                updateRadioStars(selectedRating);
                if (ratingText) {
                    ratingText.textContent = selectedRating > 0
                        ? 'Note : ' + selectedRating + ' étoile' + (selectedRating > 1 ? 's' : '')
                        : 'Cliquez sur les étoiles pour noter';
                }
            } else {
                updateRadioStars(0);
                if (ratingText) ratingText.textContent = 'Cliquez sur les étoiles pour noter';
            }
        });
    }

    // Character counter for review textarea
    var counter = document.getElementById('char-counter');
    if (textarea && counter) {
        function updateCounter() {
            var len = textarea.value.length;
            counter.textContent = len + ' / 1000';
            counter.classList.remove('text-danger', 'text-success', 'text-warning', 'text-muted');
            if (len < 10) {
                counter.classList.add('text-danger');
            } else if (len > 900) {
                counter.classList.add('text-warning');
            } else {
                counter.classList.add('text-success');
            }
        }
        textarea.addEventListener('input', updateCounter);
        updateCounter();
    }

    // Form validation
    form.addEventListener('submit', function(e) {
        var hasError = false;
        var checkedRadio = Array.from(ratingInputs).find(function(input) { return input.checked; });

        if (!checkedRadio || selectedRating === 0) {
            e.preventDefault();
            hasError = true;
            if (ratingError) ratingError.style.display = 'block';
        }

        if (textarea) {
            var commentLength = textarea.value.trim().length;
            if (commentLength < 10 || commentLength > 1000) {
                e.preventDefault();
                hasError = true;
                if (commentaireError) commentaireError.style.display = 'block';
            }
        }

        if (hasError) {
            if (ratingError && ratingError.style.display === 'block' && starRatingEl) {
                starRatingEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } else if (commentaireError && commentaireError.style.display === 'block' && textarea) {
                textarea.scrollIntoView({ behavior: 'smooth', block: 'center' });
                textarea.focus();
            }
        }
    });
}

// ============================================================================
// BOOTSTRAP DROPDOWNS INITIALIZATION
// ============================================================================
function initDropdowns() {
    if (typeof bootstrap === 'undefined' || !bootstrap.Dropdown) return;

    document.querySelectorAll('[data-bs-toggle="dropdown"]').forEach(function(element) {
        var existing = bootstrap.Dropdown.getInstance(element);
        if (existing) existing.dispose();
        new bootstrap.Dropdown(element, { autoClose: true, boundary: 'viewport' });
    });
}

// ============================================================================
// MODAL BACKDROP CLEANUP
// ============================================================================
function initBackdropCleanup() {
    if (_flags.backdropCleanup) return;
    _flags.backdropCleanup = true;

    function cleanBackdrops() {
        document.querySelectorAll('.modal-backdrop').forEach(function(b) { b.remove(); });
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
    }

    // MutationObserver to catch dynamically added backdrops
    var observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            mutation.addedNodes.forEach(function(node) {
                if (node.nodeType === 1 && node.classList && node.classList.contains('modal-backdrop')) {
                    node.remove();
                }
            });
        });
    });
    observer.observe(document.body, { childList: true, subtree: false });

    // Clean on modal hide
    document.addEventListener('hidden.bs.modal', function() {
        setTimeout(cleanBackdrops, 50);
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') setTimeout(cleanBackdrops, 100);
    });
}

// ============================================================================
// CONTACT FORM - CHARACTER COUNTER
// ============================================================================
function initContactCounter() {
    var messageField = document.getElementById('message');
    var charCount = document.getElementById('charCount');

    if (messageField && charCount && !messageField.dataset.bound) {
        messageField.dataset.bound = '1';
        messageField.addEventListener('input', function() {
            var count = this.value.length;
            charCount.textContent = count + '/1000';
            charCount.classList.toggle('text-danger', count > 1000);
        });
    }
}

// ============================================================================
// INIT ALL
// ============================================================================
function initAll() {
    initPasswordToggle();
    initPasswordStrength();
    initCookieConsent();
    initStarRating();
    initDropdowns();
    initBackdropCleanup();
    initContactCounter();
    initCharacterPreview();
}

onReady(initAll);
