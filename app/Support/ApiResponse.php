<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ApiResponse
{
    /**
     * Build standard success JSON response.
     *
     * @param mixed $data
     * @param array<string, mixed> $meta
     * @param int $status
     * @return JsonResponse
     */
    public static function success(mixed $data = null, array $meta = [], int $status = Response::HTTP_OK): JsonResponse
    {
        $payload = [
            'data' => $data ?? new \stdClass(),
            'meta' => (object) array_merge([
                'timestamp' => now()->toIso8601String(),
            ], $meta),
        ];

        return new JsonResponse($payload, $status);
    }

    /**
     * Build standard error JSON response.
     *
     * @param string $message
     * @param string $code
     * @param mixed $errors
     * @param int $status
     * @return JsonResponse
     */
    public static function error(
        string $message,
        string $code = 'ERROR',
        mixed $errors = null,
        int $status = Response::HTTP_BAD_REQUEST
    ): JsonResponse {
        $payload = [
            'message' => $message,
            'code' => $code,
            'errors' => $errors ?? new \stdClass(),
        ];

        return new JsonResponse($payload, $status);
    }
}
