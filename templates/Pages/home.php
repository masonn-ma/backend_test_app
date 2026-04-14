<?php

declare(strict_types=1);

/**
 * @var \App\View\AppView $this
 * @var array $mongoResult
 */

$this->assign('title', 'User Management');

// Helper function to get status badge
// FIX: Use h() on the label, and avoid raw HTML injection from untrusted $status values
$getStatusBadge = function (?string $status): string {
    $colors = [
        'active'   => 'badge-success',
        'pending'  => 'badge-pending',
        'disabled' => 'badge-secondary',
        'blocked'  => 'badge-danger',
    ];
    $badgeClass = $colors[$status] ?? 'badge-secondary';
    $label = h(ucfirst($status ?? 'unknown'));
    return sprintf('<span class="badge %s">%s</span>', $badgeClass, $label);
};

// Helper to get role badge color
// FIX: Same — escape label output
$getRoleBadge = function (?string $role): string {
    $roleClass = match ($role) {
        'admin'     => 'badge-admin',
        'moderator' => 'badge-moderator',
        'user'      => 'badge-user',
        default     => 'badge-secondary',
    };
    $label = h(ucfirst($role ?? 'guest'));
    return sprintf('<span class="badge %s">%s</span>', $roleClass, $label);
};

// FIX: Proper "time ago" helper instead of hardcoded format string
$timeAgo = function (?\MongoDB\BSON\UTCDateTime $dt): string {
    if ($dt === null) {
        return '<span class="text-muted">—</span>';
    }
    $timestamp = $dt->toDateTime()->getTimestamp();
    $diff = time() - $timestamp;

    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $mins = (int)floor($diff / 60);
        return $mins . 'm ago';
    } elseif ($diff < 86400) {
        $hrs = (int)floor($diff / 3600);
        return $hrs . 'h ago';
    } elseif ($diff < 604800) {
        $days = (int)floor($diff / 86400);
        return $days . 'd ago';
    } else {
        return $dt->toDateTime()->format('M j, Y');
    }
};

// FIX: Use paginator metadata for total if available, fall back to document count
$totalRows = $mongoResult['totalCount'] ?? count($mongoResult['documents'] ?? []);
$currentPage = $mongoResult['page'] ?? 1;
$perPage = $mongoResult['perPage'] ?? 10;
$totalPages = $perPage > 0 ? (int)ceil($totalRows / $perPage) : 1;
$startRow = $totalRows > 0 ? (($currentPage - 1) * $perPage) + 1 : 0;
$endRow = $totalRows > 0 ? min($currentPage * $perPage, $totalRows) : 0;
$queryParamsBase = ['perPage' => $perPage];
if (!empty($searchQuery)) {
    $queryParamsBase['q'] = $searchQuery;
}
$pageUrl = static function (int $pageNumber) use ($queryParamsBase): string {
    return '?' . http_build_query($queryParamsBase + ['page' => $pageNumber]);
};
?>

