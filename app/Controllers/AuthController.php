<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\View;
use PDO;

class AuthController
{
    public function showLogin(): void
    {
        if (!empty($_SESSION['user_id'])) {
            header('Location: ' . url('/'));
            exit;
        }

        View::render('auth/login', [
            'error' => null,
        ]);
    }

    public function login(): void
    {
        $pdo = Database::connection();

        $username = trim($_POST['username'] ?? '');
        $password = (string)($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            View::render('auth/login', [
                'error' => 'Username and password are required.',
            ]);
            return;
        }

        $stmt = $pdo->prepare("
            SELECT id, username, password_hash
            FROM users
            WHERE username = :username
            LIMIT 1
        ");
        $stmt->execute(['username' => $username]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            View::render('auth/login', [
                'error' => 'Invalid username or password.',
            ]);
            return;
        }

        session_regenerate_id(true);

        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['username'] = $user['username'];

        header('Location: ' . url('/'));
        exit;
    }

    public function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();

        header('Location: ' . url('/login'));
        exit;
    }
}