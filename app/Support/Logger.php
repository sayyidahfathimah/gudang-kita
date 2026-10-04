<?php

namespace App\Support;

final class Logger
{
    public static function error(\Throwable $e, string $event = 'app_error'): void
    {
        self::write('error', $event, ['error_type' => $e::class]);
    }
        public static function write(string $level, string $event, array $context = []): void
    {
        unset($context['password'],$context['token'],$context['session'],$context['authorization']);
        error_log(json_encode(['timestamp' => gmdate('c'),'level' => $level,'service' => 'gudang-kita','environment' => envv('APP_ENV', 'production'),'event' => $event,'trace_id' => $_SERVER['HTTP_TRACEPARENT'] ?? null] + $context, JSON_UNESCAPED_SLASHES));
    }
}
