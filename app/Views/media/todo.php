<h1>TODO / Review Queue</h1>

<?php if (empty($items)): ?>
    <p>No incomplete or review-needed items.</p>
<?php else: ?>
    <div class="grid">
        <?php foreach ($items as $item): ?>
            <article class="card">
                <h2><?= htmlspecialchars($item['title']) ?></h2>

                <?php if (!empty($item['year'])): ?>
                    <p>Year: <?= (int)$item['year'] ?></p>
                <?php endif; ?>

                <?php if (!empty($item['type'])): ?>
                    <p>Type: <?= htmlspecialchars($item['type']) ?></p>
                <?php endif; ?>

                <?php if (!empty($item['watch_url'])): ?>
                    <p><a href="<?= htmlspecialchars($item['watch_url']) ?>" target="_blank" rel="noopener noreferrer">Open watch link</a></p>
                <?php endif; ?>

                <?php if (empty($item['watch_url'])): ?>
                    <p>This entry is incomplete because it has no watch URL.</p>
                <?php elseif (!empty($item['needs_review'])): ?>
                    <p>This entry is in TODO because it still needs manual review.</p>
                <?php endif; ?>

                <p>
                    <a class="btn" href="<?= htmlspecialchars(url('/media/edit?id=' . $item['id'])) ?>">Review / Edit</a>
                </p>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>