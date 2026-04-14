document.addEventListener('DOMContentLoaded', () => {
    'use strict';

    const modal = document.getElementById('eum-modal');
    const backdrop = document.getElementById('eum-backdrop');
    const closeBtn = document.getElementById('eum-close');
    const cancelBtn = document.getElementById('eum-cancel');
    const form = document.getElementById('eum-form');
    const editButtons = Array.from(document.querySelectorAll('.um-icon-btn-edit'));

    const firstInput = document.getElementById('eum-firstName');
    const lastInput = document.getElementById('eum-lastName');
    const emailInput = document.getElementById('eum-email');
    const usernameInput = document.getElementById('eum-username');
    const originalUsernameInput = document.getElementById('eum-original-username');
    const roleSelect = document.getElementById('eum-role');
    const statusSelect = document.getElementById('eum-status');
    const avatarEl = document.getElementById('eum-avatar-preview');
    const previewName = document.getElementById('eum-preview-name');
    const previewUser = document.getElementById('eum-preview-username');
    const roleBadge = document.getElementById('eum-role-badge');
    const statusBadge = document.getElementById('eum-status-badge');

    if (!modal || !backdrop || !closeBtn || !cancelBtn || !form || editButtons.length === 0) {
        return;
    }

    const avatarPalettes = {
        A: ['#dbeafe', '#1e40af'], N: ['#dbeafe', '#1e40af'],
        B: ['#fce7f3', '#9d174d'], O: ['#fce7f3', '#9d174d'],
        C: ['#dcfce7', '#166534'], P: ['#dcfce7', '#166534'],
        D: ['#fef9c3', '#92400e'], Q: ['#fef9c3', '#92400e'],
        E: ['#ffedd5', '#9a3412'], R: ['#ffedd5', '#9a3412'],
        F: ['#ede9fe', '#5b21b6'], S: ['#ede9fe', '#5b21b6'],
        G: ['#e0f2fe', '#075985'], T: ['#e0f2fe', '#075985'],
        H: ['#fdf2f8', '#831843'], U: ['#fdf2f8', '#831843'],
        I: ['#f0fdf4', '#14532d'], V: ['#f0fdf4', '#14532d'],
        J: ['#fef3c7', '#92400e'], W: ['#fef3c7', '#92400e'],
        K: ['#fee2e2', '#991b1b'], X: ['#fee2e2', '#991b1b'],
        L: ['#f5f3ff', '#4c1d95'], Y: ['#f5f3ff', '#4c1d95'],
        M: ['#ecfdf5', '#065f46'], Z: ['#ecfdf5', '#065f46'],
    };

    const roleBadgeMap = {
        admin: 'badge-admin',
        moderator: 'badge-moderator',
        user: 'badge-user',
        guest: 'badge-secondary',
    };

    const statusBadgeMap = {
        active: 'badge-success',
        pending: 'badge-pending',
        disabled: 'badge-secondary',
        blocked: 'badge-danger',
    };

    function updateAvatar() {
        const first = (firstInput.value.trim() || '?').charAt(0).toUpperCase();
        const second = (lastInput.value.trim() || usernameInput.value.trim() || '?').charAt(0).toUpperCase();
        const initials = first + second;
        const palette = avatarPalettes[first] || ['#e0e7ff', '#3730a3'];

        avatarEl.textContent = initials;
        avatarEl.dataset.initial = initials;
        avatarEl.style.background = palette[0];
        avatarEl.style.color = palette[1];

        const fullName = [firstInput.value.trim(), lastInput.value.trim()].filter(Boolean).join(' ');
        previewName.textContent = fullName || 'User';
        previewUser.textContent = usernameInput.value.trim() ? '@' + usernameInput.value.trim() : '@username';
    }

    function updateBadges() {
        const role = roleSelect.value;
        const status = statusSelect.value;

        roleBadge.className = 'badge ' + (roleBadgeMap[role] || 'badge-secondary');
        roleBadge.textContent = role.charAt(0).toUpperCase() + role.slice(1);

        statusBadge.className = 'badge ' + (statusBadgeMap[status] || 'badge-secondary');
        statusBadge.textContent = status.charAt(0).toUpperCase() + status.slice(1);
    }

    function openModalFromButton(button) {
        firstInput.value = button.dataset.firstName || '';
        lastInput.value = button.dataset.lastName || '';
        emailInput.value = button.dataset.email || '';
        usernameInput.value = button.dataset.username || '';
        originalUsernameInput.value = button.dataset.originalUsername || button.dataset.username || '';
        roleSelect.value = (button.dataset.role || 'user').toLowerCase();
        statusSelect.value = (button.dataset.status || 'active').toLowerCase();

        updateAvatar();
        updateBadges();

        modal.classList.add('is-open');
        backdrop.classList.add('is-open');
        backdrop.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        setTimeout(() => { firstInput.focus(); }, 240);
    }

    function closeModal() {
        modal.classList.remove('is-open');
        backdrop.classList.remove('is-open');
        backdrop.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    editButtons.forEach((button) => {
        button.addEventListener('click', () => openModalFromButton(button));
    });

    closeBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);
    backdrop.addEventListener('click', closeModal);

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modal.classList.contains('is-open')) {
            closeModal();
        }
    });

    modal.addEventListener('keydown', (event) => {
        if (event.key !== 'Tab') {
            return;
        }

        const focusable = modal.querySelectorAll(
            'button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
        );
        const first = focusable[0];
        const last = focusable[focusable.length - 1];

        if (event.shiftKey) {
            if (document.activeElement === first) {
                event.preventDefault();
                last.focus();
            }
        } else if (document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    });

    [firstInput, lastInput, usernameInput].forEach((input) => {
        input.addEventListener('input', updateAvatar);
    });
    roleSelect.addEventListener('change', updateBadges);
    statusSelect.addEventListener('change', updateBadges);

    updateAvatar();
    updateBadges();

    [closeBtn, cancelBtn, backdrop].forEach((element) => {
        element.addEventListener('click', () => {
            setTimeout(() => {
                form.reset();
                updateAvatar();
                updateBadges();
            }, 250);
        });
    });
});
