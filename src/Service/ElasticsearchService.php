<?php

declare(strict_types=1);

namespace App\Service;

use Cake\Core\Configure;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
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
            'body' => $document,
        ];

        if ($id !== null && $id !== '') {
            $params['id'] = $id;
        }

        $this->client->index($params);
    }

    public function search(string $query): array
    {
        if ($query === '') {
            return [];
        }

        $params = [
            'index' => $this->indexName,
            'body' => [
                'query' => [
                    'simple_query_string' => [
                        'query' => $query,
                        'default_operator' => 'and',
                    ],
                ],
                'size' => 25,
            ],
        ];

        $response = $this->client->search($params)->asArray();
        $hits = $response['hits']['hits'] ?? [];

        return is_array($hits) ? $hits : [];
    }
}
