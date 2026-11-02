<?php

declare(strict_types=1);

$editing = is_array($editingRiyaz);
$formValues = array_merge($editing ? $editingRiyaz : [], $old);
$e = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $e($appName) ?> · Riyaz</title>
    <link rel="stylesheet" href="<?= $e($baseUrl) ?>/assets/css/dashboard.css">
</head>
<body class="dashboard-screen">
    <div class="dashboard-layout">
        <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
        <?php $activeNav = 'riyaz'; require dirname(__DIR__) . '/partials/sidebar.php'; ?>
        <div class="dashboard-main">
            <header class="topbar">
                <div class="topbar-left">
                    <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle menu">☰</button>
                    <div class="topbar-title"><h1>Riyaz</h1><p>Upload and manage JSON sync files for library media.</p></div>
                </div>
                <div class="topbar-right">
                    <form method="POST" action="<?= $e($baseUrl) ?>/logout">
                        <?= $csrfField ?>
                        <button type="submit" class="logout-button">Log out</button>
                    </form>
                </div>
            </header>
            <main class="dashboard-content">
                <div class="content-header">
                    <div><h2>Riyaz</h2><p><?= count($items) ?> records</p></div>
                    <div class="content-header-actions">
                        
                    <button type="button" class="primary-button" data-modal-open="#riyazModal">Add Riyaz record</button>
                    </div>
                </div>
                <?php if ($notice !== null): ?><p class="form-success" role="status"><?= $e($notice) ?></p><?php endif; ?>

                <section class="panel content-pages-panel">
                    <div class="panel-header"><div><h2>All Riyaz records</h2><p>Records are linked to library media.</p></div></div>
                    <div class="table-wrap">
                        <table class="data-table content-table">
                            <thead><tr><th>Media</th><th>Synced JSON</th><th>Updated by</th><th>Added</th><th>Updated</th><th><span class="visually-hidden">Actions</span></th></tr></thead>
                            <tbody>
                            <?php if ($items === []): ?>
                                <tr><td colspan="6" class="empty-state">No Riyaz records found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td><?= $e($item['media_title']) ?><br><small>ID: <?= (int) $item['media_id'] ?></small></td>
                                        <td><a href="<?= $e($baseUrl . '/' . $item['synced_json']) ?>" target="_blank" rel="noopener noreferrer"><?= $e(basename((string) $item['synced_json'])) ?></a></td>
                                        <td><?= $e($item['updated_by_name'] ?? '') ?></td>
                                        <td><?= $e(date('M j, Y', strtotime((string) $item['added_date_time']))) ?></td>
                                        <td><?= $e(date('M j, Y', strtotime((string) $item['updated_date_time']))) ?></td>
                                        <td class="user-actions content-actions">
                                            <a class="text-action" href="<?= $e($riyazUrl) ?>?edit=<?= (int) $item['id'] ?>">Edit</a>
                                            <form method="POST" action="<?= $e($riyazUrl) ?>/<?= (int) $item['id'] ?>/delete" data-confirm="Delete this Riyaz record?">
                                                <?= $csrfField ?>
                                                <button type="submit" class="text-action is-danger">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <?php ob_start(); ?>
                    <?php if ($error !== null): ?><p class="form-error" role="alert"><?= $e($error) ?></p><?php endif; ?>
                    <form method="POST" action="<?= $e($riyazUrl) ?><?= $editing ? '/' . (int) $editingRiyaz['id'] . '/update' : '' ?>" class="user-form content-form" enctype="multipart/form-data">
                            <?= $csrfField ?>
                            <label for="media_id">Library media</label>
                            <select id="media_id" name="media_id" required>
                                <option value="">Select media</option>
                                <?php foreach ($mediaItems as $media): ?>
                                    <option value="<?= (int) $media['id'] ?>"<?= (string) ($formValues['media_id'] ?? '') === (string) $media['id'] ? ' selected' : '' ?>>
                                        <?= $e($media['title']) ?> (<?= $e($media['slug']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <label for="synced_json">Synced JSON file</label>
                            <input id="synced_json" name="synced_json" type="file" accept=".json,application/json"<?= $editing ? '' : ' required' ?>>
                            <p class="field-hint">Valid .json file; max 10 MB. Files are stored under public/uploads/riyaz/.</p>
                            <?php if ($editing): ?>
                                <p class="field-hint">Current: <a href="<?= $e($baseUrl . '/' . $editingRiyaz['synced_json']) ?>" target="_blank" rel="noopener noreferrer"><?= $e(basename((string) $editingRiyaz['synced_json'])) ?></a>. Leave empty to keep the current file.</p>
                            <?php endif; ?>

                            <button type="submit" class="primary-button"><?= $editing ? 'Save changes' : 'Add Riyaz record' ?></button>
                    </form>
                <?php
                    $modalContent = (string) ob_get_clean();
                    $modalId = 'riyazModal';
                    $modalTitle = $editing ? 'Edit Riyaz record' : 'New Riyaz record';
                    $modalOpen = $editing || $error !== null;
                    require dirname(__DIR__) . '/partials/modal.php';
                ?>
            </main>
        </div>
    </div>
    <script src="<?= $e($baseUrl) ?>/assets/js/dashboard.js?v=riyaz-modal-1"></script>
</body>
</html>
