<?php
declare(strict_types=1);

namespace PhpQbittorrent;

use PhpQbittorrent\API\ApplicationAPI;
use PhpQbittorrent\API\TransferAPI;
use PhpQbittorrent\API\TorrentAPI;
use PhpQbittorrent\API\RSSAPI;
use PhpQbittorrent\API\SearchAPI;
use PhpQbittorrent\Contract\TransportInterface;
use PhpQbittorrent\Transport\CurlTransport;
use Nyholm\Psr7\Factory\Psr17Factory;
use PhpQbittorrent\Exception\AuthenticationException;
use PhpQbittorrent\Exception\NetworkException;
use PhpQbittorrent\Exception\ValidationException;

/**
 * qBittorrent客户端 参数对象化
 *
 * 提供统一的API访问接口，整合所有v2 API模块
 */
class Client
{
    /** @var TransportInterface 传输层实例 */
    private TransportInterface $transport;

    /** @var string qBittorrent服务器地址 */
    private string $baseUrl;

    /** @var string 用户名 */
    private string $username;

    /** @var string 密码 */
    private string $password;

    /** @var bool 是否已认证 */
    private bool $authenticated = false;

    /** @var ApplicationAPI|null 应用API实例 */
    private ?ApplicationAPI $applicationAPI = null;

    /** @var TransferAPI|null 传输API实例 */
    private ?TransferAPI $transferAPI = null;

    /** @var TorrentAPI|null 种子API实例 */
    private ?TorrentAPI $torrentAPI = null;

    /** @var RSSAPI|null RSS API实例 */
    private ?RSSAPI $rssAPI = null;

    /** @var SearchAPI|null 搜索API实例 */
    private ?SearchAPI $searchAPI = null;

    /**
     * 构造函数
     *
     * @param string $baseUrl qBittorrent服务器地址
     * @param string $username 用户名
     * @param string $password 密码
     * @param TransportInterface|null $transport 自定义传输层实例
     * @throws ValidationException 配置异常
     */
    public function __construct(
        string $baseUrl,
        string $username,
        string $password,
        ?TransportInterface $transport = null
    ) {
        $this->validateConfiguration($baseUrl, $username, $password);

        $this->baseUrl = rtrim($baseUrl, '/');
        $this->username = $username;
        $this->password = $password;
        $this->transport = $transport ?? new CurlTransport(new Psr17Factory(), new Psr17Factory());

        // 设置基础URL
        $this->transport->setBaseUrl($this->baseUrl);
    }

    /**
     * 验证配置
     *
     * @param string $baseUrl 基础URL
     * @param string $username 用户名
     * @param string $password 密码
     * @throws ValidationException 配置异常
     */
    private function validateConfiguration(string $baseUrl, string $username, string $password): void
    {
        if (empty(trim($baseUrl))) {
            throw new ValidationException('qBittorrent服务器地址不能为空');
        }

        if (!filter_var($baseUrl, FILTER_VALIDATE_URL) && !str_starts_with($baseUrl, 'http://') && !str_starts_with($baseUrl, 'https://')) {
            throw new ValidationException('qBittorrent服务器地址格式无效，必须以http://或https://开头');
        }

        if (empty(trim($username))) {
            throw new ValidationException('用户名不能为空');
        }

        if (empty(trim($password))) {
            throw new ValidationException('密码不能为空');
        }
    }

    /**
     * 认证登录
     *
     * @return bool 是否认证成功
     * @throws AuthenticationException 认证异常
     * @throws NetworkException 网络异常
     */
    public function login(): bool
    {
        try {
            $response = $this->transport->request('POST', '/api/v2/auth/login', [
                'form_params' => [
                    'username' => $this->username,
                    'password' => $this->password,
                ]
            ]);

            // 获取认证cookie并设置到传输层
            $cookie = $this->transport->getAuthentication();
            if ($cookie) {
                $this->authenticated = true;
                return true;
            }

            // 如果没有获取到cookie，可能是登录失败
            throw new AuthenticationException(
                '登录失败：未能获取认证Cookie',
                'AUTH_FAILED',
                ['username' => $this->username],
                $this->username,
                'invalid_credentials'
            );

        } catch (NetworkException $e) {
            throw new AuthenticationException(
                '认证时发生网络错误: ' . $e->getMessage(),
                'AUTH_NETWORK_ERROR',
                ['original_error' => $e->getMessage()],
                $this->username,
                'network_error',
                $e
            );
        }
    }

