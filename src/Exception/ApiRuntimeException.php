<?php
declare(strict_types=1);

namespace PhpQbittorrent\Exception;

use Throwable;

/**
 * API运行时异常
 *
 * 当API调用过程中发生运行时错误时抛出此异常
 */
class ApiRuntimeException extends ClientException
{
    private ?string $apiEndpoint = null;
    private ?string $httpMethod = null;
    private ?int $httpStatusCode = null;
    private array $requestContext = [];

    /**
     * 构造函数
     *
     * @param string $message 异常消息
     * @param string $errorCode 错误代码
     * @param array $errorDetails 错误详情
     * @param string|null $apiEndpoint API端点
     * @param string|null $httpMethod HTTP方法
     * @param int|null $httpStatusCode HTTP状态码
     * @param array $requestContext 请求上下文
     * @param Throwable|null $previous 前一个异常
     */
    public function __construct(
        string $message,
        string $errorCode = 'API_RUNTIME_ERROR',
        array $errorDetails = [],
        ?string $apiEndpoint = null,
        ?string $httpMethod = null,
        ?int $httpStatusCode = null,
        array $requestContext = [],
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $errorCode, $errorDetails, null, $previous);

        $this->apiEndpoint = $apiEndpoint;
        $this->httpMethod = $httpMethod;
        $this->httpStatusCode = $httpStatusCode;
        $this->requestContext = $requestContext;

