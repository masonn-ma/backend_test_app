<?php

declare(strict_types=1);

namespace App\Controller\Component;

use App\Service\AuditService;
use Cake\Controller\Component;

/**
 * Audit Component
 *
 * Provides convenient audit logging for controller actions.
 */
class AuditComponent extends Component
{
    private AuditService $auditService;

    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->auditService = new AuditService();
    }

    /**
     * Log a view action.
     *
     * @param mixed $entityId The entity ID
     * @return string|null The MongoDB document ID or null on failure
     */
    public function logView(mixed $entityId): ?string
    {
        return $this->auditService->logView($entityId);
    }

    /**
     * Log a create action.
     *
     * @param mixed $entityId The entity ID
     * @param array<string, mixed> $data The entity data
     * @return string|null The MongoDB document ID or null on failure
     */
    public function logCreate(mixed $entityId, array $data = []): ?string
    {
        return $this->auditService->logCreate($entityId, $data);
    }

    /**
     * Log an update action.
     *
     * @param mixed $entityId The entity ID
     * @param array<string, mixed> $data The entity data
     * @return string|null The MongoDB document ID or null on failure
     */
    public function logUpdate(mixed $entityId, array $data = []): ?string
    {
        return $this->auditService->logUpdate($entityId, $data);
    }

    /**
     * Log a delete action.
     *
     * @param mixed $entityId The entity ID
     * @return string|null The MongoDB document ID or null on failure
     */
    public function logDelete(mixed $entityId): ?string
    {
        return $this->auditService->logDelete($entityId);
    }
}
