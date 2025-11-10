<?php
declare(strict_types=1);

namespace PhpQbittorrent\API;

use PhpQbittorrent\Contract\ApiInterface;
use PhpQbittorrent\Contract\TransportInterface;
use PhpQbittorrent\Request\Transfer\GetGlobalTransferInfoRequest;
use PhpQbittorrent\Request\Transfer\GetAlternativeSpeedLimitsStateRequest;
use PhpQbittorrent\Request\Transfer\ToggleAlternativeSpeedLimitsRequest;
use PhpQbittorrent\Response\Transfer\GlobalTransferInfoResponse;
use PhpQbittorrent\Response\Transfer\AlternativeSpeedLimitsStateResponse;
use PhpQbittorrent\Exception\NetworkException;
use PhpQbittorrent\Exception\ApiRuntimeException;
use PhpQbittorrent\Exception\ValidationException;

/**
 * Transfer API 参数对象化
 *
 * 提供传输管理相关的API功能
 */
class TransferAPI implements ApiInterface
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
        return '/api/v2/transfer';
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
     * 获取全局传输信息
     *
     * @param GetGlobalTransferInfoRequest $request 获取全局传输信息请求
     * @return GlobalTransferInfoResponse 全局传输信息响应
     * @throws NetworkException 网络异常
     * @throws ValidationException 验证异常
     * @throws ApiRuntimeException API运行时异常
     */
    public function getGlobalTransferInfo(GetGlobalTransferInfoRequest $request): GlobalTransferInfoResponse
    {
        // 验证请求
        $validation = $request->validate();
        if (!$validation->isValid()) {
            throw ValidationException::fromValidationResult(
                $validation,
                'GetGlobalTransferInfo request validation failed'
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
            return $this->handleGlobalTransferInfoResponse($transportResponse, $request);

        } catch (NetworkException $e) {
            throw new ApiRuntimeException(
                'Get global transfer info failed due to network error: ' . $e->getMessage(),
                'GET_GLOBAL_TRANSFER_INFO_NETWORK_ERROR',
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
     * 获取传输信息（别名方法）
     *
     * @return GlobalTransferInfoResponse 全局传输信息响应
     * @throws NetworkException 网络异常
     * @throws ValidationException 验证异常
     * @throws ApiRuntimeException API运行时异常
     */
    public function getTransferInfo(): GlobalTransferInfoResponse
    {
        return $this->getGlobalTransferInfo(\PhpQbittorrent\Request\Transfer\GetGlobalTransferInfoRequest::create());
    }

    /**
     * 获取替代速度限制状态
     *
     * @param GetAlternativeSpeedLimitsStateRequest $request 获取替代速度限制状态请求
     * @return AlternativeSpeedLimitsStateResponse 替代速度限制状态响应
     * @throws NetworkException 网络异常
     * @throws ValidationException 验证异常
     * @throws ApiRuntimeException API运行时异常
     */
    public function getAlternativeSpeedLimitsState(GetAlternativeSpeedLimitsStateRequest $request): AlternativeSpeedLimitsStateResponse
    {
        // 验证请求
        $validation = $request->validate();
        if (!$validation->isValid()) {
            throw ValidationException::fromValidationResult(
                $validation,
                'GetAlternativeSpeedLimitsState request validation failed'
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
            return $this->handleAlternativeSpeedLimitsStateResponse($transportResponse, $request);

        } catch (NetworkException $e) {
            throw new ApiRuntimeException(
                'Get alternative speed limits state failed due to network error: ' . $e->getMessage(),
                'GET_ALTERNATIVE_SPEED_LIMITS_STATE_NETWORK_ERROR',
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
     * 切换替代速度限制
     *
     * @param ToggleAlternativeSpeedLimitsRequest $request 切换替代速度限制请求
     * @return AlternativeSpeedLimitsStateResponse 切换后的状态响应
     * @throws NetworkException 网络异常
     * @throws ValidationException 验证异常
     * @throws ApiRuntimeException API运行时异常
     */
    public function toggleAlternativeSpeedLimits(ToggleAlternativeSpeedLimitsRequest $request): AlternativeSpeedLimitsStateResponse
    {
        // 验证请求
        $validation = $request->validate();
        if (!$validation->isValid()) {
            throw ValidationException::fromValidationResult(
                $validation,
                'ToggleAlternativeSpeedLimits request validation failed'
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
            return $this->handleAlternativeSpeedLimitsStateResponse($transportResponse, $request);

        } catch (NetworkException $e) {
            throw new ApiRuntimeException(
                'Toggle alternative speed limits failed due to network error: ' . $e->getMessage(),
                'TOGGLE_ALTERNATIVE_SPEED_LIMITS_NETWORK_ERROR',
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
     * 处理全局传输信息响应
     *
     * @param \PhpQbittorrent\Contract\TransportResponse $transportResponse 传输响应
     * @param GetGlobalTransferInfoRequest $request 请求对象
     * @return GlobalTransferInfoResponse 全局传输信息响应
     */
    private function handleGlobalTransferInfoResponse(
        \PhpQbittorrent\Contract\TransportResponse $transportResponse,
        GetGlobalTransferInfoRequest $request
    ): GlobalTransferInfoResponse {
        $statusCode = $transportResponse->getStatusCode();
        $headers = $transportResponse->getHeaders();
        $rawResponse = $transportResponse->getBody();

        if ($statusCode === 200) {
            try {
                $transferInfo = $transportResponse->getJson() ?? [];
                return GlobalTransferInfoResponse::fromApiResponse($transferInfo, $headers, $statusCode, $rawResponse);
            } catch (\Exception $e) {
                return GlobalTransferInfoResponse::failure(
                    ['响应解析失败: ' . $e->getMessage()],
                    $headers,
                    $statusCode,
                    $rawResponse
                );
            }
        } else {
            return GlobalTransferInfoResponse::failure(
                ["获取全局传输信息失败，状态码: {$statusCode}"],
                $headers,
                $statusCode,
                $rawResponse
            );
        }
    }

    /**
     * 处理替代速度限制状态响应
     *
     * @param \PhpQbittorrent\Contract\TransportResponse $transportResponse 传输响应
     * @param GetAlternativeSpeedLimitsStateRequest|ToggleAlternativeSpeedLimitsRequest $request 请求对象
     * @return AlternativeSpeedLimitsStateResponse 替代速度限制状态响应
     */
    private function handleAlternativeSpeedLimitsStateResponse(
        \PhpQbittorrent\Contract\TransportResponse $transportResponse,
        GetAlternativeSpeedLimitsStateRequest|ToggleAlternativeSpeedLimitsRequest $request
    ): AlternativeSpeedLimitsStateResponse {
        $statusCode = $transportResponse->getStatusCode();
        $headers = $transportResponse->getHeaders();
        $rawResponse = $transportResponse->getBody();

        if ($statusCode === 200) {
            try {
                // API返回的是字符串 "0" 或 "1"
                $state = $transportResponse->getBody();
                return AlternativeSpeedLimitsStateResponse::fromApiResponse($state, $headers, $statusCode, $rawResponse);
            } catch (\Exception $e) {
                return AlternativeSpeedLimitsStateResponse::failure(
                    ['响应解析失败: ' . $e->getMessage()],
                    $headers,
                    $statusCode,
                    $rawResponse
                );
            }
        } else {
            return AlternativeSpeedLimitsStateResponse::failure(
                ["获取替代速度限制状态失败，状态码: {$statusCode}"],
                $headers,
                $statusCode,
                $rawResponse
            );
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