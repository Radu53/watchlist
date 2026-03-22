<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Watchlist</title>
    <link rel="stylesheet" href="<?= htmlspecialchars(url('/assets/style.css')) ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars(url('/assets/login.css')) ?>">
</head>
<body>
    <header class="topbar">
        <nav>
            <?php if (is_logged_in()): ?>
                <a href="<?= htmlspecialchars(url('/')) ?>">Library</a>
                <a href="<?= htmlspecialchars(url('/media/create')) ?>">Add</a>
                <a href="<?= htmlspecialchars(url('/media/todo')) ?>">TODO</a>
                <a href="<?= htmlspecialchars(url('/history')) ?>">History</a>

                <span class="nav-user">
                    <?= htmlspecialchars(current_username() ?? '') ?>
                </span>

                <form method="post" action="<?= htmlspecialchars(url('/logout')) ?>" class="logout-form">
                    <button type="submit">Logout</button>
                </form>
            <?php endif; ?>
        </nav>
    </header>
    <main class="container">