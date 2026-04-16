<?php

declare(strict_types=1);

namespace App\Service;

use Cake\Core\Configure;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use RuntimeException;

class ElasticsearchService
{
    private Client $client;
    private string $indexName;

    public function __construct()
    {
        $config = Configure::read('Elasticsearch');

        if (!is_array($config) || empty($config['host']) || empty($config['index'])) {
            throw new RuntimeException('Elasticsearch config is missing. Set Elasticsearch.host and Elasticsearch.index in app_local.php');
        }

        $this->client = ClientBuilder::create()
            ->setHosts([$config['host']])
            ->build();

        $this->indexName = (string)$config['index'];
    }

    public function configuredIndexName(): string
    {
        return $this->indexName;
    }


    public function indexDocument(array $document, ?string $id = null): void
    {
        $params = [
            'index' => $this->indexName,
            'body' => $this->normalizeDocument($document),
        ];

        if ($id !== null && $id !== '') {
            $params['id'] = $id;
        }

        $this->client->index($params);
    }

    public function deleteDocument(string $id): void
    {
        if ($id === '') {
            return;
        }

        try {
            $this->client->delete([
                'index' => $this->indexName,
                'id' => $id,
            ]);
        } catch (\Throwable $exception) {
            // Ignore missing documents so delete sync is idempotent.
            if ((int)$exception->getCode() !== 404) {
                throw $exception;
            }
        }
    }

    public function search(string $query, string $role = '', string $status = ''): array
    {
        $query = trim($query);
        $role = strtolower(trim($role));
        $status = strtolower(trim($status));

        if ($query === '') {
            return [];
        }

        $filters = [];

        if ($role !== '') {
            $filters[] = ['term' => ['role' => $role]];
        }

        if ($status !== '') {
            $filters[] = ['term' => ['status' => $status]];
        }

        $params = [
            'index' => $this->indexName,
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [
                            [
                                'simple_query_string' => [
                                    'query' => $query,
                                    'default_operator' => 'and',
                                    'fields' => ['*'],
                                ],
                            ],
                        ],
                        'filter' => $filters,
                    ],
                ],
                'size' => 25,
            ],
        ];

        $response = $this->client->search($params)->asArray();
        $hits = $response['hits']['hits'] ?? [];

        return is_array($hits) ? $hits : [];
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private function normalizeValue(mixed $value): mixed
    {
        if ($value instanceof UTCDateTime) {
            return $value->toDateTime()->format(DATE_ATOM);
        }

        if ($value instanceof ObjectId) {
            return (string)$value;
        }

        if (is_array($value)) {
            $normalized = [];
            foreach ($value as $key => $item) {
                $normalized[$key] = $this->normalizeValue($item);
            }

            return $normalized;
        }

        if (is_object($value)) {
            // BSON documents can be converted by reusing json serialization output.
            $decoded = json_decode((string)json_encode($value), true);
            if (is_array($decoded)) {
                return $this->normalizeValue($decoded);
            }
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $document
     * @return array<string, mixed>
     */
    private function normalizeDocument(array $document): array
    {
        $normalized = $this->normalizeValue($document);

        return is_array($normalized) ? $normalized : [];
    }
}
