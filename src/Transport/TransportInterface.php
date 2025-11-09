<?php
declare(strict_types=1);

namespace PhpQbittorrent\Transport;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * HTTP传输层接口
 *
 * 定义与qBittorrent Web API通信的标准接口
 */
interface TransportInterface
{
    /**
     * 发送HTTP请求
     *
     * @param string $method HTTP方法
     * @param string $uri 请求URI
     * @param array $options 请求选项
     * @return array 响应数据（已解析的JSON）
     * @throws \PhpQbittorrent\Exception\NetworkException 网络异常
     * @throws \PhpQbittorrent\Exception\ClientException 客户端异常
     */
    public function request(string $method, string $uri, array $options = []): array;

    /**
     * 发送原始HTTP请求
     *
     * @param RequestInterface $request PSR-7请求对象
     * @return ResponseInterface PSR-7响应对象
     * @throws \PhpQbittorrent\Exception\NetworkException 网络异常
     */
    public function sendRequest(RequestInterface $request): ResponseInterface;

    /**
     * 设置基础URL
     *
     * @param string $baseUrl qBittorrent服务器基础URL
     */
    public function setBaseUrl(string $baseUrl): void;

    /**
     * 获取基础URL
     *
     * @return string
     */
    public function getBaseUrl(): string;

    /**
     * 设置认证Cookie
     *
     * @param string|null $cookie SID cookie值
     */
    public function setAuthentication(?string $cookie): void;

    /**
     * 获取认证Cookie
     *
     * @return string|null
     */
    public function getAuthentication(): ?string;

    /**
     * 获取最后一次响应的状态码
     *
     * @return int
     */
    public function getLastResponseCode(): int;

    /**
     * 获取最后一次错误信息
     *
     * @return string|null
     */
    public function getLastError(): ?string;

    /**
     * 设置请求超时时间（秒）
     *
     * @param float $timeout 超时时间
     */
    public function setTimeout(float $timeout): void;

    /**
     * 设置连接超时时间（秒）
     *
     * @param float $timeout 连接超时时间
     */
    public function setConnectTimeout(float $timeout): void;

    /**
     * 设置用户代理
     *
     * @param string $userAgent 用户代理字符串
     */
    public function setUserAgent(string $userAgent): void;

    /**
     * 启用/禁用SSL证书验证
     *
     * @param bool $verify 是否验证SSL证书
     */
    public function setVerifySSL(bool $verify): void;

    /**
     * 设置自定义SSL证书路径
     *
     * @param string|null $path SSL证书路径
     */
    public function setSSLCertPath(?string $path): void;

    /**
     * 设置代理配置
     *
     * @param string|null $proxy 代理URL，格式：http://proxy:port
     * @param string|null $auth 代理认证信息，格式：username:password
     */
    public function setProxy(?string $proxy, ?string $auth = null): void;

    /**
     * 关闭连接并清理资源
     */
    public function close(): void;
}