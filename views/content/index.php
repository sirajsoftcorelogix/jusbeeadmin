<?php

declare(strict_types=1);

$editing = is_array($editingPage);
$formValues = array_merge($editing ? $editingPage : [], $old);
$formTitle = (string) ($formValues['title'] ?? '');
$formBody = (string) ($formValues['body'] ?? '');
$formStatus = (string) ($formValues['status'] ?? 'draft');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?> · Content</title>
    <link rel="stylesheet" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/css/dashboard.css">
</head>
<body class="dashboard-screen">
    <div class="dashboard-layout">
        <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <span class="bee-logo" aria-hidden="true"></span>
                <span><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <ul class="sidebar-nav">
                <li><a href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/" class="sidebar-link"><span class="nav-icon">📊</span> Dashboard</a></li>
                <li><a href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/users" class="sidebar-link"><span class="nav-icon">👥</span> Users</a></li>
                <li><a href="<?= htmlspecialchars($contentUrl, ENT_QUOTES, 'UTF-8') ?>" class="sidebar-link is-active" aria-current="page"><span class="nav-icon">📄</span> Content</a></li>
                <li><a href="#" class="sidebar-link"><span class="nav-icon">📝</span> Check-ins</a></li>
                <li><a href="#" class="sidebar-link"><span class="nav-icon">🔔</span> Notifications</a></li>
                <li><a href="#" class="sidebar-link"><span class="nav-icon">📈</span> Reports</a></li>
                <li><a href="#" class="sidebar-link"><span class="nav-icon">⚙️</span> Settings</a></li>
            </ul>
        </aside>

        <div class="dashboard-main">
            <header class="topbar">
                <div class="topbar-left">
                    <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle menu">☰</button>
                    <div class="topbar-title">
                        <h1>Content Management</h1>
                        <p>Create and publish pages for your app.</p>
                    </div>
                </div>
                <div class="topbar-right">
                    <form method="POST" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/logout">
                        <?= $csrfField ?>
                        <button type="submit" class="logout-button">Log out</button>
                    </form>
                </div>
            </header>

            <main class="dashboard-content">
                <div class="content-header">
                    <div>
                        <h2>Pages</h2>
                        <p>Write pages, save drafts, and publish when ready.</p>
                    </div>
                    <span class="user-count"><?= $totalPages ?> total</span>
                </div>

                <?php if ($error !== null): ?>
                    <p class="form-error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
                <?php if ($notice !== null): ?>
                    <p class="form-success" role="status"><?= htmlspecialchars($notice, ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>

                <div class="content-management-grid">
                    <section class="panel content-form-panel" aria-labelledby="contentFormTitle">
                        <div class="panel-header">
                            <h2 id="contentFormTitle"><?= $editing ? 'Edit page' : 'New page' ?></h2>
                            <?php if ($editing): ?>
                                <a class="panel-link" href="<?= htmlspecialchars($contentUrl, ENT_QUOTES, 'UTF-8') ?>">Cancel</a>
                            <?php endif; ?>
                        </div>
                        <form method="POST" action="<?= htmlspecialchars($contentUrl, ENT_QUOTES, 'UTF-8') ?><?= $editing ? '/' . (int) $editingPage['id'] . '/update' : '' ?>" class="user-form content-form">
                            <?= $csrfField ?>
                            <label for="title">Page title</label>
                            <input id="title" name="title" type="text" maxlength="160" value="<?= htmlspecialchars($formTitle, ENT_QUOTES, 'UTF-8') ?>" placeholder="e.g. About Jusbee" required>
                            <p class="field-hint">A URL-friendly link is generated from the title.</p>

                            <label for="body">Page content</label>
                            <textarea id="body" name="body" rows="12" maxlength="20000" required><?= htmlspecialchars($formBody, ENT_QUOTES, 'UTF-8') ?></textarea>
                            <p class="field-hint">Plain text only. Line breaks are preserved on the published page.</p>

                            <label for="status">Status</label>
                            <select id="status" name="status" required>
                                <option value="draft"<?= $formStatus === 'draft' ? ' selected' : '' ?>>Draft</option>
                                <option value="published"<?= $formStatus === 'published' ? ' selected' : '' ?>>Published</option>
                            </select>
                            <button type="submit" class="primary-button"><?= $editing ? 'Save changes' : 'Create page' ?></button>
                        </form>
                    </section>

                    <section class="panel content-pages-panel" aria-labelledby="pagesTitle">
                        <div class="panel-header content-pages-header">
                            <div>
                                <h2 id="pagesTitle">Your pages</h2>
                                <p><?= count($pages) ?> of <?= $totalPages ?> pages</p>
                            </div>
                            <form method="GET" action="<?= htmlspecialchars($contentUrl, ENT_QUOTES, 'UTF-8') ?>" class="user-filters">
                                <label class="visually-hidden" for="pageSearch">Search pages</label>
                                <input id="pageSearch" type="search" name="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="Search pages">
                                <label class="visually-hidden" for="pageStatus">Filter by status</label>
                                <select id="pageStatus" name="status">
                                    <option value="all"<?= $status === 'all' ? ' selected' : '' ?>>All statuses</option>
                                    <option value="draft"<?= $status === 'draft' ? ' selected' : '' ?>>Draft</option>
                                    <option value="published"<?= $status === 'published' ? ' selected' : '' ?>>Published</option>
                                </select>
                                <button type="submit" class="secondary-button">Filter</button>
                            </form>
                        </div>
                        <div class="table-wrap">
                            <table class="data-table content-table">
                                <thead>
                                    <tr><th>Page</th><th>Status</th><th>Updated</th><th><span class="visually-hidden">Actions</span></th></tr>
                                </thead>
                                <tbody>
                                    <?php if ($pages === []): ?>
                                        <tr><td colspan="4" class="empty-state">No pages found. Create a page to get started.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($pages as $page): ?>
                                            <?php $published = $page['status'] === 'published'; ?>
                                            <tr>
                                                <td>
                                                    <strong class="content-page-title"><?= htmlspecialchars((string) $page['title'], ENT_QUOTES, 'UTF-8') ?></strong>
                                                    <span class="content-page-slug">/pages/<?= htmlspecialchars((string) $page['slug'], ENT_QUOTES, 'UTF-8') ?></span>
                                                </td>
                                                <td><span class="status-badge <?= $published ? 'is-active' : 'is-inactive' ?>"><?= $published ? 'Published' : 'Draft' ?></span></td>
                                                <td class="content-date"><?= htmlspecialchars(date('M j, Y', strtotime((string) $page['updated_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                                                <td class="user-actions content-actions">
                                                    <a class="text-action" href="<?= htmlspecialchars($contentUrl, ENT_QUOTES, 'UTF-8') ?>?edit=<?= (int) $page['id'] ?>">Edit</a>
                                                    <?php if ($published): ?>
                                                        <a class="text-action" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/pages/<?= rawurlencode((string) $page['slug']) ?>" target="_blank" rel="noopener">View</a>
                                                    <?php endif; ?>
                                                    <form method="POST" action="<?= htmlspecialchars($contentUrl, ENT_QUOTES, 'UTF-8') ?>/<?= (int) $page['id'] ?>/status">
                                                        <?= $csrfField ?>
                                                        <input type="hidden" name="status" value="<?= $published ? 'draft' : 'published' ?>">
                                                        <button type="submit" class="text-action"><?= $published ? 'Unpublish' : 'Publish' ?></button>
                                                    </form>
                                                    <form method="POST" action="<?= htmlspecialchars($contentUrl, ENT_QUOTES, 'UTF-8') ?>/<?= (int) $page['id'] ?>/delete" data-confirm="Delete this page permanently?">
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
    <script src="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/js/dashboard.js"></script>
</body>
</html>
