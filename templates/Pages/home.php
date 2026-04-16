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

// Normalize date values from MongoDB BSON objects or Elasticsearch string payloads.
$normalizeTimestamp = static function (mixed $value): ?int {
    if ($value instanceof \MongoDB\BSON\UTCDateTime) {
        return $value->toDateTime()->getTimestamp();
    }

    if ($value instanceof \DateTimeInterface) {
        return $value->getTimestamp();
    }

    if (is_int($value)) {
        return $value;
    }

    if (is_string($value) && $value !== '') {
        $parsed = strtotime($value);

        return $parsed !== false ? $parsed : null;
    }

    return null;
};

// FIX: Proper "time ago" helper instead of hardcoded format string
$timeAgo = static function (mixed $value) use ($normalizeTimestamp): string {
    $timestamp = $normalizeTimestamp($value);

    if ($timestamp === null) {
        return '<span class="text-muted">—</span>';
    }

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
    }

    return date('M j, Y', $timestamp);
};

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
$queryParamsBase['activeOnly'] = !empty($activeOnly) ? '1' : '0';
if (!empty($sortBy)) {
    $queryParamsBase['sortBy'] = $sortBy;
}
if (!empty($sortDir)) {
    $queryParamsBase['sortDir'] = $sortDir;
}
if (!empty($roleFilter)) {
    $queryParamsBase['role'] = $roleFilter;
}
if (!empty($statusFilter)) {
    $queryParamsBase['status'] = $statusFilter;
}
$pageUrl = static function (int $pageNumber) use ($queryParamsBase): string {
    return '?' . http_build_query(array_merge($queryParamsBase, ['page' => $pageNumber]));
};

$sortUrl = static function (string $column) use ($queryParamsBase, $sortBy, $sortDir): string {
    $nextDirection = ($sortBy === $column && $sortDir === 'asc') ? 'desc' : 'asc';

    return '?' . http_build_query(array_merge($queryParamsBase, [
        'page' => 1,
        'sortBy' => $column,
        'sortDir' => $nextDirection,
    ]));
};

