<?php

declare(strict_types=1);

/**
 * @var \App\View\AppView $this
 * @var array $mongoResult
 */

$this->assign('title', 'User Management');
echo $this->Html->css('home', ['block' => true]);

// Helper function to get status badge
$getStatusBadge = function (?string $status): string {
    $colors = [
        'active' => 'badge-success',
        'pending' => 'badge-dark',
        'disabled' => 'badge-secondary',
        'blocked' => 'badge-danger',
    ];
    $badgeClass = $colors[$status] ?? 'badge-secondary';
    return sprintf('<span class="badge %s">%s</span>', $badgeClass, ucfirst($status ?? 'unknown'));
};

// Helper to get role badge color
$getRoleBadge = function (?string $role): string {
    $roleClass = match ($role) {
        'admin' => 'badge-danger',
        'moderator' => 'badge-warning',
        'user' => 'badge-info',
        default => 'badge-secondary',
    };
    return sprintf('<span class="badge %s">%s</span>', $roleClass, ucfirst($role ?? 'guest'));
};
?>

<div class="user-management-wrapper">
    <!-- Header Section -->
    <div class="management-header mb-5">
        <h1>User Management</h1>
        <p class="text-muted">Manage all users in one place. Control access, assign roles, and monitor activity across your platform.</p>
    </div>

    <!-- Filters & Actions Bar -->
    <div class="filters-bar mb-4">
        <div class="search-filters">
            <input type="text" class="form-control search-input" placeholder="Search">
            <select class="form-select filter-select">
                <option>Role</option>
                <option>Admin</option>
                <option>Moderator</option>
                <option>User</option>
                <option>Guest</option>
            </select>
            <select class="form-select filter-select">
                <option>Status</option>
                <option>Active</option>
                <option>Pending</option>
                <option>Disabled</option>
                <option>Blocked</option>
            </select>
            <select class="form-select filter-select">
                <option>Date</option>
                <option>Last 7 days</option>
                <option>Last 30 days</option>
                <option>Last 90 days</option>
            </select>
        </div>
        <div class="action-buttons">
            <button class="btn btn-outline-secondary">Export</button>
            <button class="btn btn-primary">+ Add User</button>
        </div>
    </div>

    <!-- Users Table -->
    <div class="data-container">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th style="width: 40px;">
                        <input type="checkbox" class="form-check-input select-all">
                    </th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Username</th>
                    <th>Status</th>
                    <th>Role</th>
                    <th>Joined Date</th>
                    <th>Last Active</th>
                    <th style="width: 100px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($mongoResult['documents'] as $doc): ?>
                    <?php
                    $fullName = trim((string)(($doc['firstName'] ?? '') . ' ' . ($doc['lastName'] ?? ''))) ?: $doc['username'] ?? 'N/A';
                    $email = $doc['email'] ?? 'N/A';
                    $username = $doc['username'] ?? 'N/A';
                    $status = strtolower($doc['status'] ?? 'pending');
                    $role = $doc['roles'][0] ?? 'guest';
                    $joinedDate = $doc['createdAt'] ? $doc['createdAt']->toDateTime()->format('M d, Y') : 'N/A';
                    $lastLogin = $doc['lastLogin'] ? $doc['lastLogin']->toDateTime()->format('j \m\i\n\u\t\e ago') : 'N/A';
                    ?>
                    <tr>
                        <td>
                            <input type="checkbox" class="form-check-input row-checkbox">
                        </td>
                        <td>
                            <strong><?= h($fullName) ?></strong>
                        </td>
                        <td><?= h($email) ?></td>
                        <td><?= h($username) ?></td>
                        <td><?= $getStatusBadge($status) ?></td>
                        <td><?= $getRoleBadge($role) ?></td>
                        <td><?= $joinedDate ?></td>
                        <td><?= $lastLogin ?></td>
                        <td>
                            <button class="btn btn-sm btn-link" title="Edit">✎</button>
                            <button class="btn btn-sm btn-link text-danger" title="Delete">🗑</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="pagination-footer mt-3">
        <div class="rows-info">
            Rows per page: <select class="form-select d-inline-block" style="width: auto;">
                <option>10</option>
                <option>25</option>
                <option>50</option>
            </select>
            of <?= count($mongoResult['documents'] ?? []) ?> rows
        </div>
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-end">
                <li class="page-item"><a class="page-link" href="#">«</a></li>
                <li class="page-item"><a class="page-link" href="#">Previous</a></li>
                <li class="page-item active"><a class="page-link" href="#">1</a></li>
                <li class="page-item"><a class="page-link" href="#">2</a></li>
                <li class="page-item"><a class="page-link" href="#">3</a></li>
                <li class="page-item"><a class="page-link" href="#">...</a></li>
                <li class="page-item"><a class="page-link" href="#">10</a></li>
                <li class="page-item"><a class="page-link" href="#">Next</a></li>
                <li class="page-item"><a class="page-link" href="#">»</a></li>
            </ul>
        </nav>
    </div>
</div>