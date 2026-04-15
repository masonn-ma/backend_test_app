<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\ElasticsearchService;
use App\Service\MongoService;
use MongoDB\BSON\UTCDateTime;
use Cake\Http\Response;

class HomeController extends AppController
{
    public function index(): ?Response
    {
        $query = trim((string)$this->request->getQuery('q', ''));
        $page = max(1, (int)$this->request->getQuery('page', 1));
        $perPage = $this->normalizePerPage((int)$this->request->getQuery('perPage', 10));
        $this->set($this->buildHomeViewData($query, $page, $perPage));

        return $this->render('/Pages/home');
    }

    // Search action to handle AJAX search requests
    public function search(): ?Response
    {
        $query = trim((string)$this->request->getQuery('q', ''));
        $page = max(1, (int)$this->request->getQuery('page', 1));
        $perPage = $this->normalizePerPage((int)$this->request->getQuery('perPage', 10));
        $viewData = $this->buildHomeViewData($query, $page, $perPage);
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

    // Mongo data action to handle AJAX requests for MongoDB data
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

    // Handle adding a new user
    public function addUser(): Response
    {
        $isAjax = $this->request->is('ajax') || (string)$this->request->getQuery('ajax', '') === '1';
        $payload = [
            'success' => false,
            'error' => null,
        ];
        try {
            $mongo = new MongoService();
            $es = new ElasticsearchService();
            $firstName = trim((string)$this->request->getData('firstName'));
            $lastName = trim((string)$this->request->getData('lastName'));
            $email = trim((string)$this->request->getData('email'));
            $username = trim((string)$this->request->getData('username'));
            $password = (string)$this->request->getData('password');
            $role = strtolower(trim((string)$this->request->getData('role', 'user')));
            $status = strtolower(trim((string)$this->request->getData('status', 'active')));
            $isActive = true;
            $permissionsByRole = [
                'admin' => ['read', 'write', 'moderate', 'delete', 'admin'],
                'moderator' => ['read', 'write', 'moderate'],
                'user' => ['read', 'write'],
                'guest' => ['read'],
            ];
            $permissions = $permissionsByRole[$role] ?? ['read'];
            $now = new UTCDateTime();
            $defaultDob = new UTCDateTime((int)(strtotime('2000-01-01T00:00:00Z') * 1000));

            $newUserData = [
                'username' => $username,
                'email' => $email,
                'passwordHash' => password_hash($password, PASSWORD_DEFAULT),
                'firstName' => $firstName,
                'lastName' => $lastName,
                'fullName' => trim($firstName . ' ' . $lastName),
                'age' => 18,
                'dateOfBirth' => $defaultDob,
                'gender' => 'prefer not to say',
                'phoneNumber' => '+10000000000',
                'address' => [
                    'street' => 'Unknown',
                    'city' => 'Unknown',
                    'state' => 'Unknown',
                    'postalCode' => '00000',
                    'country' => 'Unknown',
                ],
                'profilePicture' => '',
                'bio' => '',
                'role' => $role,
                'status' => $status,
                'permissions' => $permissions,
                'isActive' => $isActive,
                'isEmailVerified' => false,
                'isPhoneVerified' => false,
                'lastLogin' => $now,
                'loginCount' => 0,
                'createdAt' => $now,
                'updatedAt' => $now,
                'socialLogin' => [
                    'google' => null,
                    'facebook' => null,
                    'twitter' => null,
                ],
                'preferences' => [
                    'language' => 'en',
                    'theme' => 'system',
                    'notifications' => [
                        'email' => true,
                        'push' => true,
                        'sms' => false,
                    ],
                ],
                'twoFactorEnabled' => false,
            ];
            $insertedId = $mongo->newUser($newUserData);

            // Keep ES sync as best-effort so Mongo remains source of truth.
            try {
                $es->indexDocument($newUserData, $insertedId);
            } catch (\Throwable $syncException) {
                trigger_error(
                    sprintf('Elasticsearch user sync failed on create: %s', $syncException->getMessage()),
                    E_USER_WARNING
                );
            }

            $payload['success'] = true;
        } catch (\MongoDB\Driver\Exception\BulkWriteException $exception) {
            $writeErrors = $exception->getWriteResult()->getWriteErrors();
            if (!empty($writeErrors)) {
                $payload['error'] = $writeErrors[0]->getMessage();
            } else {
                $payload['error'] = $exception->getMessage();
            }
        } catch (\Throwable $exception) {
            $payload['error'] = $exception->getMessage();
        }

        if ($payload['error'] !== null && str_contains($payload['error'], 'email_1 dup key')) {
            $payload['error'] = 'This email is already registered. Please use a different email.';
        }

        if ($isAjax) {
            return $this->response
                ->withType('application/json')
                ->withStringBody((string)json_encode($payload));
        }

        if ($payload['success']) {
            $this->Flash->success('User created successfully.');
        } else {
            $this->Flash->error($payload['error'] ?? 'Unable to create user. Please try again.');
        }

        return $this->redirect(['action' => 'index']);
    }

    // Handle editing an existing user
    public function editUser(): Response
    {
        $isAjax = $this->request->is('ajax') || (string)$this->request->getQuery('ajax', '') === '1';
        $payload = [
            'success' => false,
            'error' => null,
        ];

        try {
            $mongo = new MongoService();
            $es = new ElasticsearchService();
            $originalUsername = trim((string)$this->request->getData('originalUsername'));
            $firstName = trim((string)$this->request->getData('firstName'));
            $lastName = trim((string)$this->request->getData('lastName'));
            $email = trim((string)$this->request->getData('email'));
            $username = trim((string)$this->request->getData('username'));
            $role = strtolower(trim((string)$this->request->getData('role', 'user')));
            $status = strtolower(trim((string)$this->request->getData('status', 'active')));

            if ($originalUsername === '') {
                throw new \RuntimeException('Missing original username for update.');
            }

            $permissionsByRole = [
                'admin' => ['read', 'write', 'moderate', 'delete', 'admin'],
                'moderator' => ['read', 'write', 'moderate'],
                'user' => ['read', 'write'],
                'guest' => ['read'],
            ];
            $permissions = $permissionsByRole[$role] ?? ['read'];

            $updateResult = $mongo->collection('users')->updateOne(
                ['username' => $originalUsername],
                [
                    '$set' => [
                        'username' => $username,
                        'email' => $email,
                        'firstName' => $firstName,
                        'lastName' => $lastName,
                        'fullName' => trim($firstName . ' ' . $lastName),
                        'role' => $role,
                        'permissions' => $permissions,
                        'status' => $status,
                        'updatedAt' => new UTCDateTime(),
                    ],
                ]
            );

            if ($updateResult->getMatchedCount() === 0) {
                $payload['error'] = 'User not found.';
            } else {
                $updatedUser = $mongo->collection('users')->findOne(
                    ['username' => $username],
                    ['typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array']]
                );

                if (is_array($updatedUser) && isset($updatedUser['_id'])) {
                    $esId = (string)$updatedUser['_id'];
                    unset($updatedUser['_id']);

                    try {
                        $es->indexDocument($updatedUser, $esId);
                    } catch (\Throwable $syncException) {
                        trigger_error(
                            sprintf('Elasticsearch user sync failed on update: %s', $syncException->getMessage()),
                            E_USER_WARNING
                        );
                    }
                }

                $payload['success'] = true;
            }
        } catch (\Throwable $exception) {
            $payload['error'] = $exception->getMessage();
        }

        if ($payload['error'] !== null && str_contains($payload['error'], 'email_1 dup key')) {
            $payload['error'] = 'This email is already registered. Please use a different email.';
        }

        if ($payload['error'] !== null && str_contains($payload['error'], 'username_1 dup key')) {
            $payload['error'] = 'This username is already taken. Please use a different username.';
        }

        if ($isAjax) {
            return $this->response
                ->withType('application/json')
                ->withStringBody((string)json_encode($payload));
        }

        if ($payload['success']) {
            $this->Flash->success('User updated successfully.');
        } else {
            $this->Flash->error($payload['error'] ?? 'Unable to update user. Please try again.');
        }

        return $this->redirect(['action' => 'index']);
    }

