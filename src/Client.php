<?php
declare(strict_types=1);

namespace PhpQbittorrent;

use PhpQbittorrent\Config\ClientConfig;
use PhpQbittorrent\Transport\TransportInterface;
use PhpQbittorrent\Transport\CurlTransport;
use PhpQbittorrent\API\AuthAPI;
use PhpQbittorrent\API\TorrentAPI;
use PhpQbittorrent\API\ApplicationAPI;
use PhpQbittorrent\API\TransferAPI;
use PhpQbittorrent\API\RSSAPI;
use PhpQbittorrent\API\SearchAPI;
use PhpQbittorrent\Exception\{
    ClientException,
    AuthenticationException,
    ValidationException,
    NetworkException
};

/**
 * qBittorrent客户端主类
 *
 * 提供完整的qBittorrent Web API访问功能，兼容qBittorrent 5.x版本
 */
final class Client
{
    private ClientConfig $config;
    private TransportInterface $transport;
    private ?AuthAPI $authAPI = null;
    private ?TorrentAPI $torrentAPI = null;
    private ?ApplicationAPI $applicationAPI = null;
    private ?TransferAPI $transferAPI = null;
    private ?RSSAPI $rssAPI = null;
    private ?SearchAPI $searchAPI = null;
    private bool $isLoggedIn = false;

    /**
     * 创建客户端实例
     *
     * @param ClientConfig $config 客户端配置
     * @param TransportInterface|null $transport 自定义传输层
     * @throws ValidationException 配置验证失败
     */
    public function __construct(ClientConfig $config, ?TransportInterface $transport = null)
    {
        // 验证配置
        if (!$config->validate()) {
            throw ValidationException::invalidConfig($config->getErrors());
        }

        $this->config = $config;
        $this->transport = $transport ?? new CurlTransport();

        // 配置传输层
        $this->configureTransport();
    }

    /**
     * 认证登录
     *
     * @throws AuthenticationException 认证失败
     * @throws NetworkException 网络错误
     */
    public function login(): void
    {
        try {
            $authAPI = $this->getAuthAPI();
            $success = $authAPI->login(
                $this->config->getUsername(),
                $this->config->getPassword()
            );

            if ($success) {
                $this->isLoggedIn = true;
            } else {
                throw new AuthenticationException('登录失败：用户名或密码错误');
            }

        } catch (AuthenticationException $e) {
            $this->isLoggedIn = false;
            throw $e;
        } catch (NetworkException $e) {
            $this->isLoggedIn = false;
            throw $e;
        }
    }

    /**
     * 登出
     *
     * @throws ClientException 登出失败
     */
    public function logout(): void
    {
        if ($this->isLoggedIn) {
            try {
                $this->getAuthAPI()->logout();
            } finally {
                $this->isLoggedIn = false;
                $this->transport->setAuthentication(null);
            }
        }
    }

    /**
     * 检查是否已登录
     */
    public function isLoggedIn(): bool
    {
        return $this->isLoggedIn;
    }

    /**
     * 获取认证API
     */
    public function getAuthAPI(): AuthAPI
    {
        if ($this->authAPI === null) {
            $this->authAPI = new AuthAPI($this->transport);
        }

        return $this->authAPI;
    }

    /**
     * 获取Torrent API
     *
     * @throws AuthenticationException 未登录时抛出异常
     */
    public function getTorrentAPI(): TorrentAPI
    {
        $this->requireAuthentication();

        if ($this->torrentAPI === null) {
            $this->torrentAPI = new TorrentAPI($this->transport);
        }

        return $this->torrentAPI;
    }

    /**
     * 获取应用程序API
     *
     * @throws AuthenticationException 未登录时抛出异常
     */
    public function getApplicationAPI(): ApplicationAPI
    {
        $this->requireAuthentication();

        if ($this->applicationAPI === null) {
            $this->applicationAPI = new ApplicationAPI($this->transport);
        }

        return $this->applicationAPI;
    }

    /**
     * 获取传输API
     *
     * @throws AuthenticationException 未登录时抛出异常
     */
    public function getTransferAPI(): TransferAPI
    {
        $this->requireAuthentication();

        if ($this->transferAPI === null) {
            $this->transferAPI = new TransferAPI($this->transport);
        }

        return $this->transferAPI;
    }

    /**
     * 获取RSS API
     *
     * @throws AuthenticationException 未登录时抛出异常
     */
    public function getRSSAPI(): RSSAPI
    {
        $this->requireAuthentication();

        if ($this->rssAPI === null) {
            $this->rssAPI = new RSSAPI($this->transport);
        }

        return $this->rssAPI;
    }

    /**
     * 获取搜索API
     *
     * @throws AuthenticationException 未登录时抛出异常
     */
    public function getSearchAPI(): SearchAPI
    {
        $this->requireAuthentication();

        if ($this->searchAPI === null) {
            $this->searchAPI = new SearchAPI($this->transport);
        }

        return $this->searchAPI;
    }

