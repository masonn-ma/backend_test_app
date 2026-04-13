<?php

/**
 * @var \App\View\AppView $this
 * @var string $query
 * @var array $results
 * @var ?string $error
 * @var ?string $indexName
 */
?>

<div class="test">
    <h1>Elasticsearch Search</h1>

    <form method="get" action="<?= $this->Url->build(['controller' => 'Test', 'action' => 'search']) ?>">
        <input type="text" name="q" value="<?= h($query) ?>" placeholder="Search indexed Mongo documents" style="max-width: 520px; width: 100%;" />
        <button type="submit">Search</button>
    </form>

    <?php if ($error !== null): ?>
        <p>Search is unavailable: <?= h($error) ?></p>
    <?php elseif ($query === ''): ?>
        <p>Enter a search term.</p>
    <?php else: ?>
        <p>
            Index: <strong><?= h((string)$indexName) ?></strong><br />
            Query: <strong><?= h($query) ?></strong><br />
            Results: <strong><?= h((string)count($results)) ?></strong>
        </p>

        <?php if (empty($results)): ?>
            <p>No results found.</p>
        <?php else: ?>
            <table border="1" cellpadding="10" cellspacing="0" style="width: 100%; margin-bottom: 30px;">
                <thead>
                    <tr>
                        <th>Score</th>
                        <th>Document</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $hit): ?>
                        <tr>
                            <td><?= h((string)($hit['_score'] ?? '')) ?></td>
                            <td>
                                <pre><?= h(json_encode($hit['_source'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?></pre>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php endif; ?>
</div>