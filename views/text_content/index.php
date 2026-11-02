<?php

declare(strict_types=1);

$editing = is_array($editingContent);
$formValues = array_merge($editing ? $editingContent : [], $old);
$selectedCategorySlugs = is_array($selectedCategorySlugs ?? null) ? $selectedCategorySlugs : [];
$e = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $e($appName) ?> · Text Content</title>
    <link rel="stylesheet" href="<?= $e($baseUrl) ?>/assets/css/dashboard.css">
</head>
<body class="dashboard-screen">
    <div class="dashboard-layout">
        <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
        <?php $activeNav = 'text-content'; require dirname(__DIR__) . '/partials/sidebar.php'; ?>
        <div class="dashboard-main">
            <header class="topbar">
                <div class="topbar-left">
                    <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle menu">☰</button>
                    <div class="topbar-title"><h1>Text Content</h1><p>Create and manage app text content.</p></div>
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
                    <div><h2>Text Content</h2><p><?= count($items) ?> items · <a href="<?= $e($baseUrl) ?>/content-categories">Manage Text Content Categories</a></p></div>
                </div>
                <?php if ($error !== null): ?><p class="form-error" role="alert"><?= $e($error) ?></p><?php endif; ?>
                <?php if ($notice !== null): ?><p class="form-success" role="status"><?= $e($notice) ?></p><?php endif; ?>
                <div class="content-management-grid">
                    <section class="panel content-form-panel">
                        <div class="panel-header">
                            <h2><?= $editing ? 'Edit Text Content' : 'New Text Content' ?></h2>
                            <?php if ($editing): ?><a class="panel-link" href="<?= $e($contentUrl) ?>">Cancel</a><?php endif; ?>
                        </div>
                        <form method="POST" action="<?= $e($contentUrl) ?><?= $editing ? '/' . (int) $editingContent['id'] . '/update' : '' ?>" class="user-form content-form" enctype="multipart/form-data">
                            <?= $csrfField ?>
                            <label for="title">Title</label>
                            <input id="title" name="title" type="text" maxlength="255" value="<?= $e($formValues['title'] ?? '') ?>" required>

                            <label for="slug">Slug</label>
                            <input id="slug" name="slug" type="text" maxlength="160" pattern="[a-z0-9]+(-[a-z0-9]+)*" value="<?= $e($formValues['slug'] ?? '') ?>" required>
                            <p class="field-hint">Lowercase letters, numbers, and hyphens.</p>

                            <label for="short_test">Short text</label>
                            <textarea id="short_test" name="short_test" rows="3" maxlength="1000"><?= $e($formValues['short_test'] ?? '') ?></textarea>

                            <label for="full_text">Full text</label>
                            <textarea id="full_text" name="full_text" rows="10" required><?= $e($formValues['full_text'] ?? '') ?></textarea>

                            <label for="thumb_nail">Thumbnail</label>
                            <input id="thumb_nail" name="thumb_nail" type="file" accept=".webp,.jpg,.jpeg,image/webp,image/jpeg">
                            <p class="field-hint">WebP or JPEG; max 100 MB.</p>
                            <?php if ($editing && !empty($editingContent['thumb_nail'])): ?>
                                <p class="field-hint">
                                    Current: <a href="<?= $e($baseUrl . '/' . $editingContent['thumb_nail']) ?>" target="_blank" rel="noopener noreferrer"><?= $e(basename((string) $editingContent['thumb_nail'])) ?></a>
                                    <label><input type="checkbox" name="remove_thumb_nail" value="1"> Remove</label>
                                </p>
                            <?php endif; ?>

                            <label for="category_slugs">Categories</label>
                            <select id="category_slugs" name="category_slugs[]" multiple>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $e($category['slug']) ?>"<?= in_array($category['slug'], $selectedCategorySlugs, true) ? ' selected' : '' ?>><?= $e($category['category_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="field-hint">Use Ctrl or Command to select multiple categories.</p>
                            <button type="submit" class="primary-button"><?= $editing ? 'Save changes' : 'Create Text Content' ?></button>
                        </form>
                    </section>
                    <section class="panel content-pages-panel">
                        <div class="panel-header content-pages-header">
                            <div><h2>All Text Content</h2><p>Search by title or slug.</p></div>
                            <form method="GET" action="<?= $e($contentUrl) ?>" class="user-filters">
                                <label class="visually-hidden" for="contentSearch">Search Text Content</label>
                                <input id="contentSearch" type="search" name="search" value="<?= $e($search) ?>" placeholder="Search title or slug">
                                <button type="submit" class="secondary-button">Search</button>
                            </form>
                        </div>
                        <div class="table-wrap">
                            <table class="data-table content-table">
                                <thead><tr><th>Title / slug</th><th>Categories</th><th>Thumbnail</th><th>Updated by</th><th>Updated</th><th><span class="visually-hidden">Actions</span></th></tr></thead>
                                <tbody>
                                <?php if ($items === []): ?>
                                    <tr><td colspan="6" class="empty-state">No text content found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($items as $item): ?>
                                        <tr>
                                            <td><strong class="content-page-title"><?= $e($item['title']) ?></strong><br><small><?= $e($item['slug']) ?></small></td>
                                            <td><?= $e($item['category_names'] ?? '') ?></td>
                                            <td>
                                                <?php if (!empty($item['thumb_nail'])): ?>
                                                    <a href="<?= $e($baseUrl . '/' . $item['thumb_nail']) ?>" target="_blank" rel="noopener noreferrer">View</a>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= $e($item['updated_by_name'] ?? '') ?></td>
                                            <td class="content-date"><?= $e(date('M j, Y', strtotime((string) $item['updated_date_time']))) ?></td>
                                            <td class="user-actions content-actions">
                                                <a class="text-action" href="<?= $e($contentUrl) ?>?edit=<?= (int) $item['id'] ?>">Edit</a>
                                                <form method="POST" action="<?= $e($contentUrl) ?>/<?= (int) $item['id'] ?>/delete" data-confirm="Delete this text content permanently?">
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
