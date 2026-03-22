<h1>Add Media</h1>

<form method="post" action="<?= htmlspecialchars(url('/media/store')) ?>" class="media-form">
    <label>
        Title *
        <input type="text" name="title" required>
    </label>

    <label>
        Type
        <select name="type">
            <option value="unknown">Unknown</option>
            <option value="movie">Movie</option>
            <option value="tv">TV Show</option>
        </select>
    </label>

    <label>
        Year
        <input type="number" name="year" min="1888" max="2100">
    </label>

    <label>
        Description
        <textarea name="description" rows="5"></textarea>
    </label>

    <label>
        Cover URL
        <input type="url" name="cover_url">
    </label>

    <label>
        IMDb Rating
        <input type="number" name="imdb_rating" min="0" max="10" step="0.1">
    </label>

    <label>
        Watch URL
        <input type="url" name="watch_url">
    </label>

    <button type="submit">Save</button>
</form>