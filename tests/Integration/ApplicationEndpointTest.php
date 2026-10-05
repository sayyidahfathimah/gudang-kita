<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ApplicationEndpointTest extends TestCase
{
    private function request(string $path): array
    {
        $baseUrl = rtrim((string) (getenv('APP_URL') ?: 'http://localhost:8080'), '/');
        $context = stream_context_create(['http' => ['timeout' => 5, 'ignore_errors' => true]]);
        $body = file_get_contents($baseUrl . $path, false, $context);
        return [$body, $http_response_header ?? []];
    }

    public function testLivenessEndpointReturnsOk(): void
    {
        [$body] = $this->request('/health/live');
        self::assertNotFalse($body);
        self::assertSame('ok', json_decode((string) $body, true)['status'] ?? null);
    }

    public function testMetricsEndpointIsAvailable(): void
    {
        [$body] = $this->request('/metrics');
        self::assertNotFalse($body);
        self::assertStringContainsString('gudang_kita_up', (string) $body);
    }
}
