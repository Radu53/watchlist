<h1>Categories</h1>

<div class="category-grid">
    <?php foreach ($genres as $genre): ?>
        <article class="category-card">
            <div class="category-card-header">
                <div>
                    <h2><?= htmlspecialchars($genre['name']) ?></h2>
                    <p><?= (int)($genre['item_count'] ?? 0) ?> titles</p>
                </div>
                <a class="btn" href="<?= htmlspecialchars(url('/?genre=' . urlencode((string)($genre['name'] ?? '')))) ?>">Open</a>
            </div>

            <?php if (!empty($genre['sample_items'])): ?>
                <div class="poster-grid category-samples">
                    <?php foreach ($genre['sample_items'] as $sample): ?>
                        <?php $coverUrl = trim((string)($sample['cover_url'] ?? '')); ?>
                        <article class="poster-card category-sample-card">
                            <?php if ($coverUrl !== ''): ?>
                                <img src="<?= htmlspecialchars($coverUrl) ?>" alt="<?= htmlspecialchars($sample['title'] ?? '') ?>" class="poster-image">
                            <?php else: ?>
                                <div class="poster-fallback">No Cover</div>
                            <?php endif; ?>
                            <div class="poster-title-wrap">
                                <h3 class="poster-title"><?= htmlspecialchars($sample['title'] ?? '') ?></h3>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="empty-state">No entries here yet.</p>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>
</div>
