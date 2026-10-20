<?php

declare(strict_types=1);

$editing = is_array($editingUser);
$formValues = array_merge($editing ? $editingUser : [], $old);
$formStatus = (string) ($formValues['status'] ?? 'active');
$e = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?><!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $e($appName) ?> · App Users</title>
    <link rel="stylesheet" href="<?= $e($baseUrl) ?>/assets/css/dashboard.css">
</head>
<body class="dashboard-screen">
    <div class="dashboard-layout">
        <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
        <?php $activeNav = 'app-users'; require dirname(__DIR__) . '/partials/sidebar.php'; ?>

        <div class="dashboard-main">
            <header class="topbar">
                <div class="topbar-left">
                    <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle menu">☰</button>
                    <div class="topbar-title">
                        <h1>App Users</h1>
                        <p>Manage users who register through the API.</p>
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
                        <h2>App users</h2>
                        <p>Accounts created through the registration API.</p>
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
                    <section class="panel content-form-panel" aria-labelledby="appUserFormTitle">
                        <div class="panel-header">
                            <h2 id="appUserFormTitle"><?= $editing ? 'Edit app user' : 'New app user' ?></h2>
                            <?php if ($editing): ?>
                                <a class="panel-link" href="<?= $e($appUsersUrl) ?>">Cancel</a>
                            <?php endif; ?>
                        </div>
                        <form method="POST" action="<?= $e($appUsersUrl) ?><?= $editing ? '/' . (int) $editingUser['id'] . '/update' : '' ?>" class="user-form content-form" autocomplete="off">
                            <?= $csrfField ?>
                            <label for="name">Name</label>
                            <input id="name" name="name" type="text" maxlength="120" value="<?= $e($formValues['name'] ?? '') ?>" required>

                            <label for="email">Email</label>
                            <input id="email" name="email" type="email" maxlength="190" value="<?= $e($formValues['email'] ?? '') ?>" required>

                            <label for="phone">Phone</label>
                            <input id="phone" name="phone" type="tel" maxlength="20" value="<?= $e($formValues['phone'] ?? '') ?>" required>

                            <label for="password">Password</label>
                            <input id="password" name="password" type="password" minlength="8" maxlength="72" autocomplete="new-password" <?= $editing ? '' : 'required' ?>>
                            <p class="field-hint"><?= $editing ? 'Leave blank to keep the current password. Changing it signs the user out of the app.' : 'At least 8 characters.' ?></p>

                            <label for="status">Status</label>
                            <select id="status" name="status" required>
                                <option value="active"<?= $formStatus === 'active' ? ' selected' : '' ?>>Active</option>
                                <option value="inactive"<?= $formStatus === 'inactive' ? ' selected' : '' ?>>Inactive</option>
                            </select>
                            <button type="submit" class="primary-button"><?= $editing ? 'Save changes' : 'Create app user' ?></button>
                        </form>
                    </section>

                    <section class="panel content-pages-panel" aria-labelledby="appUserListTitle">
                        <div class="panel-header content-pages-header">
                            <div>
                                <h2 id="appUserListTitle">All app users</h2>
                                <p><?= count($items) ?> of <?= (int) $totalItems ?> users</p>
                            </div>
                            <form method="GET" action="<?= $e($appUsersUrl) ?>" class="user-filters">
                                <label class="visually-hidden" for="appUserSearch">Search app users</label>
                                <input id="appUserSearch" type="search" name="search" value="<?= $e($search) ?>" placeholder="Name, email or phone">
                                <label class="visually-hidden" for="appUserStatus">Filter by status</label>
                                <select id="appUserStatus" name="status">
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
                                    <tr><th>Name</th><th>Contact</th><th>Status</th><th>Last login</th><th><span class="visually-hidden">Actions</span></th></tr>
                                </thead>
                                <tbody>
                                    <?php if ($items === []): ?>
                                        <tr><td colspan="5" class="empty-state">No app users found.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($items as $item): ?>
                                            <?php $active = $item['status'] === 'active'; ?>
                                            <tr>
                                                <td><strong class="content-page-title"><?= $e($item['name']) ?></strong></td>
                                                <td><?= $e($item['email']) ?><br><small><?= $e($item['phone']) ?></small></td>
                                                <td><span class="status-badge <?= $active ? 'is-active' : 'is-inactive' ?>"><?= $active ? 'Active' : 'Inactive' ?></span></td>
                                                <td class="content-date"><?= $item['last_login_at'] ? $e(date('M j, Y', strtotime((string) $item['last_login_at']))) : 'Never' ?></td>
                                                <td class="user-actions content-actions">
                                                    <a class="text-action" href="<?= $e($appUsersUrl) ?>?edit=<?= (int) $item['id'] ?>">Edit</a>
                                                    <form method="POST" action="<?= $e($appUsersUrl) ?>/<?= (int) $item['id'] ?>/status">
                                                        <?= $csrfField ?>
                                                        <button type="submit" class="text-action"><?= $active ? 'Deactivate' : 'Activate' ?></button>
                                                    </form>
                                                    <form method="POST" action="<?= $e($appUsersUrl) ?>/<?= (int) $item['id'] ?>/delete" data-confirm="Delete this app user permanently?">
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
