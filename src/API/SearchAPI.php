<?php
declare(strict_types=1);

namespace PhpQbittorrent\API;

use PhpQbittorrent\Contract\ApiInterface;
use PhpQbittorrent\Contract\TransportInterface;
use PhpQbittorrent\Request\Search\StartSearchRequest;
use PhpQbittorrent\Request\Search\StopSearchRequest;
use PhpQbittorrent\Request\Search\GetSearchStatusRequest;
use PhpQbittorrent\Request\Search\GetSearchResultsRequest;
use PhpQbittorrent\Request\Search\DeleteSearchRequest;
use PhpQbittorrent\Request\Search\GetSearchPluginsRequest;
use PhpQbittorrent\Response\Search\SearchResponse;
use PhpQbittorrent\Response\Search\SearchStatusResponse;
use PhpQbittorrent\Response\Search\SearchResultsResponse;
use PhpQbittorrent\Response\Search\SearchPluginsResponse;
use PhpQbittorrent\Exception\NetworkException;
use PhpQbittorrent\Exception\ApiRuntimeException;
use PhpQbittorrent\Exception\ValidationException;

/**
 * 搜索API 参数对象化
 *
 * 提供搜索管理相关的API功能
 */
class SearchAPI implements ApiInterface
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
        return '/api/v2/search';
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
     * 开始搜索
     *
     * @param StartSearchRequest $request 开始搜索请求
     * @return SearchResponse 搜索响应
     * @throws NetworkException 网络异常
     * @throws ValidationException 验证异常
     * @throws ApiRuntimeException API运行时异常
     */
    public function startSearch(StartSearchRequest $request): SearchResponse
    {
        // 验证请求
        $validation = $request->validate();
        if (!$validation->isValid()) {
            throw ValidationException::fromValidationResult(
                $validation,
                'StartSearch request validation failed'
            );
        }

        try {
            // 发送请求
            $url = $this->getBasePath() . $request->getEndpoint();
            $transportResponse = $this->transport->post(
                $url,
                $request->toArray(),
                $request->getHeaders()
            );

            // 处理响应
            return $this->handleSearchResponse($transportResponse, $request);

        } catch (NetworkException $e) {
            throw new ApiRuntimeException(
                'Start search failed due to network error: ' . $e->getMessage(),
                'START_SEARCH_NETWORK_ERROR',
                ['original_error' => $e->getMessage()],
                $url,
                'POST',
                $transportResponse->getStatusCode() ?? null,
                ['request_summary' => $request->getSummary()],
                $e
            );
        }
    }

    /**
     * 停止搜索
     *
     * @param StopSearchRequest $request 停止搜索请求
     * @return \PhpQbittorrent\Contract\ResponseInterface 停止响应
     * @throws NetworkException 网络异常
     * @throws ValidationException 验证异常
     * @throws ApiRuntimeException API运行时异常
     */
    public function stopSearch(StopSearchRequest $request): \PhpQbittorrent\Contract\ResponseInterface
    {
        // 验证请求
        $validation = $request->validate();
        if (!$validation->isValid()) {
            throw ValidationException::fromValidationResult(
                $validation,
                'StopSearch request validation failed'
            );
        }

        try {
            // 发送请求
            $url = $this->getBasePath() . $request->getEndpoint();
            $transportResponse = $this->transport->post(
                $url,
                $request->toArray(),
                $request->getHeaders()
            );

            // 处理响应
            return $this->handleGenericResponse($transportResponse, $request);

        } catch (NetworkException $e) {
            throw new ApiRuntimeException(
                'Stop search failed due to network error: ' . $e->getMessage(),
                'STOP_SEARCH_NETWORK_ERROR',
                ['original_error' => $e->getMessage()],
                $url,
                'POST',
                $transportResponse->getStatusCode() ?? null,
                ['request_summary' => $request->getSummary()],
                $e
            );
        }
    }

    /**
     * 获取搜索状态
     *
     * @param GetSearchStatusRequest $request 获取搜索状态请求
     * @return SearchStatusResponse 搜索状态响应
     * @throws NetworkException 网络异常
     * @throws ValidationException 验证异常
     * @throws ApiRuntimeException API运行时异常
     */
    public function getSearchStatus(GetSearchStatusRequest $request): SearchStatusResponse
    {
        // 验证请求
        $validation = $request->validate();
        if (!$validation->isValid()) {
            throw ValidationException::fromValidationResult(
                $validation,
                'GetSearchStatus request validation failed'
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
            return $this->handleSearchStatusResponse($transportResponse, $request);

        } catch (NetworkException $e) {
            throw new ApiRuntimeException(
                'Get search status failed due to network error: ' . $e->getMessage(),
                'GET_SEARCH_STATUS_NETWORK_ERROR',
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
     * 获取搜索结果
     *
     * @param GetSearchResultsRequest $request 获取搜索结果请求
     * @return SearchResultsResponse 搜索结果响应
     * @throws NetworkException 网络异常
     * @throws ValidationException 验证异常
     * @throws ApiRuntimeException API运行时异常
     */
    public function getSearchResults(GetSearchResultsRequest $request): SearchResultsResponse
    {
        // 验证请求
        $validation = $request->validate();
        if (!$validation->isValid()) {
            throw ValidationException::fromValidationResult(
                $validation,
                'GetSearchResults request validation failed'
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
            return $this->handleSearchResultsResponse($transportResponse, $request);

        } catch (NetworkException $e) {
            throw new ApiRuntimeException(
                'Get search results failed due to network error: ' . $e->getMessage(),
                'GET_SEARCH_RESULTS_NETWORK_ERROR',
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
     * 删除搜索
     *
     * @param DeleteSearchRequest $request 删除搜索请求
     * @return \PhpQbittorrent\Contract\ResponseInterface 删除响应
     * @throws NetworkException 网络异常
     * @throws ValidationException 验证异常
     * @throws ApiRuntimeException API运行时异常
     */
    public function deleteSearch(DeleteSearchRequest $request): \PhpQbittorrent\Contract\ResponseInterface
    {
        // 验证请求
        $validation = $request->validate();
        if (!$validation->isValid()) {
            throw ValidationException::fromValidationResult(
                $validation,
                'DeleteSearch request validation failed'
            );
        }

        try {
            // 发送请求
            $url = $this->getBasePath() . $request->getEndpoint();
            $transportResponse = $this->transport->post(
                $url,
                $request->toArray(),
                $request->getHeaders()
            );

            // 处理响应
            return $this->handleGenericResponse($transportResponse, $request);

        } catch (NetworkException $e) {
            throw new ApiRuntimeException(
                'Delete search failed due to network error: ' . $e->getMessage(),
                'DELETE_SEARCH_NETWORK_ERROR',
                ['original_error' => $e->getMessage()],
                $url,
                'POST',
                $transportResponse->getStatusCode() ?? null,
                ['request_summary' => $request->getSummary()],
                $e
            );
        }
    }

    /**
     * 处理搜索响应
     *
     * @param \PhpQbittorrent\Contract\TransportResponse $transportResponse 传输响应
     * @param StartSearchRequest $request 请求对象
     * @return SearchResponse 搜索响应
     */
    private function handleSearchResponse(
        \PhpQbittorrent\Contract\TransportResponse $transportResponse,
        StartSearchRequest $request
    ): SearchResponse {
        $statusCode = $transportResponse->getStatusCode();
        $headers = $transportResponse->getHeaders();
        $rawResponse = $transportResponse->getBody();

        if ($statusCode === 200 || $statusCode === 201) {
            try {
                $responseData = $transportResponse->getJson() ?? [];
                return SearchResponse::fromApiResponse(
                    $responseData,
                    $headers,
                    $statusCode,
                    $rawResponse
                );
            } catch (\Exception $e) {
                return SearchResponse::failure(
                    ['响应解析失败: ' . $e->getMessage()],
                    $headers,
                    $statusCode,
                    $rawResponse
                );
            }
        } else {
            return SearchResponse::failure(
                ["开始搜索失败，状态码: {$statusCode}"],
                $headers,
                $statusCode,
                $rawResponse
            );
        }
    }

    /**
     * 处理搜索状态响应
     *
     * @param \PhpQbittorrent\Contract\TransportResponse $transportResponse 传输响应
     * @param GetSearchStatusRequest $request 请求对象
     * @return SearchStatusResponse 搜索状态响应
     */
    private function handleSearchStatusResponse(
        \PhpQbittorrent\Contract\TransportResponse $transportResponse,
        GetSearchStatusRequest $request
    ): SearchStatusResponse {
        $statusCode = $transportResponse->getStatusCode();
        $headers = $transportResponse->getHeaders();
        $rawResponse = $transportResponse->getBody();

        if ($statusCode === 200) {
            try {
                $responseData = $transportResponse->getJson() ?? [];
                return SearchStatusResponse::fromApiResponse(
                    $responseData,
                    $headers,
                    $statusCode,
                    $rawResponse
                );
            } catch (\Exception $e) {
                return SearchStatusResponse::failure(
                    ['响应解析失败: ' . $e->getMessage()],
                    $headers,
                    $statusCode,
                    $rawResponse
                );
            }
        } else {
            return SearchStatusResponse::failure(
                ["获取搜索状态失败，状态码: {$statusCode}"],
                $headers,
                $statusCode,
                $rawResponse
            );
        }
    }

    /**
     * 处理搜索结果响应
     *
     * @param \PhpQbittorrent\Contract\TransportResponse $transportResponse 传输响应
     * @param GetSearchResultsRequest $request 请求对象
     * @return SearchResultsResponse 搜索结果响应
     */
    private function handleSearchResultsResponse(
        \PhpQbittorrent\Contract\TransportResponse $transportResponse,
        GetSearchResultsRequest $request
    ): SearchResultsResponse {
        $statusCode = $transportResponse->getStatusCode();
        $headers = $transportResponse->getHeaders();
        $rawResponse = $transportResponse->getBody();

        if ($statusCode === 200) {
            try {
                $responseData = $transportResponse->getJson() ?? [];
                return SearchResultsResponse::fromApiResponse(
                    $responseData,
                    $request->getSearchId(),
                    $headers,
                    $statusCode,
                    $rawResponse
                );
            } catch (\Exception $e) {
                return SearchResultsResponse::failure(
                    ['响应解析失败: ' . $e->getMessage()],
                    $headers,
                    $statusCode,
                    $rawResponse
                );
            }
        } else {
            return SearchResultsResponse::failure(
                ["获取搜索结果失败，状态码: {$statusCode}"],
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

    /**
     * 获取搜索插件
     *
     * @param GetSearchPluginsRequest $request 获取搜索插件请求
     * @return SearchPluginsResponse 搜索插件响应
     * @throws NetworkException 网络异常
     * @throws ValidationException 验证异常
     * @throws ApiRuntimeException API运行时异常
     */
    public function getSearchPlugins(GetSearchPluginsRequest $request): SearchPluginsResponse
    {
        // 验证请求
        $validation = $request->validate();
        if (!$validation->isValid()) {
            throw ValidationException::fromValidationResult(
                $validation,
                'GetSearchPlugins request validation failed'
            );
        }

        try {
            // 发送请求
            $url = $this->getBasePath() . $request->getEndpoint();
            $transportResponse = $this->transport->get(
                $url,
                $request->getParameters(),
                $request->getHeaders()
            );

            // 处理响应
            return $this->handleSearchPluginsResponse($transportResponse, $request);

        } catch (NetworkException $e) {
            throw new ApiRuntimeException(
                'Get search plugins failed due to network error: ' . $e->getMessage(),
                'GET_SEARCH_PLUGINS_NETWORK_ERROR',
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
     * 处理搜索插件响应
     *
     * @param \PhpQbittorrent\Contract\TransportResponse $transportResponse 传输响应
     * @param GetSearchPluginsRequest $request 请求对象
     * @return SearchPluginsResponse 搜索插件响应
     */
    private function handleSearchPluginsResponse(
        \PhpQbittorrent\Contract\TransportResponse $transportResponse,
        GetSearchPluginsRequest $request
    ): SearchPluginsResponse {
        $statusCode = $transportResponse->getStatusCode();
        $headers = $transportResponse->getHeaders();
        $rawResponse = $transportResponse->getBody();

        if ($statusCode === 200) {
            try {
                $responseData = $transportResponse->getJson() ?? [];
                return SearchPluginsResponse::fromApiResponse(
                    $responseData,
                    $headers,
                    $statusCode,
                    $rawResponse
                );
            } catch (\Exception $e) {
                return SearchPluginsResponse::failure(
                    ['响应解析失败: ' . $e->getMessage()],
                    $headers,
                    $statusCode,
                    $rawResponse
                );
            }
        } else {
            return SearchPluginsResponse::failure(
                ["获取搜索插件失败，状态码: {$statusCode}"],
                $headers,
                $statusCode,
                $rawResponse
            );
        }
    }
}