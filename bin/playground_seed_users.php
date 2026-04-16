<?php

declare(strict_types=1);

use App\Service\ElasticsearchService;
use App\Service\MongoService;
use MongoDB\BSON\UTCDateTime;

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/config/bootstrap.php';

/**
 * Build a UTCDateTime from a unix timestamp (seconds).
 */
function utcFromTimestamp(int $timestamp): UTCDateTime
{
    return new UTCDateTime($timestamp * 1000);
}

/**
 * Build one mock user using the same schema used by HomeController::addUser().
 *
 * @return array<string, mixed>
 */
function buildMockUser(int $i, string $seed): array
{
    $firstNames = [
        'Alex',
        'Jordan',
        'Taylor',
        'Sam',
        'Avery',
        'Riley',
        'Morgan',
        'Casey',
        'Quinn',
        'Skyler',
    ];
    $lastNames = [
        'Nguyen',
        'Patel',
        'Smith',
        'Garcia',
        'Kim',
        'Brown',
        'Davis',
        'Wilson',
        'Lopez',
        'Martinez',
    ];
    $cities = [
        'Austin',
        'Seattle',
        'Chicago',
        'Boston',
        'Denver',
        'Miami',
        'Phoenix',
        'Atlanta',
        'Portland',
        'Dallas',
    ];
    $states = ['TX', 'WA', 'IL', 'MA', 'CO', 'FL', 'AZ', 'GA', 'OR', 'TX'];
    $roles = ['admin', 'moderator', 'user', 'guest'];
    $genders = ['male', 'female', 'non-binary', 'prefer not to say'];

    $firstName = $firstNames[$i % count($firstNames)];
    $lastName = $lastNames[$i % count($lastNames)];
    $fullName = $firstName . ' ' . $lastName;
    $username = strtolower($firstName . '.' . $lastName . '.' . $seed . '.' . ($i + 1));
    $email = strtolower($firstName . '.' . $lastName . '.' . $seed . '.' . ($i + 1) . '@example.dev');
    $age = 20 + ($i % 18);
    $role = $roles[$i % count($roles)];
    $status = ($i % 5 === 0) ? 'inactive' : 'active';
    $isActive = $status !== 'inactive';
    $now = utcFromTimestamp(time());
    $dobTimestamp = strtotime('-' . $age . ' years') ?: strtotime('2000-01-01T00:00:00Z');

    $permissionsByRole = [
        'admin' => ['read', 'write', 'moderate', 'delete', 'admin'],
        'moderator' => ['read', 'write', 'moderate'],
        'user' => ['read', 'write'],
        'guest' => ['read'],
    ];

    return [
        'username' => $username,
        'email' => $email,
        'passwordHash' => password_hash('Password123!', PASSWORD_DEFAULT),
        'firstName' => $firstName,
        'lastName' => $lastName,
        'fullName' => $fullName,
        'age' => $age,
        'dateOfBirth' => utcFromTimestamp((int)$dobTimestamp),
        'gender' => $genders[$i % count($genders)],
        'phoneNumber' => '+1555' . str_pad((string)(1000000 + $i), 7, '0', STR_PAD_LEFT),
        'address' => [
            'street' => (100 + $i) . ' Mockingbird Ln',
            'city' => $cities[$i % count($cities)],
            'state' => $states[$i % count($states)],
            'postalCode' => str_pad((string)(75000 + $i), 5, '0', STR_PAD_LEFT),
            'country' => 'USA',
        ],
        'profilePicture' => '',
        'bio' => 'Mock playground user #' . ($i + 1),
        'role' => $role,
        'status' => $status,
        'permissions' => $permissionsByRole[$role] ?? ['read'],
        'isActive' => $isActive,
        'isEmailVerified' => ($i % 3) === 0,
        'isPhoneVerified' => ($i % 4) === 0,
        'lastLogin' => $now,
        'loginCount' => $i,
        'createdAt' => $now,
        'updatedAt' => $now,
        'socialLogin' => [
            'google' => null,
            'facebook' => null,
            'twitter' => null,
        ],
        'preferences' => [
            'language' => ($i % 2 === 0) ? 'en' : 'es',
            'theme' => ($i % 2 === 0) ? 'system' : 'light',
            'notifications' => [
                'email' => true,
                'push' => ($i % 3) !== 0,
                'sms' => ($i % 4) === 0,
            ],
        ],
        'twoFactorEnabled' => ($i % 6) === 0,
    ];
}

$count = 20;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--count=')) {
        $raw = (int)substr($arg, strlen('--count='));
        if ($raw > 0) {
            $count = $raw;
        }
    }
}

$seed = date('YmdHis');
$mongo = new MongoService();
$es = new ElasticsearchService();

$insertedCount = 0;
$indexedCount = 0;
$errorCount = 0;

for ($i = 0; $i < $count; $i++) {
    try {
        $user = buildMockUser($i, $seed);
        $insertedId = $mongo->newUser($user);
        $insertedCount++;

        try {
            $es->indexDocument($user, $insertedId);
            $indexedCount++;
        } catch (Throwable $exception) {
            $errorCount++;
            fwrite(STDERR, 'Elasticsearch indexing failed for user ' . $insertedId . ': ' . $exception->getMessage() . PHP_EOL);
        }
    } catch (Throwable $exception) {
        $errorCount++;
        fwrite(STDERR, 'Mongo insert failed for user #' . ($i + 1) . ': ' . $exception->getMessage() . PHP_EOL);
    }
}

fwrite(
    STDOUT,
    sprintf(
        "Done. Requested: %d, Inserted: %d, Indexed: %d, Errors: %d, ES Index: %s" . PHP_EOL,
        $count,
        $insertedCount,
        $indexedCount,
        $errorCount,
        $es->configuredIndexName()
    )
);
