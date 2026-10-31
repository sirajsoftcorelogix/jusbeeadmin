<?php

declare(strict_types=1);

$editing = is_array($editingItem);
$formValues = array_merge($editing ? $editingItem : [], $old);
$formStatus = (string) ($formValues['status'] ?? 'active');
$formMediaType = (string) ($formValues['media_type'] ?? 'audio');
$selectedCategorySlugs = is_array($selectedCategorySlugs ?? null) ? $selectedCategorySlugs : [];
$e = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$mediaAccept = '.mp3,.mp4,.wav,.mov,.m4v,.m4a,audio/mpeg,audio/wav,audio/mp4,video/mp4,video/quicktime';
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
                        <p>Manage audio and video library items.</p>
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
                        <p>Each item has a unique slug, thumbnail, and media file.</p>
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
                            <label for="title">Title</label>
                            <input id="title" name="title" type="text" maxlength="255" value="<?= $e($formValues['title'] ?? '') ?>" required>

                            <label for="slug">Slug</label>
                            <input id="slug" name="slug" type="text" maxlength="160" pattern="[a-z0-9]+(-[a-z0-9]+)*" value="<?= $e($formValues['slug'] ?? '') ?>" placeholder="e.g. breathing-exercise" required>
                            <p class="field-hint">Lowercase letters, numbers, and hyphens.</p>

                            <label for="thumbnail">Thumbnail</label>
                            <input id="thumbnail" name="thumbnail" type="file" accept=".webp,.jpg,.jpeg,image/webp,image/jpeg">
                            <p class="field-hint">WebP or JPEG; max 100 MB.</p>
                            <?php if ($editing && !empty($editingItem['thumbnail'])): ?>
                                <p class="field-hint">
                                    Current: <a href="<?= $e($baseUrl . '/' . $editingItem['thumbnail']) ?>" target="_blank" rel="noopener noreferrer"><?= $e(basename((string) $editingItem['thumbnail'])) ?></a>
                                    <label><input type="checkbox" name="remove_thumbnail" value="1"> Remove</label>
                                </p>
                            <?php endif; ?>

                            <label for="media_type">Media type</label>
                            <select id="media_type" name="media_type" required>
                                <option value="audio"<?= $formMediaType === 'audio' ? ' selected' : '' ?>>Audio</option>
                                <option value="video"<?= $formMediaType === 'video' ? ' selected' : '' ?>>Video</option>
                            </select>

                            <label for="media_file">Media file</label>
                            <input id="media_file" name="media_file" type="file" accept="<?= $e($mediaAccept) ?>" <?= !$editing || empty($editingItem['media_file']) ? 'required' : '' ?>>
                            <p class="field-hint">MP3, MP4, WAV, MOV, M4V, or M4A; max 100 MB.</p>
                            <?php if ($editing && !empty($editingItem['media_file'])): ?>
                                <p class="field-hint">
                                    Current: <a href="<?= $e($baseUrl . '/' . $editingItem['media_file']) ?>" target="_blank" rel="noopener noreferrer"><?= $e(basename((string) $editingItem['media_file'])) ?></a>
                                    <label><input type="checkbox" name="remove_media_file" value="1"> Remove</label>
                                </p>
                            <?php endif; ?>

                            <label for="play_time">Play time</label>
                            <input id="play_time" name="play_time" type="text" maxlength="20" value="<?= $e($formValues['play_time'] ?? '') ?>" placeholder="e.g. 04:32">

                            <label for="artist">Artist</label>
                            <input id="artist" name="artist" type="text" maxlength="255" value="<?= $e($formValues['artist'] ?? '') ?>">

                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="5"><?= $e($formValues['description'] ?? '') ?></textarea>

                            <label for="category_slugs">Categories</label>
                            <select id="category_slugs" name="category_slugs[]" multiple>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $e($category['slug']) ?>"<?= in_array($category['slug'], $selectedCategorySlugs, true) ? ' selected' : '' ?>><?= $e($category['category_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="field-hint">Use Ctrl or Command to select multiple categories. <a href="<?= $e($baseUrl) ?>/media-categories">Manage categories</a>.</p>

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
                                <label class="visually-hidden" for="librarySearch">Search library items</label>
                                <input id="librarySearch" type="search" name="search" value="<?= $e($search) ?>" placeholder="Search title, slug, artist">
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
                                    <tr><th>Title / slug</th><th>Categories</th><th>Thumbnail</th><th>Media</th><th>Artist / play time</th><th>Updated by</th><th>Status</th><th>Modified</th><th><span class="visually-hidden">Actions</span></th></tr>
                                </thead>
                                <tbody>
                                    <?php if ($items === []): ?>
                                        <tr><td colspan="9" class="empty-state">No library items found.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($items as $item): ?>
                                            <?php $active = $item['status'] === 'active'; ?>
                                            <tr>
                                                <td><strong class="content-page-title"><?= $e($item['title']) ?></strong><br><small><?= $e($item['slug']) ?></small></td>
                                                <td><?= $e($item['category_names'] ?? '') ?></td>
                                                <td>
                                                    <?php if (!empty($item['thumbnail'])): ?>
                                                        <a href="<?= $e($baseUrl . '/' . $item['thumbnail']) ?>" target="_blank" rel="noopener noreferrer">View thumbnail</a>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="role-badge"><?= $e($item['media_type']) ?></span>
                                                    <?php if (!empty($item['media_file'])): ?>
                                                        <a class="text-action" href="<?= $e($baseUrl . '/' . $item['media_file']) ?>" target="_blank" rel="noopener noreferrer"><?= $e(basename((string) $item['media_file'])) ?></a>
                                                    <?php else: ?>
                                                        <small>Upload a media file</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= $e($item['artist'] ?? '') ?><br><small><?= $e($item['play_time'] ?? '') ?></small></td>
                                                <td><?= $e($item['updated_by_name'] ?? '') ?></td>
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
