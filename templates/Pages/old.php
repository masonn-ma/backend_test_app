<?php

declare(strict_types=1);

/**
 * @var \App\View\AppView $this
 * @var array $mongoResult
 * @var string $searchQuery
 * @var array $searchResults
 * @var ?string $searchError
 * @var ?int $searchErrorCode
 * @var ?string $searchIndexName
 */

$this->assign('title', 'Home');
echo $this->Html->css('home', ['block' => true]);
?>

<div class="container text-center">
    <h1>Mongo + Elasticsearch Demo</h1>

    <h2>Search Elasticsearch</h2>
    <form id="es-search-form" method="get" action="<?= $this->Url->build('/') ?>">
        <input id="es-search-input" type="text" name="q" value="<?= h($searchQuery) ?>" placeholder="Search indexed Mongo documents" style="max-width: 520px; width: 100%;" />
        <button type="submit">Search</button>
    </form>

    <div id="es-search-results">
        <?php if ($searchError !== null): ?>
            <p>Search is unavailable: <?= h($searchError) ?></p>
        <?php elseif ($searchQuery !== ''): ?>
            <p>
                Index: <strong><?= h((string)$searchIndexName) ?></strong><br />
                Query: <strong><?= h($searchQuery) ?></strong><br />
                Results: <strong><?= h((string)count($searchResults)) ?></strong>
            </p>
            <?php if (empty($searchResults)): ?>
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
                        <?php foreach ($searchResults as $hit): ?>
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

    <div id="data-table">
        <?php if (!$mongoResult['connected']): ?>
            <p>Unable to connect to MongoDB: <?= h((string)$mongoResult['error']) ?></p>
        <?php elseif (empty($mongoResult['collectionName'])): ?>
            <p>No MongoDB collection is configured in app_local.php.</p>
        <?php elseif (empty($mongoResult['documents'])): ?>
            <p>No documents found in configured collection: <?= h((string)$mongoResult['collectionName']) ?>.</p>
        <?php else: ?>
            <table border="1" cellpadding="10" cellspacing="0" style="width: 100%; margin-bottom: 30px;">
                <thead>
                    <tr>
                        <th>Document Data</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($mongoResult['documents'] as $document): ?>
                        <tr>
                            <td>
                                <pre><?= h(json_encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?></pre>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?= $this->element('containers/table') ?>

<script>
    (function() {
        const form = document.getElementById('es-search-form');
        const input = document.getElementById('es-search-input');
        const resultsContainer = document.getElementById('es-search-results');
        const mongoDataContainer = document.getElementById('data-table');

        if (!form || !input || !resultsContainer) {
            return;
        }

        function escapeHtml(value) {
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/\"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function renderSearch(data) {
            if (data.error) {
                if (data.errorCode === 404) {
                    resultsContainer.innerHTML = '<p>Search error 404: index/resource not found. Check Elasticsearch index config.</p>';
                } else {
                    resultsContainer.innerHTML = '<p>Search is unavailable: ' + escapeHtml(data.error) + '</p>';
                }
                return;
            }

            if (!data.query) {
                resultsContainer.innerHTML = '';
                return;
            }

            const results = Array.isArray(data.results) ? data.results : [];
            let html = '';
            html += '<p>';
            html += 'Index: <strong>' + escapeHtml(data.indexName || '') + '</strong><br />';
            html += 'Query: <strong>' + escapeHtml(data.query) + '</strong><br />';
            html += 'Results: <strong>' + escapeHtml(results.length) + '</strong>';
            html += '</p>';

            if (results.length === 0) {
                html += '<p>No results found.</p>';
                resultsContainer.innerHTML = html;
                return;
            }

            html += window.renderTable(['Score', 'Document'], results.map(function(hit) {
                const score = escapeHtml(hit && hit._score !== undefined ? hit._score : '');
                const source = escapeHtml(JSON.stringify((hit && hit._source) || {}, null, 2));

                return [score, '<pre>' + source + '</pre>'];
            }));
            resultsContainer.innerHTML = html;
        }

        async function runSearch() {
            const q = input.value.trim();
            const url = '<?= $this->Url->build(['controller' => 'Home', 'action' => 'search']) ?>' + '?ajax=1&q=' + encodeURIComponent(q);

            try {
                const response = await fetch(url, {
                    headers: {
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();
                renderSearch(data);
            } catch (error) {
                resultsContainer.innerHTML = '<p>Search request failed. Please try again.</p>';
            }
        }

        function renderMongoData(data) {
            if (!mongoDataContainer) {
                return;
            }

            if (data.error) {
                mongoDataContainer.innerHTML = '<p>Unable to load Mongo data: ' + escapeHtml(data.error) + '</p>';
                return;
            }

            const documents = Array.isArray(data.documents) ? data.documents : [];
            const collectionName = data.collectionName || '';

            if (!collectionName) {
                mongoDataContainer.innerHTML = '<p>No MongoDB collection is configured in app_local.php.</p>';
                return;
            }

            if (documents.length === 0) {
                mongoDataContainer.innerHTML = '<p>No documents found in configured collection: ' + escapeHtml(collectionName) + '.</p>';
                return;
            }

            let html = '';
            html += window.renderTable(['Document Data'], documents.map(function(documentRow) {
                return ['<pre>' + escapeHtml(JSON.stringify(documentRow || {}, null, 2)) + '</pre>'];
            }));
            mongoDataContainer.innerHTML = html;
        }

        async function refreshMongoData() {
            if (!mongoDataContainer) {
                return;
            }

            const url = '<?= $this->Url->build(['controller' => 'Home', 'action' => 'mongoData']) ?>';

            try {
                const response = await fetch(url, {
                    headers: {
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();
                renderMongoData(data);
            } catch (error) {
                mongoDataContainer.innerHTML = '<p>Failed to refresh Mongo data.</p>';
            }
        }

        form.addEventListener('submit', function(event) {
            event.preventDefault();
            runSearch();
        });

        refreshMongoData();
        setInterval(refreshMongoData, 5000);
    }());
</script>