    /**
     * 检查是否已认证
     *
     * @return bool
     */
    public function isLoggedIn(): bool
    {
        return $this->authenticated;
    }

    /**
     * 登出
     *
     * @return bool 是否登出成功
     * @throws NetworkException 网络异常
     */
    public function logout(): bool
    {
        if (!$this->authenticated) {
            return true;
        }

        try {
            $response = $this->transport->request('POST', '/api/v2/auth/logout');
            $this->authenticated = false;
            return true; // 登出端点成功时返回空数组
        } catch (NetworkException $e) {
            // 即使登出失败，也标记为未认证
            $this->authenticated = false;
            throw $e;
        }
    }

    /**
     * 检查是否已认证
     *
     * @return bool 是否已认证
     */
    public function isAuthenticated(): bool
    {
        return $this->authenticated;
    }

    /**
     * 强制认证（如果未认证则自动认证）
     *
     * @return bool 是否认证成功
     * @throws AuthenticationException 认证异常
     * @throws NetworkException 网络异常
     */
    public function ensureAuthenticated(): bool
    {
        if (!$this->authenticated) {
            return $this->login();
        }
        return true;
    }

    /**
     * 获取应用API实例
     *
     * @return ApplicationAPI 应用API实例
     * @throws AuthenticationException 认证异常
     */
    public function application(): ApplicationAPI
    {
        $this->ensureAuthenticated();

        if ($this->applicationAPI === null) {
            $this->applicationAPI = new ApplicationAPI($this->transport);
        }

        return $this->applicationAPI;
    }

    /**
     * 获取传输API实例
     *
     * @return TransferAPI 传输API实例
     * @throws AuthenticationException 认证异常
     */
    public function transfer(): TransferAPI
    {
        $this->ensureAuthenticated();

        if ($this->transferAPI === null) {
            $this->transferAPI = new TransferAPI($this->transport);
        }

        return $this->transferAPI;
    }

    /**
     * 获取种子API实例
     *
     * @return TorrentAPI 种子API实例
     * @throws AuthenticationException 认证异常
     */
    public function torrents(): TorrentAPI
    {
        $this->ensureAuthenticated();

        if ($this->torrentAPI === null) {
            $this->torrentAPI = new TorrentAPI($this->transport);
        }

        return $this->torrentAPI;
    }

    /**
     * 获取RSS API实例
     *
     * @return RSSAPI RSS API实例
     * @throws AuthenticationException 认证异常
     */
    public function rss(): RSSAPI
    {
        $this->ensureAuthenticated();

        if ($this->rssAPI === null) {
            $this->rssAPI = new RSSAPI($this->transport);
        }

        return $this->rssAPI;
    }

    /**
     * 获取搜索API实例
     *
     * @return SearchAPI 搜索API实例
     * @throws AuthenticationException 认证异常
     */
    public function search(): SearchAPI
    {
        $this->ensureAuthenticated();

        if ($this->searchAPI === null) {
            $this->searchAPI = new SearchAPI($this->transport);
        }

        return $this->searchAPI;
    }

    /**
     * 获取传输API实例（别名方法）
     *
     * @return TransferAPI 传输API实例
     * @throws AuthenticationException 认证异常
     */
    public function getTransferAPI(): TransferAPI
    {
        return $this->transfer();
    }

    /**
     * 获取种子API实例（别名方法）
     *
     * @return TorrentAPI 种子API实例
     * @throws AuthenticationException 认证异常
     */
    public function getTorrentAPI(): TorrentAPI
    {
        return $this->torrents();
    }

    /**
     * 获取RSS API实例（别名方法）
     *
     * @return RSSAPI RSS API实例
     * @throws AuthenticationException 认证异常
     */
    public function getRSSAPI(): RSSAPI
    {
        return $this->rss();
    }

    /**
     * 获取搜索API实例（别名方法）
     *
     * @return SearchAPI 搜索API实例
     * @throws AuthenticationException 认证异常
     */
    public function getSearchAPI(): SearchAPI
    {
        return $this->search();
    }

    /**
     * 获取传输层实例
     *
     * @return TransportInterface 传输层实例
     */
    public function getTransport(): TransportInterface
    {
        return $this->transport;
    }

