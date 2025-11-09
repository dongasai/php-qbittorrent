<?php
declare(strict_types=1);

namespace Dongasai\qBittorrent\API\v2;

use Dongasai\qBittorrent\Contract\ApiInterface;
use Dongasai\qBittorrent\Contract\TransportInterface;
use Dongasai\qBittorrent\Request\Application\GetVersionRequest;
use Dongasai\qBittorrent\Request\Application\GetWebApiVersionRequest;
use Dongasai\qBittorrent\Request\Application\GetBuildInfoRequest;
use Dongasai\qBittorrent\Response\Application\VersionResponse;
use Dongasai\qBittorrent\Response\Application\BuildInfoResponse;
use Dongasai\qBittorrent\Exception\NetworkException;
use Dongasai\qBittorrent\Exception\ApiRuntimeException;
use Dongasai\qBittorrent\Exception\ValidationException;

/**
 * Application API v2
 *
 * 提供应用管理相关的API功能
 */
class ApplicationAPI implements ApiInterface
{
    /** @var TransportInterface 传输层实例 */
    private TransportInterface $transport;

    /**
     * 构造函数
     *
     * @param TransportInterface $transport 传输层实例
     */
    public function __construct(TransportInterface $transport)
    {
        $this->transport = $transport;
    }

    /**
     * 获取API的基础路径
     *
     * @return string API基础路径
     */
    public function getBasePath(): string
    {
        return '/api/v2/app';
    }

    /**
     * 获取传输层实例
     *
     * @return TransportInterface 传输层实例
     */
    public function getTransport(): TransportInterface
    {
        return $this->transport;
    }

    /**
     * 设置传输层实例
     *
     * @param TransportInterface $transport 传输层实例
     * @return static 返回自身以支持链式调用
     */
    public function setTransport(TransportInterface $transport): static
    {
        $this->transport = $transport;
        return $this;
    }

    /**
     * 执行GET请求
     *
     * @param string $endpoint API端点
     * @param array<string, mixed> $parameters 请求参数
     * @param array<string, string> $headers 请求头
     * @return \Dongasai\qBittorrent\Contract\ResponseInterface 响应对象
     */
    public function get(string $endpoint, array $parameters = [], array $headers = []): \Dongasai\qBittorrent\Contract\ResponseInterface
    {
        $url = $this->getBasePath() . $endpoint;
        $transportResponse = $this->transport->get($url, $parameters, $headers);
        return $this->createGenericResponse($transportResponse);
    }

    /**
     * 执行POST请求
     *
     * @param string $endpoint API端点
     * @param array<string, mixed> $data 请求数据
     * @param array<string, string> $headers 请求头
     * @return \Dongasai\qBittorrent\Contract\ResponseInterface 响应对象
     */
    public function post(string $endpoint, array $data = [], array $headers = []): \Dongasai\qBittorrent\Contract\ResponseInterface
    {
        $url = $this->getBasePath() . $endpoint;
        $transportResponse = $this->transport->post($url, $data, $headers);
        return $this->createGenericResponse($transportResponse);
    }

    /**
     * 获取应用版本
     *
     * @param GetVersionRequest $request 获取版本请求
     * @return VersionResponse 版本响应
     * @throws NetworkException 网络异常
     * @throws ValidationException 验证异常
     * @throws ApiRuntimeException API运行时异常
     */
    public function getVersion(GetVersionRequest $request): VersionResponse
    {
        // 验证请求
        $validation = $request->validate();
        if (!$validation->isValid()) {
            throw ValidationException::fromValidationResult(
                $validation,
                'GetVersion request validation failed'
            );
        }

        try {
            // 发送请求
            $url = $this->getBasePath() . $request->getEndpoint();
            $transportResponse = $this->transport->get(
                $url,
                $request->toArray(),
                $request->getHeaders()
            );

            // 处理响应
            return $this->handleVersionResponse($transportResponse, $request);

        } catch (NetworkException $e) {
            throw new ApiRuntimeException(
                'Get version failed due to network error: ' . $e->getMessage(),
                'GET_VERSION_NETWORK_ERROR',
                ['original_error' => $e->getMessage()],
                $url,
                'GET',
                $transportResponse->getStatusCode() ?? null,
                ['request_summary' => $request->getSummary()],
                $e
            );
        }
    }

