<?php if (!empty($suggestions)): ?>
    <section class="suggestions-block" data-suggestions-block>
        <div class="suggestions-header">
            <div>
                <h2>Suggestions</h2>
                <p class="suggestions-subtitle">A fresh mix of titles, progress, and recent picks.</p>
            </div>
            <button type="button" class="btn suggestions-refresh-btn" data-refresh-url="<?= htmlspecialchars(url('/suggestions')) ?>">Refresh</button>
        </div>
        <div class="poster-grid">
            <?php foreach ($suggestions as $suggestion): ?>
                <?php
                    $coverUrl = trim((string)($suggestion['cover_url'] ?? ''));
                    $watchUrl = trim((string)($suggestion['watch_url'] ?? ''));
                    $type = trim((string)($suggestion['type'] ?? 'unknown'));
                ?>
                <article class="poster-card" data-id="<?= (int)($suggestion['id'] ?? 0) ?>" data-watch-url="<?= htmlspecialchars($watchUrl) ?>">
                    <?php if ($coverUrl !== ''): ?>
                        <img src="<?= htmlspecialchars($coverUrl) ?>" alt="<?= htmlspecialchars($suggestion['title'] ?? '') ?>" class="poster-image">
                    <?php else: ?>
                        <div class="poster-fallback">No Cover</div>
                    <?php endif; ?>

                    <div class="poster-overlay">
                        <div class="poster-actions">
                            <button
                                type="button"
                                class="btn watch-action-btn"
                                data-id="<?= (int)($suggestion['id'] ?? 0) ?>"
                                data-type="<?= htmlspecialchars($type) ?>"
                            >
                                Watch
                            </button>

                            <a class="btn" href="<?= htmlspecialchars(url('/media/edit?id=' . ((int)($suggestion['id'] ?? 0)))) ?>">Edit</a>
                        </div>
                    </div>

                    <div class="poster-title-wrap">
                        <h3 class="poster-title"><?= htmlspecialchars($suggestion['title'] ?? '') ?></h3>
                        <p class="poster-rating">Avg rating: <?= number_format((float)($suggestion['avg_rating'] ?? 0), 1) ?>/5</p>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>
