<?php
declare(strict_types=1);

namespace PhpQbittorrent\Exception;

use Throwable;

/**
 * 网络异常
 *
 * 处理HTTP请求过程中的网络相关错误
 */
class NetworkException extends ClientException
{
    private ?string $requestMethod = null;
    private ?string $requestUri = null;
    private ?float $timeout = null;

    /**
     * @param string $message 错误消息
     * @param string $errorCode 错误代码
     * @param array $errorDetails 错误详情
     * @param string|null $requestMethod 请求方法
     * @param string|null $requestUri 请求URI
     * @param float|null $timeout 超时时间
     * @param Throwable|null $previous 前一个异常
     */
    public function __construct(
        string $message,
        string $errorCode = 'NETWORK_ERROR',
        array $errorDetails = [],
        ?string $requestMethod = null,
        ?string $requestUri = null,
        ?float $timeout = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $errorCode, $errorDetails, null, $previous);

        $this->requestMethod = $requestMethod;
        $this->requestUri = $requestUri;
        $this->timeout = $timeout;

        // 添加网络相关错误详情
        if ($requestMethod !== null) {
            $this->addErrorDetail('request_method', $requestMethod);
        }
        if ($requestUri !== null) {
            $this->addErrorDetail('request_uri', $requestUri);
        }
        if ($timeout !== null) {
            $this->addErrorDetail('timeout', $timeout);
        }
    }

    public function isNetworkError(): bool
    {
        return true;
    }

    public function isTimeoutError(): bool
    {
        return $this->errorCode === 'TIMEOUT' ||
               str_contains(strtolower($this->getMessage()), 'timeout');
    }

    public function isConnectionError(): bool
    {
        return $this->errorCode === 'CONNECTION_FAILED' ||
               str_contains(strtolower($this->getMessage()), 'connection') ||
               str_contains(strtolower($this->getMessage()), 'resolve host');
    }

    public function isSSLError(): bool
    {
        return $this->errorCode === 'SSL_ERROR' ||
               str_contains(strtolower($this->getMessage()), 'ssl') ||
               str_contains(strtolower($this->getMessage()), 'certificate');
    }

    public function getRequestMethod(): ?string
    {
        return $this->requestMethod;
    }

    public function getRequestUri(): ?string
    {
        return $this->requestUri;
    }

    public function getTimeout(): ?float
    {
        return $this->timeout;
    }

    /**
     * 创建连接失败异常
     */
    public static function connectionFailed(
        string $uri,
        ?string $method = null,
        ?Throwable $previous = null
    ): self {
        return new self(
            "连接失败: {$uri}",
            'CONNECTION_FAILED',
            [],
            $method,
            $uri,
            null,
            $previous
        );
    }

    /**
     * 创建超时异常
     */
    public static function timeout(
        float $timeout,
        ?string $uri = null,
        ?string $method = null
    ): self {
        return new self(
            "请求超时 ({$timeout}秒)",
            'TIMEOUT',
            [],
            $method,
            $uri,
            $timeout
        );
    }

    /**
     * 创建SSL异常
     */
    public static function sslError(
        string $message,
        ?string $uri = null,
        ?Throwable $previous = null
    ): self {
        return new self(
            "SSL错误: {$message}",
            'SSL_ERROR',
            ['ssl_message' => $message],
            null,
            $uri,
            null,
            $previous
        );
    }

    /**
     * 创建DNS解析失败异常
     */
    public static function dnsFailed(
        string $host,
        ?string $uri = null,
        ?Throwable $previous = null
    ): self {
        return new self(
            "DNS解析失败: {$host}",
            'DNS_FAILED',
            ['host' => $host],
            null,
            $uri,
            null,
            $previous
        );
    }
}