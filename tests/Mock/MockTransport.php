<?php
declare(strict_types=1);

namespace Tests\Mock;

use Dongasai\qBittorrent\Contract\TransportInterface;
use Dongasai\qBittorrent\Contract\TransportResponse;
use Dongasai\qBittorrent\Exception\NetworkException;

/**
 * 模拟传输层实现
 *
 * 用于测试目的，模拟HTTP请求和响应
 */
class MockTransport implements TransportInterface
{
    /** @var array<string, mixed> 模拟响应数据 */
    private array $mockResponse = [
        'status_code' => 200,
        'headers' => [],
        'body' => 'OK'
    ];

    /** @var \Exception|null 模拟异常 */
    private ?\Exception $mockException = null;

    /** @var array<string, mixed> 最后一次请求信息 */
    private array $lastRequest = [];

    /** @var string 基础URL */
    private string $baseUrl = 'http://localhost:8080';

    /**
     * 设置模拟响应
     *
     * @param int $statusCode 状态码
     * @param array<string, string> $headers 响应头
     * @param string $body 响应体
     * @return void
     */
    public function setMockResponse(int $statusCode, array $headers = [], string $body = 'OK'): void
    {
        $this->mockResponse = [
            'status_code' => $statusCode,
            'headers' => $headers,
            'body' => $body
        ];
        $this->mockException = null;
    }

    /**
     * 设置模拟异常
     *
     * @param \Exception $exception 异常
     * @return void
     */
    public function setMockException(\Exception $exception): void
    {
        $this->mockException = $exception;
    }

    /**
     * 获取最后一次请求信息
     *
     * @return array<string, mixed> 请求信息
     */
    public function getLastRequest(): array
    {
        return $this->lastRequest;
    }

    /**
     * 执行HTTP GET请求
     *
     * @param string $url 请求URL
     * @param array<string, mixed> $parameters 查询参数
     * @param array<string, string> $headers 请求头
     * @return TransportResponse 传输响应
     * @throws NetworkException 网络异常
     */
    public function get(string $url, array $parameters = [], array $headers = []): TransportResponse
    {
        return $this->executeRequest('GET', $url, $parameters, $headers);
    }

    /**
     * 执行HTTP POST请求
     *
     * @param string $url 请求URL
     * @param array<string, mixed> $data 请求数据
     * @param array<string, string> $headers 请求头
     * @return TransportResponse 传输响应
     * @throws NetworkException 网络异常
     */
    public function post(string $url, array $data = [], array $headers = []): TransportResponse
    {
        return $this->executeRequest('POST', $url, $data, $headers);
    }

    /**
     * 执行HTTP PUT请求
     *
     * @param string $url 请求URL
     * @param array<string, mixed> $data 请求数据
     * @param array<string, string> $headers 请求头
     * @return TransportResponse 传输响应
     * @throws NetworkException 网络异常
     */
    public function put(string $url, array $data = [], array $headers = []): TransportResponse
    {
        return $this->executeRequest('PUT', $url, $data, $headers);
    }

    /**
     * 执行HTTP DELETE请求
     *
     * @param string $url 请求URL
     * @param array<string, mixed> $parameters 查询参数
     * @param array<string, string> $headers 请求头
     * @return TransportResponse 传输响应
     * @throws NetworkException 网络异常
     */
    public function delete(string $url, array $parameters = [], array $headers = []): TransportResponse
    {
        return $this->executeRequest('DELETE', $url, $parameters, $headers);
    }

    /**
     * 设置基础URL
     *
     * @param string $baseUrl 基础URL
     * @return static 返回自身以支持链式调用
     */
    public function setBaseUrl(string $baseUrl): static
    {
        $this->baseUrl = $baseUrl;
        return $this;
    }

    /**
     * 获取基础URL
     *
     * @return string 基础URL
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * 设置超时时间
     *
     * @param int $timeout 超时时间（秒）
     * @return static 返回自身以支持链式调用
     */
    public function setTimeout(int $timeout): static
    {
        // Mock实现中不执行实际操作
        return $this;
    }

    /**
     * 设置请求头
     *
     * @param array<string, string> $headers 请求头
     * @return static 返回自身以支持链式调用
     */
    public function setHeaders(array $headers): static
    {
        // Mock实现中不执行实际操作
        return $this;
    }

    /**
     * 添加请求头
     *
     * @param string $name 头名称
     * @param string $value 头值
     * @return static 返回自身以支持链式调用
     */
    public function addHeader(string $name, string $value): static
    {
        // Mock实现中不执行实际操作
        return $this;
    }

    /**
     * 设置认证信息
     *
     * @param string $username 用户名
     * @param string $password 密码
     * @return static 返回自身以支持链式调用
     */
    public function setAuth(string $username, string $password): static
    {
        // Mock实现中不执行实际操作
        return $this;
    }

    /**
     * 设置Cookie
     *
     * @param string $cookie Cookie字符串
     * @return static 返回自身以支持链式调用
     */
    public function setCookie(string $cookie): static
    {
        // Mock实现中不执行实际操作
        return $this;
    }

    /**
     * 启用/禁用SSL验证
     *
     * @param bool $verify 是否验证SSL
     * @return static 返回自身以支持链式调用
     */
    public function setSslVerify(bool $verify): static
    {
        // Mock实现中不执行实际操作
        return $this;
    }

    /**
     * 执行HTTP请求的通用方法
     *
     * @param string $method HTTP方法
     * @param string $url 请求URL
     * @param array<string, mixed> $data 请求数据或参数
     * @param array<string, string> $headers 请求头
     * @return TransportResponse 传输响应
     * @throws NetworkException 网络异常
     */
    private function executeRequest(string $method, string $url, array $data, array $headers): TransportResponse
    {
        // 记录最后一次请求信息
        $this->lastRequest = [
            'method' => $method,
            'url' => $url,
            'data' => $data,
            'headers' => $headers
        ];

        // 如果设置了模拟异常，抛出异常
        if ($this->mockException !== null) {
            throw $this->mockException;
        }

        // 返回模拟响应
        return new MockTransportResponse(
            $this->mockResponse['status_code'],
            $this->mockResponse['headers'],
            $this->mockResponse['body']
        );
    }
}

/**
 * 模拟传输响应实现
 */
class MockTransportResponse implements TransportResponse
{
    private int $statusCode;
    private array $headers;
    private string $body;
    private ?array $jsonData = null;

    public function __construct(int $statusCode, array $headers, string $body)
    {
        $this->statusCode = $statusCode;
        $this->headers = $headers;
        $this->body = $body;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getJson(): ?array
    {
        if ($this->jsonData === null) {
            $decoded = json_decode($this->body, true);
            $this->jsonData = is_array($decoded) ? $decoded : null;
        }
        return $this->jsonData;
    }

    public function isSuccess(int ...$acceptableCodes): bool
    {
        $codes = empty($acceptableCodes) ? [200, 201, 204] : $acceptableCodes;
        return in_array($this->statusCode, $codes);
    }

    public function isJson(): bool
    {
        return $this->getJson() !== null;
    }

    public function getHeader(string $name): ?string
    {
        return $this->headers[$name] ?? null;
    }
}