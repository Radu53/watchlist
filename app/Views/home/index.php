<h1>Library</h1>

<form method="get" class="filters">
    <input type="text" name="search" placeholder="Search title..." value="<?= htmlspecialchars($filters['search']) ?>">
    <input type="number" name="year" placeholder="Year" value="<?= htmlspecialchars($filters['year']) ?>">

    <select name="type">
        <option value="">All types</option>
        <option value="movie" <?= $filters['type'] === 'movie' ? 'selected' : '' ?>>Movie</option>
        <option value="tv" <?= $filters['type'] === 'tv' ? 'selected' : '' ?>>TV</option>
        <option value="unknown" <?= $filters['type'] === 'unknown' ? 'selected' : '' ?>>Unknown</option>
    </select>

    <select name="status">
        <option value="">All statuses</option>
        <option value="pending" <?= $filters['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
        <option value="started" <?= $filters['status'] === 'started' ? 'selected' : '' ?>>Started</option>
        <option value="watched" <?= $filters['status'] === 'watched' ? 'selected' : '' ?>>Watched</option>
        <option value="finished" <?= $filters['status'] === 'finished' ? 'selected' : '' ?>>Finished</option>
    </select>

    <button type="submit">Filter</button>
</form>

<?php if (empty($items)): ?>
    <p>No items found.</p>
<?php else: ?>
    <div class="grid">
        <?php foreach ($items as $item): ?>
            <article class="card">
                <?php if (!empty($item['cover_url'])): ?>
                    <img src="<?= htmlspecialchars($item['cover_url']) ?>" alt="<?= htmlspecialchars($item['title']) ?>" class="cover">
                <?php endif; ?>

                <h2><?= htmlspecialchars($item['title']) ?></h2>

                <div class="meta">
                    <span><?= htmlspecialchars(strtoupper($item['type'])) ?></span>
                    <?php if (!empty($item['year'])): ?>
                        <span><?= (int)$item['year'] ?></span>
                    <?php endif; ?>
                    <span class="badge"><?= htmlspecialchars($item['status']) ?></span>
                </div>

                <?php if (!empty($item['description'])): ?>
                    <p><?= nl2br(htmlspecialchars($item['description'])) ?></p>
                <?php endif; ?>

                <?php if (!empty($item['watch_url'])): ?>
                    <p><a href="<?= htmlspecialchars($item['watch_url']) ?>" target="_blank" rel="noopener noreferrer">Open watch link</a></p>
                <?php endif; ?>

                <div class="actions">
                    <a class="btn" href="<?= htmlspecialchars(url('/media/edit?id=' . $item['id'])) ?>">Edit</a>

                    <?php if ($item['type'] === 'movie' && $item['status'] === 'pending'): ?>
                        <form method="post" action="<?= htmlspecialchars(url('/media/status')) ?>">
                            <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                            <input type="hidden" name="status" value="watched">
                            <button type="submit">Mark Watched</button>
                        </form>
                    <?php endif; ?>

                    <?php if ($item['type'] === 'tv' && $item['status'] === 'pending'): ?>
                        <form method="post" action="<?= htmlspecialchars(url('/media/status')) ?>">
                            <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                            <input type="hidden" name="status" value="started">
                            <button type="submit">Start</button>
                        </form>
                    <?php endif; ?>

                    <?php if ($item['type'] === 'tv' && $item['status'] === 'started'): ?>
                        <form method="post" action="<?= htmlspecialchars(url('/media/status')) ?>">
                            <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                            <input type="hidden" name="status" value="finished">
                            <button type="submit">Finish</button>
                        </form>
                    <?php endif; ?>

                    <?php if ($item['type'] === 'tv' && $item['status'] === 'finished'): ?>
                        <form method="post" action="<?= htmlspecialchars(url('/media/status')) ?>">
                            <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                            <input type="hidden" name="status" value="started">
                            <button type="submit">Restart</button>
                        </form>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>