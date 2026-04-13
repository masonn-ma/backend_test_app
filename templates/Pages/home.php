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
?>

<div class="um-wrap">

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
            <button class="um-btn um-btn-ghost">
                <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" width="15" height="15">
                    <path d="M3 10H17M3 5H17M3 15H11" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                </svg>
                Export
            </button>
            <button class="um-btn um-btn-primary">
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
                        $firstName  = trim((string)($doc['firstName'] ?? ''));
                        $lastName   = trim((string)($doc['lastName'] ?? ''));
                        $fullName   = trim("$firstName $lastName") ?: ($doc['username'] ?? 'N/A');
                        $initials   = strtoupper(
                            substr($firstName, 0, 1) . substr($lastName ?: ($doc['username'] ?? '?'), 0, 1)
                        );
                        $email      = $doc['email'] ?? 'N/A';
                        $username   = $doc['username'] ?? 'N/A';
                        $status     = strtolower($doc['status'] ?? 'pending');
                        // FIX: roles is an array — guard against non-array values
                        $role       = is_array($doc['roles'] ?? null) ? ($doc['roles'][0] ?? 'guest') : 'guest';
                        // FIX: guard against missing/null createdAt
                        $joinedDate = isset($doc['createdAt'])
                            ? $doc['createdAt']->toDateTime()->format('M j, Y')
                            : '—';
                        ?>
                        <tr>
                            <td class="col-check">
                                <input type="checkbox" class="um-checkbox row-checkbox">
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
                                    <button class="um-icon-btn" title="Edit user" aria-label="Edit <?= h($fullName) ?>">
                                        <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" width="15" height="15">
                                            <path d="M14.5 2.5a2.121 2.121 0 0 1 3 3L6 17H3v-3L14.5 2.5z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" />
                                        </svg>
                                    </button>
                                    <button class="um-icon-btn um-icon-btn-danger" title="Delete user" aria-label="Delete <?= h($fullName) ?>">
                                        <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" width="15" height="15">
                                            <path d="M3 5h14M8 5V3h4v2M6 5l1 12h6l1-12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </button>
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
            Rows per page:
            <select class="um-select um-select-sm">
                <option <?= $perPage === 10 ? 'selected' : '' ?>>10</option>
                <option <?= $perPage === 25 ? 'selected' : '' ?>>25</option>
                <option <?= $perPage === 50 ? 'selected' : '' ?>>50</option>
            </select>
            <span class="um-muted">
                <?= (($currentPage - 1) * $perPage) + 1 ?>–<?= min($currentPage * $perPage, $totalRows) ?> of <?= $totalRows ?>
            </span>
        </div>
        <nav class="um-pagination" aria-label="Page navigation">
            <a class="um-page-btn <?= $currentPage <= 1 ? 'disabled' : '' ?>" href="?page=1" aria-label="First page">«</a>
            <a class="um-page-btn <?= $currentPage <= 1 ? 'disabled' : '' ?>" href="?page=<?= max(1, $currentPage - 1) ?>" aria-label="Previous page">‹</a>

            <?php
            $window = 2;
            $start = max(1, $currentPage - $window);
            $end = min($totalPages, $currentPage + $window);
            if ($start > 1): ?>
                <a class="um-page-btn" href="?page=1">1</a>
                <?php if ($start > 2): ?><span class="um-page-ellipsis">…</span><?php endif; ?>
            <?php endif; ?>

            <?php for ($p = $start; $p <= $end; $p++): ?>
                <a class="um-page-btn <?= $p === $currentPage ? 'active' : '' ?>" href="?page=<?= $p ?>"><?= $p ?></a>
            <?php endfor; ?>

            <?php if ($end < $totalPages): ?>
                <?php if ($end < $totalPages - 1): ?><span class="um-page-ellipsis">…</span><?php endif; ?>
                <a class="um-page-btn" href="?page=<?= $totalPages ?>"><?= $totalPages ?></a>
            <?php endif; ?>

            <a class="um-page-btn <?= $currentPage >= $totalPages ? 'disabled' : '' ?>" href="?page=<?= min($totalPages, $currentPage + 1) ?>" aria-label="Next page">›</a>
            <a class="um-page-btn <?= $currentPage >= $totalPages ? 'disabled' : '' ?>" href="?page=<?= $totalPages ?>" aria-label="Last page">»</a>
        </nav>
    </div>
</div>