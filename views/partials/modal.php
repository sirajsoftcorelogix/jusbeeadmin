<?php

declare(strict_types=1);

$modalId = (string) ($modalId ?? 'contentModal');
$modalTitle = (string) ($modalTitle ?? 'Dialog');
$modalOpen = (bool) ($modalOpen ?? false);
$modalContent = (string) ($modalContent ?? '');
$modalEsc = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
?>
<dialog id="<?= $modalEsc($modalId) ?>" class="modal-dialog" aria-labelledby="<?= $modalEsc($modalId) ?>Title"<?= $modalOpen ? ' data-modal-initial-open' : '' ?>>
    <div class="modal-panel">
        <header class="modal-header">
            <h2 id="<?= $modalEsc($modalId) ?>Title"><?= $modalEsc($modalTitle) ?></h2>
            <button type="button" class="modal-close" data-modal-close aria-label="Close dialog">×</button>
        </header>
        <div class="modal-content"><?= $modalContent ?></div>
    </div>
</dialog>
