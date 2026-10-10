<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>419</title>
</head>
<body>
    <h1>419 - Invalid Session Token</h1>
    <p><?= htmlspecialchars($errorMessage ?? 'Invalid CSRF token', ENT_QUOTES, 'UTF-8') ?></p>
</body>
</html>
