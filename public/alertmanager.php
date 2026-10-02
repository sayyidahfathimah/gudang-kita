<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$payload = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($payload)) {
    http_response_code(400);
    exit;
}

$alerts = is_array($payload['alerts'] ?? null) ? $payload['alerts'] : [];
foreach ($alerts as $alert) {
    \App\Support\Logger::write('warning', 'alertmanager_notification', [
        'alert_name' => (string) ($alert['labels']['alertname'] ?? 'unknown'),
        'severity' => (string) ($alert['labels']['severity'] ?? 'unknown'),
        'status' => (string) ($alert['status'] ?? 'unknown'),
    ]);
}

http_response_code(204);