        // 添加API相关错误详情
        if ($apiEndpoint !== null) {
            $this->addErrorDetail('api_endpoint', $apiEndpoint);
        }
        if ($httpMethod !== null) {
            $this->addErrorDetail('http_method', $httpMethod);
        }
        if ($httpStatusCode !== null) {
            $this->addErrorDetail('http_status_code', $httpStatusCode);
        }
        if (!empty($requestContext)) {
            $this->addErrorDetail('request_context', $requestContext);
        }
    }

    /**
     * 获取API端点
     *
     * @return string|null API端点
     */
    public function getApiEndpoint(): ?string
    {
        return $this->apiEndpoint;
    }

    /**
     * 获取HTTP方法
     *
     * @return string|null HTTP方法
     */
    public function getHttpMethod(): ?string
    {
        return $this->httpMethod;
    }

    /**
     * 获取HTTP状态码
     *
     * @return int|null HTTP状态码
     */
    public function getHttpStatusCode(): ?int
    {
        return $this->httpStatusCode;
    }

    /**
     * 获取请求上下文
     *
     * @return array 请求上下文
     */
    public function getRequestContext(): array
    {
        return $this->requestContext;
    }

    /**
     * 创建HTTP状态码错误异常
     *
     * @param string $apiEndpoint API端点
     * @param string $httpMethod HTTP方法
     * @param int $httpStatusCode HTTP状态码
     * @param string $message 异常消息
     * @return static API运行时异常
     */
    public static function httpStatusError(
        string $apiEndpoint,
        string $httpMethod,
        int $httpStatusCode,
        string $message = ''
    ): static {
        if (empty($message)) {
            $message = "HTTP请求失败: {$httpMethod} {$apiEndpoint} - {$httpStatusCode}";
        }

        return new static(
            $message,
            'HTTP_STATUS_ERROR',
            ['status_code' => $httpStatusCode],
            $apiEndpoint,
            $httpMethod,
            $httpStatusCode
        );
    }

    /**
     * 创建响应解析异常
     *
     * @param string $apiEndpoint API端点
     * @param string $httpMethod HTTP方法
     * @param string $rawResponse 原始响应
     * @return static API运行时异常
     */
    public static function responseParseError(
        string $apiEndpoint,
        string $httpMethod,
        string $rawResponse
    ): static {
        return new static(
            "响应解析失败: {$httpMethod} {$apiEndpoint}",
            'RESPONSE_PARSE_ERROR',
            [
                'raw_response_length' => strlen($rawResponse),
                'raw_response_preview' => substr($rawResponse, 0, 200)
            ],
            $apiEndpoint,
            $httpMethod,
            null,
            ['raw_response' => $rawResponse]
        );
    }

    /**
     * 创建请求验证异常
     *
     * @param string $apiEndpoint API端点
     * @param string $httpMethod HTTP方法
     * @param array $validationErrors 验证错误
     * @return static API运行时异常
     */
    public static function requestValidationError(
        string $apiEndpoint,
        string $httpMethod,
        array $validationErrors
    ): static {
        return new static(
            "请求验证失败: {$httpMethod} {$apiEndpoint}",
            'REQUEST_VALIDATION_ERROR',
            ['validation_errors' => $validationErrors],
            $apiEndpoint,
            $httpMethod,
            null,
            $validationErrors
        );
    }

    /**
     * 创建API响应格式异常
     *
     * @param string $apiEndpoint API端点
     * @param string $httpMethod HTTP方法
     * @param mixed $responseData 响应数据
     * @param string $expectedFormat 期望格式
     * @return static API运行时异常
     */
    public static function invalidResponseFormat(
        string $apiEndpoint,
        string $httpMethod,
        mixed $responseData,
        string $expectedFormat
    ): static {
        return new static(
            "API响应格式无效: {$httpMethod} {$apiEndpoint}，期望格式: {$expectedFormat}",
            'INVALID_RESPONSE_FORMAT',
            [
                'expected_format' => $expectedFormat,
                'actual_type' => gettype($responseData)
            ],
            $apiEndpoint,
            $httpMethod,
            null,
            ['response_data' => $responseData]
        );
    }

    /**
     * 创建API功能未实现异常
     *
     * @param string $apiEndpoint API端点
     * @param string $feature 功能名称
     * @return static API运行时异常
     */
    public static function notImplemented(string $apiEndpoint, string $feature): static
    {
        return new static(
            "API功能未实现: {$feature} - {$apiEndpoint}",
            'NOT_IMPLEMENTED',
            ['feature' => $feature],
            $apiEndpoint
        );
    }

    /**
     * 创建API限流异常
     *
     * @param string $apiEndpoint API端点
     * @param int $retryAfter 重试等待时间（秒）
     * @return static API运行时异常
     */
    public static function rateLimitExceeded(string $apiEndpoint, int $retryAfter = 60): static
    {
        return new static(
            "API请求频率超限: {$apiEndpoint}",
            'RATE_LIMIT_EXCEEDED',
            ['retry_after' => $retryAfter],
            $apiEndpoint,
            null,
            429,
            ['retry_after' => $retryAfter]
        );
    }

    /**
     * 创建API版本不兼容异常
     *
     * @param string $apiEndpoint API端点
     * @param string $requiredVersion 需要的版本
     * @param string $actualVersion 实际版本
     * @return static API运行时异常
     */
    public static function versionMismatch(
        string $apiEndpoint,
        string $requiredVersion,
        string $actualVersion
    ): static {
        return new static(
            "API版本不兼容: {$apiEndpoint}，需要版本: {$requiredVersion}，实际版本: {$actualVersion}",
            'VERSION_MISMATCH',
            [
                'required_version' => $requiredVersion,
                'actual_version' => $actualVersion
            ],
            $apiEndpoint
        );
    }

    /**
     * 获取格式化的错误消息
     *
     * @param bool $includeContext 是否包含上下文信息
     * @return string 格式化的错误消息
     */
    public function getFormattedMessage(bool $includeContext = true): string
    {
        $message = $this->getMessage();

        if ($includeContext) {
            $context = [];

            if ($this->httpMethod !== null && $this->apiEndpoint !== null) {
                $context[] = "{$this->httpMethod} {$this->apiEndpoint}";
            }

            if ($this->httpStatusCode !== null) {
                $context[] = "HTTP {$this->httpStatusCode}";
            }

            if (!empty($context)) {
                $message .= " [" . implode(", ", $context) . "]";
            }
        }

        return $message;
    }
}