<div class="um-wrap">
    <?= $this->Form->create(null, [
        'url' => ['controller' => 'Home', 'action' => 'deleteUsers'],
        'id' => 'bulk-delete-form',
        'class' => 'um-delete-form',
    ]) ?>
    <div id="bulk-delete-inputs"></div>
    <?= $this->Form->end() ?>

    <div class="um-confirm-backdrop hidden" id="delete-confirm-backdrop" aria-hidden="true"></div>
    <div class="um-confirm-modal hidden" id="delete-confirm-modal" role="dialog" aria-modal="true" aria-labelledby="delete-confirm-title">
        <div class="um-confirm-icon" aria-hidden="true">
            <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" width="18" height="18">
                <path d="M3 5h14M8 5V3h4v2M6 5l1 12h6l1-12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </div>
        <h2 id="delete-confirm-title">Confirm deletion</h2>
        <p id="delete-confirm-message">This action will delete the selected user(s). This cannot be undone.</p>
        <div class="um-confirm-actions">
            <button type="button" class="um-btn um-btn-ghost" id="delete-confirm-cancel">Cancel</button>
            <button type="button" class="um-btn um-btn-danger" id="delete-confirm-approve">Delete</button>
        </div>
    </div>

    <!-- Filters & Actions Bar -->
    <div class="um-toolbar">
        <div class="um-filters">
            <div class="um-search-wrapper">
                <svg class="um-search-icon" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="9" cy="9" r="6" stroke="currentColor" stroke-width="1.5" />
                    <path d="M13.5 13.5L17 17" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                </svg>
                <input type="text" class="um-input um-search" placeholder="Search users…">
            </div>
            <select class="um-select">
                <option value="">All roles</option>
                <option>Admin</option>
                <option>Moderator</option>
                <option>User</option>
                <option>Guest</option>
            </select>
            <select class="um-select">
                <option value="">All statuses</option>
                <option>Active</option>
                <option>Pending</option>
                <option>Disabled</option>
                <option>Blocked</option>
            </select>
            <select class="um-select">
                <option value="">Any time</option>
                <option>Last 7 days</option>
                <option>Last 30 days</option>
                <option>Last 90 days</option>
            </select>
        </div>
        <div class="um-actions">
            <button type="button" class="um-btn um-btn-danger" id="bulk-delete-button" disabled>
                <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" width="15" height="15">
                    <path d="M3 5h14M8 5V3h4v2M6 5l1 12h6l1-12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                Delete
            </button>
            <button type="button" class="um-btn um-btn-primary" id="open-add-user">
                <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" width="15" height="15">
                    <path d="M10 4V16M4 10H16" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                </svg>
                Add user
            </button>
        </div>
    </div>

    <!-- Table -->
    <div class="um-table-container">
        <table class="um-table">
            <thead>
                <tr>
                    <th class="col-check">
                        <input type="checkbox" class="um-checkbox select-all">
                    </th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Username</th>
                    <th>Status</th>
                    <th>Role</th>
                    <th>Joined</th>
                    <th>Last active</th>
                    <th class="col-actions">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($mongoResult['documents'])): ?>
                    <tr>
                        <td colspan="9" class="um-empty">
                            <div class="um-empty-inner">
                                <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" width="40" height="40">
                                    <circle cx="24" cy="24" r="20" stroke="currentColor" stroke-width="1.5" opacity=".3" />
                                    <path d="M24 16v8M24 28v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                </svg>
                                <p>No users found</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($mongoResult['documents'] as $doc): ?>
                        <?php
                        $userId = isset($doc['_id']) ? (string)$doc['_id'] : '';
                        $firstName  = trim((string)($doc['firstName'] ?? ''));
                        $lastName   = trim((string)($doc['lastName'] ?? ''));
                        $fullName   = trim("$firstName $lastName") ?: ($doc['username'] ?? 'N/A');
                        $initials   = strtoupper(
                            substr($firstName, 0, 1) . substr($lastName ?: ($doc['username'] ?? '?'), 0, 1)
                        );
                        $email      = $doc['email'] ?? 'N/A';
                        $username   = $doc['username'] ?? 'N/A';
                        $status     = strtolower($doc['status'] ?? 'pending');
                        $role = strtolower((string)($doc['role'] ?? 'guest'));
                        $joinedDate = isset($doc['createdAt'])
                            ? $doc['createdAt']->toDateTime()->format('M j, Y')
                            : '—';
                        ?>
                        <tr>
                            <td class="col-check">
                                <input type="checkbox" class="um-checkbox row-checkbox" value="<?= h($userId) ?>">
                            </td>
                            <td>
                                <div class="um-user-cell">
                                    <div class="um-avatar" data-initial="<?= h($initials) ?>">
                                        <?= h($initials) ?>
                                    </div>
                                    <span class="um-username"><?= h($fullName) ?></span>
                                </div>
                            </td>
                            <td class="um-muted"><?= h($email) ?></td>
                            <td class="um-code">@<?= h($username) ?></td>
                            <td><?= $getStatusBadge($status) ?></td>
                            <td><?= $getRoleBadge($role) ?></td>
                            <td class="um-muted"><?= h($joinedDate) ?></td>
                            <td class="um-muted"><?= $timeAgo($doc['lastLogin'] ?? null) ?></td>
                            <td class="col-actions">
                                <div class="um-row-actions">
                                    <button
                                        type="button"
                                        class="um-icon-btn um-icon-btn-edit"
                                        title="Edit user"
                                        aria-label="Edit <?= h($fullName) ?>"
                                        data-original-username="<?= h($username) ?>"
                                        data-first-name="<?= h($firstName) ?>"
                                        data-last-name="<?= h($lastName) ?>"
                                        data-email="<?= h($email) ?>"
                                        data-username="<?= h($username) ?>"
                                        data-role="<?= h($role) ?>"
                                        data-status="<?= h($status) ?>">
                                        <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" width="15" height="15">
                                            <path d="M14.5 2.5a2.121 2.121 0 0 1 3 3L6 17H3v-3L14.5 2.5z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" />
                                        </svg>
                                    </button>
                                    <?= $this->Form->create(null, [
                                        'url' => ['controller' => 'Home', 'action' => 'deleteUsers'],
                                        'class' => 'um-inline-form',
                                        'data-delete-form' => 'single',
                                    ]) ?>
                                    <input type="hidden" name="userIds[]" value="<?= h($userId) ?>">
                                    <button type="button" class="um-icon-btn um-icon-btn-danger js-delete-trigger" title="Delete user" aria-label="Delete <?= h($fullName) ?>">
                                        <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" width="15" height="15">
                                            <path d="M3 5h14M8 5V3h4v2M6 5l1 12h6l1-12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </button>
                                    <?= $this->Form->end() ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <!-- FIX: Use real pagination data instead of hardcoded values -->
    <div class="um-footer">
        <div class="um-rows-info">
            <form method="get" action="" class="um-rows-form">
                <?php if (!empty($searchQuery)): ?>
                    <input type="hidden" name="q" value="<?= h($searchQuery) ?>">
                <?php endif; ?>
                <input type="hidden" name="page" value="1">
                Rows per page:
                <select class="um-select um-select-sm" name="perPage" onchange="this.form.submit()">
                    <option value="10" <?= $perPage === 10 ? 'selected' : '' ?>>10</option>
                    <option value="25" <?= $perPage === 25 ? 'selected' : '' ?>>25</option>
                    <option value="50" <?= $perPage === 50 ? 'selected' : '' ?>>50</option>
                </select>
            </form>
            <span class="um-muted">
                <?= $startRow ?>–<?= $endRow ?> of <?= $totalRows ?>
            </span>
        </div>
        <nav class="um-pagination" aria-label="Page navigation">
            <a class="um-page-btn <?= $currentPage <= 1 ? 'disabled' : '' ?>" href="<?= $pageUrl(1) ?>" aria-label="First page">«</a>
            <a class="um-page-btn <?= $currentPage <= 1 ? 'disabled' : '' ?>" href="<?= $pageUrl(max(1, $currentPage - 1)) ?>" aria-label="Previous page">‹</a>

            <?php
            $window = 2;
            $start = max(1, $currentPage - $window);
            $end = min($totalPages, $currentPage + $window);
            if ($start > 1): ?>
                <a class="um-page-btn" href="<?= $pageUrl(1) ?>">1</a>
                <?php if ($start > 2): ?><span class="um-page-ellipsis">…</span><?php endif; ?>
            <?php endif; ?>

            <?php for ($p = $start; $p <= $end; $p++): ?>
                <a class="um-page-btn <?= $p === $currentPage ? 'active' : '' ?>" href="<?= $pageUrl($p) ?>"><?= $p ?></a>
            <?php endfor; ?>

            <?php if ($end < $totalPages): ?>
                <?php if ($end < $totalPages - 1): ?><span class="um-page-ellipsis">…</span><?php endif; ?>
                <a class="um-page-btn" href="<?= $pageUrl($totalPages) ?>"><?= $totalPages ?></a>
            <?php endif; ?>

            <a class="um-page-btn <?= $currentPage >= $totalPages ? 'disabled' : '' ?>" href="<?= $pageUrl(min($totalPages, $currentPage + 1)) ?>" aria-label="Next page">›</a>
            <a class="um-page-btn <?= $currentPage >= $totalPages ? 'disabled' : '' ?>" href="<?= $pageUrl($totalPages) ?>" aria-label="Last page">»</a>
        </nav>
    </div>
</div>