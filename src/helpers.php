<?php declare(strict_types=1);

namespace Workbunny\WebmanConnectionsManager;

use support\Response;

if (!function_exists('rest_success')) {
    /**
     * @param array|null $data
     * @param array $headers
     * @return Response
     */
    function rest_success(array|null $data, array $headers = []): Response
    {
        return response(
            $data === null
                ? ''
                : ($data ? json_encode($data, JSON_UNESCAPED_UNICODE) : '{}'),
            headers: [
                'Content-Type' => 'application/json'
            ] + $headers
        );
    }
}

if (!function_exists('rest_error')) {
    /**
     * @param int $code
     * @param string $message
     * @param int|string $error
     * @param array $extra
     * @param array $headers
     * @return Response
     */
    function rest_error(int $code, string $message, int|string $error, array $extra = [], array $headers = []): Response
    {
        return response(
            json_encode([
                'message' => $message,
                'error'   => $error,
                'extra'   => $extra
            ], JSON_UNESCAPED_UNICODE),
            status: $code,
            headers: [
                'Content-Type' => 'application/json'
            ] + $headers
        );
    }
}

if (!function_exists('debug_dump')) {
    /**
     * @param mixed ...$params
     * @return void
     */
    function debug_dump(mixed ...$params): void
    {
        if (\config('app.debug')) {
            dump(...$params);
        }
    }
}

if (!function_exists('ms_time')) {
    /**
     * 获取int型的毫秒时间戳
     * @return int
     */
    function ms_time(): int
    {
        return intval(ceil(microtime(true) * 1000));
    }
}
