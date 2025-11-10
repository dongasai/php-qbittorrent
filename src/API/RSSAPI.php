<?php
declare(strict_types=1);

namespace PhpQbittorrent\API;

use PhpQbittorrent\Contract\ApiInterface;
use PhpQbittorrent\Contract\TransportInterface;
use PhpQbittorrent\Request\RSS\GetAllItemsRequest;
use PhpQbittorrent\Request\RSS\MarkAsReadRequest;
use PhpQbittorrent\Request\RSS\RefreshItemRequest;
use PhpQbittorrent\Response\RSS\RSSItemsResponse;
use PhpQbittorrent\Exception\NetworkException;
use PhpQbittorrent\Exception\ApiRuntimeException;
use PhpQbittorrent\Exception\ValidationException;

/**
 * RSS API 参数对象化
 *
 * 提供RSS管理相关的API功能
 */
class RSSAPI implements ApiInterface
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
        return '/api/v2/rss';
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
     * @return \PhpQbittorrent\Contract\ResponseInterface 响应对象
     */
    public function get(string $endpoint, array $parameters = [], array $headers = []): \PhpQbittorrent\Contract\ResponseInterface
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
     * @return \PhpQbittorrent\Contract\ResponseInterface 响应对象
     */
    public function post(string $endpoint, array $data = [], array $headers = []): \PhpQbittorrent\Contract\ResponseInterface
    {
        $url = $this->getBasePath() . $endpoint;
        $transportResponse = $this->transport->post($url, $data, $headers);
        return $this->createGenericResponse($transportResponse);
    }

    /**
     * 获取所有RSS项目
     *
     * @param GetAllItemsRequest $request 获取所有RSS项目请求
     * @return RSSItemsResponse RSS项目响应
     * @throws NetworkException 网络异常
     * @throws ValidationException 验证异常
     * @throws ApiRuntimeException API运行时异常
     */
    public function getAllItems(GetAllItemsRequest $request): RSSItemsResponse
    {
        // 验证请求
        $validation = $request->validate();
        if (!$validation->isValid()) {
            throw ValidationException::fromValidationResult(
                $validation,
                'GetAllItems request validation failed'
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
            return $this->handleRSSItemsResponse($transportResponse, $request);

        } catch (NetworkException $e) {
            throw new ApiRuntimeException(
                'Get RSS items failed due to network error: ' . $e->getMessage(),
                'GET_RSS_ITEMS_NETWORK_ERROR',
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
     * 标记为已读
     *
     * @param MarkAsReadRequest $request 标记为已读请求
     * @return \PhpQbittorrent\Contract\ResponseInterface 标记响应
     * @throws NetworkException 网络异常
     * @throws ValidationException 验证异常
     * @throws ApiRuntimeException API运行时异常
     */
    public function markAsRead(MarkAsReadRequest $request): \PhpQbittorrent\Contract\ResponseInterface
    {
        // 验证请求
        $validation = $request->validate();
        if (!$validation->isValid()) {
            throw ValidationException::fromValidationResult(
                $validation,
                'MarkAsRead request validation failed'
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
            return $this->handleGenericResponse($transportResponse, $request);

        } catch (NetworkException $e) {
            throw new ApiRuntimeException(
                'Mark RSS item as read failed due to network error: ' . $e->getMessage(),
                'MARK_RSS_ITEM_AS_READ_NETWORK_ERROR',
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
     * 刷新RSS项目
     *
     * @param RefreshItemRequest $request 刷新RSS项目请求
     * @return \PhpQbittorrent\Contract\ResponseInterface 刷新响应
     * @throws NetworkException 网络异常
     * @throws ValidationException 验证异常
     * @throws ApiRuntimeException API运行时异常
     */
    public function refreshItem(RefreshItemRequest $request): \PhpQbittorrent\Contract\ResponseInterface
    {
        // 验证请求
        $validation = $request->validate();
        if (!$validation->isValid()) {
            throw ValidationException::fromValidationResult(
                $validation,
                'RefreshItem request validation failed'
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
            return $this->handleGenericResponse($transportResponse, $request);

        } catch (NetworkException $e) {
            throw new ApiRuntimeException(
                'Refresh RSS item failed due to network error: ' . $e->getMessage(),
                'REFRESH_RSS_ITEM_NETWORK_ERROR',
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
     * 处理RSS项目响应
     *
     * @param \PhpQbittorrent\Contract\TransportResponse $transportResponse 传输响应
     * @param GetAllItemsRequest $request 请求对象
     * @return RSSItemsResponse RSS项目响应
     */
    private function handleRSSItemsResponse(
        \PhpQbittorrent\Contract\TransportResponse $transportResponse,
        GetAllItemsRequest $request
    ): RSSItemsResponse {
        $statusCode = $transportResponse->getStatusCode();
        $headers = $transportResponse->getHeaders();
        $rawResponse = $transportResponse->getBody();

        if ($statusCode === 200) {
            try {
                $rssData = $transportResponse->getJson() ?? [];
                return RSSItemsResponse::fromApiResponse(
                    $rssData,
                    $request->isWithData(),
                    $headers,
                    $statusCode,
                    $rawResponse
                );
            } catch (\Exception $e) {
                return RSSItemsResponse::failure(
                    ['响应解析失败: ' . $e->getMessage()],
                    $headers,
                    $statusCode,
                    $rawResponse
                );
            }
        } else {
            return RSSItemsResponse::failure(
                ["获取RSS项目失败，状态码: {$statusCode}"],
                $headers,
                $statusCode,
                $rawResponse
            );
        }
    }

    /**
     * 处理通用响应
     *
     * @param \PhpQbittorrent\Contract\TransportResponse $transportResponse 传输响应
     * @param object $request 请求对象
     * @return \PhpQbittorrent\Contract\ResponseInterface 通用响应
     */
    private function handleGenericResponse(
        \PhpQbittorrent\Contract\TransportResponse $transportResponse,
        object $request
    ): \PhpQbittorrent\Contract\ResponseInterface {
        $statusCode = $transportResponse->getStatusCode();
        $headers = $transportResponse->getHeaders();
        $rawResponse = $transportResponse->getBody();

        if ($statusCode === 200) {
            return $this->createGenericResponse($transportResponse, [
                'success' => true,
                'message' => '操作成功',
                'timestamp' => time(),
            ]);
        } else {
            return $this->createGenericResponse($transportResponse, [
                'success' => false,
                'error' => "操作失败，状态码: {$statusCode}",
                'timestamp' => time(),
            ]);
        }
    }

    /**
     * 创建通用响应对象
     *
     * @param \PhpQbittorrent\Contract\TransportResponse $transportResponse 传输响应
     * @param array<string, mixed> $additionalData 额外数据
     * @return \PhpQbittorrent\Contract\ResponseInterface 响应对象
     */
    private function createGenericResponse(
        \PhpQbittorrent\Contract\TransportResponse $transportResponse,
        array $additionalData = []
    ): \PhpQbittorrent\Contract\ResponseInterface {
        // 这里可以创建一个通用的响应对象
        // 为了简化，我们创建一个简单的响应数组
        return new class($transportResponse, $additionalData) implements \PhpQbittorrent\Contract\ResponseInterface {
            private \PhpQbittorrent\Contract\TransportResponse $response;
            private array $data;
            private array $additionalData;

            public function __construct(
                \PhpQbittorrent\Contract\TransportResponse $response,
                array $additionalData = []
            ) {
                $this->response = $response;
                $this->data = $response->getJson() ?? [];
                $this->additionalData = $additionalData;
            }

            public static function fromArray(array $data): static {
                return new self(new class($data) implements \PhpQbittorrent\Contract\TransportResponse {
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