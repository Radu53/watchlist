<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\View;

class HomeController
{
    public function index(): void
    {
        $pdo = Database::connection();

        $search = trim($_GET['search'] ?? '');
        $year = trim($_GET['year'] ?? '');
        $type = trim($_GET['type'] ?? '');
        $status = trim($_GET['status'] ?? '');

        $sql = "SELECT * FROM media WHERE watch_url IS NOT NULL AND watch_url != ''";
        $params = [];

        if ($search !== '') {
            $sql .= " AND title LIKE :search";
            $params['search'] = '%' . $search . '%';
        }

        if ($year !== '') {
            $sql .= " AND year = :year";
            $params['year'] = $year;
        }

        if ($type !== '') {
            $sql .= " AND type = :type";
            $params['type'] = $type;
        }

        if ($status !== '') {
            if ($status === 'none') {
                $sql .= " AND (status IS NULL OR status = '')";
            } else {
                $sql .= " AND status = :status";
                $params['status'] = $status;
            }
        }

        $sql .= " ORDER BY created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll();

        View::render('home/index', [
            'items' => $items,
            'filters' => compact('search', 'year', 'type', 'status'),
        ]);
    }
}