    /**
     * 设置传输层实例
     *
     * @param TransportInterface $transport 传输层实例
     * @return static 返回自身以支持链式调用
     */
    public function setTransport(TransportInterface $transport): static
    {
        $this->transport = $transport;
        $this->transport->setBaseUrl($this->baseUrl);

        // 重置所有API实例，使其使用新的传输层
        $this->applicationAPI = null;
        $this->transferAPI = null;
        $this->torrentAPI = null;
        $this->rssAPI = null;
        $this->searchAPI = null;

        return $this;
    }

    /**
     * 获取基础URL
     *
     * @return string 基础URL
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * 设置基础URL
     *
     * @param string $baseUrl 基础URL
     * @return static 返回自身以支持链式调用
     * @throws ValidationException 配置异常
     */
    public function setBaseUrl(string $baseUrl): static
    {
        $this->validateConfiguration($baseUrl, $this->username, $this->password);

        $this->baseUrl = rtrim($baseUrl, '/');
        $this->transport->setBaseUrl($this->baseUrl);

        // 重新认证
        $this->authenticated = false;

        return $this;
    }

    /**
     * 获取用户名
     *
     * @return string 用户名
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * 设置用户名
     *
     * @param string $username 用户名
     * @return static 返回自身以支持链式调用
     * @throws ValidationException 配置异常
     */
    public function setUsername(string $username): static
    {
        if (empty(trim($username))) {
            throw new ValidationException('用户名不能为空');
        }

        $this->username = $username;
        $this->authenticated = false;

        return $this;
    }

    /**
     * 设置密码
     *
     * @param string $password 密码
     * @return static 返回自身以支持链式调用
     * @throws ValidationException 配置异常
     */
    public function setPassword(string $password): static
    {
        if (empty(trim($password))) {
            throw new ValidationException('密码不能为空');
        }

        $this->password = $password;
        $this->authenticated = false;

        return $this;
    }

    /**
     * 获取服务器信息
     *
     * @return array<string, mixed> 服务器信息
     * @throws AuthenticationException 认证异常
     * @throws NetworkException 网络异常
     */
    public function getServerInfo(): array
    {
        try {
            $this->ensureAuthenticated();

            $applicationAPI = $this->application();

            // 获取版本信息
            $versionResponse = $applicationAPI->getVersion(\PhpQbittorrent\Request\Application\GetVersionRequest::create());
            $webApiVersionResponse = $applicationAPI->getWebApiVersion(\PhpQbittorrent\Request\Application\GetWebApiVersionRequest::create());

            $serverInfo = [
                'version' => $versionResponse->isSuccess() ? $versionResponse->getVersion() : 'Unknown',
                'web_api_version' => $webApiVersionResponse->isSuccess() ? $webApiVersionResponse->getVersion() : 'Unknown',
            ];

            // 尝试获取构建信息（可选）
            try {
                $buildInfoResponse = $applicationAPI->getBuildInfo(\PhpQbittorrent\Request\Application\GetBuildInfoRequest::create());
                if ($buildInfoResponse->isSuccess()) {
                    $serverInfo['build_info'] = $buildInfoResponse->getBuildInfo();
                }
            } catch (\Exception $e) {
                // 构建信息获取失败不影响主要功能
                $serverInfo['build_info'] = null;
            }

            // 尝试获取偏好设置（可选）
            try {
                $preferencesResponse = $applicationAPI->getPreferences(\PhpQbittorrent\Request\Application\GetPreferencesRequest::create());
                if ($preferencesResponse->isSuccess()) {
                    $data = $preferencesResponse->getData();
                    $serverInfo['preferences'] = $data['preferences'] ?? [];
                }
            } catch (\Exception $e) {
                // 偏好设置获取失败不影响主要功能
                $serverInfo['preferences'] = [];
            }

            return $serverInfo;

        } catch (NetworkException $e) {
            throw new NetworkException(
                '获取服务器信息失败: ' . $e->getMessage(),
                'GET_SERVER_INFO_FAILED',
                ['original_error' => $e->getMessage()],
                $e
            );
        }
    }

    /**
     * 测试连接
     *
     * @return bool 连接是否成功
     * @throws NetworkException 网络异常
     * @throws AuthenticationException 认证异常
     */
    public function testConnection(): bool
    {
        try {
            // 尝试获取应用版本作为连接测试
            $versionResponse = $this->application()->getVersion(\PhpQbittorrent\Request\Application\GetVersionRequest::create());
            return $versionResponse->isSuccess();
        } catch (NetworkException $e) {
            throw new NetworkException(
                '连接测试失败: ' . $e->getMessage(),
                'CONNECTION_TEST_FAILED',
                ['original_error' => $e->getMessage()],
                $e
            );
        }
    }

