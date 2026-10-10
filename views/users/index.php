<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?> · Users</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
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
                <li><a href="./" class="sidebar-link"><span class="nav-icon">📊</span> Dashboard</a></li>
                <li><a href="users" class="sidebar-link is-active"><span class="nav-icon">👥</span> Users</a></li>
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
                        <p>Create and manage administrator access.</p>
                    </div>
                </div>
                <div class="topbar-right">
                    <form method="POST" action="logout">
                        <input type="hidden" name="_token" value="<?= htmlspecialchars(app(\App\Core\Csrf::class)->token(), ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit" class="logout-button">Log out</button>
                    </form>
                </div>
            </header>

            <main class="dashboard-content">
                <div class="user-management-grid">
                    <section class="panel user-form-panel">
                        <div class="panel-header">
                            <h2>Add user</h2>
                        </div>
                        <?php if ($error !== null): ?>
                            <p class="form-error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>
                        <form method="POST" action="users" class="user-form">
                            <?= $csrfField ?>
                            <label for="name">Full name</label>
                            <input id="name" name="name" type="text" value="<?= htmlspecialchars((string) ($old['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                            <label for="email">Email address</label>
                            <input id="email" name="email" type="email" value="<?= htmlspecialchars((string) ($old['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                            <label for="password">Temporary password</label>
                            <input id="password" name="password" type="password" minlength="8" required>
                            <label for="role">Role</label>
                            <select id="role" name="role" required>
                                <?php foreach ($roles as $value => $label): ?>
                                    <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>"<?= ($old['role'] ?? 'operations') === $value ? ' selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="primary-button">Create user</button>
                        </form>
                    </section>

                    <section class="panel">
                        <div class="panel-header">
                            <h2>Users</h2>
                            <span class="user-count"><?= count($users) ?> total</span>
                        </div>
                        <div class="table-wrap">
                            <table class="data-table">
                                <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th></tr></thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?= htmlspecialchars((string) $user['name'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars((string) $user['email'], ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><span class="role-badge"><?= htmlspecialchars($roles[$user['role']] ?? (string) $user['role'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                            <td><span class="status-active">Active</span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            </main>
        </div>
    </div>
    <script src="assets/js/dashboard.js"></script>
</body>
</html>
