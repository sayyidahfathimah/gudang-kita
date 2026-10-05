<?php

declare(strict_types=1);

namespace App\Controller;

use App\Security\Auth;

final class ApiController extends BaseController
{
    public function availability(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (!Auth::check()) {
            http_response_code(401);
            echo json_encode(['error' => 'Autentikasi diperlukan.'], JSON_THROW_ON_ERROR);
            return;
        }
        $sku = trim((string) ($_GET['sku'] ?? ''));
        if ($sku === '') {
            http_response_code(422);
            echo json_encode(['error' => 'Parameter sku wajib diisi.'], JSON_THROW_ON_ERROR);
            return;
        }
        $statement = $this->pdo->prepare('SELECT id,code AS sku,name,unit FROM products WHERE code=? AND status=\'Active\'');
        $statement->execute([$sku]);
        $product = $statement->fetch();
        if (!$product) {
            http_response_code(404);
            echo json_encode(['error' => 'Produk tidak ditemukan.'], JSON_THROW_ON_ERROR);
            return;
        }
        $stock = $this->pdo->prepare('SELECT w.code AS warehouse_code,w.name AS warehouse_name,COALESCE(s.current_stock,0) AS available_stock FROM warehouses w LEFT JOIN stocks s ON s.warehouse_id=w.id AND s.product_id=? WHERE w.status=\'Active\' ORDER BY w.name');
        $stock->execute([(int) $product['id']]);
        $product['warehouses'] = $stock->fetchAll();
        unset($product['id']);
        echo json_encode(['data' => $product], JSON_THROW_ON_ERROR);
    }

    public function notFound(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint tidak ditemukan.'], JSON_THROW_ON_ERROR);
    }
}
