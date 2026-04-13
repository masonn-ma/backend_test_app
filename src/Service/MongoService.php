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
        return $this->collection()->find()->toArray();
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
}
