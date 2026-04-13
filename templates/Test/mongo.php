<?php

/**
 * @var \App\View\AppView $this
 * @var string $collectionName
 * @var array $documents
 */
?>

<div class="test">
    <h1>MongoDB Collection Contents</h1>
    <h2><?= h($collectionName) ?></h2>

    <?php if (empty($documents)): ?>
        <p>No documents found in this collection.</p>
    <?php else: ?>
        <table border="1" cellpadding="10" cellspacing="0" style="width: 100%; margin-bottom: 30px;">
            <thead>
                <tr>
                    <th>Document Data</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($documents as $document): ?>
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