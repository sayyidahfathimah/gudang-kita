<?php

declare(strict_types=1);
function e(mixed $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
function envv(string $key, mixed $default = null): mixed
{
    return $_ENV[$key] ?? getenv($key) ?: $default;
}
function redirect(string $page, array $params = []): never
{
    header('Location: ?'.http_build_query(array_merge(['page' => $page], $params)));
    exit;
}
function flash(string $type, string $message): void
{
    $_SESSION['_flash'][$type] = $message;
}
function getFlash(string $type): string
{
    $m = $_SESSION['_flash'][$type] ?? '';
    unset($_SESSION['_flash'][$type]);
    return $m;
}
function paginate(array $items, int $page, int $perPage = 10): array
{
    $total = count($items);
    $pages = max(1, (int)ceil($total / $perPage));
    $page = min(max(1, $page), $pages);
    return ['items' => array_slice($items, ($page - 1) * $perPage, $perPage),'page' => $page,'pages' => $pages,'total' => $total];
}
function paginationLink(int $page, string $label, array $params, string $class = ''): string
{
    $query = http_build_query(array_merge($params, ['current_page' => $page]));
    return '<a class="page-btn '.$class.'" href="?'.e($query).'">'.$label.'</a>';
}

function paginationLinks(array $result, array $params): string
{
    if ($result['pages'] <= 1) {
        return '';
    }
    $html = '<nav class="pagination" aria-label="Pagination">';
    if ($result['page'] > 1) {
        $html .= paginationLink($result['page'] - 1, '‹ Sebelumnya', $params);
    }
    for ($i = 1; $i <= $result['pages']; $i++) {
        $html .= paginationLink($i, (string) $i, $params, $i === $result['page'] ? 'current' : '');
    }
    if ($result['page'] < $result['pages']) {
        $html .= paginationLink($result['page'] + 1, 'Berikutnya ›', $params);
    }
    return $html.'</nav>';
}
