<?php
declare(strict_types=1);

namespace PhpQbittorrent\Exception;

use RuntimeException;
use Throwable;

/**
 * 客户端异常基类
 *
 * 所有php-qbittorrent库相关异常的基类
 */
class ClientException extends RuntimeException implements Exception
{
    private string $errorCode;
    private array $errorDetails = [];
    private ?int $httpStatusCode = null;

    /**
     * @param string $message 错误消息
     * @param string $errorCode 错误代码
     * @param array $errorDetails 错误详情
     * @param int|null $httpStatusCode HTTP状态码
     * @param Throwable|null $previous 前一个异常
     */
    public function __construct(
        string $message,
        string $errorCode = 'CLIENT_ERROR',
        array $errorDetails = [],
        ?int $httpStatusCode = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
        $this->errorCode = $errorCode;
        $this->errorDetails = $errorDetails;
        $this->httpStatusCode = $httpStatusCode;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getErrorDetails(): array
    {
        return $this->errorDetails;
    }

    public function getHttpStatusCode(): ?int
    {
        return $this->httpStatusCode;
    }

    public function isNetworkError(): bool
    {
        return false; // 基础客户端异常通常不是网络错误
    }

    public function isAuthenticationError(): bool
    {
        return $this->errorCode === 'AUTH_FAILED' || $this->httpStatusCode === 401;
    }

    public function isServerError(): bool
    {
        return $this->httpStatusCode !== null && $this->httpStatusCode >= 500;
    }

    public function isClientError(): bool
    {
        return $this->httpStatusCode !== null && $this->httpStatusCode >= 400 && $this->httpStatusCode < 500;
    }

    /**
     * 设置HTTP状态码
     */
    protected function setHttpStatusCode(?int $statusCode): void
    {
        $this->httpStatusCode = $statusCode;
    }

    /**
     * 添加错误详情
     */
    protected function addErrorDetail(string $key, $value): void
    {
        $this->errorDetails[$key] = $value;
    }

    /**
     * 转换为数组格式
     */
    public function toArray(): array
    {
        return [
            'type' => static::class,
            'message' => $this->getMessage(),
            'code' => $this->errorCode,
            'http_status' => $this->httpStatusCode,
            'details' => $this->errorDetails,
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'trace' => $this->getTraceAsString(),
        ];
    }

    /**
     * 转换为JSON字符串
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}