<div class="aum-backdrop" id="aum-backdrop" aria-hidden="true"></div>

<div class="aum-modal" id="aum-modal" role="dialog" aria-modal="true" aria-labelledby="aum-title">

    <div class="aum-header">
        <div class="aum-header-left">
            <div class="aum-header-icon">
                <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" width="18" height="18">
                    <circle cx="10" cy="7" r="3.5" stroke="currentColor" stroke-width="1.5" />
                    <path d="M3 17c0-3.314 3.134-6 7-6s7 2.686 7 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                    <path d="M14 3v4M12 5h4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                </svg>
            </div>
            <div>
                <h2 id="aum-title">Add new user</h2>
                <p>Fill in the details below to create a new account.</p>
            </div>
        </div>
        <button class="aum-close" id="aum-close" aria-label="Close modal">
            <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" width="18" height="18">
                <path d="M5 5l10 10M15 5L5 15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
            </svg>
        </button>
    </div>

    <?= $this->Form->create(null, ['url' => ['action' => 'addUser'], 'id' => 'aum-form', 'class' => 'aum-form']) ?>

    <div class="aum-section-label">Personal information</div>

    <div class="aum-row">
        <div class="aum-field">
            <label class="aum-label" for="aum-firstName">First name <span class="aum-required">*</span></label>
            <input type="text" id="aum-firstName" name="firstName" class="aum-input" placeholder="e.g. John" required autocomplete="given-name">
        </div>
        <div class="aum-field">
            <label class="aum-label" for="aum-lastName">Last name <span class="aum-required">*</span></label>
            <input type="text" id="aum-lastName" name="lastName" class="aum-input" placeholder="e.g. Smith" required autocomplete="family-name">
        </div>
    </div>

    <div class="aum-field">
        <label class="aum-label" for="aum-email">Email address <span class="aum-required">*</span></label>
        <div class="aum-wrap-icon">
            <input type="email" id="aum-email" name="email" class="aum-input aum-has-icon" placeholder="john@example.com" required autocomplete="email">
            <svg class="aum-icon-left" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" width="15" height="15">
                <rect x="2" y="4" width="16" height="12" rx="2" stroke="currentColor" stroke-width="1.5" />
                <path d="M2 7l8 5 8-5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
            </svg>
        </div>
    </div>

    <div class="aum-section-label">Account details</div>

    <div class="aum-row">
        <div class="aum-field">
            <label class="aum-label" for="aum-username">Username <span class="aum-required">*</span></label>
            <div class="aum-wrap-icon">
                <input type="text" id="aum-username" name="username" class="aum-input aum-has-prefix" placeholder="jonny77" required autocomplete="username" pattern="[a-zA-Z0-9_\-]+" title="Letters, numbers, underscores and hyphens only">
                <span class="aum-icon-left aum-at">@</span>
            </div>
        </div>
        <div class="aum-field">
            <label class="aum-label" for="aum-password">Password <span class="aum-required">*</span></label>
            <div class="aum-wrap-icon">
                <input type="password" id="aum-password" name="password" class="aum-input aum-has-toggle" placeholder="Min. 8 characters" required minlength="8" autocomplete="new-password">
                <button type="button" class="aum-pw-toggle" aria-label="Toggle password visibility" tabindex="-1">
                    <svg class="aum-eye-open" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" width="16" height="16">
                        <path d="M1 10s3.5-6 9-6 9 6 9 6-3.5 6-9 6-9-6-9-6z" stroke="currentColor" stroke-width="1.5" />
                        <circle cx="10" cy="10" r="2.5" stroke="currentColor" stroke-width="1.5" />
                    </svg>
                    <svg class="aum-eye-closed" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" width="16" height="16" style="display:none">
                        <path d="M3 3l14 14M8.5 8.7A2.5 2.5 0 0 0 12.3 12M6 6.3C3.8 7.6 2 10 2 10s3.5 6 8 6c1.5 0 2.9-.4 4-1.1M9 4.1C9.3 4 9.7 4 10 4c4.5 0 8 6 8 6s-.9 1.5-2.3 2.9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div class="aum-section-label">Permissions</div>

    <div class="aum-row">
        <div class="aum-field">
            <label class="aum-label" for="aum-role">Role</label>
            <select id="aum-role" name="role" class="aum-select">
                <option value="user" selected>User</option>
                <option value="moderator">Moderator</option>
                <option value="admin">Admin</option>
                <option value="guest">Guest</option>
            </select>
        </div>
        <div class="aum-field">
            <label class="aum-label" for="aum-status">Status</label>
            <select id="aum-status" name="status" class="aum-select">
                <option value="active" selected>Active</option>
                <option value="pending">Pending</option>
                <option value="disabled">Disabled</option>
                <option value="blocked">Blocked</option>
            </select>
        </div>
    </div>

    <div class="aum-role-preview" id="aum-role-preview">
        <span class="aum-role-preview-label">Preview:</span>
        <span class="badge badge-user" id="aum-role-badge">User</span>
        <span class="badge badge-success" id="aum-status-badge">Active</span>
    </div>

    <div class="aum-avatar-preview">
        <div class="um-avatar aum-avatar-lg" id="aum-avatar-preview" data-initial="?">?</div>
        <div class="aum-avatar-preview-text">
            <span id="aum-preview-name">New User</span>
            <span class="aum-preview-sub" id="aum-preview-username">@username</span>
        </div>
    </div>

    <div class="aum-footer">
        <button type="button" class="um-btn um-btn-ghost" id="aum-cancel">Cancel</button>
        <button type="submit" class="um-btn um-btn-primary" id="aum-submit">
            <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" width="15" height="15">
                <path d="M10 4V16M4 10H16" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
            </svg>
            Create user
        </button>
    </div>

    <?= $this->Form->end() ?>

</div>