<?php

declare(strict_types=1);

$editing = is_array($editingUser);
$formValues = array_merge($editing ? $editingUser : [], $old);
$formName = (string) ($formValues['name'] ?? '');
$formEmail = (string) ($formValues['email'] ?? '');
$formRole = (string) ($formValues['role'] ?? 'operations');
$protectAdminRole = $editing
    && $editingUser['role'] === 'admin'
    && (int) $editingUser['is_active'] === 1
    && $activeAdminCount <= 1;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?> · Users</title>
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
                <li><a href="<?= htmlspecialchars($usersUrl, ENT_QUOTES, 'UTF-8') ?>" class="sidebar-link is-active" aria-current="page"><span class="nav-icon">👥</span> Users</a></li>
                <li><a href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/content" class="sidebar-link"><span class="nav-icon">📄</span> Content</a></li>
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
                        <h1>User Management</h1>
                        <p>Create accounts and manage administrator access.</p>
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
                        <h2>Users</h2>
                        <p>Manage team access and account status.</p>
                    </div>
                    <span class="user-count"><?= $totalUsers ?> total</span>
                </div>

                <?php if ($error !== null): ?>
                    <p class="form-error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
                <?php if ($notice !== null): ?>
                    <p class="form-success" role="status"><?= htmlspecialchars($notice, ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>

                <div class="user-management-grid">
                    <section class="panel user-form-panel" aria-labelledby="userFormTitle">
                        <div class="panel-header">
                            <h2 id="userFormTitle"><?= $editing ? 'Edit user' : 'Add user' ?></h2>
                            <?php if ($editing): ?>
                                <a class="panel-link" href="<?= htmlspecialchars($usersUrl, ENT_QUOTES, 'UTF-8') ?>">Cancel</a>
                            <?php endif; ?>
                        </div>
                        <form method="POST" action="<?= htmlspecialchars($usersUrl, ENT_QUOTES, 'UTF-8') ?><?= $editing ? '/' . (int) $editingUser['id'] . '/update' : '' ?>" class="user-form">
                            <?= $csrfField ?>
                            <label for="name">Full name</label>
                            <input id="name" name="name" type="text" maxlength="100" autocomplete="name" value="<?= htmlspecialchars($formName, ENT_QUOTES, 'UTF-8') ?>" required>

                            <label for="email">Email address</label>
                            <input id="email" name="email" type="email" maxlength="191" autocomplete="email" value="<?= htmlspecialchars($formEmail, ENT_QUOTES, 'UTF-8') ?>" required>

                            <label for="password"><?= $editing ? 'New password' : 'Temporary password' ?></label>
                            <input id="password" name="password" type="password" minlength="8" maxlength="255" autocomplete="new-password" <?= $editing ? '' : 'required' ?> aria-describedby="passwordHint">
                            <p class="field-hint" id="passwordHint"><?= $editing ? 'Leave blank to keep the current password.' : 'Use at least 8 characters.' ?></p>

                            <label for="role">Role</label>
                            <?php if ($protectAdminRole): ?>
                                <input type="hidden" name="role" value="admin">
                                <input id="role" type="text" value="Admin (last active administrator)" disabled>
                            <?php else: ?>
                                <select id="role" name="role" required>
                                    <?php foreach ($roles as $value => $label): ?>
                                        <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>"<?= $formRole === $value ? ' selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>

                            <button type="submit" class="primary-button"><?= $editing ? 'Save changes' : 'Create user' ?></button>
                        </form>
                    </section>

                    <section class="panel users-panel" aria-labelledby="usersTitle">
                        <div class="panel-header users-panel-header">
                            <div>
                                <h2 id="usersTitle">Team members</h2>
                                <p><?= count($users) ?> of <?= $totalUsers ?> accounts</p>
                            </div>
                            <form method="GET" action="<?= htmlspecialchars($usersUrl, ENT_QUOTES, 'UTF-8') ?>" class="user-filters">
                                <label class="visually-hidden" for="userSearch">Search users</label>
                                <input id="userSearch" type="search" name="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="Search name or email">
                                <label class="visually-hidden" for="userStatus">Filter by status</label>
                                <select id="userStatus" name="status">
                                    <option value="all"<?= $status === 'all' ? ' selected' : '' ?>>All statuses</option>
                                    <option value="active"<?= $status === 'active' ? ' selected' : '' ?>>Active</option>
                                    <option value="inactive"<?= $status === 'inactive' ? ' selected' : '' ?>>Inactive</option>
                                </select>
                                <button type="submit" class="secondary-button">Filter</button>
                            </form>
                        </div>
                        <div class="table-wrap">
                            <table class="data-table users-table">
                                <thead>
                                    <tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th><span class="visually-hidden">Actions</span></th></tr>
                                </thead>
                                <tbody>
                                    <?php if ($users === []): ?>
                                        <tr><td colspan="5" class="empty-state">No users match these filters.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($users as $user): ?>
                                            <?php $isActive = (int) $user['is_active'] === 1; ?>
                                            <tr>
                                                <td class="user-name-cell"><?= htmlspecialchars((string) $user['name'], ENT_QUOTES, 'UTF-8') ?></td>
                                                <td class="user-email-cell"><?= htmlspecialchars((string) $user['email'], ENT_QUOTES, 'UTF-8') ?></td>
                                                <td><span class="role-badge"><?= htmlspecialchars($roles[$user['role']] ?? (string) $user['role'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                                <td><span class="status-badge<?= $isActive ? ' is-active' : ' is-inactive' ?>"><?= $isActive ? 'Active' : 'Inactive' ?></span></td>
                                                <td class="user-actions">
                                                    <a class="text-action" href="<?= htmlspecialchars($usersUrl, ENT_QUOTES, 'UTF-8') ?>?edit=<?= (int) $user['id'] ?>">Edit</a>
                                                    <?php if ((int) $user['id'] === $currentUserId): ?>
                                                        <span class="protected-action">You</span>
                                                    <?php elseif ($isActive && $user['role'] === 'admin' && $activeAdminCount <= 1): ?>
                                                        <span class="protected-action" title="At least one active administrator must remain">Protected</span>
                                                    <?php else: ?>
                                                        <form method="POST" action="<?= htmlspecialchars($usersUrl, ENT_QUOTES, 'UTF-8') ?>/<?= (int) $user['id'] ?>/status" data-confirm="<?= $isActive ? 'Deactivate this account? The user will no longer be able to sign in.' : 'Activate this account?' ?>">
                                                            <?= $csrfField ?>
                                                            <input type="hidden" name="is_active" value="<?= $isActive ? '0' : '1' ?>">
                                                            <button type="submit" class="text-action<?= $isActive ? ' is-danger' : '' ?>"><?= $isActive ? 'Deactivate' : 'Activate' ?></button>
                                                        </form>
                                                    <?php endif; ?>
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
