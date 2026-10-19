<?php

declare(strict_types=1);

$editing = is_array($editingItem);
$formValues = array_merge($editing ? $editingItem : [], $old);
$formStatus = (string) ($formValues['status'] ?? 'active');
$e = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$fileFields = ['audio' => 'audio/*,.mp3,.wav,.ogg,.m4a,.aac', 'video' => 'video/*,.mp4,.webm,.ogg,.mov', 'image' => 'image/*,.jpg,.jpeg,.png,.gif,.webp'];
$labels = ['youtube' => 'YouTube URL', 'audio' => 'Audio URL', 'video' => 'Video URL', 'link' => 'Link URL', 'image' => 'Image URL'];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $e($appName) ?> · Library</title>
    <link rel="stylesheet" href="<?= $e($baseUrl) ?>/assets/css/dashboard.css">
</head>
<body class="dashboard-screen">
    <div class="dashboard-layout">
        <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
        <?php $activeNav = 'library'; require dirname(__DIR__) . '/partials/sidebar.php'; ?>

        <div class="dashboard-main">
            <header class="topbar">
                <div class="topbar-left">
                    <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle menu">☰</button>
                    <div class="topbar-title">
                        <h1>Library</h1>
                        <p>Manage videos, audio, images, and links.</p>
                    </div>
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
                    <div>
                        <h2>Library items</h2>
                        <p>Each item has a unique slug and one or more media sources.</p>
                    </div>
                    <span class="user-count"><?= (int) $totalItems ?> total</span>
                </div>

                <?php if ($error !== null): ?>
                    <p class="form-error" role="alert"><?= $e($error) ?></p>
                <?php endif; ?>
                <?php if ($notice !== null): ?>
                    <p class="form-success" role="status"><?= $e($notice) ?></p>
                <?php endif; ?>

                <div class="content-management-grid">
                    <section class="panel content-form-panel" aria-labelledby="libraryFormTitle">
                        <div class="panel-header">
                            <h2 id="libraryFormTitle"><?= $editing ? 'Edit item' : 'New item' ?></h2>
                            <?php if ($editing): ?>
                                <a class="panel-link" href="<?= $e($libraryUrl) ?>">Cancel</a>
                            <?php endif; ?>
                        </div>
                        <form method="POST" action="<?= $e($libraryUrl) ?><?= $editing ? '/' . (int) $editingItem['id'] . '/update' : '' ?>" class="user-form content-form" enctype="multipart/form-data">
                            <?= $csrfField ?>
                            <label for="slug">Slug</label>
                            <input id="slug" name="slug" type="text" maxlength="160" pattern="[a-z0-9]+(-[a-z0-9]+)*" value="<?= $e($formValues['slug'] ?? '') ?>" placeholder="e.g. breathing-exercise" required>
                            <p class="field-hint">Lowercase letters, numbers, and hyphens.</p>

                            <?php foreach ($mediaFields as $field): ?>
                                <?php if (isset($fileFields[$field])): ?>
                                    <label for="<?= $e($field) ?>"><?= $e(ucfirst($field)) ?> file</label>
                                    <input id="<?= $e($field) ?>" name="<?= $e($field) ?>" type="file" accept="<?= $e($fileFields[$field]) ?>">
                                    <?php if ($editing && !empty($editingItem[$field])): ?>
                                        <p class="field-hint">
                                            Current: <a href="<?= $e($baseUrl . '/' . $editingItem[$field]) ?>" target="_blank" rel="noopener noreferrer"><?= $e(basename((string) $editingItem[$field])) ?></a>
                                            <label><input type="checkbox" name="remove_<?= $e($field) ?>" value="1"> Remove</label>
                                        </p>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <label for="<?= $e($field) ?>"><?= $e($labels[$field]) ?></label>
                                    <input id="<?= $e($field) ?>" name="<?= $e($field) ?>" type="url" maxlength="500" value="<?= $e($formValues[$field] ?? '') ?>" placeholder="https://">
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <p class="field-hint">Audio, video and image are file uploads (max 100 MB, subject to the server upload limit). Provide at least one source.</p>

                            <label for="status">Status</label>
                            <select id="status" name="status" required>
                                <option value="active"<?= $formStatus === 'active' ? ' selected' : '' ?>>Active</option>
                                <option value="inactive"<?= $formStatus === 'inactive' ? ' selected' : '' ?>>Inactive</option>
                            </select>
                            <button type="submit" class="primary-button"><?= $editing ? 'Save changes' : 'Create item' ?></button>
                        </form>
                    </section>

                    <section class="panel content-pages-panel" aria-labelledby="libraryListTitle">
                        <div class="panel-header content-pages-header">
                            <div>
                                <h2 id="libraryListTitle">All items</h2>
                                <p><?= count($items) ?> of <?= (int) $totalItems ?> items</p>
                            </div>
                            <form method="GET" action="<?= $e($libraryUrl) ?>" class="user-filters">
                                <label class="visually-hidden" for="librarySearch">Search by slug</label>
                                <input id="librarySearch" type="search" name="search" value="<?= $e($search) ?>" placeholder="Search slug">
                                <label class="visually-hidden" for="libraryStatus">Filter by status</label>
                                <select id="libraryStatus" name="status">
                                    <option value="all"<?= $status === 'all' ? ' selected' : '' ?>>All statuses</option>
                                    <option value="active"<?= $status === 'active' ? ' selected' : '' ?>>Active</option>
                                    <option value="inactive"<?= $status === 'inactive' ? ' selected' : '' ?>>Inactive</option>
                                </select>
                                <button type="submit" class="secondary-button">Filter</button>
                            </form>
                        </div>
                        <div class="table-wrap">
                            <table class="data-table content-table">
                                <thead>
                                    <tr><th>Slug</th><th>Sources</th><th>Status</th><th>Modified</th><th><span class="visually-hidden">Actions</span></th></tr>
                                </thead>
                                <tbody>
                                    <?php if ($items === []): ?>
                                        <tr><td colspan="5" class="empty-state">No library items found.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($items as $item): ?>
                                            <?php $active = $item['status'] === 'active'; ?>
                                            <tr>
                                                <td><strong class="content-page-title"><?= $e($item['slug']) ?></strong></td>
                                                <td>
                                                    <?php foreach ($mediaFields as $field): ?>
                                                        <?php if (!empty($item[$field])): ?>
                                                            <a class="role-badge" href="<?= $e(isset($fileFields[$field]) ? $baseUrl . '/' . $item[$field] : $item[$field]) ?>" target="_blank" rel="noopener noreferrer"><?= $e($field) ?></a>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </td>
                                                <td><span class="status-badge <?= $active ? 'is-active' : 'is-inactive' ?>"><?= $active ? 'Active' : 'Inactive' ?></span></td>
                                                <td class="content-date"><?= $e(date('M j, Y', strtotime((string) $item['modified_at']))) ?></td>
                                                <td class="user-actions content-actions">
                                                    <a class="text-action" href="<?= $e($libraryUrl) ?>?edit=<?= (int) $item['id'] ?>">Edit</a>
                                                    <form method="POST" action="<?= $e($libraryUrl) ?>/<?= (int) $item['id'] ?>/delete" data-confirm="Delete this library item permanently?">
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
                </div>
            </main>
        </div>
    </div>
    <script src="<?= $e($baseUrl) ?>/assets/js/dashboard.js"></script>
</body>
</html>
