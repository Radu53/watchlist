<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\View;
use PDO;

class HomeController
{
    public function index(): void
    {
        $pdo = Database::connection();
        $userId = current_user_id();
        $filters = $this->getFilters();

        $search = $filters['search'];
        $year = $filters['year'];
        $type = $filters['type'];
        $status = $filters['status'];
        $genre = $filters['genre'];
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 24;

        $sql = "
            SELECT m.*, ums.status AS user_status, avg_ratings.avg_rating, user_ratings.rating AS user_rating
            FROM media m
            LEFT JOIN media_genres mg ON mg.media_id = m.id
            LEFT JOIN genres g ON g.id = mg.genre_id
            LEFT JOIN user_media_status ums ON ums.media_id = m.id AND ums.user_id = :user_id
            LEFT JOIN (
                SELECT media_id, AVG(rating) AS avg_rating
                FROM user_media_ratings
                GROUP BY media_id
            ) avg_ratings ON avg_ratings.media_id = m.id
            LEFT JOIN user_media_ratings user_ratings ON user_ratings.media_id = m.id AND user_ratings.user_id = :user_id
            WHERE m.watch_url IS NOT NULL
              AND m.watch_url != ''
              AND m.needs_review = 0
        ";
        $params = ['user_id' => $userId];

        if ($search !== '') {
            $sql .= " AND m.title LIKE :search";
            $params['search'] = '%' . $search . '%';
        }

        if ($year !== '') {
            $sql .= " AND m.year = :year";
            $params['year'] = $year;
        }

        if ($type !== '') {
            $sql .= " AND m.type = :type";
            $params['type'] = $type;
        }

        if ($status !== '') {
            if ($status === 'none') {
                $sql .= " AND (ums.status IS NULL OR ums.status = '')";
            } else {
                $sql .= " AND ums.status = :status";
                $params['status'] = $status;
            }
        }

        if ($genre !== '') {
            $sql .= " AND g.name = :genre";
            $params['genre'] = $genre;
        }

        $sql .= " GROUP BY m.id ORDER BY m.created_at DESC";

        $countSql = "SELECT COUNT(*) FROM (" . $sql . ") AS counted";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $totalItems = (int)$countStmt->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $sql .= " LIMIT " . $perPage . " OFFSET " . $offset;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($items)) {
            $ids = array_column($items, 'id');
            $genreMap = $this->getGenresForMediaIds($pdo, $ids);

            foreach ($items as &$item) {
                $item['genres'] = $genreMap[(int)$item['id']] ?? [];
            }
            unset($item);
        }

        $allGenres = $pdo->query("SELECT name FROM genres ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);
        $suggestions = $this->getSuggestions($pdo, $userId, $filters);

        $totalPages = max(1, (int)ceil($totalItems / $perPage));

        View::render('home/index', [
            'items' => $items,
            'filters' => $filters,
            'allGenres' => $allGenres,
            'suggestions' => $suggestions,
            'page' => $page,
            'perPage' => $perPage,
            'totalItems' => $totalItems,
            'totalPages' => $totalPages,
        ]);
    }

    public function suggestions(): void
    {
        $pdo = Database::connection();
        $userId = current_user_id();
        $filters = $this->getFilters();
        $suggestions = $this->getSuggestions($pdo, $userId, $filters, true);

        ob_start();
        require __DIR__ . '/../Views/home/_suggestions.php';
        $html = ob_get_clean();

        header('Content-Type: text/html; charset=utf-8');
        echo $html;
    }

