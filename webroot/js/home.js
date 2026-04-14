document.addEventListener('DOMContentLoaded', () => {
    const table = document.querySelector('.um-table');
    const bulkDeleteButton = document.querySelector('#bulk-delete-button');
    const bulkDeleteForm = document.querySelector('#bulk-delete-form');
    const bulkDeleteInputs = document.querySelector('#bulk-delete-inputs');
    const confirmBackdrop = document.querySelector('#delete-confirm-backdrop');
    const confirmModal = document.querySelector('#delete-confirm-modal');
    const confirmMessage = document.querySelector('#delete-confirm-message');
    const confirmCancel = document.querySelector('#delete-confirm-cancel');
    const confirmApprove = document.querySelector('#delete-confirm-approve');

    if (
        !table ||
        !bulkDeleteButton ||
        !bulkDeleteForm ||
        !bulkDeleteInputs ||
        !confirmBackdrop ||
        !confirmModal ||
        !confirmMessage ||
        !confirmCancel ||
        !confirmApprove
    ) {
        return;
    }

    const selectAllCheckbox = table.querySelector('.select-all');
    const rowCheckboxes = Array.from(table.querySelectorAll('.row-checkbox'));
    const deleteTriggers = Array.from(table.querySelectorAll('.js-delete-trigger'));
    let pendingSubmit = null;

    if (!selectAllCheckbox || rowCheckboxes.length === 0) {
        return;
    }

    const openConfirm = (message, onApprove) => {
        pendingSubmit = onApprove;
        confirmMessage.textContent = message;
        confirmBackdrop.classList.remove('hidden');
        confirmModal.classList.remove('hidden');
        document.body.classList.add('um-modal-open');
        confirmCancel.focus();
    };

    const closeConfirm = () => {
        pendingSubmit = null;
        confirmBackdrop.classList.add('hidden');
        confirmModal.classList.add('hidden');
        document.body.classList.remove('um-modal-open');
    };

    const syncSelectAllState = () => {
        const checkedCount = rowCheckboxes.filter((checkbox) => checkbox.checked).length;

        selectAllCheckbox.checked = checkedCount === rowCheckboxes.length;
        selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < rowCheckboxes.length;
        bulkDeleteButton.disabled = checkedCount === 0;
    };

    selectAllCheckbox.addEventListener('change', () => {
        const shouldCheckAll = selectAllCheckbox.checked;

        rowCheckboxes.forEach((checkbox) => {
            checkbox.checked = shouldCheckAll;
        });

        syncSelectAllState();
    });

    rowCheckboxes.forEach((checkbox) => {
        checkbox.addEventListener('change', syncSelectAllState);
    });

    bulkDeleteButton.addEventListener('click', () => {
        const selectedIds = rowCheckboxes
            .filter((checkbox) => checkbox.checked)
            .map((checkbox) => checkbox.value)
            .filter((value) => value !== '');

        if (selectedIds.length === 0) {
            return;
        }

        openConfirm(`Delete ${selectedIds.length} selected ${selectedIds.length === 1 ? 'user' : 'users'}? This cannot be undone.`, () => {
            bulkDeleteInputs.innerHTML = '';
            selectedIds.forEach((userId) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'userIds[]';
                input.value = userId;
                bulkDeleteInputs.appendChild(input);
            });

            bulkDeleteForm.submit();
        });
    });

    deleteTriggers.forEach((trigger) => {
        trigger.addEventListener('click', () => {
            const form = trigger.closest('form');

            if (!form) {
                return;
            }

            openConfirm('Delete this user? This cannot be undone.', () => {
                form.submit();
            });
        });
    });

    confirmCancel.addEventListener('click', closeConfirm);
    confirmBackdrop.addEventListener('click', closeConfirm);

    confirmApprove.addEventListener('click', () => {
        if (typeof pendingSubmit === 'function') {
            pendingSubmit();
        }

        closeConfirm();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !confirmModal.classList.contains('hidden')) {
            closeConfirm();
        }
    });

    syncSelectAllState();
});
