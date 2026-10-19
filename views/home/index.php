<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($appName ?? 'MVC', ENT_QUOTES, 'UTF-8') ?> · Admin Dashboard</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body class="dashboard-screen">
    <div class="dashboard-layout">
        <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

        <?php $activeNav = 'dashboard'; require dirname(__DIR__) . '/partials/sidebar.php'; ?>

        <div class="dashboard-main">
            <header class="topbar">
                <div class="topbar-left">
                    <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle menu">☰</button>
                    <div class="topbar-title">
                        <h1>Dashboard</h1>
                        <p>Overview of mobile app activity</p>
                    </div>
                </div>

                <div class="topbar-right">
                    <span class="status-pill"><span class="status-dot"></span> API online</span>
                    <div class="avatar">A</div>
                    <form method="POST" action="logout">
                        <input type="hidden" name="_token" value="<?= htmlspecialchars(app(\App\Core\Csrf::class)->token(), ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit" class="logout-button">Log out</button>
                    </form>
                </div>
            </header>

            <main class="dashboard-content">
                <div class="content-header">
                    <h2>Welcome back, Admin</h2>
                    <p>Here's what's happening with the Jusbee app today.</p>
                </div>

                <section class="stat-grid" aria-label="Key metrics">
                    <div class="stat-card">
                        <div class="stat-icon">👥</div>
                        <div>
                            <p class="stat-value">--</p>
                            <p class="stat-label">Total Users</p>
                            <p class="stat-trend is-up">&nbsp;</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon is-success">📝</div>
                        <div>
                            <p class="stat-value">--</p>
                            <p class="stat-label">Check-ins Today</p>
                            <p class="stat-trend is-up">&nbsp;</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon is-warning">🔥</div>
                        <div>
                            <p class="stat-value">--</p>
                            <p class="stat-label">Active Streaks</p>
                            <p class="stat-trend is-down">&nbsp;</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon is-danger">⚠️</div>
                        <div>
                            <p class="stat-value">--</p>
                            <p class="stat-label">Flagged Moods</p>
                            <p class="stat-trend">&nbsp;</p>
                        </div>
                    </div>
                </section>

                <section class="widget-grid">
                    <div class="panel">
                        <div class="panel-header">
                            <h3>Recent Check-ins</h3>
                            <a href="#" class="panel-link">View all</a>
                        </div>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Mood</th>
                                    <th>Submitted</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="3" class="placeholder-note">No check-in data connected yet.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="panel">
                        <div class="panel-header">
                            <h3>Recent Activity</h3>
                        </div>
                        <ul class="activity-list">
                            <li>
                                <span class="activity-dot"></span>
                                <div>
                                    <p class="activity-text placeholder-note">Activity feed will appear here once connected.</p>
                                </div>
                            </li>
                        </ul>
                    </div>
                </section>
            </main>
        </div>
    </div>

    <script src="assets/js/dashboard.js"></script>
</body>
</html>
