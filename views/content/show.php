<?php

declare(strict_types=1);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars((string) $page['title'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/assets/css/dashboard.css">
</head>
<body class="dashboard-screen public-content-screen">
    <main class="public-content">
        <a class="public-content-brand" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/">
            <span class="bee-logo" aria-hidden="true"></span>
            <span><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></span>
        </a>
        <article class="panel public-content-panel">
            <p class="eyebrow">JUSBEE · INFORMATION</p>
            <h1><?= htmlspecialchars((string) $page['title'], ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="public-content-date">Updated <?= htmlspecialchars(date('F j, Y', strtotime((string) $page['updated_at'])), ENT_QUOTES, 'UTF-8') ?></p>
            <div class="public-content-body"><?= nl2br(htmlspecialchars((string) $page['body'], ENT_QUOTES, 'UTF-8')) ?></div>
            <a class="public-content-back" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>/login">Admin sign in</a>
        </article>
    </main>
</body>
</html>
