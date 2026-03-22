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

    <div class="genre-field">
        <label for="genre-search">Genres</label>
        <input
            type="text"
            id="genre-search"
            class="genre-search-input"
            placeholder="Type a genre and press Enter"
            autocomplete="off"
        >
        <div class="genre-suggestions" id="genre-suggestions"></div>
        <div class="genre-tags" id="genre-tags"></div>
        <small>Press Enter to add a new genre or choose one from the list.</small>
    </div>

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

<script>
(function () {
    const input = document.getElementById('genre-search');
    const suggestionsBox = document.getElementById('genre-suggestions');
    const tagsBox = document.getElementById('genre-tags');
    const form = input.closest('form');
    const selectedGenres = [];

    function normalizeGenre(name) {
        return name.trim().replace(/\s+/g, ' ');
    }

    function titleCaseGenre(name) {
        return normalizeGenre(name)
            .toLowerCase()
            .replace(/\b\p{L}/gu, char => char.toUpperCase());
    }

    function hasGenre(name) {
        const normalized = normalizeGenre(name).toLowerCase();
        return selectedGenres.some(item => item.toLowerCase() === normalized);
    }

    function addGenre(name) {
        const clean = titleCaseGenre(name);
        if (!clean || hasGenre(clean)) {
            input.value = '';
            clearSuggestions();
            return;
        }

        selectedGenres.push(clean);
        renderTags();
        input.value = '';
        clearSuggestions();
    }

    function removeGenre(name) {
        const normalized = name.toLowerCase();
        const index = selectedGenres.findIndex(item => item.toLowerCase() === normalized);
        if (index !== -1) {
            selectedGenres.splice(index, 1);
            renderTags();
        }
    }

    function renderTags() {
        tagsBox.innerHTML = '';

        selectedGenres.forEach(name => {
            const tag = document.createElement('div');
            tag.className = 'genre-tag';

            const text = document.createElement('span');
            text.textContent = name;

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'genre-tag-remove';
            removeBtn.textContent = '×';
            removeBtn.addEventListener('click', () => removeGenre(name));

            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'genres[]';
            hidden.value = name;

            tag.appendChild(text);
            tag.appendChild(removeBtn);
            tag.appendChild(hidden);
            tagsBox.appendChild(tag);
        });
    }

    function clearSuggestions() {
        suggestionsBox.innerHTML = '';
        suggestionsBox.classList.remove('active');
    }

    function renderSuggestions(genres) {
        suggestionsBox.innerHTML = '';

        const filtered = genres.filter(name => !hasGenre(name));

        if (filtered.length === 0) {
            clearSuggestions();
            return;
        }

        filtered.forEach(name => {
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'genre-suggestion-item';
            item.textContent = name;
            item.addEventListener('click', () => addGenre(name));
            suggestionsBox.appendChild(item);
        });

        suggestionsBox.classList.add('active');
    }

    let searchAbortController = null;

    async function fetchSuggestions(query) {
        if (searchAbortController) {
            searchAbortController.abort();
        }

        searchAbortController = new AbortController();

        try {
            const response = await fetch(
                '<?= htmlspecialchars(url('/genres/search')) ?>?q=' + encodeURIComponent(query),
                { signal: searchAbortController.signal }
            );

            const data = await response.json();

            if (!response.ok || !data.ok) {
                clearSuggestions();
                return;
            }

            renderSuggestions(data.genres || []);
        } catch (error) {
            if (error.name !== 'AbortError') {
                clearSuggestions();
            }
        }
    }

    input.addEventListener('input', () => {
        const value = input.value.trim();
        if (value === '') {
            clearSuggestions();
            return;
        }

        fetchSuggestions(value);
    });

    input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();

            const firstSuggestion = suggestionsBox.querySelector('.genre-suggestion-item');
            const value = input.value.trim();

            if (firstSuggestion && value !== '') {
                const suggestionText = firstSuggestion.textContent.trim().toLowerCase();
                if (suggestionText === value.toLowerCase()) {
                    addGenre(firstSuggestion.textContent);
                    return;
                }
            }

            if (value !== '') {
                addGenre(value);
            }
        }
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest('.genre-field')) {
            clearSuggestions();
        }
    });
})();
</script>