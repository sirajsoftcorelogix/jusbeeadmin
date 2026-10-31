<?php

declare(strict_types=1);

$editing = is_array($editingCounsellor);
$formValues = array_merge($editing ? $editingCounsellor : [], $old);
$e = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $e($appName) ?> · Counsellors</title>
    <link rel="stylesheet" href="<?= $e($baseUrl) ?>/assets/css/dashboard.css">
</head>
<body class="dashboard-screen">
    <div class="dashboard-layout">
        <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
        <?php $activeNav = 'counsellors'; require dirname(__DIR__) . '/partials/sidebar.php'; ?>
        <div class="dashboard-main">
            <header class="topbar">
                <div class="topbar-left">
                    <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle menu">☰</button>
                    <div class="topbar-title"><h1>Counsellors</h1><p>Add and manage counsellor profiles.</p></div>
                </div>
                <div class="topbar-right">
                    <form method="POST" action="<?= $e($baseUrl) ?>/logout">
                        <?= $csrfField ?>
                        <button type="submit" class="logout-button">Log out</button>
                    </form>
                </div>
            </header>
            <main class="dashboard-content">
                <div class="content-header"><div><h2>Counsellors</h2><p><?= $totalItems ?> profiles</p></div></div>
                <?php if ($error !== null): ?><p class="form-error" role="alert"><?= $e($error) ?></p><?php endif; ?>
                <?php if ($notice !== null): ?><p class="form-success" role="status"><?= $e($notice) ?></p><?php endif; ?>
                <div class="content-management-grid">
                    <section class="panel content-form-panel">
                        <div class="panel-header">
                            <h2><?= $editing ? 'Edit counsellor' : 'New counsellor' ?></h2>
                            <?php if ($editing): ?><a class="panel-link" href="<?= $e($counsellorsUrl) ?>">Cancel</a><?php endif; ?>
                        </div>
                        <form method="POST" action="<?= $e($counsellorsUrl) ?><?= $editing ? '/' . (int) $editingCounsellor['id'] . '/update' : '' ?>" class="user-form content-form" enctype="multipart/form-data">
                            <?= $csrfField ?>
                            <label for="consellor_name">Counsellor name</label>
                            <input id="consellor_name" name="consellor_name" type="text" maxlength="255" value="<?= $e($formValues['consellor_name'] ?? '') ?>" required>

                            <label for="profile_thumbnail">Profile thumbnail</label>
                            <input id="profile_thumbnail" name="profile_thumbnail" type="file" accept=".webp,.jpg,.jpeg,image/webp,image/jpeg">
                            <p class="field-hint">WebP or JPEG; max 100 MB.</p>
                            <?php if ($editing && !empty($editingCounsellor['profile_thumbnail'])): ?>
                                <p class="field-hint">
                                    Current: <a href="<?= $e($baseUrl . '/' . $editingCounsellor['profile_thumbnail']) ?>" target="_blank" rel="noopener noreferrer"><?= $e(basename((string) $editingCounsellor['profile_thumbnail'])) ?></a>
                                    <label><input type="checkbox" name="remove_profile_thumbnail" value="1"> Remove</label>
                                </p>
                            <?php endif; ?>

                            <label for="qualifications">Qualifications</label>
                            <textarea id="qualifications" name="qualifications" rows="3"><?= $e($formValues['qualifications'] ?? '') ?></textarea>

                            <label for="email">Email</label>
                            <input id="email" name="email" type="email" maxlength="190" value="<?= $e($formValues['email'] ?? '') ?>">

                            <label for="phone">Phone</label>
                            <input id="phone" name="phone" type="tel" maxlength="30" value="<?= $e($formValues['phone'] ?? '') ?>">

                            <label for="verifies">Verified</label>
                            <select id="verifies" name="verifies" required>
                                <option value="N"<?= ($formValues['verifies'] ?? 'N') === 'N' ? ' selected' : '' ?>>No</option>
                                <option value="Y"<?= ($formValues['verifies'] ?? 'N') === 'Y' ? ' selected' : '' ?>>Yes</option>
                            </select>

                            <label for="biography">Biography</label>
                            <textarea id="biography" name="biography" rows="5"><?= $e($formValues['biography'] ?? '') ?></textarea>

                            <label for="expertise">Expertise</label>
                            <textarea id="expertise" name="expertise" rows="3"><?= $e($formValues['expertise'] ?? '') ?></textarea>

                            <label for="standard_fee">Standard fee</label>
                            <input id="standard_fee" name="standard_fee" type="number" min="0" max="99999999.99" step="0.01" value="<?= $e($formValues['standard_fee'] ?? '0.00') ?>" required>

                            <label for="discovery_fee">Discovery fee</label>
                            <input id="discovery_fee" name="discovery_fee" type="number" min="0" max="99999999.99" step="0.01" value="<?= $e($formValues['discovery_fee'] ?? '0.00') ?>" required>

                            <button type="submit" class="primary-button"><?= $editing ? 'Save changes' : 'Create counsellor' ?></button>
                        </form>
                    </section>
                    <section class="panel content-pages-panel">
                        <div class="panel-header content-pages-header">
                            <div><h2>All counsellors</h2><p>Search by name, email or expertise.</p></div>
                            <form method="GET" action="<?= $e($counsellorsUrl) ?>" class="user-filters">
                                <label class="visually-hidden" for="counsellorSearch">Search counsellors</label>
                                <input id="counsellorSearch" type="search" name="search" value="<?= $e($search) ?>" placeholder="Search counsellors">
                                <button type="submit" class="secondary-button">Search</button>
                            </form>
                        </div>
                        <div class="table-wrap">
                            <table class="data-table content-table">
                                <thead><tr><th>Counsellor</th><th>Contact</th><th>Verified</th><th>Fees</th><th>Updated by</th><th>Updated</th><th><span class="visually-hidden">Actions</span></th></tr></thead>
                                <tbody>
                                <?php if ($items === []): ?>
                                    <tr><td colspan="7" class="empty-state">No counsellors found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($items as $item): ?>
                                        <tr>
                                            <td>
                                                <strong><?= $e($item['consellor_name']) ?></strong>
                                                <?php if (!empty($item['profile_thumbnail'])): ?><br><a href="<?= $e($baseUrl . '/' . $item['profile_thumbnail']) ?>" target="_blank" rel="noopener noreferrer">Profile image</a><?php endif; ?>
                                            </td>
                                            <td><?= $e($item['email'] ?? '') ?><br><?= $e($item['phone'] ?? '') ?></td>
                                            <td><?= $item['verifies'] === 'Y' ? 'Yes' : 'No' ?></td>
                                            <td>Standard: <?= $e($item['standard_fee']) ?><br>Discovery: <?= $e($item['discovery_fee']) ?></td>
                                            <td><?= $e($item['updated_by_name'] ?? '') ?></td>
                                            <td><?= $e(date('M j, Y', strtotime((string) $item['updated_date_time']))) ?></td>
                                            <td class="user-actions content-actions">
                                                <a class="text-action" href="<?= $e($counsellorsUrl) ?>?edit=<?= (int) $item['id'] ?>">Edit</a>
                                                <form method="POST" action="<?= $e($counsellorsUrl) ?>/<?= (int) $item['id'] ?>/delete" onsubmit="return confirm('Delete this counsellor?')">
                                                    <?= $csrfField ?>
                                                    <button type="submit" class="text-action danger-action">Delete</button>
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
