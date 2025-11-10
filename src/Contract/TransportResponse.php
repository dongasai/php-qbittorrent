<?php
declare(strict_types=1);

namespace PhpQbittorrent\Contract;

/**
 * 传输响应接口
 *
 * 定义HTTP响应的统一接口
 */
interface TransportResponse
{
    /**
     * 获取状态码
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
     * 获取响应体内容
     *
     * @return string 响应体内容
     */
    public function getBody(): string;

    /**
     * 获取JSON解析后的数据
     *
     * @return array<string, mixed>|null JSON数据，如果解析失败返回null
     */
    public function getJson(): ?array;

    /**
     * 检查响应是否成功
     *
     * @param int ...$acceptableCodes 可接受的状态码
     * @return bool 是否成功
     */
    public function isSuccess(int ...$acceptableCodes): bool;

    /**
     * 检查响应是否为JSON格式
     *
     * @return bool 是否为JSON格式
     */
    public function isJson(): bool;

    /**
     * 获取指定头信息
     *
     * @param string $name 头名称
     * @return string|null 头值，如果不存在返回null
     */
    public function getHeader(string $name): ?string;
}