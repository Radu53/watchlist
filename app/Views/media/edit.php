<h1>Edit Media</h1>

<form method="post" action="<?= htmlspecialchars(url('/media/update')) ?>" class="media-form">
    <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">

    <label>
        Title *
        <input type="text" name="title" required value="<?= htmlspecialchars($item['title']) ?>">
    </label>

    <label>
        Type
        <select name="type">
            <option value="unknown" <?= $item['type'] === 'unknown' ? 'selected' : '' ?>>Unknown</option>
            <option value="movie" <?= $item['type'] === 'movie' ? 'selected' : '' ?>>Movie</option>
            <option value="tv" <?= $item['type'] === 'tv' ? 'selected' : '' ?>>TV Show</option>
        </select>
    </label>

    <label>
        Year
        <input type="number" name="year" min="1888" max="2100" value="<?= htmlspecialchars((string)($item['year'] ?? '')) ?>">
    </label>

    <label>
        Description
        <textarea name="description" rows="6"><?= htmlspecialchars($item['description'] ?? '') ?></textarea>
    </label>

    <label>
        Cover URL
        <input type="url" name="cover_url" value="<?= htmlspecialchars($item['cover_url'] ?? '') ?>">
    </label>

    <label>
        IMDb Rating
        <input type="number" name="imdb_rating" min="0" max="10" step="0.1" value="<?= htmlspecialchars((string)($item['imdb_rating'] ?? '')) ?>">
    </label>

    <label>
        Watch URL
        <input type="url" name="watch_url" value="<?= htmlspecialchars($item['watch_url'] ?? '') ?>">
    </label>

    <button type="submit">Update</button>
</form>