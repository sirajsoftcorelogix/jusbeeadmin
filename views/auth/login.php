<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($appName ?? 'MVC', ENT_QUOTES, 'UTF-8') ?> · Admin Login</title>
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body class="login-screen">
    <main class="login-shell">
        <section class="login-showcase" aria-label="Jusbee admin portal">
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
                <a class="login-brand" href="./">
                    <span><?= htmlspecialchars($appName ?? 'MVC', ENT_QUOTES, 'UTF-8') ?></span>
                </a>

                <div class="login-heading">
                    <p class="eyebrow">WELCOME BACK</p>
                    <h2>Sign in to your account</h2>
                    <p>Enter your details to access the admin dashboard.</p>
                </div>

                <?php if (isset($error)): ?>
                    <p class="login-error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>

                <form method="POST" action="login" class="login-form">
                    <?= $csrfField ?? '' ?>

                    <label for="email">Email address</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="<?= htmlspecialchars($email ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        autocomplete="username"
                        placeholder="name@company.com"
                        required
                        autofocus
                    >

                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" placeholder="Enter your password" required>

                    <button type="submit">Sign in <span aria-hidden="true">→</span></button>
                </form>

                <p class="login-help">Need assistance? <a href="mailto:support@jusbee.local">Contact support</a></p>
            </div>
        </section>
    </main>
</body>
</html>
