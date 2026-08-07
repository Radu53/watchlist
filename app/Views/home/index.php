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

<?php if (!empty($suggestions)): ?>
    <section class="suggestions-block">
        <div class="suggestions-header">
            <h2>Suggestions</h2>
            <a class="btn" href="<?= htmlspecialchars(url('/?refresh=1')) ?>">Refresh</a>
        </div>
        <div class="poster-grid">
            <?php foreach ($suggestions as $suggestion): ?>
                <?php $coverUrl = trim((string)($suggestion['cover_url'] ?? '')); ?>
                <article class="poster-card">
                    <?php if ($coverUrl !== ''): ?>
                        <img src="<?= htmlspecialchars($coverUrl) ?>" alt="<?= htmlspecialchars($suggestion['title'] ?? '') ?>" class="poster-image">
                    <?php else: ?>
                        <div class="poster-fallback">No Cover</div>
                    <?php endif; ?>
                    <div class="poster-title-wrap">
                        <h3 class="poster-title"><?= htmlspecialchars($suggestion['title'] ?? '') ?></h3>
                        <p class="poster-rating">Avg rating: <?= number_format((float)($suggestion['avg_rating'] ?? 0), 1) ?>/5</p>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<?php if (empty($items)): ?>
    <p>No items found.</p>
<?php else: ?>
    <div class="poster-grid">
        <?php foreach ($items as $item): ?>
            <?php
                $status = $item['user_status'] ?? '';
                $type = $item['type'] ?? 'unknown';
                $watchUrl = $item['watch_url'] ?? '';
                $coverUrl = trim((string)($item['cover_url'] ?? ''));
                $title = $item['title'] ?? '';
                $year = $item['year'] ?? null;
                $genres = $item['genres'] ?? [];
                $avgRating = (float)($item['avg_rating'] ?? 0);
                $userRating = (int)($item['user_rating'] ?? 0);
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

                    <div class="poster-meta">
                        <form method="post" action="<?= htmlspecialchars(url('/media/status')) ?>" class="status-form">
                            <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                            <select name="status" class="status-select">
                                <option value="none" <?= $status === '' ? 'selected' : '' ?>>None</option>
                                <option value="started" <?= $status === 'started' ? 'selected' : '' ?>>Started</option>
                                <option value="watched" <?= $status === 'watched' ? 'selected' : '' ?>>Watched</option>
                            </select>
                        </form>

                        <select class="rating-select" data-id="<?= (int)$item['id'] ?>">
                            <option value="0" <?= $userRating === 0 ? 'selected' : '' ?>>No rating</option>
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <option value="<?= $i ?>" <?= $userRating === $i ? 'selected' : '' ?>><?= $i ?>★</option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="poster-rating-line">Average: <?= number_format($avgRating, 1) ?>/5</div>

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

document.addEventListener('change', async function (event) {
    const statusSelect = event.target.closest('.status-select');
    if (statusSelect) {
        const form = statusSelect.closest('.status-form');
        const formData = new FormData(form);
        const params = new URLSearchParams(formData);

        try {
            const response = await fetch('<?= htmlspecialchars(url('/media/status')) ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                },
                body: params.toString()
            });

            if (!response.ok) {
                alert('Status update failed.');
                return;
            }

            window.location.reload();
        } catch (error) {
            console.error(error);
            alert('Status update failed.');
        }
    }

    const ratingSelect = event.target.closest('.rating-select');
    if (ratingSelect) {
        const id = ratingSelect.dataset.id;
        const rating = ratingSelect.value;
        if (!id || rating === '0') return;

        try {
            const response = await fetch('<?= htmlspecialchars(url('/media/rate')) ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                },
                body: new URLSearchParams({ id, rating })
            });

            const data = await response.json();
            if (!response.ok || !data.ok) {
                alert(data.message || 'Rating update failed.');
                return;
            }

            window.location.reload();
        } catch (error) {
            console.error(error);
            alert('Rating update failed.');
        }
    }
});
</script>