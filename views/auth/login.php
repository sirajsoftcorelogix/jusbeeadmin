<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($appName ?? 'MVC', ENT_QUOTES, 'UTF-8') ?> · Admin Login</title>
    <link rel="stylesheet" href="<?= htmlspecialchars($baseUrl ?? '', ENT_QUOTES, 'UTF-8') ?>/assets/css/login.css">
</head>
<body class="login-screen">
    <main class="login-shell">
        <section class="login-showcase" aria-label="Jusbee admin portal">
            <a class="showcase-brand" href="<?= htmlspecialchars($baseUrl ?? '', ENT_QUOTES, 'UTF-8') ?>/login">
                <img src="<?= htmlspecialchars($baseUrl ?? '', ENT_QUOTES, 'UTF-8') ?>/assets/images/honey-bee-logo.png" alt="">
                <span><?= htmlspecialchars($appName ?? 'MVC', ENT_QUOTES, 'UTF-8') ?></span>
            </a>

            <div class="showcase-copy">
                <p class="eyebrow">JUSBEE ADMIN PORTAL</p>
                <h1>Make room for a little more well-being.</h1>
                <p>A thoughtful space to support your team, keep an eye on activity, and help your community thrive.</p>
            </div>

            <div class="showcase-visual" aria-hidden="true">
                <div class="honeycomb">
                    <i></i><i></i><i></i><i></i><i></i><i></i><i></i>
                </div>
                <div class="bee-orbit orbit-one"></div>
                <div class="bee-orbit orbit-two"></div>
                <div class="bee-halo"></div>
                <div class="bee-hero"></div>
            </div>
        </section>

        <section class="login-panel">
            <div class="login-form-wrap">
                <a class="login-brand" href="<?= htmlspecialchars($baseUrl ?? '', ENT_QUOTES, 'UTF-8') ?>/login">
                    <span class="bee-logo" aria-hidden="true"></span>
                    <span><?= htmlspecialchars($appName ?? 'MVC', ENT_QUOTES, 'UTF-8') ?></span>
                </a>

                <div class="login-heading">
                    <p class="eyebrow">WELCOME BACK</p>
                    <h2>Sign in to your account</h2>
                    <p>Enter your administrator credentials to continue.</p>
                </div>

                <?php if (isset($error)): ?>
                    <p class="login-error" id="loginError" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>

                <form method="POST" action="<?= htmlspecialchars($baseUrl ?? '', ENT_QUOTES, 'UTF-8') ?>/login" class="login-form">
                    <?= $csrfField ?? '' ?>

                    <label for="email">Email address</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="<?= htmlspecialchars($email ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        autocomplete="email"
                        placeholder="name@company.com"
                        maxlength="191"
                        <?= isset($error) ? 'aria-invalid="true" aria-describedby="loginError"' : '' ?>
                        required
                        autofocus
                    >

                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" placeholder="Enter your password" <?= isset($error) ? 'aria-invalid="true" aria-describedby="loginError"' : '' ?> required>

                    <button type="submit">Sign in <span aria-hidden="true">→</span></button>
                </form>

                <p class="login-help">Having trouble signing in? Contact your system administrator.</p>
            </div>
            <p class="login-footer">A safe space to help your team flourish.</p>
        </section>
    </main>
</body>
</html>
