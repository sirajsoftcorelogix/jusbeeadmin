<?php

declare(strict_types=1);

// Expects $activeNav (dashboard|users|content|library) and optionally $appName.
$navBase = rtrim((string) config('app.url'), '/');
$navName = (string) ($appName ?? config('app.name'));
$navEsc = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$navItems = [
    'dashboard' => ['/', '📊', 'Dashboard'],
    'users' => ['/users', '👥', 'Users'],
    'content' => ['/content', '📄', 'Content'],
    'library' => ['/library', '📚', 'Library'],
];
$navPlaceholders = [['📝', 'Check-ins'], ['🔔', 'Notifications'], ['📈', 'Reports'], ['⚙️', 'Settings']];
?>
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <span class="bee-logo" aria-hidden="true"></span>
                <span><?= $navEsc($navName) ?></span>
            </div>
            <ul class="sidebar-nav">
                <?php foreach ($navItems as $key => [$path, $icon, $label]): ?>
                    <?php $isActive = ($activeNav ?? '') === $key; ?>
                    <li><a href="<?= $navEsc($navBase . $path) ?>" class="sidebar-link<?= $isActive ? ' is-active' : '' ?>"<?= $isActive ? ' aria-current="page"' : '' ?>><span class="nav-icon"><?= $icon ?></span> <?= $label ?></a></li>
                <?php endforeach; ?>
                <?php foreach ($navPlaceholders as [$icon, $label]): ?>
                    <li><a href="#" class="sidebar-link"><span class="nav-icon"><?= $icon ?></span> <?= $label ?></a></li>
                <?php endforeach; ?>
            </ul>
            <?php if (($activeNav ?? '') === 'dashboard'): ?>
                <div class="sidebar-footer">
                    API health: <code>GET /ping</code>
                </div>
            <?php endif; ?>
        </aside>
