<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\ElasticsearchService;
use App\Service\MongoService;
use Cake\Http\Response;

class HomeController extends AppController
{
    public function index(): ?Response
    {
        $query = trim((string)$this->request->getQuery('q', ''));
        $this->set($this->buildHomeViewData($query));

        return $this->render('/Pages/home');
    }

    public function search(): ?Response
    {
        $query = trim((string)$this->request->getQuery('q', ''));
        $viewData = $this->buildHomeViewData($query);
        $isAjax = (string)$this->request->getQuery('ajax', '') === '1';

        if ($isAjax) {
            $payload = [
                'query' => $viewData['searchQuery'],
                'indexName' => $viewData['searchIndexName'],
                'results' => $viewData['searchResults'],
                'error' => $viewData['searchError'],
                'errorCode' => $viewData['searchErrorCode'],
            ];

            return $this->response
                ->withType('application/json')
                ->withStringBody((string)json_encode($payload));
        }

        $this->set($viewData);

        return $this->render('/Pages/home');
    }

    public function mongoData(): Response
    {
        $payload = [
            'collectionName' => null,
            'documents' => [],
            'error' => null,
        ];

        try {
            $mongo = new MongoService();
            $payload['collectionName'] = $mongo->configuredCollectionName();
            $payload['documents'] = $mongo->getConfiguredCollectionDocuments();
        } catch (\Throwable $exception) {
            $payload['error'] = $exception->getMessage();
        }

        return $this->response
            ->withType('application/json')
            ->withStringBody((string)json_encode($payload));
    }

    private function buildHomeViewData(string $query): array
    {
        $mongoResult = [
            'connected' => false,
            'error' => null,
            'database' => null,
            'collectionName' => null,
            'documents' => [],
        ];

        try {
            $mongo = new MongoService();
            $mongoResult['connected'] = true;
            $mongoResult['collectionName'] = $mongo->configuredCollectionName();
            $mongoResult['documents'] = $mongo->getConfiguredCollectionDocuments();
        } catch (\Throwable $exception) {
            $mongoResult['error'] = $exception->getMessage();
        }

        $searchResults = [];
        $searchError = null;
        $searchErrorCode = null;
        $searchIndexName = null;

        if ($query !== '') {
            try {
                $es = new ElasticsearchService();
                $searchIndexName = $es->configuredIndexName();
                $searchResults = $es->search($query);
            } catch (\Throwable $exception) {
                $exceptionCode = (int)$exception->getCode();
                if ($exceptionCode === 404) {
                    $searchErrorCode = 404;
                    $searchError = 'Query error 404: Elasticsearch index or resource was not found.';
                } else {
                    $searchErrorCode = $exceptionCode > 0 ? $exceptionCode : null;
                    $searchError = $exception->getMessage();
                }
            }
        }

        return [
            'mongoResult' => $mongoResult,
            'searchQuery' => $query,
            'searchResults' => $searchResults,
            'searchError' => $searchError,
            'searchErrorCode' => $searchErrorCode,
            'searchIndexName' => $searchIndexName,
        ];
    }
}
