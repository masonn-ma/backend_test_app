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

    public function getConfiguredCollectionDocuments(bool $activeOnly = true, string $role = '', string $status = ''): array
    {
        $collection = $this->collection();
        $filter = $this->buildReadFilter($this->collectionName, $activeOnly, $role, $status);

        return $collection->find($filter)->toArray();
    }

    /**
     * Fetch paginated documents from the configured collection.
     *
     * @return array{documents: array<mixed>, totalCount: int, page: int, perPage: int}
     */
    public function getConfiguredCollectionPage(
        int $page,
        int $perPage,
        bool $activeOnly = true,
        string $role = '',
        string $status = '',
        string $sortBy = '',
        string $sortDir = ''
    ): array {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $skip = ($page - 1) * $perPage;
        $collection = $this->collection();
        $filter = $this->buildReadFilter($this->collectionName, $activeOnly, $role, $status);
        $sortField = $this->resolveSortField($sortBy);
        $sortDirection = strtolower($sortDir) === 'asc' ? 1 : -1;

        $findOptions = [
            'skip' => $skip,
            'limit' => $perPage,
        ];

        if ($sortField !== null && ($sortDir === 'asc' || $sortDir === 'desc')) {
            $findOptions['sort'] = [$sortField => $sortDirection, '_id' => -1];
        }

        $documents = $collection->find($filter, $findOptions)->toArray();

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
        $password = (string)($userData['password'] ?? '');
        if ($password === '') {
            throw new \InvalidArgumentException('Password is required to create a user.');
        }

        $role = strtolower(trim((string)($userData['role'] ?? 'user')));
        $status = strtolower(trim((string)($userData['status'] ?? 'active')));
        $permissions = $this->permissionsForRole($role);
        $now = new \MongoDB\BSON\UTCDateTime();
        $defaultDob = new \MongoDB\BSON\UTCDateTime((int)(strtotime('2000-01-01T00:00:00Z') * 1000));

        $newUserData = [
            'username' => $userData['username'] ?? null,
            'email' => $userData['email'] ?? null,
            'passwordHash' => password_hash($password, PASSWORD_DEFAULT),
            'firstName' => $userData['firstName'] ?? null,
            'lastName' => $userData['lastName'] ?? null,
            'fullName' => trim(($userData['firstName'] ?? '') . ' ' . ($userData['lastName'] ?? '')),
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
            'isActive' => $userData['isActive'] ?? true,
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
        $result = $this->collection('users')->insertOne($newUserData);
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

    public function updateUserByUsername(string $originalUsername, array $updateData): bool
    {
        $updateResult = $this->collection('users')->updateOne(
            ['username' => $originalUsername],
            [
                '$set' => [
                    ...$updateData,
                    'updatedAt' => new \MongoDB\BSON\UTCDateTime(),
                ],
            ]
        );

        return $updateResult->getMatchedCount() > 0;
    }

    public function updateUserProfileByUsername(
        string $originalUsername,
        string $username,
        string $email,
        string $firstName,
        string $lastName,
        string $role,
        string $status
    ): bool {
        $normalizedRole = strtolower(trim($role));
        $normalizedStatus = strtolower(trim($status));

        return $this->updateUserByUsername($originalUsername, [
            'username' => $username,
            'email' => $email,
            'firstName' => $firstName,
            'lastName' => $lastName,
            'fullName' => trim($firstName . ' ' . $lastName),
            'role' => $normalizedRole,
            'permissions' => $this->permissionsForRole($normalizedRole),
            'status' => $normalizedStatus,
        ]);
    }

    public function findUserByUsername(string $username): ?array
    {
        $user = $this->collection('users')->findOne(
            ['username' => $username],
            ['typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array']]
        );

        return is_array($user) ? $user : null;
    }

    /* -------------------------------------------- */

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
    private function buildReadFilter(string $collectionName, bool $activeOnly = true, string $role = '', string $status = ''): array
    {
        if ($collectionName !== 'users') {
            return [];
        }

        $role = strtolower(trim($role));
        $status = strtolower(trim($status));

        $filter = [];

        if (!$activeOnly) {
            $filter = [];
        } else {
            $filter['isActive'] = true;
        }

        if ($role !== '') {
            $filter['role'] = $role;
        }

        if ($status !== '') {
            $filter['status'] = $status;
        }

        return $filter;
    }

    /**
     * @return array<int, string>
     */
    private function permissionsForRole(string $role): array
    {
        $permissionsByRole = [
            'admin' => ['read', 'write', 'moderate', 'delete', 'admin'],
            'moderator' => ['read', 'write', 'moderate'],
            'user' => ['read', 'write'],
            'guest' => ['read'],
        ];

        return $permissionsByRole[$role] ?? ['read'];
    }

    private function resolveSortField(string $sortBy): ?string
    {
        $mapping = [
            'name' => 'fullName',
            'email' => 'email',
            'username' => 'username',
            'status' => 'status',
            'role' => 'role',
            'joined' => 'createdAt',
            'lastActive' => 'lastLogin',
        ];

        return $mapping[$sortBy] ?? null;
    }
}
