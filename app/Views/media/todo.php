<h1>TODO / Drafts</h1>

<?php if (empty($items)): ?>
    <p>No draft items.</p>
<?php else: ?>
    <div class="grid">
        <?php foreach ($items as $item): ?>
            <article class="card">
                <h2><?= htmlspecialchars($item['title']) ?></h2>
                <p>Status: <strong><?= htmlspecialchars($item['status']) ?></strong></p>
                <p>Created: <?= htmlspecialchars($item['created_at']) ?></p>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>