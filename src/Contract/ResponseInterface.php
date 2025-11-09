<?php
declare(strict_types=1);

namespace Dongasai\qBittorrent\Contract;

use JsonSerializable;

/**
 * 响应对象接口
 *
 * 所有API响应对象必须实现此接口，确保统一的响应处理方式
 */
interface ResponseInterface extends JsonSerializable
{
    /**
     * 从数组数据创建响应对象
     *
     * @param array<string, mixed> $data 响应数据
     * @return static 响应对象实例
     */
    public static function fromArray(array $data): static;

    /**
     * 检查响应是否成功
     *
     * @return bool 是否成功
     */
    public function isSuccess(): bool;

    /**
     * 获取错误信息
     *
     * @return array<string> 错误信息数组
     */
    public function getErrors(): array;

    /**
     * 获取响应数据
     *
     * @return mixed 响应数据
     */
    public function getData(): mixed;

    /**
     * 获取HTTP状态码
     *
     * @return int HTTP状态码
     */
    public function getStatusCode(): int;

    /**
     * 获取响应头
     *
     * @return array<string, string> 响应头数组
     */
    public function getHeaders(): array;

    /**
     * 获取原始响应内容
     *
     * @return string 原始响应内容
     */
    public function getRawResponse(): string;

    /**
     * 转换为数组格式
     *
     * @return array<string, mixed> 响应数据数组
     */
    public function toArray(): array;
}