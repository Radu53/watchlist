<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\View;

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

        $status = 'draft';
        if ($type !== 'unknown' || $year !== '' || $description !== '' || $coverUrl !== '' || $watchUrl !== '') {
            $status = 'pending';
        }

        $watchDomain = null;
        if ($watchUrl !== '') {
            $host = parse_url($watchUrl, PHP_URL_HOST);
            if ($host) {
                $watchDomain = preg_replace('/^www\./i', '', strtolower($host));
            }
        }

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
            WHERE status = 'draft'
            ORDER BY created_at DESC
        ");

        $items = $stmt->fetchAll();

        View::render('media/todo', [
            'items' => $items,
        ]);
    }
}