<h1>TODO / Missing Watch URL</h1>

<?php if (empty($items)): ?>
    <p>No incomplete items.</p>
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

                <p>This entry is incomplete because it has no watch URL.</p>

                <p>
                    <a class="btn" href="<?= htmlspecialchars(url('/media/edit?id=' . $item['id'])) ?>">Complete Entry</a>
                </p>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>