    /**
     * 获取Web API版本
     *
     * @param GetWebApiVersionRequest $request 获取Web API版本请求
     * @return VersionResponse Web API版本响应
     * @throws NetworkException 网络异常
     * @throws ValidationException 验证异常
     * @throws ApiRuntimeException API运行时异常
     */
    public function getWebApiVersion(GetWebApiVersionRequest $request): VersionResponse
    {
        // 验证请求
        $validation = $request->validate();
        if (!$validation->isValid()) {
            throw ValidationException::fromValidationResult(
                $validation,
                'GetWebApiVersion request validation failed'
            );
        }

        try {
            // 发送请求
            $url = $this->getBasePath() . $request->getEndpoint();
            $transportResponse = $this->transport->get(
                $url,
                $request->toArray(),
                $request->getHeaders()
            );

            // 处理响应
            return $this->handleVersionResponse($transportResponse, $request);

        } catch (NetworkException $e) {
            throw new ApiRuntimeException(
                'Get Web API version failed due to network error: ' . $e->getMessage(),
                'GET_WEB_API_VERSION_NETWORK_ERROR',
                ['original_error' => $e->getMessage()],
                $url,
                'GET',
                $transportResponse->getStatusCode() ?? null,
                ['request_summary' => $request->getSummary()],
                $e
            );
        }
    }

    /**
     * 获取构建信息
     *
     * @param GetBuildInfoRequest $request 获取构建信息请求
     * @return BuildInfoResponse 构建信息响应
     * @throws NetworkException 网络异常
     * @throws ValidationException 验证异常
     * @throws ApiRuntimeException API运行时异常
     */
    public function getBuildInfo(GetBuildInfoRequest $request): BuildInfoResponse
    {
        // 验证请求
        $validation = $request->validate();
        if (!$validation->isValid()) {
            throw ValidationException::fromValidationResult(
                $validation,
                'GetBuildInfo request validation failed'
            );
        }

        try {
            // 发送请求
            $url = $this->getBasePath() . $request->getEndpoint();
            $transportResponse = $this->transport->get(
                $url,
                $request->toArray(),
                $request->getHeaders()
            );

            // 处理响应
            return $this->handleBuildInfoResponse($transportResponse, $request);

        } catch (NetworkException $e) {
            throw new ApiRuntimeException(
                'Get build info failed due to network error: ' . $e->getMessage(),
                'GET_BUILD_INFO_NETWORK_ERROR',
                ['original_error' => $e->getMessage()],
                $url,
                'GET',
                $transportResponse->getStatusCode() ?? null,
                ['request_summary' => $request->getSummary()],
                $e
            );
        }
    }

    /**
     * 处理版本响应
     *
     * @param \Dongasai\qBittorrent\Contract\TransportResponse $transportResponse 传输响应
     * @param GetVersionRequest|GetWebApiVersionRequest $request 请求对象
     * @return VersionResponse 版本响应
     */
    private function handleVersionResponse(
        \Dongasai\qBittorrent\Contract\TransportResponse $transportResponse,
        GetVersionRequest|GetWebApiVersionRequest $request
    ): VersionResponse {
        $statusCode = $transportResponse->getStatusCode();
        $headers = $transportResponse->getHeaders();
        $rawResponse = $transportResponse->getBody();

        if ($statusCode === 200) {
            try {
                $version = $transportResponse->getBody(); // 版本通常是字符串格式
                return VersionResponse::fromApiResponse($version, $headers, $statusCode, $rawResponse);
            } catch (\Exception $e) {
                return VersionResponse::failure(
                    ['响应解析失败: ' . $e->getMessage()],
                    $headers,
                    $statusCode,
                    $rawResponse
                );
            }
        } else {
            return VersionResponse::failure(
                ["获取版本信息失败，状态码: {$statusCode}"],
                $headers,
                $statusCode,
                $rawResponse
            );
        }
    }