    /**
     * 获取客户端信息
     *
     * @return array<string, mixed> 客户端信息
     */
    public function getClientInfo(): array
    {
        return [
            'base_url' => $this->baseUrl,
            'username' => $this->username,
            'authenticated' => $this->authenticated,
            'transport_class' => get_class($this->transport),
            'api_instances' => [
                'application' => $this->applicationAPI !== null,
                'transfer' => $this->transferAPI !== null,
                'torrents' => $this->torrentAPI !== null,
                'rss' => $this->rssAPI !== null,
                'search' => $this->searchAPI !== null,
            ],
        ];
    }

    /**
     * 创建客户端实例
     *
     * @param string $baseUrl qBittorrent服务器地址
     * @param string $username 用户名
     * @param string $password 密码
     * @param TransportInterface|null $transport 自定义传输层实例
     * @return self 客户端实例
     */
    public static function create(
        string $baseUrl,
        string $username,
        string $password,
        ?TransportInterface $transport = null
    ): self {
        return new self($baseUrl, $username, $password, $transport);
    }

    /**
     * 从配置数组创建客户端实例
     *
     * @param array<string, mixed> $config 配置数组
     * @return self 客户端实例
     * @throws ValidationException 配置异常
     */
    public static function fromConfig(array $config): self
    {
        $baseUrl = $config['base_url'] ?? $config['baseUrl'] ?? '';
        $username = $config['username'] ?? '';
        $password = $config['password'] ?? '';
        $transport = $config['transport'] ?? null;

        if (empty($baseUrl) || empty($username) || empty($password)) {
            throw new ValidationException('配置数组中缺少必要的参数：base_url, username, password');
        }

        return new self($baseUrl, $username, $password, $transport);
    }

    /**
     * 魔术方法 - 支持动态访问应用API方法
     *
     * @param string $name 方法名
     * @param array $arguments 参数
     * @return mixed
     * @throws \Exception 方法不存在时抛出异常
     */
    public function __call(string $name, array $arguments)
    {
        // 支持应用API方法的魔术调用
        $applicationAPI = $this->application();

        // 检查方法是否存在
        if (method_exists($applicationAPI, $name)) {
            return $applicationAPI->$name(...$arguments);
        }

        throw new \Exception("方法 {$name} 不存在");
    }

    /**
     * 魔术方法 - 支持动态访问应用属性
     *
     * @param string $name 属性名
     * @return mixed
     * @throws \Exception 属性不存在时抛出异常
     */
    public function __get(string $name)
    {
        try {
            $applicationAPI = $this->application();

            // 支持一些常用的属性访问
            switch ($name) {
                case 'version':
                    $versionResponse = $applicationAPI->getVersion(\PhpQbittorrent\Request\Application\GetVersionRequest::create());
                    return $versionResponse->isSuccess() ? $versionResponse->getVersion() : null;

                case 'webApiVersion':
                    $versionResponse = $applicationAPI->getWebApiVersion(\PhpQbittorrent\Request\Application\GetWebApiVersionRequest::create());
                    return $versionResponse->isSuccess() ? $versionResponse->getVersion() : null;

                case 'buildInfo':
                    $buildResponse = $applicationAPI->getBuildInfo(\PhpQbittorrent\Request\Application\GetBuildInfoRequest::create());
                    return $buildResponse->isSuccess() ? $buildResponse->getBuildInfo() : null;

                case 'preferences':
                    $preferencesResponse = $applicationAPI->getPreferences(\PhpQbittorrent\Request\Application\GetPreferencesRequest::create());
                    if ($preferencesResponse->isSuccess()) {
                        $data = $preferencesResponse->getData();
                        return $data['preferences'] ?? null;
                    }
                    return null;

                default:
                    throw new \Exception("属性 {$name} 不存在");
            }
        } catch (\Exception $e) {
            throw new \Exception("获取属性 {$name} 失败: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 析构函数 - 自动登出
     */
    public function __destruct()
    {
        if ($this->authenticated) {
            try {
                $this->logout();
            } catch (\Exception $e) {
                // 静默处理登出错误
            }
        }
    }
}