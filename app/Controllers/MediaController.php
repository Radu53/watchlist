<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\View;
use PDO;

class MediaController
{
    public function create(): void
    {
        View::render('media/create');
    }

    public function store(): void
    {
        $pdo = Database::connection();

        $title = trim($_POST['title'] ?? '');
        $watchUrl = trim($_POST['watch_url'] ?? '');
        $type = trim($_POST['type'] ?? 'unknown');
        $year = trim($_POST['year'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $coverUrl = trim($_POST['cover_url'] ?? '');
        $imdbRating = trim($_POST['imdb_rating'] ?? '');
        $genreNames = $_POST['genres'] ?? [];
        $needsReview = isset($_POST['needs_review']) ? 1 : 0;

        if (!is_array($genreNames)) {
            $genreNames = [];
        }

        if ($title === '') {
            http_response_code(422);
            exit('Title is required.');
        }

        if (isset($_POST['duplicate_action']) && $_POST['duplicate_action'] === 'cancel') {
            header('Location: ' . url('/'));
            exit;
        }

        $duplicateCandidates = $this->findDuplicateMedia($pdo, $title, $watchUrl);
        if (!empty($duplicateCandidates) && !isset($_POST['duplicate_action'])) {
            View::render('media/duplicate', [
                'pendingData' => [
                    'title' => $title,
                    'watch_url' => $watchUrl,
                    'type' => $type,
                    'year' => $year,
                    'description' => $description,
                    'cover_url' => $coverUrl,
                    'imdb_rating' => $imdbRating,
                    'genres' => $genreNames,
                    'needs_review' => $needsReview,
                ],
                'duplicateCandidates' => $duplicateCandidates,
            ]);
            return;
        }

        if ($watchUrl === '') {
            $needsReview = 1;
        }

        $status = $watchUrl !== '' ? null : 'draft';
        $watchDomain = $this->extractDomain($watchUrl);

        $stmt = $pdo->prepare("
            INSERT INTO media (
                type, title, year, description, cover_url, imdb_rating,
                watch_url, watch_domain, status, needs_review
            ) VALUES (
                :type, :title, :year, :description, :cover_url, :imdb_rating,
                :watch_url, :watch_domain, :status, :needs_review
            )
        ");

        $stmt->execute([
            'type' => in_array($type, ['unknown', 'movie', 'tv'], true) ? $type : 'unknown',
            'title' => $title,
            'year' => $year !== '' ? (int)$year : null,
            'description' => $description !== '' ? $description : null,
            'cover_url' => $coverUrl !== '' ? $coverUrl : null,
            'imdb_rating' => $imdbRating !== '' ? $imdbRating : null,
            'watch_url' => $watchUrl !== '' ? $watchUrl : null,
            'watch_domain' => $watchDomain,
            'status' => $status,
            'needs_review' => $needsReview,
        ]);

        $mediaId = (int)$pdo->lastInsertId();
        $this->syncGenres($pdo, $mediaId, $genreNames);

        header('Location: ' . url('/'));
        exit;
    }

    public function todo(): void
    {
        $pdo = Database::connection();

        $stmt = $pdo->query("
            SELECT *
            FROM media
            WHERE needs_review = 1
               OR watch_url IS NULL
               OR watch_url = ''
            ORDER BY created_at DESC
        ");

        $items = $stmt->fetchAll();

        View::render('media/todo', [
            'items' => $items,
        ]);
    }

    public function edit(): void
    {
        $pdo = Database::connection();

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(400);
            exit('Invalid media id.');
        }

        $stmt = $pdo->prepare("SELECT * FROM media WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $item = $stmt->fetch();

        if (!$item) {
            http_response_code(404);
            exit('Item not found.');
        }

        $item['genre_names'] = $this->getGenreNamesForMedia($pdo, $id);

        View::render('media/edit', [
            'item' => $item,
        ]);
    }

    public function update(): void
    {
        $pdo = Database::connection();

        $id = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $watchUrl = trim($_POST['watch_url'] ?? '');
        $type = trim($_POST['type'] ?? 'unknown');
        $year = trim($_POST['year'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $coverUrl = trim($_POST['cover_url'] ?? '');
        $imdbRating = trim($_POST['imdb_rating'] ?? '');
        $genreNames = $_POST['genres'] ?? [];
        $needsReview = isset($_POST['needs_review']) ? 1 : 0;

        if (!is_array($genreNames)) {
            $genreNames = [];
        }

        if ($id <= 0) {
            http_response_code(400);
            exit('Invalid media id.');
        }

        if ($title === '') {
            http_response_code(422);
            exit('Title is required.');
        }

        $stmt = $pdo->prepare("SELECT * FROM media WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $existing = $stmt->fetch();

        if (!$existing) {
            http_response_code(404);
            exit('Item not found.');
        }

        if ($watchUrl === '') {
            $needsReview = 1;
        }

        $watchDomain = $this->extractDomain($watchUrl);
        $status = $existing['status'];

        if ($watchUrl === '') {
            $status = 'draft';
        } elseif ($status === 'draft') {
            $status = null;
        }

        $stmt = $pdo->prepare("
            UPDATE media
            SET
                type = :type,
                title = :title,
                year = :year,
                description = :description,
                cover_url = :cover_url,
                imdb_rating = :imdb_rating,
                watch_url = :watch_url,
                watch_domain = :watch_domain,
                status = :status,
                needs_review = :needs_review
            WHERE id = :id
        ");

        $stmt->execute([
            'id' => $id,
            'type' => in_array($type, ['unknown', 'movie', 'tv'], true) ? $type : 'unknown',
            'title' => $title,
            'year' => $year !== '' ? (int)$year : null,
            'description' => $description !== '' ? $description : null,
            'cover_url' => $coverUrl !== '' ? $coverUrl : null,
            'imdb_rating' => $imdbRating !== '' ? $imdbRating : null,
            'watch_url' => $watchUrl !== '' ? $watchUrl : null,
            'watch_domain' => $watchDomain,
            'status' => $status,
            'needs_review' => $needsReview,
        ]);

        $this->syncGenres($pdo, $id, $genreNames);

        header('Location: ' . url('/'));
        exit;
    }

    public function delete(): void
    {
        $pdo = Database::connection();
        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            http_response_code(400);
            exit('Invalid media id.');
        }

        $pdo->prepare("DELETE FROM user_media_status WHERE media_id = :id")->execute(['id' => $id]);
        $pdo->prepare("DELETE FROM user_media_ratings WHERE media_id = :id")->execute(['id' => $id]);
        $pdo->prepare("DELETE FROM media_genres WHERE media_id = :id")->execute(['id' => $id]);
        $pdo->prepare("DELETE FROM watch_history WHERE media_id = :id")->execute(['id' => $id]);
        $pdo->prepare("DELETE FROM media WHERE id = :id")->execute(['id' => $id]);

        header('Location: ' . url('/'));
        exit;
    }

    public function changeStatus(): void
    {
        $pdo = Database::connection();

        $id = (int)($_POST['id'] ?? 0);
        $newStatus = trim($_POST['status'] ?? '');

        if ($id <= 0 || $newStatus === '') {
            http_response_code(400);
            exit('Invalid request.');
        }

        $stmt = $pdo->prepare("SELECT id, type, title, watch_url FROM media WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$item) {
            http_response_code(404);
            exit('Item not found.');
        }

        if (empty($item['watch_url'])) {
            http_response_code(422);
            exit('Cannot change status without a watch URL.');
        }

        $userId = current_user_id();
        if ($userId === null) {
            http_response_code(401);
            exit('You must be logged in.');
        }

        if ($newStatus === 'none') {
            $newStatus = null;
        }

        if ($newStatus !== null && !in_array($newStatus, ['started', 'watched'], true)) {
            $newStatus = null;
        }

        $stmt = $pdo->prepare("REPLACE INTO user_media_status (user_id, media_id, status) VALUES (:user_id, :media_id, :status)");
        $stmt->execute([
            'user_id' => $userId,
            'media_id' => $id,
            'status' => $newStatus,
        ]);

        $stmt = $pdo->prepare("
            INSERT INTO watch_history (media_id, old_status, new_status, action_date)
            VALUES (:media_id, :old_status, :new_status, :action_date)
        ");

        $stmt->execute([
            'media_id' => $id,
            'old_status' => null,
            'new_status' => $newStatus ?? 'none',
            'action_date' => date('Y-m-d'),
        ]);

        header('Location: ' . url('/'));
        exit;
    }

    public function watch(): void
    {
        $pdo = Database::connection();

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            $this->json(['ok' => false, 'message' => 'Invalid id.'], 400);
        }

        $stmt = $pdo->prepare("SELECT id, type, watch_url FROM media WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$item) {
            $this->json(['ok' => false, 'message' => 'Item not found.'], 404);
        }

        if (empty($item['watch_url'])) {
            $this->json(['ok' => false, 'message' => 'Missing watch URL.'], 422);
        }

        $userId = current_user_id();
        if ($userId === null) {
            $this->json(['ok' => false, 'message' => 'You must be logged in.'], 401);
        }

        $stmt = $pdo->prepare("SELECT status FROM user_media_status WHERE user_id = :user_id AND media_id = :media_id LIMIT 1");
        $stmt->execute(['user_id' => $userId, 'media_id' => $id]);
        $oldStatus = $stmt->fetchColumn();
        $newStatus = $oldStatus;

        if ($item['type'] === 'movie') {
            if ($oldStatus !== 'watched') {
                $newStatus = 'watched';
            }
        } elseif ($item['type'] === 'tv') {
            if ($oldStatus === null || $oldStatus === '') {
                $newStatus = 'started';
            }
        }

        if ($newStatus !== $oldStatus) {
            $stmt = $pdo->prepare("REPLACE INTO user_media_status (user_id, media_id, status) VALUES (:user_id, :media_id, :status)");
            $stmt->execute([
                'user_id' => $userId,
                'media_id' => $id,
                'status' => $newStatus,
            ]);
        }

        $this->json([
            'ok' => true,
            'watch_url' => $item['watch_url'],
            'status' => $newStatus,
        ]);
    }

    public function rate(): void
    {
        $pdo = Database::connection();
        $userId = current_user_id();
        $id = (int)($_POST['id'] ?? 0);
        $rating = (int)($_POST['rating'] ?? 0);

        if ($userId === null) {
            $this->json(['ok' => false, 'message' => 'You must be logged in.'], 401);
        }

        if ($id <= 0 || $rating < 1 || $rating > 5) {
            $this->json(['ok' => false, 'message' => 'Invalid rating.'], 422);
        }

        $stmt = $pdo->prepare("SELECT id FROM media WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        if (!$stmt->fetch()) {
            $this->json(['ok' => false, 'message' => 'Item not found.'], 404);
        }

        $stmt = $pdo->prepare("REPLACE INTO user_media_ratings (user_id, media_id, rating) VALUES (:user_id, :media_id, :rating)");
        $stmt->execute([
            'user_id' => $userId,
            'media_id' => $id,
            'rating' => $rating,
        ]);

        $this->json(['ok' => true, 'rating' => $rating]);
    }

    public function searchGenres(): void
    {
        $pdo = Database::connection();

        $query = trim($_GET['q'] ?? '');

        if ($query === '') {
            $stmt = $pdo->query("
                SELECT name
                FROM genres
                ORDER BY name ASC
                LIMIT 10
            ");
        } else {
            $stmt = $pdo->prepare("
                SELECT name
                FROM genres
                WHERE name LIKE :query
                ORDER BY name ASC
                LIMIT 10
            ");
            $stmt->execute([
                'query' => '%' . $query . '%',
            ]);
        }

        $genres = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $this->json([
            'ok' => true,
            'genres' => $genres,
        ]);
    }

    public function parse(): void
    {
        $url = trim($_POST['watch_url'] ?? '');

        if ($url === '') {
            $this->json(['ok' => false, 'message' => 'Watch URL is required.'], 422);
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $this->json(['ok' => false, 'message' => 'Invalid URL.'], 422);
        }

        $domain = $this->extractDomain($url);
        if (!$domain) {
            $this->json(['ok' => false, 'message' => 'Could not extract domain.'], 422);
        }

        $html = $this->fetchUrl($url);
        if ($html === null || $html === '') {
            $this->json(['ok' => false, 'message' => 'Failed to fetch page HTML.'], 500);
        }

        $pdo = Database::connection();

        $parsed = [
            'title' => null,
            'type' => null,
            'year' => null,
            'description' => null,
            'cover_url' => null,
            'imdb_rating' => null,
        ];

        $site = $this->findParserSite($pdo, $domain);
        if ($site) {
            $rules = $this->getParserRules($pdo, (int)$site['id']);

            foreach ($rules as $rule) {
                $field = $rule['field_name'];
                if (!array_key_exists($field, $parsed)) {
                    continue;
                }

                if (!empty($parsed[$field])) {
                    continue;
                }

                $value = null;

                if ($rule['rule_type'] === 'regex') {
                    $value = $this->applyRegexRule($html, $rule['rule_value']);
                } elseif ($rule['rule_type'] === 'meta') {
                    $value = $this->extractMetaValue($html, $rule['rule_value']);
                }

                if ($value !== null && $value !== '') {
                    $parsed[$field] = $this->cleanParsedField($field, $value);
                }
            }
        }

        if (empty($parsed['title'])) {
            $parsed['title'] = $this->extractMetaValue($html, 'og:title')
                ?? $this->extractMetaValue($html, 'twitter:title')
                ?? $this->extractTitleTag($html);
        }

        if (empty($parsed['description'])) {
            $parsed['description'] = $this->extractMetaValue($html, 'og:description')
                ?? $this->extractMetaValue($html, 'description')
                ?? $this->extractMetaValue($html, 'twitter:description');
        }

        if (empty($parsed['cover_url'])) {
            $parsed['cover_url'] = $this->extractMetaValue($html, 'og:image')
                ?? $this->extractMetaValue($html, 'twitter:image');
        }

        if (empty($parsed['type'])) {
            $parsed['type'] = $this->inferTypeFromUrl($url);
        }

        if (empty($parsed['type'])) {
            $parsed['type'] = 'unknown';
        }

        if (!in_array($parsed['type'], ['movie', 'tv', 'unknown'], true)) {
            $parsed['type'] = 'unknown';
        }

        if (!empty($parsed['year'])) {
            $parsed['year'] = $this->normalizeYear($parsed['year']);
        }

        $duplicate = $this->findDuplicateMedia($pdo, $parsed['title'] ?? '', $url);

        $this->json([
            'ok' => true,
            'domain' => $domain,
            'parsed' => $parsed,
            'recognized_site' => $site ? true : false,
            'duplicate' => $duplicate ? [
                'found' => true,
                'message' => 'This URL already exists in the library.',
                'item' => $duplicate[0],
            ] : null,
        ]);
    }

    public function history(): void
    {
        $pdo = Database::connection();

        $stmt = $pdo->query("
            SELECT
                wh.*,
                m.title,
                m.type
            FROM watch_history wh
            INNER JOIN media m ON m.id = wh.media_id
            ORDER BY wh.action_date DESC, wh.id DESC
        ");

        $items = $stmt->fetchAll();

        View::render('media/history', [
            'items' => $items,
        ]);
    }

    private function extractDomain(string $watchUrl): ?string
    {
        if ($watchUrl === '') {
            return null;
        }

        $host = parse_url($watchUrl, PHP_URL_HOST);
        if (!$host) {
            return null;
        }

        return preg_replace('/^www\./i', '', strtolower($host));
    }

    private function findDuplicateMedia(PDO $pdo, string $title, string $watchUrl): array
    {
        $conditions = [];
        $params = [];

        if ($title !== '') {
            $normalizedTitle = strtolower(preg_replace('/\s+/', '', $title) ?? $title);
            $conditions[] = 'LOWER(REPLACE(title, " ", "")) = :title';
            $params['title'] = $normalizedTitle;
        }

        if ($watchUrl !== '') {
            $conditions[] = 'LOWER(watch_url) = :watch_url';
            $params['watch_url'] = strtolower(trim($watchUrl));
        }

        if ($conditions === []) {
            return [];
        }

        $stmt = $pdo->prepare("SELECT id, title, watch_url, cover_url, year FROM media WHERE " . implode(' OR ', $conditions) . " LIMIT 5");
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function syncGenres(PDO $pdo, int $mediaId, array $genreNames): void
    {
        $genreNames = $this->normalizeGenreArray($genreNames);

        $stmt = $pdo->prepare("DELETE FROM media_genres WHERE media_id = :media_id");
        $stmt->execute(['media_id' => $mediaId]);

        if (empty($genreNames)) {
            return;
        }

        foreach ($genreNames as $genreName) {
            $genreId = $this->findOrCreateGenre($pdo, $genreName);

            $stmt = $pdo->prepare("
                INSERT IGNORE INTO media_genres (media_id, genre_id)
                VALUES (:media_id, :genre_id)
            ");

            $stmt->execute([
                'media_id' => $mediaId,
                'genre_id' => $genreId,
            ]);
        }
    }

    private function normalizeGenreArray(array $genreNames): array
    {
        $genres = [];

        foreach ($genreNames as $name) {
            if (!is_string($name)) {
                continue;
            }

            $name = trim($name);
            if ($name === '') {
                continue;
            }

            $name = preg_replace('/\s+/', ' ', $name);
            $name = mb_convert_case($name, MB_CASE_TITLE, 'UTF-8');
            $genres[] = $name;
        }

        return array_values(array_unique($genres));
    }

    private function findOrCreateGenre(PDO $pdo, string $genreName): int
    {
        $stmt = $pdo->prepare("SELECT id FROM genres WHERE name = :name LIMIT 1");
        $stmt->execute(['name' => $genreName]);
        $genre = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($genre) {
            return (int)$genre['id'];
        }

        $stmt = $pdo->prepare("INSERT INTO genres (name) VALUES (:name)");
        $stmt->execute(['name' => $genreName]);

        return (int)$pdo->lastInsertId();
    }

    private function getGenreNamesForMedia(PDO $pdo, int $mediaId): array
    {
        $stmt = $pdo->prepare("
            SELECT g.name
            FROM genres g
            INNER JOIN media_genres mg ON mg.genre_id = g.id
            WHERE mg.media_id = :media_id
            ORDER BY g.name ASC
        ");

        $stmt->execute(['media_id' => $mediaId]);

        return array_map(
            static fn(array $row) => $row['name'],
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    private function findParserSite(PDO $pdo, string $domain): ?array
    {
        $stmt = $pdo->prepare("
            SELECT *
            FROM parser_sites
            WHERE domain = :domain
              AND is_active = 1
            LIMIT 1
        ");
        $stmt->execute(['domain' => $domain]);

        $site = $stmt->fetch(PDO::FETCH_ASSOC);
        return $site ?: null;
    }

    private function getParserRules(PDO $pdo, int $siteId): array
    {
        $stmt = $pdo->prepare("
            SELECT *
            FROM parser_rules
            WHERE site_id = :site_id
              AND is_active = 1
            ORDER BY field_name ASC, fallback_order ASC, id ASC
        ");
        $stmt->execute(['site_id' => $siteId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function applyRegexRule(string $html, string $pattern): ?string
    {
        $result = @preg_match($pattern, $html, $matches);
        if ($result !== 1) {
            return null;
        }

        if (!empty($matches[1])) {
            return html_entity_decode(trim($matches[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        if (!empty($matches[0])) {
            return html_entity_decode(trim($matches[0]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        return null;
    }

    private function extractMetaValue(string $html, string $metaName): ?string
    {
        $quoted = preg_quote($metaName, '/');

        $patterns = [
            '/<meta[^>]+property=["\']' . $quoted . '["\'][^>]+content=["\']([^"\']+)["\'][^>]*>/i',
            '/<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']' . $quoted . '["\'][^>]*>/i',
            '/<meta[^>]+name=["\']' . $quoted . '["\'][^>]+content=["\']([^"\']+)["\'][^>]*>/i',
            '/<meta[^>]+content=["\']([^"\']+)["\'][^>]+name=["\']' . $quoted . '["\'][^>]*>/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches) === 1 && !empty($matches[1])) {
                return html_entity_decode(trim($matches[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
        }

        return null;
    }

    private function extractTitleTag(string $html): ?string
    {
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $matches) === 1 && !empty($matches[1])) {
            return html_entity_decode(trim(strip_tags($matches[1])), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        return null;
    }

    private function inferTypeFromUrl(string $url): ?string
    {
        $path = strtolower((string)parse_url($url, PHP_URL_PATH));

        $movieHints = ['/movie/', '/movies/', '/film/', '/filme/'];
        $tvHints = ['/tv/', '/serial/', '/seriale/', '/series/'];

        foreach ($movieHints as $hint) {
            if (str_contains($path, $hint)) {
                return 'movie';
            }
        }

        foreach ($tvHints as $hint) {
            if (str_contains($path, $hint)) {
                return 'tv';
            }
        }

        return null;
    }

    private function normalizeYear(string $value): ?int
    {
        if (preg_match('/\b(18\d{2}|19\d{2}|20\d{2}|21\d{2})\b/', $value, $matches) === 1) {
            return (int)$matches[1];
        }

        return null;
    }

    private function cleanParsedField(string $field, string $value): string
    {
        $value = trim(strip_tags(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        if ($field === 'title') {
            $value = preg_replace('/\s+/', ' ', $value);
        }

        return $value;
    }

    private function fetchUrl(string $url): ?string
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 20,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_USERAGENT => 'Mozilla/5.0 WatchlistParser/1.0',
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ]);

            $html = curl_exec($ch);
            $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($html !== false && $httpCode >= 200 && $httpCode < 400) {
                return $html;
            }
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "User-Agent: Mozilla/5.0 WatchlistParser/1.0\r\n",
                'timeout' => 20,
            ],
        ]);

        $html = @file_get_contents($url, false, $context);
        return $html !== false ? $html : null;
    }

    private function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }
}