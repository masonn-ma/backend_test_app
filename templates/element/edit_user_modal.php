<div class="aum-backdrop" id="eum-backdrop" aria-hidden="true"></div>

<div class="aum-modal" id="eum-modal" role="dialog" aria-modal="true" aria-labelledby="eum-title">

    <div class="aum-header">
        <div class="aum-header-left">
            <div class="aum-header-icon">
                <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" width="18" height="18">
                    <path d="M14.5 2.5a2.121 2.121 0 0 1 3 3L6 17H3v-3L14.5 2.5z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" />
                    <path d="M4 16h4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                </svg>
            </div>
            <div>
                <h2 id="eum-title">Edit user</h2>
                <p>Update the details below to modify the account.</p>
            </div>
        </div>
        <button class="aum-close" id="eum-close" aria-label="Close modal">
            <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" width="18" height="18">
                <path d="M5 5l10 10M15 5L5 15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
            </svg>
        </button>
    </div>

    <?= $this->Form->create(null, ['url' => ['controller' => 'Home', 'action' => 'editUser'], 'id' => 'eum-form', 'class' => 'aum-form']) ?>
    <input type="hidden" id="eum-original-username" name="originalUsername" value="">

    <div class="aum-section-label">Personal information</div>

    <div class="aum-row">
        <div class="aum-field">
            <label class="aum-label" for="eum-firstName">First name <span class="aum-required">*</span></label>
            <input type="text" id="eum-firstName" name="firstName" class="aum-input" placeholder="e.g. John" required autocomplete="given-name">
        </div>
        <div class="aum-field">
            <label class="aum-label" for="eum-lastName">Last name <span class="aum-required">*</span></label>
            <input type="text" id="eum-lastName" name="lastName" class="aum-input" placeholder="e.g. Smith" required autocomplete="family-name">
        </div>
    </div>

    <div class="aum-field">
        <label class="aum-label" for="eum-email">Email address <span class="aum-required">*</span></label>
        <div class="aum-wrap-icon">
            <input type="email" id="eum-email" name="email" class="aum-input aum-has-icon" placeholder="john@example.com" required autocomplete="email">
            <svg class="aum-icon-left" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" width="15" height="15">
                <rect x="2" y="4" width="16" height="12" rx="2" stroke="currentColor" stroke-width="1.5" />
                <path d="M2 7l8 5 8-5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
            </svg>
        </div>
    </div>

    <div class="aum-section-label">Account details</div>

    <div class="aum-row">
        <div class="aum-field">
            <label class="aum-label" for="eum-username">Username <span class="aum-required">*</span></label>
            <div class="aum-wrap-icon">
                <input type="text" id="eum-username" name="username" class="aum-input aum-has-prefix" placeholder="jonny77" required autocomplete="username" pattern="[a-zA-Z0-9_\-]+" title="Letters, numbers, underscores and hyphens only">
                <span class="aum-icon-left aum-at">@</span>
            </div>
        </div>
        <div class="aum-field">
            <label class="aum-label" for="eum-role">Role</label>
            <select id="eum-role" name="role" class="aum-select">
                <option value="user">User</option>
                <option value="moderator">Moderator</option>
                <option value="admin">Admin</option>
                <option value="guest">Guest</option>
            </select>
        </div>
    </div>

    <div class="aum-row">
        <div class="aum-field">
            <label class="aum-label" for="eum-status">Status</label>
            <select id="eum-status" name="status" class="aum-select">
                <option value="active">Active</option>
                <option value="pending">Pending</option>
                <option value="disabled">Disabled</option>
                <option value="blocked">Blocked</option>
            </select>
        </div>
        <div class="aum-field">
            <label class="aum-label">&nbsp;</label>
            <div class="aum-role-preview" id="eum-role-preview" style="margin-bottom: 0;">
                <span class="aum-role-preview-label">Preview:</span>
                <span class="badge badge-user" id="eum-role-badge">User</span>
                <span class="badge badge-success" id="eum-status-badge">Active</span>
            </div>
        </div>
    </div>

    <div class="aum-avatar-preview">
        <div class="um-avatar aum-avatar-lg" id="eum-avatar-preview" data-initial="?">?</div>
        <div class="aum-avatar-preview-text">
            <span id="eum-preview-name">User</span>
            <span class="aum-preview-sub" id="eum-preview-username">@username</span>
        </div>
    </div>

    <div class="aum-footer">
        <button type="button" class="um-btn um-btn-ghost" id="eum-cancel">Cancel</button>
        <button type="submit" class="um-btn um-btn-primary" id="eum-submit">
            <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" width="15" height="15">
                <path d="M10 4V16M4 10H16" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
            </svg>
            Update user
        </button>
    </div>
    <?= $this->Form->end() ?>

</div>