$sortIcon = static function (string $column) use ($sortBy, $sortDir): string {
    if ($sortBy !== $column) {
        return '↕';
    }

    return $sortDir === 'asc' ? '↑' : '↓';
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
                <form method="get" action="<?= $this->Url->build(['controller' => 'Home', 'action' => 'search']) ?>" class="um-search-form">
                    <svg class="um-search-icon" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="9" cy="9" r="6" stroke="currentColor" stroke-width="1.5" />
                        <path d="M13.5 13.5L17 17" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                    </svg>
                    <input type="hidden" name="page" value="1">
                    <input type="hidden" name="perPage" value="<?= h((string)$perPage) ?>">
                    <input type="hidden" name="activeOnly" value="<?= h((string)($activeOnly ? 1 : 0)) ?>">
                    <input type="hidden" name="role" value="<?= h($roleFilter ?? '') ?>">
                    <input type="hidden" name="status" value="<?= h($statusFilter ?? '') ?>">
                    <input type="hidden" name="sortBy" value="<?= h($sortBy ?? '') ?>">
                    <input type="hidden" name="sortDir" value="<?= h($sortDir ?? '') ?>">
                    <input type="text" name="q" value="<?= h($searchQuery ?? '') ?>" class="um-input um-search" placeholder="Search users…" aria-label="Search users">
                </form>
            </div>

            <form method="get" action="" class="um-filter-form">
                <input type="hidden" name="page" value="1">
                <input type="hidden" name="perPage" value="<?= h((string)$perPage) ?>">
                <input type="hidden" name="activeOnly" value="<?= $activeOnly ? '1' : '0' ?>">
                <input type="hidden" name="sortBy" value="<?= h($sortBy ?? '') ?>">
                <input type="hidden" name="sortDir" value="<?= h($sortDir ?? '') ?>">
                <?php if (!empty($searchQuery)): ?>
                    <input type="hidden" name="q" value="<?= h($searchQuery) ?>">
                <?php endif; ?>
                <select class="um-select" name="role" onchange="this.form.submit()">
                    <option value="">All roles</option>
                    <option value="admin" <?= ($roleFilter ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                    <option value="moderator" <?= ($roleFilter ?? '') === 'moderator' ? 'selected' : '' ?>>Moderator</option>
                    <option value="user" <?= ($roleFilter ?? '') === 'user' ? 'selected' : '' ?>>User</option>
                    <option value="guest" <?= ($roleFilter ?? '') === 'guest' ? 'selected' : '' ?>>Guest</option>
                </select>
                <select class="um-select" name="status" onchange="this.form.submit()">
                    <option value="">All statuses</option>
                    <option value="active" <?= ($statusFilter ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="pending" <?= ($statusFilter ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="disabled" <?= ($statusFilter ?? '') === 'disabled' ? 'selected' : '' ?>>Disabled</option>
                    <option value="blocked" <?= ($statusFilter ?? '') === 'blocked' ? 'selected' : '' ?>>Blocked</option>
                </select>
            </form>

            <form method="get" action="" class="um-toggle-form">
                <input type="hidden" name="page" value="1">
                <input type="hidden" name="perPage" value="<?= h((string)$perPage) ?>">
                <input type="hidden" name="activeOnly" value="<?= $activeOnly ? '0' : '1' ?>">
                <input type="hidden" name="role" value="<?= h($roleFilter ?? '') ?>">
                <input type="hidden" name="status" value="<?= h($statusFilter ?? '') ?>">
                <input type="hidden" name="sortBy" value="<?= h($sortBy ?? '') ?>">
                <input type="hidden" name="sortDir" value="<?= h($sortDir ?? '') ?>">
                <?php if (!empty($searchQuery)): ?>
                    <input type="hidden" name="q" value="<?= h($searchQuery) ?>">
                <?php endif; ?>
                <button type="submit" class="um-btn um-btn-ghost um-btn-toggle <?= $activeOnly ? 'is-active' : '' ?>">
                    <?= $activeOnly ? 'Active only' : 'All users' ?>
                </button>
            </form>
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
                    <th class="col-name"><a class="um-sort-link" href="<?= $sortUrl('name') ?>">Name <span class="um-sort-icon" aria-hidden="true"><?= h($sortIcon('name')) ?></span></a></th>
                    <th class="col-email"><a class="um-sort-link" href="<?= $sortUrl('email') ?>">Email <span class="um-sort-icon" aria-hidden="true"><?= h($sortIcon('email')) ?></span></a></th>
                    <th class="col-username"><a class="um-sort-link" href="<?= $sortUrl('username') ?>">Username <span class="um-sort-icon" aria-hidden="true"><?= h($sortIcon('username')) ?></span></a></th>
                    <th><a class="um-sort-link" href="<?= $sortUrl('status') ?>">Status <span class="um-sort-icon" aria-hidden="true"><?= h($sortIcon('status')) ?></span></a></th>
                    <th><a class="um-sort-link" href="<?= $sortUrl('role') ?>">Role <span class="um-sort-icon" aria-hidden="true"><?= h($sortIcon('role')) ?></span></a></th>
                    <th><a class="um-sort-link" href="<?= $sortUrl('joined') ?>">Joined <span class="um-sort-icon" aria-hidden="true"><?= h($sortIcon('joined')) ?></span></a></th>
                    <th><a class="um-sort-link" href="<?= $sortUrl('lastActive') ?>">Last active <span class="um-sort-icon" aria-hidden="true"><?= h($sortIcon('lastActive')) ?></span></a></th>
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
                        $joinedTimestamp = $normalizeTimestamp($doc['createdAt'] ?? null);
                        $joinedDate = $joinedTimestamp !== null ? date('M j, Y', $joinedTimestamp) : '—';
                        ?>
                        <tr>
                            <td class="col-check">
                                <input type="checkbox" class="um-checkbox row-checkbox" value="<?= h($userId) ?>">
                            </td>
                            <td class="col-name">
                                <div class="um-user-cell">
                                    <div class="um-avatar" data-initial="<?= h($initials) ?>">
                                        <?= h($initials) ?>
                                    </div>
                                    <span class="um-username um-truncate" title="<?= h($fullName) ?>"><?= h($fullName) ?></span>
                                </div>
                            </td>
                            <td class="um-muted col-email"><span class="um-truncate" title="<?= h((string)$email) ?>"><?= h($email) ?></span></td>
                            <td class="um-code col-username"><span class="um-truncate" title="@<?= h((string)$username) ?>">@<?= h($username) ?></span></td>
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

    <?php if (empty($searchQuery)): ?>
        <!-- Pagination -->
        <div class="um-footer">
            <div class="um-rows-info">
                <form method="get" action="" class="um-rows-form">
                    <input type="hidden" name="page" value="1">
                    <input type="hidden" name="activeOnly" value="<?= $activeOnly ? '1' : '0' ?>">
                    <input type="hidden" name="role" value="<?= h($roleFilter ?? '') ?>">
                    <input type="hidden" name="status" value="<?= h($statusFilter ?? '') ?>">
                    <input type="hidden" name="sortBy" value="<?= h($sortBy ?? '') ?>">
                    <input type="hidden" name="sortDir" value="<?= h($sortDir ?? '') ?>">
                    <?php if (!empty($searchQuery)): ?>
                        <input type="hidden" name="q" value="<?= h($searchQuery) ?>">
                    <?php endif; ?>
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
    <?php endif; ?>
</div>