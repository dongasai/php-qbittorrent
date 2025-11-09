<?php
declare(strict_types=1);

namespace Dongasai\qBittorrent\Contract;

/**
 * API接口定义
 *
 * 所有API类必须实现此接口，确保统一的API调用方式
 */
interface ApiInterface
{
    /**
     * 获取API的基础路径
     *
     * @return string API基础路径
     */
    public function getBasePath(): string;

    /**
     * 获取传输层实例
     *
     * @return TransportInterface 传输层实例
     */
    public function getTransport(): TransportInterface;

    /**
     * 设置传输层实例
     *
     * @param TransportInterface $transport 传输层实例
     * @return static 返回自身以支持链式调用
     */
    public function setTransport(TransportInterface $transport): static;

    /**
     * 执行GET请求
     *
     * @param string $endpoint API端点
     * @param array<string, mixed> $parameters 请求参数
     * @param array<string, string> $headers 请求头
     * @return ResponseInterface 响应对象
     */
    public function get(string $endpoint, array $parameters = [], array $headers = []): ResponseInterface;

    /**
     * 执行POST请求
     *
     * @param string $endpoint API端点
     * @param array<string, mixed> $data 请求数据
     * @param array<string, string> $headers 请求头
     * @return ResponseInterface 响应对象
     */
    public function post(string $endpoint, array $data = [], array $headers = []): ResponseInterface;
}