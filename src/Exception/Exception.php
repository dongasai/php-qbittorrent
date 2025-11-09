<?php
declare(strict_types=1);

namespace PhpQbittorrent\Exception;

use Throwable;

/**
 * 基础异常接口
 */
interface Exception extends Throwable
{
    /**
     * 获取错误代码
     */
    public function getErrorCode(): string;

    /**
     * 获取错误详情
     */
    public function getErrorDetails(): array;

    /**
     * 是否为网络相关错误
     */
    public function isNetworkError(): bool;

    /**
     * 是否为认证相关错误
     */
    public function isAuthenticationError(): bool;

    /**
     * 是否为服务器错误（5xx）
     */
    public function isServerError(): bool;

    /**
     * 是否为客户端错误（4xx）
     */
    public function isClientError(): bool;
}