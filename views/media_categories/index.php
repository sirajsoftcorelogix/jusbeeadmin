<?php

declare(strict_types=1);

$editing = is_array($editingCategory);
$formValues = array_merge($editing ? $editingCategory : [], $old);
$e = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$parentId = (string) ($formValues['parent_id'] ?? '');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $e($appName) ?> · Media Categories</title>
    <link rel="stylesheet" href="<?= $e($baseUrl) ?>/assets/css/dashboard.css">
</head>
<body class="dashboard-screen">
    <div class="dashboard-layout">
        <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
        <?php $activeNav = 'media-categories'; require dirname(__DIR__) . '/partials/sidebar.php'; ?>
        <div class="dashboard-main">
            <header class="topbar">
                <div class="topbar-left">
                    <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle menu">☰</button>
                    <div class="topbar-title"><h1>Media Categories</h1><p>Organize library media into parent and child categories.</p></div>
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
                    <div><h2>Categories</h2><p><?= count($categories) ?> categories</p></div>
                </div>
                <?php if ($error !== null): ?><p class="form-error" role="alert"><?= $e($error) ?></p><?php endif; ?>
                <?php if ($notice !== null): ?><p class="form-success" role="status"><?= $e($notice) ?></p><?php endif; ?>
                <div class="content-management-grid">
                    <section class="panel content-form-panel">
                        <div class="panel-header">
                            <h2><?= $editing ? 'Edit category' : 'New category' ?></h2>
                            <?php if ($editing): ?><a class="panel-link" href="<?= $e($categoriesUrl) ?>">Cancel</a><?php endif; ?>
                        </div>
                        <form method="POST" action="<?= $e($categoriesUrl) ?><?= $editing ? '/' . (int) $editingCategory['category_id'] . '/update' : '' ?>" class="user-form content-form">
                            <?= $csrfField ?>
                            <label for="category_name">Category name</label>
                            <input id="category_name" name="category_name" type="text" maxlength="255" value="<?= $e($formValues['category_name'] ?? '') ?>" required>

                            <label for="slug">Slug</label>
                            <input id="slug" name="slug" type="text" maxlength="160" pattern="[a-z0-9]+(-[a-z0-9]+)*" value="<?= $e($formValues['slug'] ?? '') ?>" required>

                            <label for="parent_id">Parent category</label>
                            <select id="parent_id" name="parent_id">
                                <option value="">None (top-level)</option>
                                <?php foreach ($categories as $category): ?>
                                    <?php if (!$editing || (int) $category['category_id'] !== (int) $editingCategory['category_id']): ?>
                                        <option value="<?= (int) $category['category_id'] ?>"<?= $parentId === (string) $category['category_id'] ? ' selected' : '' ?>><?= $e($category['category_name']) ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>

                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="5"><?= $e($formValues['description'] ?? '') ?></textarea>
                            <button type="submit" class="primary-button"><?= $editing ? 'Save changes' : 'Create category' ?></button>
                        </form>
                    </section>
                    <section class="panel content-pages-panel">
                        <div class="panel-header"><div><h2>All categories</h2><p>Categories assigned to media are also removed from their mappings when deleted.</p></div></div>
                        <div class="table-wrap">
                            <table class="data-table content-table">
                                <thead><tr><th>Category</th><th>Parent</th><th>Slug</th><th>Updated by</th><th>Updated</th><th><span class="visually-hidden">Actions</span></th></tr></thead>
                                <tbody>
                                <?php if ($categories === []): ?>
                                    <tr><td colspan="6" class="empty-state">No categories found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($categories as $category): ?>
                                        <tr>
                                            <td><strong class="content-page-title"><?= $e($category['category_name']) ?></strong></td>
                                            <td><?= $e($category['parent_name'] ?? '—') ?></td>
                                            <td><?= $e($category['slug']) ?></td>
                                            <td><?= $e($category['updated_by_name'] ?? '') ?></td>
                                            <td class="content-date"><?= $e(date('M j, Y', strtotime((string) $category['updated_date_time']))) ?></td>
                                            <td class="user-actions content-actions">
                                                <a class="text-action" href="<?= $e($categoriesUrl) ?>?edit=<?= (int) $category['category_id'] ?>">Edit</a>
                                                <form method="POST" action="<?= $e($categoriesUrl) ?>/<?= (int) $category['category_id'] ?>/delete" data-confirm="Delete this category and its media mappings?">
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
