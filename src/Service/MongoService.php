<?php

namespace App\Service;

use MongoDB\Client;
use Cake\Core\Configure;

class MongoService
{
    private $client;
    private $db;
    private string $collectionName;

    public function __construct()
    {
        $config = Configure::read('MongoDB');

        $this->client = new Client($config['dsn']);
        $this->db = $this->client->{$config['database']};
        $this->collectionName = $config['collection'] ?? 'logs';
    }

    public function collection(?string $name = null)
    {
        $name ??= $this->collectionName;

        return $this->db->{$name};
    }

    public function configuredCollectionName(): string
    {
        return $this->collectionName;
    }

    public function getConfiguredCollectionDocuments(): array
    {
        $collection = $this->collection();
        $filter = $this->buildReadFilter($this->collectionName);

        return $collection->find($filter)->toArray();
    }

    /**
     * Fetch paginated documents from the configured collection.
     *
     * @return array{documents: array<mixed>, totalCount: int, page: int, perPage: int}
     */
    public function getConfiguredCollectionPage(int $page, int $perPage): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $skip = ($page - 1) * $perPage;
        $collection = $this->collection();
        $filter = $this->buildReadFilter($this->collectionName);

        $documents = $collection
            ->find($filter, [
                'sort' => ['createdAt' => -1, '_id' => -1],
                'skip' => $skip,
                'limit' => $perPage,
            ])
            ->toArray();

        $totalCount = (int)$collection->countDocuments($filter);

        return [
            'documents' => $documents,
            'totalCount' => $totalCount,
            'page' => $page,
            'perPage' => $perPage,
        ];
    }

    /* ---------------- USER ACTIONS ---------------- */
    public function newUser(array $userData): string
    {
        $result = $this->collection('users')->insertOne($userData);
        return (string)$result->getInsertedId();
    }

    public function deleteUsers(array $userIds): bool
    {
        // Soft delete: set isActive to false instead of removing the document
        $result = $this->collection('users')->updateMany(
            ['_id' => ['$in' => array_map(static fn($id) => new \MongoDB\BSON\ObjectId($id), $userIds)]],
            ['$set' => ['isActive' => false]]
        );
        return $result->getModifiedCount() > 0;
    }

    /**
     * Get the audit collection (logs/activities).
     *
     * @return \MongoDB\Collection
     */
    public function auditCollection()
    {
        return $this->collection('activity_logs');
    }

    /**
     * Get all audit logs.
     *
     * @return array<mixed>
     */
    public function getAuditLogs(): array
    {
        return $this->auditCollection()
            ->find([], ['sort' => ['timestamp' => -1]])
            ->toArray();
    }

    /**
     * Build a read filter for collection queries.
     *
     * Only the users collection is filtered by isActive so other collections,
     * such as activity logs, keep their full result sets.
     *
     * @return array<string, mixed>
     */
    private function buildReadFilter(string $collectionName): array
    {
        if ($collectionName !== 'users') {
            return [];
        }

        return [
            '$or' => [
                ['isActive' => ['$exists' => false]],
                ['isActive' => ['$ne' => false]],
            ],
        ];
    }
}
