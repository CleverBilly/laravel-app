<?php

namespace App\Http\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    /**
     * 成功响应
     *
     * @param mixed $data 响应数据
     * @param string $message 响应消息
     * @param int $code 响应码
     * @return JsonResponse
     */
    protected function success($data = null, string $message = 'success', int $code = 200): JsonResponse
    {
        return response()->json([
            'code' => $code,
            'message' => $message,
            'data' => $data,
            'timestamp' => now()->timestamp,
        ], $code);
    }

    /**
     * 失败响应
     *
     * @param string $message 错误消息
     * @param int $code 错误码
     * @param mixed $data 额外数据
     * @return JsonResponse
     */
    protected function error(string $message = 'error', int $code = 400, $data = null): JsonResponse
    {
        return response()->json([
            'code' => $code,
            'message' => $message,
            'data' => $data,
            'timestamp' => now()->timestamp,
        ], $code);
    }

    /**
     * 未授权响应
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->error($message, 401);
    }

    /**
     * 禁止访问响应
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return $this->error($message, 403);
    }

    /**
     * 未找到响应
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function notFound(string $message = 'Not Found'): JsonResponse
    {
        return $this->error($message, 404);
    }

    /**
     * 服务器错误响应
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function serverError(string $message = 'Internal Server Error'): JsonResponse
    {
        return $this->error($message, 500);
    }

    /**
     * 分页响应
     *
     * @param mixed $data 分页数据
     * @param string $message
     * @return JsonResponse
     */
    protected function paginate($data, string $message = 'success'): JsonResponse
    {
        if (method_exists($data, 'toArray')) {
            $data = $data->toArray();
        }

        return $this->success([
            'list' => $data['data'] ?? [],
            'pagination' => [
                'current_page' => $data['current_page'] ?? 1,
                'per_page' => $data['per_page'] ?? 15,
                'total' => $data['total'] ?? 0,
                'last_page' => $data['last_page'] ?? 1,
            ],
        ], $message);
    }
}

