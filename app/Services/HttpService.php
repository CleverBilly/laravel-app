<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;

class HttpService
{
    protected Client $client;

    protected array $defaultOptions = [];

    public function __construct(array $config = [])
    {
        $this->client = new Client(array_merge([
            'timeout' => 30,
            'verify' => true,
            'http_errors' => false, // 不抛出 HTTP 错误异常
        ], $config));

        $this->defaultOptions = [
            RequestOptions::HEADERS => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ];
    }

    /**
     * GET 请求
     *
     * @param string $url
     * @param array $params
     * @param array $headers
     * @return array
     */
    public function get(string $url, array $params = [], array $headers = []): array
    {
        try {
            $options = array_merge($this->defaultOptions, [
                RequestOptions::QUERY => $params,
                RequestOptions::HEADERS => array_merge($this->defaultOptions[RequestOptions::HEADERS], $headers),
            ]);

            $response = $this->client->get($url, $options);

            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            return $this->handleException($e, $url);
        }
    }

    /**
     * POST 请求
     *
     * @param string $url
     * @param array $data
     * @param array $headers
     * @return array
     */
    public function post(string $url, array $data = [], array $headers = []): array
    {
        try {
            $options = array_merge($this->defaultOptions, [
                RequestOptions::JSON => $data,
                RequestOptions::HEADERS => array_merge($this->defaultOptions[RequestOptions::HEADERS], $headers),
            ]);

            $response = $this->client->post($url, $options);

            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            return $this->handleException($e, $url);
        }
    }

    /**
     * PUT 请求
     *
     * @param string $url
     * @param array $data
     * @param array $headers
     * @return array
     */
    public function put(string $url, array $data = [], array $headers = []): array
    {
        try {
            $options = array_merge($this->defaultOptions, [
                RequestOptions::JSON => $data,
                RequestOptions::HEADERS => array_merge($this->defaultOptions[RequestOptions::HEADERS], $headers),
            ]);

            $response = $this->client->put($url, $options);

            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            return $this->handleException($e, $url);
        }
    }

    /**
     * PATCH 请求
     *
     * @param string $url
     * @param array $data
     * @param array $headers
     * @return array
     */
    public function patch(string $url, array $data = [], array $headers = []): array
    {
        try {
            $options = array_merge($this->defaultOptions, [
                RequestOptions::JSON => $data,
                RequestOptions::HEADERS => array_merge($this->defaultOptions[RequestOptions::HEADERS], $headers),
            ]);

            $response = $this->client->patch($url, $options);

            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            return $this->handleException($e, $url);
        }
    }

    /**
     * DELETE 请求
     *
     * @param string $url
     * @param array $params
     * @param array $headers
     * @return array
     */
    public function delete(string $url, array $params = [], array $headers = []): array
    {
        try {
            $options = array_merge($this->defaultOptions, [
                RequestOptions::QUERY => $params,
                RequestOptions::HEADERS => array_merge($this->defaultOptions[RequestOptions::HEADERS], $headers),
            ]);

            $response = $this->client->delete($url, $options);

            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            return $this->handleException($e, $url);
        }
    }

    /**
     * 表单提交请求
     *
     * @param string $url
     * @param array $data
     * @param array $headers
     * @return array
     */
    public function form(string $url, array $data = [], array $headers = []): array
    {
        try {
            $options = [
                RequestOptions::FORM_PARAMS => $data,
                RequestOptions::HEADERS => array_merge([
                    'Accept' => 'application/json',
                ], $headers),
            ];

            $response = $this->client->post($url, $options);

            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            return $this->handleException($e, $url);
        }
    }

    /**
     * 处理响应
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     * @return array
     */
    protected function handleResponse($response): array
    {
        $statusCode = $response->getStatusCode();
        $body = $response->getBody()->getContents();

        $data = [
            'success' => $statusCode >= 200 && $statusCode < 300,
            'status_code' => $statusCode,
            'data' => null,
            'message' => '',
        ];

        // 尝试解析 JSON
        $jsonData = json_decode($body, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $data['data'] = $jsonData;
        } else {
            $data['data'] = $body;
        }

        // 根据状态码设置消息
        if (!$data['success']) {
            $data['message'] = $this->getStatusMessage($statusCode);
        }

        return $data;
    }

    /**
     * 处理异常
     *
     * @param GuzzleException $e
     * @param string $url
     * @return array
     */
    protected function handleException(GuzzleException $e, string $url): array
    {
        logger_error('HTTP Request Failed', [
            'url' => $url,
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return [
            'success' => false,
            'status_code' => 0,
            'data' => null,
            'message' => 'Request failed: ' . $e->getMessage(),
        ];
    }

    /**
     * 获取状态码对应的消息
     *
     * @param int $statusCode
     * @return string
     */
    protected function getStatusMessage(int $statusCode): string
    {
        return match ($statusCode) {
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            422 => 'Unprocessable Entity',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            default => 'Request Failed',
        };
    }

    /**
     * 设置默认请求头
     *
     * @param array $headers
     * @return $this
     */
    public function setDefaultHeaders(array $headers): self
    {
        $this->defaultOptions[RequestOptions::HEADERS] = array_merge(
            $this->defaultOptions[RequestOptions::HEADERS],
            $headers
        );

        return $this;
    }

    /**
     * 设置超时时间
     *
     * @param int $seconds
     * @return $this
     */
    public function setTimeout(int $seconds): self
    {
        $this->defaultOptions[RequestOptions::TIMEOUT] = $seconds;

        return $this;
    }
}

