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

        $search = trim($_GET['search'] ?? '');
        $year = trim($_GET['year'] ?? '');
        $type = trim($_GET['type'] ?? '');
        $status = trim($_GET['status'] ?? '');
        $genre = trim($_GET['genre'] ?? '');

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
        $suggestions = $this->getSuggestions($pdo, $userId);

        View::render('home/index', [
            'items' => $items,
            'filters' => compact('search', 'year', 'type', 'status', 'genre'),
            'allGenres' => $allGenres,
            'suggestions' => $suggestions,
        ]);
    }

    public function categories(): void
    {
        $pdo = Database::connection();

        $stmt = $pdo->query("
            SELECT g.name, COUNT(mg.media_id) AS item_count
            FROM genres g
            LEFT JOIN media_genres mg ON mg.genre_id = g.id
            GROUP BY g.id
            ORDER BY g.name ASC
        ");

        View::render('home/categories', [
            'genres' => $stmt->fetchAll(PDO::FETCH_ASSOC),
        ]);
    }

    private function getSuggestions(PDO $pdo, ?int $userId): array
    {
        $refresh = isset($_GET['refresh']) ? 1 : 0;
        $sql = "
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
              AND (ums.status IS NULL OR ums.status != 'watched')
        ";

        $params = ['user_id' => $userId];

        if ($refresh) {
            $sql .= " ORDER BY RAND()";
        } else {
            $sql .= " ORDER BY COALESCE(avg_ratings.avg_rating, 0) DESC, m.created_at DESC";
        }

        $sql .= " LIMIT 6";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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