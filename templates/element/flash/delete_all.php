<?php

/**
 * @var \App\View\AppView $this
 * @var array $params
 * @var string $message
 */
if (!isset($params['escape']) || $params['escape'] !== false) {
    $message = h($message);
}
?>
<div class="message warning hidden" data-delete-all-flash role="status" aria-live="polite" onclick="this.classList.add('hidden');"><?= $message ?></div>