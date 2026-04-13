<?php

declare(strict_types=1);

namespace App\Service;

use MongoDB\BSON\UTCDateTime;

/**
 * Audit Service
 *
 * Handles logging and indexing of actions to MongoDB and Elasticsearch.
 */
class AuditService
{
    private MongoService $mongo;
    private ElasticsearchService $elasticsearch;

    public function __construct(
        ?MongoService $mongo = null,
        ?ElasticsearchService $elasticsearch = null
    ) {
        $this->mongo = $mongo ?? new MongoService();
        $this->elasticsearch = $elasticsearch ?? new ElasticsearchService();
    }

    /**
     * Log an action to MongoDB and Elasticsearch.
     *
     * @param string $action The action name (e.g., 'view_test', 'create_test', 'update_test')
     * @param mixed $entityId The entity ID
     * @param array<string, mixed> $metadata Additional metadata to store
     * @return string|null The MongoDB document ID as string or null on failure
     */
    public function logAction(string $action, mixed $entityId, array $metadata = []): ?string
    {
        try {
            $payload = array_merge([
                'action' => $action,
                'entity_id' => $entityId,
                'timestamp' => new UTCDateTime((int)(microtime(true) * 1000)),
            ], $metadata);

            $insertResult = $this->mongo->auditCollection()->insertOne($payload);
            $mongoId = (string)$insertResult->getInsertedId();

            // Index to Elasticsearch (fire and forget on failure)
            try {
                $this->elasticsearch->indexDocument($payload, $mongoId);
            } catch (\Throwable $ex) {
                // Log but don't fail the audit if Elasticsearch is unavailable
                trigger_error(
                    sprintf('Elasticsearch indexing failed: %s', $ex->getMessage()),
                    E_USER_WARNING
                );
            }

            return $mongoId;
        } catch (\Throwable $ex) {
            trigger_error(
                sprintf('Audit logging failed: %s', $ex->getMessage()),
                E_USER_ERROR
            );

            return null;
        }
    }

    /**
     * Log a view action.
     *
     * @param mixed $entityId The entity ID
     * @return string|null The MongoDB document ID or null on failure
     */
    public function logView(mixed $entityId): ?string
    {
        return $this->logAction('view_test', $entityId);
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
        return $this->logAction('create_test', $entityId, ['data' => $data]);
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
        return $this->logAction('update_test', $entityId, ['data' => $data]);
    }

    /**
     * Log a delete action.
     *
     * @param mixed $entityId The entity ID
     * @return string|null The MongoDB document ID or null on failure
     */
    public function logDelete(mixed $entityId): ?string
    {
        return $this->logAction('delete_test', $entityId);
    }
}
