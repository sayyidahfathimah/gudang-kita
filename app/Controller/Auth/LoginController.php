<?php

namespace App\Controller\Auth;

use App\Controller\BaseController;
use App\Repository\UserRepository;
use App\Security\Auth;
use App\Security\Hash;
use App\Security\CsrfToken;

final class LoginController extends BaseController
{
    public function index(): void
    {
        if (Auth::check()) {
            redirect('dashboard');
        }
        include_once BASE_PATH.'/views/auth/login.php';
    }
    public function handle(): void
    {
        if (!CsrfToken::validate($_POST['csrf_token'] ?? null)) {
            flash('error', 'Sesi form tidak valid. Silakan coba lagi.');
            redirect('login');
        }
        $u = trim((string)($_POST['username'] ?? ''));
        $p = (string)($_POST['password'] ?? '');
        $user = (new UserRepository($this->pdo))->findByUsername($u);
        if (!$user || !(bool)$user['is_active'] || !Hash::verify($p, $user['password'])) {
            flash('error', 'Username atau password tidak valid.');
            redirect('login');
        }
        Auth::login($user);
        redirect('dashboard');
    }
    public function logout(): void
    {
        $this->csrf();
        Auth::logout();
        redirect('login');
    }
}