    /**
     * 处理构建信息响应
     *
     * @param \Dongasai\qBittorrent\Contract\TransportResponse $transportResponse 传输响应
     * @param GetBuildInfoRequest $request 请求对象
     * @return BuildInfoResponse 构建信息响应
     */
    private function handleBuildInfoResponse(
        \Dongasai\qBittorrent\Contract\TransportResponse $transportResponse,
        GetBuildInfoRequest $request
    ): BuildInfoResponse {
        $statusCode = $transportResponse->getStatusCode();
        $headers = $transportResponse->getHeaders();
        $rawResponse = $transportResponse->getBody();

        if ($statusCode === 200) {
            try {
                $buildInfo = $transportResponse->getJson() ?? [];
                return BuildInfoResponse::fromApiResponse($buildInfo, $headers, $statusCode, $rawResponse);
            } catch (\Exception $e) {
                return BuildInfoResponse::failure(
                    ['响应解析失败: ' . $e->getMessage()],
                    $headers,
                    $statusCode,
                    $rawResponse
                );
            }
        } else {
            return BuildInfoResponse::failure(
                ["获取构建信息失败，状态码: {$statusCode}"],
                $headers,
                $statusCode,
                $rawResponse
            );
        }
    }

    /**
     * 创建通用响应对象
     *
     * @param \Dongasai\qBittorrent\Contract\TransportResponse $transportResponse 传输响应
     * @param array<string, mixed> $additionalData 额外数据
     * @return \Dongasai\qBittorrent\Contract\ResponseInterface 响应对象
     */
    private function createGenericResponse(
        \Dongasai\qBittorrent\Contract\TransportResponse $transportResponse,
        array $additionalData = []
    ): \Dongasai\qBittorrent\Contract\ResponseInterface {
        // 这里可以创建一个通用的响应对象
        // 为了简化，我们创建一个简单的响应数组
        return new class($transportResponse, $additionalData) implements \Dongasai\qBittorrent\Contract\ResponseInterface {
            private \Dongasai\qBittorrent\Contract\TransportResponse $response;
            private array $data;
            private array $additionalData;

            public function __construct(
                \Dongasai\qBittorrent\Contract\TransportResponse $response,
                array $additionalData = []
            ) {
                $this->response = $response;
                $this->data = $response->getJson() ?? [];
                $this->additionalData = $additionalData;
            }

            public static function fromArray(array $data): static {
                return new self(new class($data) implements \Dongasai\qBittorrent\Contract\TransportResponse {
                    private array $data;
                    public function __construct(array $data) { $this->data = $data; }
                    public function getStatusCode(): int { return $this->data['status_code'] ?? 200; }
                    public function getHeaders(): array { return $this->data['headers'] ?? []; }
                    public function getBody(): string { return $this->data['body'] ?? ''; }
                    public function getJson(): ?array { return $this->data['json'] ?? null; }
                    public function isSuccess(int ...$acceptableCodes): bool { return true; }
                    public function isJson(): bool { return true; }
                    public function getHeader(string $name): ?string { return null; }
                }, $additionalData);
            }

            public function isSuccess(): bool { return $this->response->isSuccess(); }
            public function getErrors(): array { return $this->additionalData['errors'] ?? []; }
            public function getData(): mixed { return array_merge($this->data, $this->additionalData); }
            public function getStatusCode(): int { return $this->response->getStatusCode(); }
            public function getHeaders(): array { return $this->response->getHeaders(); }
            public function getRawResponse(): string { return $this->response->getBody(); }
            public function toArray(): array { return array_merge($this->data, $this->additionalData); }
            public function jsonSerialize(): array { return $this->toArray(); }
        };
    }
}