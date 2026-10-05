<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Login — Gudang Kita</title>
    <link rel="icon" type="image/svg+xml" href="assets/images/gudang-kita.svg">
    <link rel="stylesheet" href="assets/css/style.css?v=login-3">
</head>
<body class="login-page">
    <main class="login-card login-card-expanded">
        <div class="login-logo">📦</div>
        <h1>Login</h1>
        <p class="login-description">Masuk menggunakan akun yang sudah dibuat.</p>
        <?php $error = getFlash('error'); ?>
        <?php if ($error): ?>
            <div class="alert danger"><?= e($error) ?></div>
        <?php endif; ?>
        <form method="post" action="?page=login">
            <?= \App\Security\CsrfToken::field() ?>
            <label for="email">Email
                <input id="email" required type="email" name="email" autocomplete="email" placeholder="Contoh: admin@example.test">
            </label>
            <label for="password">Password
                <span class="password-field">
                    <input id="password" required type="password" name="password" autocomplete="current-password" placeholder="Masukkan password">
                    <button class="password-toggle" type="button" aria-label="Lihat password">Lihat</button>
                </span>
            </label>
            <button class="btn btn-primary full" type="submit">Masuk</button>
        </form>
    </main>
    <script>
        const password = document.getElementById('password');
        const toggle = document.querySelector('.password-toggle');
        toggle.addEventListener('click', () => {
            const visible = password.type === 'text';
            password.type = visible ? 'password' : 'text';
            toggle.textContent = visible ? 'Lihat' : 'Sembunyikan';
        });
    </script>
</body>
</html>
