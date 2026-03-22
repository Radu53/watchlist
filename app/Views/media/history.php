<h1>Watch History</h1>

<?php if (empty($items)): ?>
    <p>No history yet.</p>
<?php else: ?>
    <div class="history-list">
        <?php foreach ($items as $item): ?>
            <article class="card">
                <h2><?= htmlspecialchars($item['title']) ?></h2>
                <div class="meta">
                    <span><?= htmlspecialchars(strtoupper($item['type'])) ?></span>
                    <span><?= htmlspecialchars($item['old_status'] ?? 'none') ?> → <?= htmlspecialchars($item['new_status']) ?></span>
                    <span><?= htmlspecialchars($item['action_date']) ?></span>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>