<h1>Possible duplicate entry</h1>

<p>This looks similar to entries already in the library. Choose whether to add the current item anyway or cancel.</p>

<div class="poster-grid">
    <?php foreach ($duplicateCandidates as $candidate): ?>
        <article class="poster-card">
            <div class="poster-title-wrap">
                <h2 class="poster-title"><?= htmlspecialchars($candidate['title'] ?? '') ?></h2>
                <?php if (!empty($candidate['watch_url'])): ?>
                    <p><a href="<?= htmlspecialchars($candidate['watch_url']) ?>" target="_blank" rel="noopener">Open URL</a></p>
                <?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>
</div>

<form method="post" action="<?= htmlspecialchars(url('/media/store')) ?>" class="media-form">
    <input type="hidden" name="duplicate_action" value="add_anyway">
    <input type="hidden" name="title" value="<?= htmlspecialchars($pendingData['title'] ?? '') ?>">
    <input type="hidden" name="watch_url" value="<?= htmlspecialchars($pendingData['watch_url'] ?? '') ?>">
    <input type="hidden" name="type" value="<?= htmlspecialchars($pendingData['type'] ?? 'unknown') ?>">
    <input type="hidden" name="year" value="<?= htmlspecialchars($pendingData['year'] ?? '') ?>">
    <input type="hidden" name="description" value="<?= htmlspecialchars($pendingData['description'] ?? '') ?>">
    <input type="hidden" name="cover_url" value="<?= htmlspecialchars($pendingData['cover_url'] ?? '') ?>">
    <input type="hidden" name="imdb_rating" value="<?= htmlspecialchars($pendingData['imdb_rating'] ?? '') ?>">
    <input type="hidden" name="needs_review" value="<?= (int)($pendingData['needs_review'] ?? 0) ?>">
    <?php foreach ((array)($pendingData['genres'] ?? []) as $genre): ?>
        <input type="hidden" name="genres[]" value="<?= htmlspecialchars($genre) ?>">
    <?php endforeach; ?>

    <button type="submit">Add anyway</button>
</form>

<form method="post" action="<?= htmlspecialchars(url('/media/store')) ?>" class="media-form">
    <input type="hidden" name="duplicate_action" value="cancel">
    <button type="submit">Cancel</button>
</form>
