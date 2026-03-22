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

        $search = trim($_GET['search'] ?? '');
        $year = trim($_GET['year'] ?? '');
        $type = trim($_GET['type'] ?? '');
        $status = trim($_GET['status'] ?? '');
        $genre = trim($_GET['genre'] ?? '');

        $sql = "
            SELECT DISTINCT m.*
            FROM media m
            LEFT JOIN media_genres mg ON mg.media_id = m.id
            LEFT JOIN genres g ON g.id = mg.genre_id
            WHERE m.watch_url IS NOT NULL
              AND m.watch_url != ''
              AND m.needs_review = 0
        ";
        $params = [];

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
                $sql .= " AND (m.status IS NULL OR m.status = '')";
            } else {
                $sql .= " AND m.status = :status";
                $params['status'] = $status;
            }
        }

        if ($genre !== '') {
            $sql .= " AND g.name = :genre";
            $params['genre'] = $genre;
        }

        $sql .= " ORDER BY m.created_at DESC";

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

        View::render('home/index', [
            'items' => $items,
            'filters' => compact('search', 'year', 'type', 'status', 'genre'),
            'allGenres' => $allGenres,
        ]);
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