    // Handle deleting users (soft delete)
    public function deleteUsers(): Response
    {
        $isAjax = $this->request->is('ajax') || (string)$this->request->getQuery('ajax', '') === '1';
        $payload = [
            'success' => false,
            'error' => null,
        ];

        try {
            $mongo = new MongoService();
            $es = new ElasticsearchService();
            $userIds = $this->request->getData('userIds', []);
            if (!is_array($userIds) || empty($userIds)) {
                throw new \RuntimeException('No user IDs provided for deletion.');
            }

            $deletionSuccess = $mongo->deleteUsers($userIds);
            if ($deletionSuccess) {
                foreach ($userIds as $userId) {
                    try {
                        $es->deleteDocument((string)$userId);
                    } catch (\Throwable $syncException) {
                        trigger_error(
                            sprintf('Elasticsearch user sync failed on delete: %s', $syncException->getMessage()),
                            E_USER_WARNING
                        );
                    }
                }

                $payload['success'] = true;
            } else {
                $payload['error'] = 'No users were deleted. Please check the provided user IDs.';
            }
        } catch (\Throwable $exception) {
            $payload['error'] = $exception->getMessage();
        }

        if ($isAjax) {
            return $this->response
                ->withType('application/json')
                ->withStringBody((string)json_encode($payload));
        }

        if ($payload['success']) {
            $this->Flash->success('Selected users have been deleted successfully.');
        } else {
            $this->Flash->error($payload['error'] ?? 'Unable to delete users. Please try again.');
        }

        return $this->redirect(['action' => 'index']);
    }

    // Helper method to build view data for the home page
    private function buildHomeViewData(string $query, int $page = 1, int $perPage = 10): array
    {
        $mongoResult = [
            'connected' => false,
            'error' => null,
            'database' => null,
            'collectionName' => null,
            'documents' => [],
            'totalCount' => 0,
            'page' => $page,
            'perPage' => $perPage,
        ];

        try {
            $mongo = new MongoService();
            $pageData = $mongo->getConfiguredCollectionPage($page, $perPage);
            $mongoResult['connected'] = true;
            $mongoResult['collectionName'] = $mongo->configuredCollectionName();
            $mongoResult['documents'] = $pageData['documents'];
            $mongoResult['totalCount'] = $pageData['totalCount'];
            $mongoResult['page'] = $pageData['page'];
            $mongoResult['perPage'] = $pageData['perPage'];
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

    private function normalizePerPage(int $perPage): int
    {
        $allowed = [10, 25, 50];

        return in_array($perPage, $allowed, true) ? $perPage : 10;
    }
}
