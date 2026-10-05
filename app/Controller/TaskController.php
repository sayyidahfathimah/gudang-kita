<?php

namespace App\Controller;

use App\Repository\TaskRepository;
use App\Repository\ProjectRepository;
use App\Repository\UserRepository;
use App\Security\Auth;
use App\Validation\TaskValidator;

final class TaskController extends BaseController
{
    private const NOT_FOUND_VIEW = '/views/404.php';
    private const TO_DO = 'To Do';
    private TaskRepository $repo;
    private ProjectRepository $projects;
    private UserRepository $users;
    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->repo = new TaskRepository($pdo);
        $this->projects = new ProjectRepository($pdo);
        $this->users = new UserRepository($pdo);
    }
    public function index(): void
    {
        $f = ['search' => trim((string)($_GET['search'] ?? '')),'project_id' => (string)($_GET['project_id'] ?? ''),'status' => (string)($_GET['status'] ?? ''),'priority' => (string)($_GET['priority'] ?? ''),'sort' => (string)($_GET['sort'] ?? 'due_asc'),'page' => max(1, (int)($_GET['current_page'] ?? 1))];
        $r = $this->repo->list($f, Auth::role() === 'Sales' ? Auth::id() : null);
        $projects = Auth::role() === 'Sales' ? $this->projects->forSales(Auth::id()) : $this->projects->all();
        $this->view('tasks/index', ['pageTitle' => 'Tasks','result' => $r,'projects' => $projects,'filters' => $f]);
    }
    public function create(): void
    {
        Auth::requireAdmin();
        $this->form('create', null);
    }
    public function store(): void
    {
        Auth::requireAdmin();
        $this->csrf();
        $d = $this->data();
        $p = $this->projects->find((int)$d['project_id']);
        $e = TaskValidator::validate($d, $p);
        if (!$p) {
            $e['project_id'] = 'Project tidak ditemukan.';
        }$u = $this->users->find((int)$d['assignee_id']);
        if (!$u || $u['role'] !== 'Sales') {
            $e['assignee_id'] = 'Assignee harus merupakan Sales yang valid.';
        }
        if ($e) {
            $this->form('create', $d, $e);
            return;
        }$this->repo->create($d);
        flash('success', 'Task berhasil dibuat.');
        redirect('tasks');
    }
    public function edit(int $id): void
    {
        Auth::requireAdmin();
        $t = $this->repo->find($id);
        if (!$t) {
            http_response_code(404);
            require_once BASE_PATH.self::NOT_FOUND_VIEW;
            return;
        }$this->form('edit', $t);
    }
    public function update(int $id): void
    {
        Auth::requireAdmin();
        $this->csrf();
        $d = $this->data();
        $p = $this->projects->find((int)$d['project_id']);
        $e = TaskValidator::validate($d, $p);
        if (!$p) {
            $e['project_id'] = 'Project tidak ditemukan.';
        }$u = $this->users->find((int)$d['assignee_id']);
        if (!$u || $u['role'] !== 'Sales') {
            $e['assignee_id'] = 'Assignee harus merupakan Sales yang valid.';
        }
        if ($e) {
            $d['id'] = $id;
            $this->form('edit', $d, $e);
            return;
        }$this->repo->update($id, $d);
        flash('success', 'Task berhasil diperbarui.');
        redirect('tasks');
    }
    public function status(int $id): void
    {
        Auth::require();
        $this->csrf();
        $status = (string)($_POST['status'] ?? '');
        if (!in_array($status, [self::TO_DO,'In Progress','Done'], true)) {
            flash('error', 'Status tidak valid.');
            redirect('tasks');
        }
        if (Auth::role() === 'Sales') {
            if (!$this->repo->updateStatus($id, Auth::id(), $status)) {
                http_response_code(403);
                require_once BASE_PATH.'/views/403.php';
                return;
            }
        }
        else {
            $t = $this->repo->find($id);
            if (!$t) {
                http_response_code(404);
                require_once BASE_PATH.self::NOT_FOUND_VIEW;
                return;
            }$this->repo->update($id, [...$t,'project_id' => $t['project_id'],'assignee_id' => $t['assignee_id']]);
        }$target = (int)($_POST['return_project'] ?? 0);
        redirect('tasks', $target ? ['project_id' => $target] : []);
    }
    public function delete(int $id): void
    {
        Auth::requireAdmin();
        $this->csrf();
        $this->repo->delete($id);
        flash('success', 'Task berhasil dihapus.');
        redirect('tasks');
    }
    public function show(int $id): void
    {
        $t = $this->repo->find($id);
        if (!$t) {
            http_response_code(404);
            require_once BASE_PATH.self::NOT_FOUND_VIEW;
            return;
        }
        if (Auth::role() === 'Sales' && (int)$t['assignee_id'] !== Auth::id()) {
            http_response_code(403);
            require_once BASE_PATH.'/views/403.php';
            return;
        }$this->view('tasks/show', ['pageTitle' => 'Task Detail','task' => $t]);
    }
    private function form(string $mode, ?array $task, array $errors = []): void
    {
        $this->view('tasks/form', ['pageTitle' => $mode === 'create' ? 'Add Task' : 'Edit Task','mode' => $mode,'task' => $task ?: ['project_id' => '','title' => '','description' => '','assignee_id' => '','status' => self::TO_DO,'priority' => 'Medium','due_date' => ''],'projects' => $this->projects->all(),'members' => array_values(array_filter($this->users->all(), fn ($u) => $u['role'] === 'Sales')),'errors' => $errors]);
    }
    private function data(): array
    {
        return ['project_id' => (int)($_POST['project_id'] ?? 0),'title' => trim((string)($_POST['title'] ?? '')),'description' => trim((string)($_POST['description'] ?? '')),'assignee_id' => (int)($_POST['assignee_id'] ?? 0),'status' => (string)($_POST['status'] ?? self::TO_DO),'priority' => (string)($_POST['priority'] ?? 'Medium'),'due_date' => (string)($_POST['due_date'] ?? '')];
    }
}
