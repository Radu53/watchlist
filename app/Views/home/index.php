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
        <option value="none" <?= $filters['status'] === 'none' ? 'selected' : '' ?>>Not watched</option>
        <option value="started" <?= $filters['status'] === 'started' ? 'selected' : '' ?>>Started</option>
        <option value="watched" <?= $filters['status'] === 'watched' ? 'selected' : '' ?>>Watched</option>
    </select>

    <select name="genre">
        <option value="">All genres</option>
        <?php foreach ($allGenres as $genreName): ?>
            <option value="<?= htmlspecialchars($genreName) ?>" <?= $filters['genre'] === $genreName ? 'selected' : '' ?>>
                <?= htmlspecialchars($genreName) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <button type="submit">Filter</button>
</form>

<?php if (empty($items)): ?>
    <p>No items found.</p>
<?php else: ?>
    <div class="poster-grid">
        <?php foreach ($items as $item): ?>
            <?php
                $status = $item['status'] ?? '';
                $type = $item['type'] ?? 'unknown';
                $watchUrl = $item['watch_url'] ?? '';
                $coverUrl = trim((string)($item['cover_url'] ?? ''));
                $title = $item['title'] ?? '';
                $year = $item['year'] ?? null;
                $genres = $item['genres'] ?? [];
            ?>
            <article class="poster-card" data-id="<?= (int)$item['id'] ?>" data-watch-url="<?= htmlspecialchars($watchUrl) ?>">
                <?php if ($coverUrl !== ''): ?>
                    <img src="<?= htmlspecialchars($coverUrl) ?>" alt="<?= htmlspecialchars($title) ?>" class="poster-image">
                <?php else: ?>
                    <div class="poster-fallback">No Cover</div>
                <?php endif; ?>

                <?php if ($status === 'started'): ?>
                    <div class="poster-status started">Started</div>
                <?php elseif ($status === 'watched'): ?>
                    <div class="poster-status watched">Watched</div>
                <?php endif; ?>

                <form method="post" action="<?= htmlspecialchars(url('/media/status')) ?>" class="corner-watch-form">
                    <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                    <input type="hidden" name="status" value="watched">
                    <button type="submit" class="corner-watch-btn" title="Mark as watched">✓</button>
                </form>

                <div class="poster-overlay">
                    <div class="poster-actions">
                        <button
                            type="button"
                            class="btn watch-action-btn"
                            data-id="<?= (int)$item['id'] ?>"
                            data-type="<?= htmlspecialchars($type) ?>"
                        >
                            Watch
                        </button>

                        <a class="btn" href="<?= htmlspecialchars(url('/media/edit?id=' . $item['id'])) ?>">Edit</a>
                    </div>
                </div>

                <div class="poster-title-wrap">
                    <h2 class="poster-title">
                        <?= htmlspecialchars($title) ?>
                        <?php if (!empty($year)): ?>
                            <span>(<?= (int)$year ?>)</span>
                        <?php endif; ?>
                    </h2>

                    <?php if (!empty($genres)): ?>
                        <div class="poster-genres">
                            <?= htmlspecialchars(implode(', ', $genres)) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
document.addEventListener('click', async function (event) {
    const btn = event.target.closest('.watch-action-btn');
    if (!btn) return;

    event.preventDefault();

    const id = btn.dataset.id;
    const card = btn.closest('.poster-card');
    if (!id || !card) return;

    btn.disabled = true;

    try {
        const response = await fetch('<?= htmlspecialchars(url('/media/watch')) ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            body: new URLSearchParams({ id })
        });

        const data = await response.json();

        if (!response.ok || !data.ok) {
            alert(data.message || 'Watch action failed.');
            btn.disabled = false;
            return;
        }

        if (data.watch_url) {
            window.open(data.watch_url, '_blank', 'noopener');
        }

        window.location.reload();
    } catch (error) {
        console.error(error);
        alert('Watch action failed.');
        btn.disabled = false;
    }
});
</script>