<?php

namespace App\Controller;

use App\Repository\ProjectRepository;
use App\Security\Auth;
use App\Validation\ProjectValidator;

final class ProjectController extends BaseController
{
    private const FORM_VIEW = 'projects/form';
    private const NOT_FOUND_VIEW = '/views/404.php';
    private ProjectRepository $repo;
    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->repo = new ProjectRepository($pdo);
    }
    public function index(): void
    {
        $f = ['search' => trim((string)($_GET['search'] ?? '')),'status' => (string)($_GET['status'] ?? '')];
        $rows = Auth::role() === 'Admin' ? $this->repo->all($f) : $this->repo->forSales(Auth::id(), $f);
        $result = paginate($rows, (int)($_GET['current_page'] ?? 1));
        $this->view('projects/index', ['pageTitle' => 'Projects','result' => $result,'filters' => $f]);
    }
    public function create(): void
    {
        Auth::requireAdmin();
        $this->view(self::FORM_VIEW, ['pageTitle' => 'Add Project','mode' => 'create','project' => ['name' => '','description' => '','status' => 'Planning','start_date' => date('Y-m-d'),'target_date' => date('Y-m-d', strtotime('+30 days'))]]);
    }
    public function store(): void
    {
        Auth::requireAdmin();
        $this->csrf();
        $d = $this->data();
        $e = ProjectValidator::validate($d);
        if ($e) {
            $this->view(self::FORM_VIEW, ['pageTitle' => 'Add Project','mode' => 'create','project' => $d,'errors' => $e]);
            return;
        }$this->repo->create($d);
        flash('success', 'Project berhasil dibuat.');
        redirect('projects');
    }
    public function edit(int $id): void
    {
        Auth::requireAdmin();
        $p = $this->repo->find($id);
        if (!$p) {
            http_response_code(404);
            require_once BASE_PATH.self::NOT_FOUND_VIEW;
            return;
        }$this->view(self::FORM_VIEW, ['pageTitle' => 'Edit Project','mode' => 'edit','project' => $p]);
    }
    public function update(int $id): void
    {
        Auth::requireAdmin();
        $this->csrf();
        $p = $this->repo->find($id);
        if (!$p) {
            http_response_code(404);
            require_once BASE_PATH.self::NOT_FOUND_VIEW;
            return;
        }$d = $this->data();
        $e = ProjectValidator::validate($d);
        if ($p['status'] !== 'Archived' && $d['status'] === 'Archived' && $this->repo->taskCount($id) > 0) {
            $e['status'] = 'Project yang sudah memiliki task hanya dapat diarsipkan melalui aksi Archive.';
        }$d['id'] = $id;
        if ($e) {
            $this->view(self::FORM_VIEW, ['pageTitle' => 'Edit Project','mode' => 'edit','project' => $d,'errors' => $e]);
            return;
        }$this->repo->update($id, $d);
        flash('success', 'Project berhasil diperbarui.');
        redirect('projects');
    }
    public function archive(int $id): void
    {
        Auth::requireAdmin();
        $this->csrf();
        if ($this->repo->taskCount($id) > 0) {
            $this->repo->archive($id);
            flash('success', 'Project berhasil diarsipkan.');
        }
        else {
            flash('error', 'Project tanpa task tidak perlu diarsipkan; ubah status melalui edit.');
        }redirect('projects');
    }
    public function show(int $id): void
    {
        $p = $this->repo->find($id);
        if (!$p) {
            http_response_code(404);
            require_once BASE_PATH.self::NOT_FOUND_VIEW;
            return;
        }
        if (Auth::role() === 'Sales' && !in_array((string)$id, array_map('strval', array_column($this->repo->forSales(Auth::id()), 'id')), true)) {
            http_response_code(403);
            require_once BASE_PATH.'/views/403.php';
            return;
        }$this->view('projects/show', ['pageTitle' => 'Project Detail','project' => $p]);
    }
    private function data(): array
    {
        return ['name' => trim((string)($_POST['name'] ?? '')),'description' => trim((string)($_POST['description'] ?? '')),'status' => (string)($_POST['status'] ?? 'Planning'),'start_date' => (string)($_POST['start_date'] ?? ''),'target_date' => (string)($_POST['target_date'] ?? '')];
    }
}