    /**
     * 获取客户端配置
     */
    public function getConfig(): ClientConfig
    {
        return $this->config;
    }

    /**
     * 获取传输层实例
     */
    public function getTransport(): TransportInterface
    {
        return $this->transport;
    }

    /**
     * 设置传输层实例
     *
     * @param TransportInterface $transport 传输层实例
     * @return self 返回当前实例以支持链式调用
     */
    public function setTransport(TransportInterface $transport): self
    {
        $this->transport = $transport;
        $this->configureTransport();

        // 重置所有API实例
        $this->authAPI = null;
        $this->torrentAPI = null;
        $this->applicationAPI = null;
        $this->transferAPI = null;
        $this->rssAPI = null;
        $this->searchAPI = null;

        return $this;
    }

    /**
     * 更新客户端配置
     *
     * @param ClientConfig $config 新的配置
     * @return self 返回当前实例以支持链式调用
     * @throws ValidationException 配置验证失败
     */
    public function updateConfig(ClientConfig $config): self
    {
        if (!$config->validate()) {
            throw ValidationException::invalidConfig($config->getErrors());
        }

        $this->config = $config;
        $this->configureTransport();

        // 如果配置变更导致认证失效，清除登录状态
        if ($this->isLoggedIn) {
            $this->logout();
        }

        return $this;
    }

    /**
     * 检查qBittorrent服务器是否可访问
     *
     * @return bool 服务器是否可访问
     */
    public function testConnection(): bool
    {
        try {
            // 尝试获取应用程序版本
            $this->getApplicationAPI()->getVersion();
            return true;
        } catch (ClientException $e) {
            return false;
        }
    }

    /**
     * 获取qBittorrent服务器信息
     *
     * @return array 服务器信息
     * @throws ClientException 获取失败
     */
    public function getServerInfo(): array
    {
        $appAPI = $this->getApplicationAPI();

        return [
            'version' => $appAPI->getVersion(),
            'web_api_version' => $appAPI->getWebApiVersion(),
            'build_info' => $appAPI->getBuildInfo(),
            'preferences' => $appAPI->getPreferences(),
        ];
    }

    /**
     * 关闭客户端并清理资源
     */
    public function close(): void
    {
        try {
            if ($this->isLoggedIn) {
                $this->logout();
            }
        } finally {
            $this->transport->close();
        }
    }

    /**
     * 析构函数
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * 配置传输层
     */
    private function configureTransport(): void
    {
        $this->transport->setBaseUrl($this->config->getUrl());
        $this->transport->setTimeout($this->config->getTimeout());
        $this->transport->setConnectTimeout($this->config->getConnectTimeout());
        $this->transport->setUserAgent($this->config->getUserAgent());
        $this->transport->setVerifySSL($this->config->isVerifySSL());

        if ($this->config->getSSLCertPath()) {
            $this->transport->setSSLCertPath($this->config->getSSLCertPath());
        }

        if ($this->config->getProxy()) {
            $this->transport->setProxy(
                $this->config->getProxy(),
                $this->config->getProxyAuth()
            );
        }
    }

    /**
     * 要求认证状态
     *
     * @throws AuthenticationException 未登录时抛出异常
     */
    private function requireAuthentication(): void
    {
        if (!$this->isLoggedIn) {
            throw new AuthenticationException('需要进行认证登录', 'AUTHENTICATION_REQUIRED');
        }
    }

    /**
     * 创建客户端的静态工厂方法
     *
     * @param string $url qBittorrent服务器URL
     * @param string|null $username 用户名
     * @param string|null $password 密码
     * @return self 客户端实例
     */
    public static function create(string $url, ?string $username = null, ?string $password = null): self
    {
        $config = new ClientConfig($url, $username, $password);
        return new self($config);
    }

    /**
     * 从配置数组创建客户端
     *
     * @param array $config 配置数组
     * @return self 客户端实例
     * @throws ValidationException 配置验证失败
     */
    public static function fromArray(array $config): self
    {
        $clientConfig = ClientConfig::fromArray($config);
        return new self($clientConfig);
    }

    /**
     * 魔术方法：获取API实例的便捷访问
     *
     * @param string $name API名称
     * @return mixed API实例
     * @throws ClientException 不支持的API
     */
    public function __get(string $name)
    {
        $apiMap = [
            'auth' => 'getAuthAPI',
            'torrent' => 'getTorrentAPI',
            'application' => 'getApplicationAPI',
            'transfer' => 'getTransferAPI',
            'rss' => 'getRSSAPI',
            'search' => 'getSearchAPI',
        ];

        $method = $apiMap[strtolower($name)] ?? null;

        if ($method && method_exists($this, $method)) {
            return $this->$method();
        }

        throw new ClientException("未知的API: {$name}");
    }
}