    public function categories(): void
    {
        $pdo = Database::connection();

        $genreRows = $pdo->query("SELECT id, name FROM genres ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $genres = [];

        foreach ($genreRows as $genreRow) {
            $genreId = (int)($genreRow['id'] ?? 0);
            $countStmt = $pdo->prepare("SELECT COUNT(*) AS item_count FROM media_genres WHERE genre_id = :genre_id");
            $countStmt->execute(['genre_id' => $genreId]);
            $itemCount = (int)($countStmt->fetchColumn() ?: 0);

            $sampleStmt = $pdo->prepare("
                SELECT m.id, m.title, m.cover_url, m.type, m.year
                FROM media m
                INNER JOIN media_genres mg ON mg.media_id = m.id
                WHERE mg.genre_id = :genre_id
                  AND m.watch_url IS NOT NULL
                  AND m.watch_url != ''
                  AND m.needs_review = 0
                ORDER BY m.created_at DESC
                LIMIT 3
            ");
            $sampleStmt->execute(['genre_id' => $genreId]);

            $genres[] = [
                'id' => $genreId,
                'name' => $genreRow['name'] ?? '',
                'item_count' => $itemCount,
                'sample_items' => $sampleStmt->fetchAll(PDO::FETCH_ASSOC),
            ];
        }

        View::render('home/categories', [
            'genres' => $genres,
        ]);
    }

    private function getFilters(): array
    {
        return [
            'search' => trim($_GET['search'] ?? ''),
            'year' => trim($_GET['year'] ?? ''),
            'type' => trim($_GET['type'] ?? ''),
            'status' => trim($_GET['status'] ?? ''),
            'genre' => trim($_GET['genre'] ?? ''),
        ];
    }

    private function getSuggestions(PDO $pdo, ?int $userId, array $filters = [], bool $forceRefresh = false): array
    {
        $params = ['user_id' => $userId];
        $baseQuery = "
            SELECT m.id, m.title, m.cover_url, m.watch_url, m.type, m.year,
                   COALESCE(avg_ratings.avg_rating, 0) AS avg_rating,
                   ums.status AS user_status
            FROM media m
            LEFT JOIN user_media_status ums ON ums.media_id = m.id AND ums.user_id = :user_id
            LEFT JOIN (
                SELECT media_id, AVG(rating) AS avg_rating
                FROM user_media_ratings
                GROUP BY media_id
            ) avg_ratings ON avg_ratings.media_id = m.id
            WHERE m.watch_url IS NOT NULL
              AND m.watch_url != ''
              AND m.needs_review = 0
        ";

        $sql = $baseQuery;
        $sql .= $this->appendFilterClause($filters, $params);
        $sql .= " AND (ums.status IS NULL OR ums.status NOT IN ('started', 'watched'))";

        $movieSql = $sql . " AND m.type = 'movie' ORDER BY RAND() LIMIT 1";
        $showSql = $sql . " AND m.type = 'tv' ORDER BY RAND() LIMIT 1";
        $randomSql = $sql . " ORDER BY RAND() LIMIT 1";

        $startedSql = $baseQuery . $this->appendFilterClause($filters, $params) . " AND ums.status IN ('started', 'watched') ORDER BY CASE WHEN ums.status = 'started' THEN 0 ELSE 1 END, m.created_at DESC LIMIT 1";
        $watchedSql = $baseQuery . $this->appendFilterClause($filters, $params) . " AND ums.status = 'watched' ORDER BY m.created_at DESC LIMIT 1";

        $suggestions = [];
        $seenIds = [];

        foreach ([$movieSql, $showSql, $randomSql, $startedSql, $watchedSql] as $query) {
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row && !isset($seenIds[(int)$row['id']])) {
                $suggestions[] = $row;
                $seenIds[(int)$row['id']] = true;
            }
        }

        if ($forceRefresh) {
            shuffle($suggestions);
        }

        return $suggestions;
    }

    private function appendFilterClause(array $filters, array &$params): string
    {
        $search = trim($filters['search'] ?? '');
        $year = trim($filters['year'] ?? '');
        $type = trim($filters['type'] ?? '');
        $status = trim($filters['status'] ?? '');
        $genre = trim($filters['genre'] ?? '');
        $sql = '';

        if ($search !== '') {
            $sql .= ' AND m.title LIKE :search';
            $params['search'] = '%' . $search . '%';
        }

        if ($year !== '') {
            $sql .= ' AND m.year = :year';
            $params['year'] = $year;
        }

        if ($type !== '') {
            $sql .= ' AND m.type = :type';
            $params['type'] = $type;
        }

        if ($status !== '') {
            if ($status === 'none') {
                $sql .= " AND (ums.status IS NULL OR ums.status = '')";
            } else {
                $sql .= ' AND ums.status = :status';
                $params['status'] = $status;
            }
        }

        if ($genre !== '') {
            $sql .= ' AND EXISTS (SELECT 1 FROM media_genres mg INNER JOIN genres g ON g.id = mg.genre_id WHERE mg.media_id = m.id AND g.name = :genre)';
            $params['genre'] = $genre;
        }

        return $sql;
    }

    private function getGenresForMediaIds(PDO $pdo, array $mediaIds): array
    {
        if (empty($mediaIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($mediaIds), '?'));

        $stmt = $pdo->prepare("
            SELECT mg.media_id, g.name
            FROM media_genres mg
            INNER JOIN genres g ON g.id = mg.genre_id
            WHERE mg.media_id IN ($placeholders)
            ORDER BY g.name ASC
        ");
        $stmt->execute($mediaIds);

        $map = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $mediaId = (int)$row['media_id'];
            $map[$mediaId][] = $row['name'];
        }

        return $map;
    }
}