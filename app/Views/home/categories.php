<h1>Categories</h1>

<ul>
    <?php foreach ($genres as $genre): ?>
        <li>
            <strong><?= htmlspecialchars($genre['name']) ?></strong>
            <span>(<?= (int)($genre['item_count'] ?? 0) ?>)</span>
        </li>
    <?php endforeach; ?>
</ul>
