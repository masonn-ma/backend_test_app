document.addEventListener('DOMContentLoaded', () => {
    'use strict';

    /* ── Elements ─────────────────────────────────────────── */
    const openBtn = document.getElementById('open-add-user');
    const modal = document.getElementById('aum-modal');
    const backdrop = document.getElementById('aum-backdrop');
    const closeBtn = document.getElementById('aum-close');
    const cancelBtn = document.getElementById('aum-cancel');
    const form = document.getElementById('aum-form');

    // Live preview
    const firstInput = document.getElementById('aum-firstName');
    const lastInput = document.getElementById('aum-lastName');
    const usernameInput = document.getElementById('aum-username');
    const roleSelect = document.getElementById('aum-role');
    const statusSelect = document.getElementById('aum-status');
    const avatarEl = document.getElementById('aum-avatar-preview');
    const previewName = document.getElementById('aum-preview-name');
    const previewUser = document.getElementById('aum-preview-username');
    const roleBadge = document.getElementById('aum-role-badge');
    const statusBadge = document.getElementById('aum-status-badge');
    const pwInput = document.getElementById('aum-password');
    const pwToggle = document.querySelector('.aum-pw-toggle');
    const eyeOpen = document.querySelector('.aum-eye-open');
    const eyeClosed = document.querySelector('.aum-eye-closed');

    if (!openBtn || !modal || !backdrop || !closeBtn || !cancelBtn || !form) {
        return;
    }

    /* ── Open / Close ─────────────────────────────────────── */
    function openModal() {
        modal.classList.add('is-open');
        backdrop.classList.add('is-open');
        backdrop.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        // Focus first input after transition
        setTimeout(() => { firstInput && firstInput.focus(); }, 240);
    }

    function closeModal() {
        modal.classList.remove('is-open');
        backdrop.classList.remove('is-open');
        backdrop.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    openBtn.addEventListener('click', openModal);
    closeBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);
    backdrop.addEventListener('click', closeModal);

    // Close on Escape
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.classList.contains('is-open')) {
            closeModal();
        }
    });

    // Trap focus inside modal when open
    modal.addEventListener('keydown', function (e) {
        if (e.key !== 'Tab') return;
        const focusable = modal.querySelectorAll(
            'button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
        );
        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        if (e.shiftKey) {
            if (document.activeElement === first) { e.preventDefault(); last.focus(); }
        } else {
            if (document.activeElement === last) { e.preventDefault(); first.focus(); }
        }
    });

    /* ── Avatar colour palette (mirrors custom.css) ───────── */
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
        previewName.textContent = fullName || 'New User';
        previewUser.textContent = usernameInput.value.trim() ? '@' + usernameInput.value.trim() : '@username';
    }

    /* ── Badge preview ────────────────────────────────────── */
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

    function updateBadges() {
        const role = roleSelect.value;
        const status = statusSelect.value;

        roleBadge.className = 'badge ' + (roleBadgeMap[role] || 'badge-secondary');
        roleBadge.textContent = role.charAt(0).toUpperCase() + role.slice(1);

        statusBadge.className = 'badge ' + (statusBadgeMap[status] || 'badge-secondary');
        statusBadge.textContent = status.charAt(0).toUpperCase() + status.slice(1);
    }

    /* ── Password toggle ──────────────────────────────────── */
    if (pwToggle && pwInput) {
        pwToggle.addEventListener('click', function () {
            const isPassword = pwInput.type === 'password';
            pwInput.type = isPassword ? 'text' : 'password';
            eyeOpen.style.display = isPassword ? 'none' : '';
            eyeClosed.style.display = isPassword ? '' : 'none';
        });
    }

    /* ── Event listeners ──────────────────────────────────── */
    [firstInput, lastInput, usernameInput].forEach(el => {
        el.addEventListener('input', updateAvatar);
    });
    roleSelect.addEventListener('change', updateBadges);
    statusSelect.addEventListener('change', updateBadges);

    // Initial state
    updateAvatar();
    updateBadges();

    /* ── Form reset on close ──────────────────────────────── */
    [closeBtn, cancelBtn, backdrop].forEach(el => {
        el.addEventListener('click', function () {
            setTimeout(() => {
                form.reset();
                updateAvatar();
                updateBadges();
            }, 250); // after close animation
        });
    });

});