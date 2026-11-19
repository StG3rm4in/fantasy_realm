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

    // Version avec data-value (detail.html.twig)
    var stars = document.querySelectorAll('.star-rating .star-icon');
    var ratingInput = document.getElementById('star-rating-input');
    if (stars.length && ratingInput) {
        _flags.starRating = true;
        var currentRating = 0;

        function setRating(value) {
            currentRating = value;
            ratingInput.value = value;
            updateStarsDataValue(value);
        }

        function updateStarsDataValue(value) {
            stars.forEach(function(star) {
                var starValue = parseInt(star.getAttribute('data-value'));
                if (starValue <= value) {
                    star.classList.remove('bi-star', 'text-secondary');
                    star.classList.add('bi-star-fill', 'text-warning');
                    star.setAttribute('aria-checked', 'true');
                } else {
                    star.classList.remove('bi-star-fill', 'text-warning');
                    star.classList.add('bi-star', 'text-secondary');
                    star.setAttribute('aria-checked', 'false');
                }
            });
        }

        stars.forEach(function(star) {
            star.addEventListener('click', function() {
                setRating(parseInt(this.getAttribute('data-value')));
            });
            star.addEventListener('mouseenter', function() {
                updateStarsDataValue(parseInt(this.getAttribute('data-value')));
            });
            star.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    setRating(parseInt(this.getAttribute('data-value')));
                }
            });
        });

        var ratingContainer = document.querySelector('.star-rating');
        if (ratingContainer) {
            ratingContainer.addEventListener('mouseleave', function() {
                updateStarsDataValue(currentRating);
            });
        }
        return;
    }

    // Version avec radio inputs (avis system - commentaire-form)
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
// CONFIRMATION DIALOGS
// ============================================================================
function initConfirmDialogs() {
    document.querySelectorAll('.confirm-form button[type="submit"]').forEach(function(button) {
        if (button.dataset.bound) return;
        button.dataset.bound = '1';
        button.addEventListener('click', function(e) {
            var message = this.getAttribute('data-confirm-message');
            if (message && !confirm(message)) {
                e.preventDefault();
            }
        });
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
// ADMIN - TAB MANAGEMENT
// ============================================================================
function initAdminTabs() {
    var adminTabs = document.getElementById('adminTabs');
    if (!adminTabs) return;

    var urlParams = new URLSearchParams(window.location.search);
    var activeTab = urlParams.get('tab');

    if (activeTab === 'logs') {
        var logsTab = document.getElementById('logs-tab');
        var logsPanel = document.getElementById('logs-panel');
        var usersTab = document.getElementById('users-tab');
        var usersPanel = document.getElementById('users-panel');

        if (logsTab && logsPanel && usersTab && usersPanel) {
            usersTab.classList.remove('active');
            usersTab.setAttribute('aria-selected', 'false');
            usersPanel.classList.remove('show', 'active');
            logsTab.classList.add('active');
            logsTab.setAttribute('aria-selected', 'true');
            logsPanel.classList.add('show', 'active');
        }
    }

    document.querySelectorAll('#adminTabs button[data-bs-toggle="tab"]').forEach(function(button) {
        if (button.dataset.bound) return;
        button.dataset.bound = '1';
        button.addEventListener('shown.bs.tab', function(e) {
            var targetId = e.target.getAttribute('data-bs-target');
            var url = new URL(window.location);
            if (targetId === '#logs-panel') {
                url.searchParams.set('tab', 'logs');
            } else {
                url.searchParams.delete('tab');
            }
            window.history.pushState({}, '', url);
        });
    });
}

// ============================================================================
// ADMIN - EMPLOYEE FORM - ROLE_USER MANDATORY
// ============================================================================
function initEmployeeForm() {
    var employeeForm = document.querySelector('form[name="employee"]');
    if (!employeeForm) return;

    document.querySelectorAll('input[type="checkbox"][id*="roles"]').forEach(function(checkbox) {
        if (checkbox.value === 'ROLE_USER') {
            checkbox.checked = true;
            checkbox.disabled = true;
            checkbox.parentElement.style.opacity = '0.7';
            checkbox.parentElement.style.cursor = 'not-allowed';

            var form = checkbox.closest('form');
            var hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = checkbox.name;
            hiddenInput.value = 'ROLE_USER';
            form.appendChild(hiddenInput);
        }
    });
}

// ============================================================================
// EMPLOYEE - USER MANAGEMENT AJAX
// ============================================================================
function initUserManagement() {
    if (_flags.userManagement) return;

    var usersPanel = document.getElementById('users-panel');
    if (!usersPanel) return;
    _flags.userManagement = true;

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function updateCounters() {
        var activeCount = document.querySelectorAll('#active-users-body table tbody tr').length;
        var suspendedCount = document.querySelectorAll('#suspended-users-body table tbody tr').length;
        var totalCount = activeCount + suspendedCount;

        var el;
        if ((el = document.getElementById('stat-active-count'))) el.textContent = activeCount;
        if ((el = document.getElementById('stat-suspended-count'))) el.textContent = suspendedCount;
        if ((el = document.getElementById('stat-total-count'))) el.textContent = totalCount;
        if ((el = document.getElementById('active-users-count'))) el.textContent = activeCount;
        if ((el = document.getElementById('suspended-users-count'))) el.textContent = suspendedCount;
    }

    function checkEmptyState(sectionId, isActive) {
        var section = document.getElementById(sectionId);
        if (!section) return;

        var tbody = section.querySelector('table tbody');
        var hasRows = tbody && tbody.querySelectorAll('tr').length > 0;

        if (!hasRows) {
            var msg = isActive ? 'Aucun utilisateur actif' : 'Aucun utilisateur suspendu';
            section.innerHTML =
                '<div class="text-center py-5 text-secondary empty-state">' +
                    '<i class="bi bi-inbox fs-1 mb-3"></i>' +
                    '<p class="mb-0">' + msg + '</p>' +
                '</div>';
        }
    }

    function createSuspendModal(user, tokens) {
        var modalId = 'suspendModal' + user.id;
        if (document.getElementById(modalId)) return;

        document.body.insertAdjacentHTML('beforeend',
            '<div class="modal fade" id="' + modalId + '" tabindex="-1" data-bs-backdrop="false">' +
                '<div class="modal-dialog modal-dialog-centered">' +
                    '<div class="modal-content bg-dark border border-warning">' +
                        '<div class="modal-header border-warning">' +
                            '<h5 class="modal-title"><i class="bi bi-pause-circle text-warning me-2"></i>Suspendre l\'utilisateur</h5>' +
                            '<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>' +
                        '</div>' +
                        '<div class="modal-body">' +
                            '<p class="mb-3">Suspendre <strong>' + escapeHtml(user.pseudo) + '</strong> ?</p>' +
                            '<div class="alert alert-info mb-0"><i class="bi bi-info-circle me-2"></i>L\'utilisateur ne pourra plus se connecter.</div>' +
                        '</div>' +
                        '<div class="modal-footer border-warning">' +
                            '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>' +
                            '<form method="post" action="/employee/users/' + user.id + '/suspend" class="d-inline">' +
                                '<input type="hidden" name="_token" value="' + tokens.suspend + '">' +
                                '<button type="submit" class="btn btn-warning"><i class="bi bi-pause-circle me-1"></i>Suspendre</button>' +
                            '</form>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
            '</div>'
        );
    }

    function createDeleteModal(user, tokens, isSuspended) {
        var modalId = isSuspended ? 'deleteSuspendedModal' + user.id : 'deleteModal' + user.id;
        if (document.getElementById(modalId)) return;

        document.body.insertAdjacentHTML('beforeend',
            '<div class="modal fade" id="' + modalId + '" tabindex="-1" data-bs-backdrop="false">' +
                '<div class="modal-dialog modal-dialog-centered">' +
                    '<div class="modal-content bg-dark border border-danger">' +
                        '<div class="modal-header border-danger">' +
                            '<h5 class="modal-title"><i class="bi bi-trash text-danger me-2"></i>Supprimer l\'utilisateur</h5>' +
                            '<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>' +
                        '</div>' +
                        '<div class="modal-body">' +
                            '<p class="mb-3">Supprimer définitivement <strong>' + escapeHtml(user.pseudo) + '</strong> ?</p>' +
                            '<div class="alert alert-danger mb-0"><i class="bi bi-exclamation-triangle me-2"></i><strong>Attention :</strong> Cette action est irréversible !</div>' +
                        '</div>' +
                        '<div class="modal-footer border-danger">' +
                            '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>' +
                            '<form method="post" action="/employee/users/' + user.id + '/delete" class="d-inline">' +
                                '<input type="hidden" name="_token" value="' + tokens.delete + '">' +
                                '<button type="submit" class="btn btn-danger"><i class="bi bi-trash me-1"></i>Supprimer</button>' +
                            '</form>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
            '</div>'
        );
    }

    function createActiveUserRow(user, tokens) {
        createSuspendModal(user, tokens);
        createDeleteModal(user, tokens, false);

        var tr = document.createElement('tr');
        tr.setAttribute('data-user-id', user.id);
        tr.innerHTML =
            '<td><div class="d-flex align-items-center gap-2"><i class="bi bi-person-circle text-primary"></i><strong>' + escapeHtml(user.pseudo) + '</strong></div></td>' +
            '<td><span class="text-secondary">' + escapeHtml(user.email) + '</span></td>' +
            '<td><span class="text-secondary small">' + user.createdAt + '</span></td>' +
            '<td class="text-end"><div class="btn-group btn-group-sm">' +
                '<button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#suspendModal' + user.id + '" title="Suspendre"><i class="bi bi-pause-circle"></i></button>' +
                '<button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal' + user.id + '" title="Supprimer"><i class="bi bi-trash"></i></button>' +
            '</div></td>';
        return tr;
    }

    function createSuspendedUserRow(user, tokens) {
        createDeleteModal(user, tokens, true);

        var tr = document.createElement('tr');
        tr.setAttribute('data-user-id', user.id);
        tr.setAttribute('data-suspended', 'true');
        tr.innerHTML =
            '<td><div class="d-flex align-items-center gap-2"><i class="bi bi-person-circle text-warning"></i><strong>' + escapeHtml(user.pseudo) + '</strong><span class="badge bg-warning text-dark"><i class="bi bi-pause-circle me-1"></i>Suspendu</span></div></td>' +
            '<td><span class="text-secondary">' + escapeHtml(user.email) + '</span></td>' +
            '<td><span class="text-secondary small">' + user.createdAt + '</span></td>' +
            '<td class="text-end"><div class="btn-group btn-group-sm">' +
                '<form method="post" action="/employee/users/' + user.id + '/unsuspend" class="d-inline"><input type="hidden" name="_token" value="' + tokens.unsuspend + '"><button type="submit" class="btn btn-success" title="Réactiver"><i class="bi bi-play-circle me-1"></i>Réactiver</button></form>' +
                '<button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteSuspendedModal' + user.id + '" title="Supprimer"><i class="bi bi-trash"></i></button>' +
            '</div></td>';
        return tr;
    }

    function cleanupModalEffects() {
        document.querySelectorAll('.modal-backdrop').forEach(function(b) { b.remove(); });
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('padding-right');
    }

    function createTableIfNeeded(sectionId) {
        var section = document.getElementById(sectionId);
        if (!section) return null;

        var tbody = section.querySelector('table tbody');
        if (!tbody || section.querySelector('.empty-state')) {
            section.innerHTML =
                '<div class="table-responsive">' +
                    '<table class="table table-hover align-middle mb-0">' +
                        '<thead class="table-dark"><tr><th>Pseudo</th><th>Email</th><th>Inscrit le</th><th class="text-end">Actions</th></tr></thead>' +
                        '<tbody></tbody>' +
                    '</table>' +
                '</div>';
            tbody = section.querySelector('tbody');
        }
        return tbody;
    }

    document.addEventListener('submit', async function(e) {
        var form = e.target;
        if (!form.action || !form.action.includes('/employee/users/')) return;
        if (form.classList.contains('submitting')) { e.preventDefault(); return; }

        e.preventDefault();
        form.classList.add('submitting');

        var submitBtn = form.querySelector('button[type="submit"]');
        var originalHTML = submitBtn ? submitBtn.innerHTML : '';
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>...';
        }

        var url = form.action;

        try {
            var response = await fetch(url, {
                method: 'POST',
                body: new FormData(form),
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            var data = await response.json();

            if (data.success) {
                showToast(data.message, 'success');

                var modal = form.closest('.modal');
                if (modal) {
                    var bsModal = bootstrap.Modal.getInstance(modal);
                    if (bsModal) {
                        modal.addEventListener('hidden.bs.modal', cleanupModalEffects, { once: true });
                        bsModal.hide();
                    } else {
                        modal.classList.remove('show');
                        modal.style.display = 'none';
                        cleanupModalEffects();
                    }
                    setTimeout(cleanupModalEffects, 300);
                }

                if (url.includes('/suspend') && !url.includes('/unsuspend')) {
                    var userId = data.user.id;
                    var userRow = document.querySelector('#active-users-body tr[data-user-id="' + userId + '"]');
                    if (userRow) {
                        userRow.style.transition = 'opacity 0.3s ease';
                        userRow.style.opacity = '0';
                        setTimeout(function() {
                            userRow.remove();
                            if (!document.querySelector('#suspended-users-body tr[data-user-id="' + userId + '"]')) {
                                var tbody = createTableIfNeeded('suspended-users-body');
                                if (tbody) {
                                    var newRow = createSuspendedUserRow(data.user, data.tokens);
                                    newRow.style.opacity = '0';
                                    tbody.insertBefore(newRow, tbody.firstChild);
                                    setTimeout(function() { newRow.style.transition = 'opacity 0.3s ease'; newRow.style.opacity = '1'; }, 50);
                                }
                            }
                            checkEmptyState('active-users-body', true);
                            updateCounters();
                        }, 300);
                    }
                } else if (url.includes('/unsuspend')) {
                    var userId = data.user.id;
                    var userRow = document.querySelector('#suspended-users-body tr[data-user-id="' + userId + '"]');
                    if (userRow) {
                        userRow.style.transition = 'opacity 0.3s ease';
                        userRow.style.opacity = '0';
                        setTimeout(function() {
                            userRow.remove();
                            if (!document.querySelector('#active-users-body tr[data-user-id="' + userId + '"]')) {
                                var tbody = createTableIfNeeded('active-users-body');
                                if (tbody) {
                                    var newRow = createActiveUserRow(data.user, data.tokens);
                                    newRow.style.opacity = '0';
                                    tbody.insertBefore(newRow, tbody.firstChild);
                                    setTimeout(function() { newRow.style.transition = 'opacity 0.3s ease'; newRow.style.opacity = '1'; }, 50);
                                }
                            }
                            checkEmptyState('suspended-users-body', false);
                            updateCounters();
                        }, 300);
                    }
                } else if (url.includes('/delete')) {
                    var userId = data.userId;
                    var userRow = document.querySelector('#active-users-body tr[data-user-id="' + userId + '"]') ||
                                  document.querySelector('#suspended-users-body tr[data-user-id="' + userId + '"]');
                    if (userRow) {
                        var wasActive = userRow.closest('#active-users-body') !== null;
                        userRow.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                        userRow.style.opacity = '0';
                        userRow.style.transform = 'translateX(100px)';
                        setTimeout(function() {
                            userRow.remove();
                            checkEmptyState(wasActive ? 'active-users-body' : 'suspended-users-body', wasActive);
                            updateCounters();
                        }, 300);
                    }
                }
            } else {
                showToast(data.message || 'Erreur', 'danger');
            }
        } catch (error) {
            showToast('Une erreur est survenue. Veuillez réessayer.', 'danger');
        } finally {
            setTimeout(cleanupModalEffects, 500);
            form.classList.remove('submitting');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalHTML;
            }
        }
    });
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
    initConfirmDialogs();
    initContactCounter();
    initAdminTabs();
    initEmployeeForm();
    initCharacterPreview();
    initUserManagement();
}

onReady(initAll);
