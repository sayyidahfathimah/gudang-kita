<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class HealthEndpointTest extends TestCase
{
    public function testApplicationAndDatabaseAreReady(): void
    {
        $baseUrl = rtrim((string) (getenv('APP_URL') ?: 'http://localhost:8080'), '/');
        $context = stream_context_create(['http' => ['timeout' => 5, 'ignore_errors' => true]]);
        $response = file_get_contents($baseUrl . '/health/ready', false, $context);

        $this->assertNotFalse($response, 'Aplikasi Docker harus berjalan sebelum integration test.');
        $payload = json_decode((string) $response, true);
        $this->assertSame('ok', $payload['status'] ?? null);
        $this->assertSame('ready', $payload['check'] ?? null);
    }
}
