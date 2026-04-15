<?php

declare(strict_types=1);

use App\Service\ElasticsearchService;
use App\Service\MongoService;

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/config/bootstrap.php';

$mongo = new MongoService();
$es = new ElasticsearchService();

$cursor = $mongo->collection('users')->find([], [
    'typeMap' => [
        'root' => 'array',
        'document' => 'array',
        'array' => 'array',
    ],
]);

$indexedCount = 0;
$skippedCount = 0;
$errorCount = 0;

foreach ($cursor as $user) {
    if (!is_array($user) || !isset($user['_id'])) {
        $skippedCount++;
        continue;
    }

    $id = (string)$user['_id'];
    unset($user['_id']);

    try {
        $es->indexDocument($user, $id);
        $indexedCount++;
    } catch (Throwable $exception) {
        $errorCount++;
        fwrite(STDERR, 'Failed to index user ' . $id . ': ' . $exception->getMessage() . PHP_EOL);
    }
}

fwrite(
    STDOUT,
    sprintf(
        "Done. Indexed: %d, Skipped: %d, Errors: %d, Index: %s" . PHP_EOL,
        $indexedCount,
        $skippedCount,
        $errorCount,
        $es->configuredIndexName()
    )
);
