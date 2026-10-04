<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Security\Auth;
use App\Validation\UserValidator;

final class UserController extends BaseController
{
    private const FORM_VIEW = 'users/form';
    private const ADD_TITLE = 'Add User';
    private UserRepository $repo;
    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->repo = new UserRepository($pdo);
    }
    public function index(): void
    {
        Auth::requireAdmin();
        $result = paginate($this->repo->all(), (int)($_GET['current_page'] ?? 1));
        $this->view('users/index', ['pageTitle' => 'Users','result' => $result]);
    }
    public function create(): void
    {
        Auth::requireAdmin();
        $this->view(self::FORM_VIEW, ['pageTitle' => self::ADD_TITLE,'mode' => 'create','user' => ['username' => '','name' => '','email' => '','role' => 'Member','is_active' => 1]]);
    }
    public function store(): void
    {
        Auth::requireAdmin();
        $this->csrf();
        $d = $this->data();
        $e = UserValidator::validate($d);
        if ($d['password'] === '') {
            $e['password'] = 'Password wajib diisi.';
        }
        if ($e) {
            $this->view(self::FORM_VIEW, ['pageTitle' => self::ADD_TITLE,'mode' => 'create','user' => $d,'errors' => $e]);
            return;
        }try {
            $this->repo->create($d);
            flash('success', 'User berhasil dibuat.');
            redirect('users');
        }
        catch (\PDOException $x) {
            $this->view(self::FORM_VIEW, ['pageTitle' => self::ADD_TITLE,'mode' => 'create','user' => $d,'errors' => ['username' => 'Username harus unik.']]);
        }
    }
    public function edit(int $id): void
    {
        Auth::requireAdmin();
        $u = $this->repo->find($id);
        if (!$u) {
            http_response_code(404);
            require_once BASE_PATH.'/views/404.php';
            return;
        }$this->view(self::FORM_VIEW, ['pageTitle' => 'Edit User','mode' => 'edit','user' => $u]);
    }
    public function update(int $id): void
    {
        Auth::requireAdmin();
        $this->csrf();
        $d = $this->data();
        $e = UserValidator::validate($d);
        if ($e) {
            $d['id'] = $id;
            $this->view(self::FORM_VIEW, ['pageTitle' => 'Edit User','mode' => 'edit','user' => $d,'errors' => $e]);
            return;
        }try {
            $this->repo->update($id, $d);
            flash('success', 'User berhasil diperbarui.');
            redirect('users');
        }
        catch (\PDOException $x) {
            flash('error', 'Username sudah digunakan.');
            redirect('users', ['action' => 'edit','id' => $id]);
        }
    }
    public function delete(int $id): void
    {
        Auth::requireAdmin();
        $this->csrf();
        if ($id === Auth::id()) {
            flash('error', 'Akun yang sedang digunakan tidak dapat dihapus.');
            redirect('users');
        }$this->repo->delete($id);
        flash('success', 'User berhasil dihapus.');
        redirect('users');
    }
    private function data(): array
    {
        return ['username' => trim((string)($_POST['username'] ?? '')),'name' => trim((string)($_POST['name'] ?? '')),'email' => trim((string)($_POST['email'] ?? '')),'role' => (string)($_POST['role'] ?? 'Member'),'is_active' => (int)($_POST['is_active'] ?? 0),'password' => (string)($_POST['password'] ?? '')];
    }
}
