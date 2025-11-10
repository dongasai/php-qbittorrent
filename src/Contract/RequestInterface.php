<?php
declare(strict_types=1);

namespace PhpQbittorrent\Contract;

use JsonSerializable;

/**
 * 请求对象接口
 *
 * 所有API请求对象必须实现此接口，确保统一的请求处理方式
 */
interface RequestInterface extends JsonSerializable
{
    /**
     * 验证请求参数
     *
     * @return ValidationResult 验证结果
     */
    public function validate(): ValidationResult;

    /**
     * 转换为数组格式
     *
     * @return array<string, mixed> 请求数据数组
     */
    public function toArray(): array;

    /**
     * 获取请求的唯一标识
     *
     * @return string 请求唯一标识
     */
    public function getRequestId(): string;

    /**
     * 获取请求方法类型
     *
     * @return string HTTP方法 (GET/POST)
     */
    public function getMethod(): string;

    /**
     * 获取请求的API端点
     *
     * @return string API端点路径
     */
    public function getEndpoint(): string;

    /**
     * 获取请求头
     *
     * @return array<string, string> 请求头数组
     */
    public function getHeaders(): array;

    /**
     * 检查请求是否需要认证
     *
     * @return bool 是否需要认证
     */
    public function requiresAuthentication(): bool;
}