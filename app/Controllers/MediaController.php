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
        $type = trim($_POST['type'] ?? 'unknown');
        $year = trim($_POST['year'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $coverUrl = trim($_POST['cover_url'] ?? '');
        $imdbRating = trim($_POST['imdb_rating'] ?? '');
        $watchUrl = trim($_POST['watch_url'] ?? '');

        if ($title === '') {
            http_response_code(422);
            exit('Title is required.');
        }

        $status = $watchUrl !== '' ? '' : 'draft';
        $watchDomain = $this->extractDomain($watchUrl);

        $stmt = $pdo->prepare("
            INSERT INTO media (
                type, title, year, description, cover_url, imdb_rating,
                watch_url, watch_domain, status
            ) VALUES (
                :type, :title, :year, :description, :cover_url, :imdb_rating,
                :watch_url, :watch_domain, :status
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
        ]);

        header('Location: ' . url('/'));
        exit;
    }

    public function todo(): void
    {
        $pdo = Database::connection();

        $stmt = $pdo->query("
            SELECT *
            FROM media
            WHERE watch_url IS NULL OR watch_url = ''
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

        View::render('media/edit', [
            'item' => $item,
        ]);
    }

    public function update(): void
    {
        $pdo = Database::connection();

        $id = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $type = trim($_POST['type'] ?? 'unknown');
        $year = trim($_POST['year'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $coverUrl = trim($_POST['cover_url'] ?? '');
        $imdbRating = trim($_POST['imdb_rating'] ?? '');
        $watchUrl = trim($_POST['watch_url'] ?? '');

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

        $watchDomain = $this->extractDomain($watchUrl);

        $status = $existing['status'] ?? 'draft';

        if ($watchUrl === '') {
            $status = 'draft';
        } elseif ($status === 'draft') {
            $status = '';
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
                status = :status
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
        ]);

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

        $stmt = $pdo->prepare("SELECT id, type, title, status, watch_url FROM media WHERE id = :id");
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

        $oldStatus = $item['status'] ?? '';
        $type = $item['type'];

        if (!$this->isValidStatusTransition($type, $oldStatus, $newStatus)) {
            http_response_code(422);
            exit('Invalid status transition.');
        }

        $stmt = $pdo->prepare("UPDATE media SET status = :status WHERE id = :id");
        $stmt->execute([
            'status' => $newStatus,
            'id' => $id,
        ]);

        $stmt = $pdo->prepare("
            INSERT INTO watch_history (media_id, old_status, new_status, action_date)
            VALUES (:media_id, :old_status, :new_status, :action_date)
        ");

        $stmt->execute([
            'media_id' => $id,
            'old_status' => $oldStatus !== '' ? $oldStatus : null,
            'new_status' => $newStatus,
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

        $stmt = $pdo->prepare("SELECT id, type, status, watch_url FROM media WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$item) {
            $this->json(['ok' => false, 'message' => 'Item not found.'], 404);
        }

        if (empty($item['watch_url'])) {
            $this->json(['ok' => false, 'message' => 'Missing watch URL.'], 422);
        }

        $oldStatus = $item['status'] ?? '';
        $newStatus = $oldStatus;

        if ($item['type'] === 'movie') {
            if ($oldStatus !== 'watched') {
                $newStatus = 'watched';
            }
        } elseif ($item['type'] === 'tv') {
            if ($oldStatus === '') {
                $newStatus = 'started';
            }
        }

        if ($newStatus !== $oldStatus) {
            $stmt = $pdo->prepare("UPDATE media SET status = :status WHERE id = :id");
            $stmt->execute([
                'status' => $newStatus,
                'id' => $id,
            ]);

            $stmt = $pdo->prepare("
                INSERT INTO watch_history (media_id, old_status, new_status, action_date)
                VALUES (:media_id, :old_status, :new_status, :action_date)
            ");

            $stmt->execute([
                'media_id' => $id,
                'old_status' => $oldStatus !== '' ? $oldStatus : null,
                'new_status' => $newStatus,
                'action_date' => date('Y-m-d'),
            ]);
        }

        $this->json([
            'ok' => true,
            'watch_url' => $item['watch_url'],
            'status' => $newStatus,
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

    private function isValidStatusTransition(string $type, string $oldStatus, string $newStatus): bool
    {
        if ($newStatus !== 'watched') {
            return false;
        }

        if ($type === 'movie') {
            return $oldStatus === '' || $oldStatus === 'watched';
        }

        if ($type === 'tv') {
            return in_array($oldStatus, ['', 'started', 'watched'], true);
        }

        return false;
    }

    private function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }
}