<?php
declare(strict_types=1);

namespace PhpQbittorrent\Contract;

/**
 * 传输层接口
 *
 * 定义网络传输的统一接口，支持多种传输方式实现
 */
interface TransportInterface
{
    /**
     * 执行HTTP GET请求
     *
     * @param string $url 请求URL
     * @param array<string, mixed> $parameters 查询参数
     * @param array<string, string> $headers 请求头
     * @return TransportResponse 传输响应
     * @throws NetworkException 网络异常
     */
    public function get(string $url, array $parameters = [], array $headers = []): TransportResponse;

    /**
     * 执行HTTP POST请求
     *
     * @param string $url 请求URL
     * @param array<string, mixed> $data 请求数据
     * @param array<string, string> $headers 请求头
     * @return TransportResponse 传输响应
     * @throws NetworkException 网络异常
     */
    public function post(string $url, array $data = [], array $headers = []): TransportResponse;

    /**
     * 执行HTTP PUT请求
     *
     * @param string $url 请求URL
     * @param array<string, mixed> $data 请求数据
     * @param array<string, string> $headers 请求头
     * @return TransportResponse 传输响应
     * @throws NetworkException 网络异常
     */
    public function put(string $url, array $data = [], array $headers = []): TransportResponse;

    /**
     * 执行HTTP DELETE请求
     *
     * @param string $url 请求URL
     * @param array<string, mixed> $parameters 查询参数
     * @param array<string, string> $headers 请求头
     * @return TransportResponse 传输响应
     * @throws NetworkException 网络异常
     */
    public function delete(string $url, array $parameters = [], array $headers = []): TransportResponse;

    /**
     * 设置基础URL
     *
     * @param string $baseUrl 基础URL
     * @return static 返回自身以支持链式调用
     */
    public function setBaseUrl(string $baseUrl): static;

    /**
     * 获取基础URL
     *
     * @return string 基础URL
     */
    public function getBaseUrl(): string;

    /**
     * 设置超时时间
     *
     * @param int $timeout 超时时间（秒）
     * @return static 返回自身以支持链式调用
     */
    public function setTimeout(int $timeout): static;

    /**
     * 设置请求头
     *
     * @param array<string, string> $headers 请求头
     * @return static 返回自身以支持链式调用
     */
    public function setHeaders(array $headers): static;

    /**
     * 添加请求头
     *
     * @param string $name 头名称
     * @param string $value 头值
     * @return static 返回自身以支持链式调用
     */
    public function addHeader(string $name, string $value): static;

    /**
     * 设置认证信息
     *
     * @param string $username 用户名
     * @param string $password 密码
     * @return static 返回自身以支持链式调用
     */
    public function setAuth(string $username, string $password): static;

    /**
     * 设置认证信息
     *
     * @param string|null $cookie Cookie字符串
     * @return void
     */
    public function setAuthentication(?string $cookie): void;

    /**
     * 获取认证信息
     *
     * @return string|null Cookie字符串
     */
    public function getAuthentication(): ?string;

    /**
     * 设置Cookie
     *
     * @param string $cookie Cookie字符串
     * @return static 返回自身以支持链式调用
     */
    public function setCookie(string $cookie): static;

    /**
     * 启用/禁用SSL验证
     *
     * @param bool $verify 是否验证SSL
     * @return static 返回自身以支持链式调用
     */
    public function setSslVerify(bool $verify): static;
}