<div class="auth-page">
    <div class="auth-card">
        <h1>Login</h1>

        <?php if (!empty($error)): ?>
            <div class="auth-error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= htmlspecialchars(url('/login')) ?>" class="media-form">
            <label>
                Username
                <input type="text" name="username" required autocomplete="username">
            </label>

            <label>
                Password
                <input type="password" name="password" required autocomplete="current-password">
            </label>

            <button type="submit">Login</button>
        </form>
    </div>
</div>