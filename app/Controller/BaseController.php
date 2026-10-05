<?php

namespace App\Controller;

use App\Security\Auth;
use App\Security\CsrfToken;

abstract class BaseController
{
    public function __construct(protected \PDO $pdo)
    {
    }
    protected function view(string $view, array $data = []): void
    {
        $data['currentUser'] = Auth::user();
        $data['csrfField'] = CsrfToken::field();
        $data['pageTitle'] = $data['pageTitle'] ?? 'Project Activity';
        extract($data, EXTR_SKIP);
        include_once BASE_PATH.'/views/layouts/app.php';
    }
    protected function redirect(string $page, array $params = []): never
    {
        redirect($page, $params);
    }
    protected function csrf(): void
    {
        if (!CsrfToken::validate($_POST['csrf_token'] ?? null)) {
            http_response_code(403);
            require_once BASE_PATH.'/views/403.php';
            exit;
        }
    }

    protected function errorMessage(\Throwable $error, string $fallback): string
    {
        return $error instanceof \DomainException || $error instanceof \InvalidArgumentException
            ? $error->getMessage()
            : $fallback;
    }
}
