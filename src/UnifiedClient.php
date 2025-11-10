<?php
declare(strict_types=1);

namespace PhpQbittorrent;

use PhpQbittorrent\Config\ConfigurationManager;
use PhpQbittorrent\Factory\RequestFactory;
use PhpQbittorrent\Builder\ResponseBuilder;
use PhpQbittorrent\Collection\TorrentCollection;
use PhpQbittorrent\Collection\SearchResultCollection;
use PhpQbittorrent\Exception\ConfigurationException;
use PhpQbittorrent\Exception\AuthenticationException;
use PhpQbittorrent\Exception\NetworkException;

/**
 * 统一客户端
 *
 * 提供简化的API接口，整合所有功能模块
 */
class UnifiedClient
{
    /** @var Client 底层客户端 */
    private Client $client;

    /** @var ConfigurationManager 配置管理器 */
    private ConfigurationManager $config;

    /**
     * 构造函数
     *
     * @param Client|null $client 底层客户端
     * @param ConfigurationManager|null $config 配置管理器
     * @throws ConfigurationException 配置异常
     */
    public function __construct(?Client $client = null, ?ConfigurationManager $config = null)
    {
        $this->config = $config ?? new ConfigurationManager();

        if ($client === null) {
            $transport = $this->config->createTransport();
            $client = new Client(
                $this->config->getBaseUrl(),
                $this->config->get('username'),
                $this->config->get('password'),
                $transport
            );
        }

        $this->client = $client;
    }

    /**
     * 从配置创建统一客户端
     *
     * @param array<string, mixed> $config 配置数组
     * @return self 统一客户端实例
     * @throws ConfigurationException 配置异常
     */
    public static function fromConfig(array $config): self
    {
        $configManager = ConfigurationManager::fromArray($config);
        return new self(null, $configManager);
    }

    /**
     * 从JSON配置文件创建统一客户端
     *
     * @param string $configFile 配置文件路径
     * @return self 统一客户端实例
     * @throws ConfigurationException 配置异常
     */
    public static function fromJsonFile(string $configFile): self
    {
        $configManager = ConfigurationManager::fromJsonFile($configFile);
        return new self(null, $configManager);
    }

    /**
     * 从环境变量创建统一客户端
     *
     * @param string $prefix 环境变量前缀
     * @return self 统一客户端实例
     */
    public static function fromEnvironment(string $prefix = 'QBITTORRENT_'): self
    {
        $configManager = ConfigurationManager::fromEnvironment($prefix);
        return new self(null, $configManager);
    }

    /**
     * 快速创建客户端
     *
     * @param string $baseUrl 服务器地址
     * @param string $username 用户名
     * @param string $password 密码
     * @return self 统一客户端实例
     */
    public static function quick(string $baseUrl, string $username, string $password): self
    {
        return new self(Client::create($baseUrl, $username, $password));
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
        return $this->client->login();
    }

    /**
     * 登出
     *
     * @return bool 是否登出成功
     * @throws NetworkException 网络异常
     */
    public function logout(): bool
    {
        return $this->client->logout();
    }

    /**
     * 检查是否已认证
     *
     * @return bool 是否已认证
     */
    public function isAuthenticated(): bool
    {
        return $this->client->isAuthenticated();
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
        return $this->client->testConnection();
    }

    /**
     * 获取客户端信息
     *
     * @return array<string, mixed> 客户端信息
     */
    public function getClientInfo(): array
    {
        return array_merge(
            $this->client->getClientInfo(),
            ['config' => $this->config->toArray()]
        );
    }

    // ========================================
    // 简化的应用相关方法
    // ========================================

    /**
     * 获取应用版本
     *
     * @return string 应用版本
     */
    public function getVersion(): string
    {
        $request = RequestFactory::createGetVersionRequest();
        $response = $this->client->application()->getVersion($request);
        return $response->getVersion();
    }

    /**
     * 获取Web API版本
     *
     * @return string Web API版本
     */
    public function getWebApiVersion(): string
    {
        $request = RequestFactory::createGetWebApiVersionRequest();
        $response = $this->client->application()->getWebApiVersion($request);
        return $response->getVersion();
    }

    /**
     * 获取构建信息
     *
     * @return array<string, mixed> 构建信息
     */
    public function getBuildInfo(): array
    {
        $request = RequestFactory::createGetBuildInfoRequest();
        $response = $this->client->application()->getBuildInfo($request);
        return $response->toArray();
    }

    // ========================================
    // 简化的传输相关方法
    // ========================================

    /**
     * 获取全局传输信息
     *
     * @return array<string, mixed> 传输信息
     */
    public function getTransferInfo(): array
    {
        $request = RequestFactory::createGetGlobalTransferInfoRequest();
        $response = $this->client->transfer()->getGlobalTransferInfo($request);
        return $response->toArray();
    }

    /**
     * 获取下载速度
     *
     * @return int 下载速度（字节/秒）
     */
    public function getDownloadSpeed(): int
    {
        $request = RequestFactory::createGetGlobalTransferInfoRequest();
        $response = $this->client->transfer()->getGlobalTransferInfo($request);
        return $response->getDownloadSpeed();
    }

    /**
     * 获取上传速度
     *
     * @return int 上传速度（字节/秒）
     */
    public function getUploadSpeed(): int
    {
        $request = RequestFactory::createGetGlobalTransferInfoRequest();
        $response = $this->client->transfer()->getGlobalTransferInfo($request);
        return $response->getUploadSpeed();
    }

    /**
     * 切换替代速度限制
     *
     * @return bool 是否切换成功
     */
    public function toggleAlternativeSpeedLimits(): bool
    {
        $request = RequestFactory::createToggleAlternativeSpeedLimitsRequest();
        $response = $this->client->transfer()->toggleAlternativeSpeedLimits($request);
        return $response->isSuccess();
    }

    // ========================================
    // 简化的种子相关方法
    // ========================================

    /**
     * 获取所有种子
     *
     * @param array<string, mixed> $options 过滤选项
     * @return TorrentCollection 种子集合
     */
    public function getTorrents(array $options = []): TorrentCollection
    {
        $request = RequestFactory::createGetTorrentsRequest(
            $options['filter'] ?? null,
            $options['category'] ?? null,
            $options['tag'] ?? null,
            $options['sort'] ?? null,
            $options['reverse'] ?? null,
            $options['limit'] ?? null,
            $options['offset'] ?? null,
            $options['hashes'] ?? null
        );

        $response = $this->client->torrents()->getTorrents($request);
        return $response->getTorrents();
    }

    /**
     * 获取种子信息
     *
     * @param string $hash 种子哈希
     * @return array<string, mixed> 种子信息
     */
    public function getTorrentInfo(string $hash): array
    {
        $request = RequestFactory::createGetTorrentInfoRequest($hash);
        $response = $this->client->torrents()->getTorrentInfo($request);
        return $response->toArray();
    }

    /**
     * 添加种子
     *
     * @param array<string, mixed> $options 种子选项
     * @return bool 是否添加成功
     */
    public function addTorrent(array $options): bool
    {
        $request = RequestFactory::createAddTorrentRequest($options);
        $response = $this->client->torrents()->addTorrent($request);
        return $response->isSuccess();
    }

    /**
     * 添加种子URL
     *
     * @param string $url 种子URL
     * @param array<string, mixed> $options 额外选项
     * @return bool 是否添加成功
     */
    public function addTorrentFromUrl(string $url, array $options = []): bool
    {
        $options['urls'] = $url;
        return $this->addTorrent($options);
    }

    /**
     * 添加种子文件
     *
     * @param string $filename 文件名
     * @param string $content 文件内容
     * @param array<string, mixed> $options 额外选项
     * @return bool 是否添加成功
     */
    public function addTorrentFromFile(string $filename, string $content, array $options = []): bool
    {
        $options['torrents'] = $content;
        $options['filename'] = $filename;
        return $this->addTorrent($options);
    }

    /**
     * 暂停种子
     *
     * @param string $hashes 种子哈希（多个用|分隔）
     * @return bool 是否暂停成功
     */
    public function pauseTorrents(string $hashes): bool
    {
        $request = RequestFactory::createPauseTorrentsRequest($hashes);
        $response = $this->client->torrents()->pauseTorrents($request);
        return $response->isSuccess();
    }

    /**
     * 恢复种子
     *
     * @param string $hashes 种子哈希（多个用|分隔）
     * @return bool 是否恢复成功
     */
    public function resumeTorrents(string $hashes): bool
    {
        $request = RequestFactory::createResumeTorrentsRequest($hashes);
        $response = $this->client->torrents()->resumeTorrents($request);
        return $response->isSuccess();
    }

    /**
     * 删除种子
     *
     * @param string $hashes 种子哈希（多个用|分隔）
     * @param bool $deleteFiles 是否删除文件
     * @return bool 是否删除成功
     */
    public function deleteTorrents(string $hashes, bool $deleteFiles = false): bool
    {
        $request = RequestFactory::createDeleteTorrentsRequest($hashes, $deleteFiles);
        $response = $this->client->torrents()->deleteTorrents($request);
        return $response->isSuccess();
    }

    // ========================================
    // 简化的搜索相关方法
    // ========================================

    /**
     * 开始搜索
     *
     * @param string $pattern 搜索模式
     * @param array<string> $plugins 搜索插件
     * @param string $category 搜索分类
     * @return int 搜索作业ID
     */
    public function startSearch(string $pattern, array $plugins = [], string $category = 'all'): int
    {
        $request = RequestFactory::createStartSearchRequest($pattern, $plugins, $category);
        $response = $this->client->search()->startSearch($request);
        return $response->getSearchId();
    }

    /**
     * 停止搜索
     *
     * @param int $searchId 搜索作业ID
     * @return bool 是否停止成功
     */
    public function stopSearch(int $searchId): bool
    {
        $request = RequestFactory::createStopSearchRequest($searchId);
        $response = $this->client->search()->stopSearch($request);
        return $response->isSuccess();
    }

    /**
     * 获取搜索结果
     *
     * @param int $searchId 搜索作业ID
     * @param int|null $limit 结果限制
     * @param int|null $offset 结果偏移
     * @return SearchResultCollection 搜索结果集合
     */
    public function getSearchResults(int $searchId, ?int $limit = null, ?int $offset = null): SearchResultCollection
    {
        $request = RequestFactory::createGetSearchResultsRequest($searchId, $limit, $offset);
        $response = $this->client->search()->getSearchResults($request);
        return $response->getSearchResults();
    }

    /**
     * 简化的搜索方法（同步完成）
     *
     * @param string $pattern 搜索模式
     * @param array<string> $plugins 搜索插件
     * @param string $category 搜索分类
     * @param int $maxWaitTime 最大等待时间（秒）
     * @return SearchResultCollection 搜索结果集合
     */
    public function search(string $pattern, array $plugins = [], string $category = 'all', int $maxWaitTime = 30): SearchResultCollection
    {
        // 开始搜索
        $searchId = $this->startSearch($pattern, $plugins, $category);

        // 等待搜索完成
        $startTime = time();
        while (time() - $startTime < $maxWaitTime) {
            $statusRequest = RequestFactory::createGetSearchStatusRequest($searchId);
            $statusResponse = $this->client->search()->getSearchStatus($statusRequest);

            $searchJobs = $statusResponse->getSearchJobs();
            $searchJob = $searchJobs->findById($searchId);

            if ($searchJob && !$searchJob->isRunning()) {
                break;
            }

            usleep(500000); // 等待0.5秒
        }

        // 获取结果
        $results = $this->getSearchResults($searchId);

        // 清理搜索
        $this->deleteSearch($searchId);

        return $results;
    }

    /**
     * 删除搜索
     *
     * @param int $searchId 搜索作业ID
     * @return bool 是否删除成功
     */
    public function deleteSearch(int $searchId): bool
    {
        $request = RequestFactory::createDeleteSearchRequest($searchId);
        $response = $this->client->search()->deleteSearch($request);
        return $response->isSuccess();
    }

    // ========================================
    // 批量操作方法
    // ========================================

    /**
     * 批量暂停所有种子
     *
     * @return int 暂停的种子数量
     */
    public function pauseAllTorrents(): int
    {
        $torrents = $this->getTorrents(['filter' => 'active', 'hashes' => 'all']);
        $activeTorrents = $torrents->getActive();

        if ($activeTorrents->isEmpty()) {
            return 0;
        }

        $hashes = $activeTorrents->getHashListAsString();
        return $this->pauseTorrents($hashes) ? $activeTorrents->count() : 0;
    }

    /**
     * 批量恢复所有种子
     *
     * @return int 恢复的种子数量
     */
    public function resumeAllTorrents(): int
    {
        $torrents = $this->getTorrents(['filter' => 'paused', 'hashes' => 'all']);
        $pausedTorrents = $torrents->getPaused();

        if ($pausedTorrents->isEmpty()) {
            return 0;
        }

        $hashes = $pausedTorrents->getHashListAsString();
        return $this->resumeTorrents($hashes) ? $pausedTorrents->count() : 0;
    }

    /**
     * 清理完成的种子（不包括正在做种的）
     *
     * @param bool $deleteFiles 是否删除文件
     * @return int 清理的种子数量
     */
    public function cleanCompletedTorrents(bool $deleteFiles = false): int
    {
        $torrents = $this->getTorrents(['filter' => 'completed']);
        $completedTorrents = $torrents->getCompleted()->notSeeding();

        if ($completedTorrents->isEmpty()) {
            return 0;
        }

        $hashes = $completedTorrents->getHashListAsString();
        return $this->deleteTorrents($hashes, $deleteFiles) ? $completedTorrents->count() : 0;
    }

    // ========================================
    // 统计和监控方法
    // ========================================

    /**
     * 获取完整的统计信息
     *
     * @return array<string, mixed> 统计信息
     */
    public function getStatistics(): array
    {
        $torrents = $this->getTorrents();
        $transferInfo = $this->getTransferInfo();

        return [
            'transfer' => $transferInfo,
            'torrents' => [
                'total' => $torrents->count(),
                'downloading' => $torrents->getDownloading()->count(),
                'seeding' => $torrents->getSeeding()->count(),
                'completed' => $torrents->getCompleted()->count(),
                'paused' => $torrents->getPaused()->count(),
                'active' => $torrents->getActive()->count(),
                'inactive' => $torrents->getInactive()->count(),
                'errored' => $torrents->getErrored()->count(),
            ],
            'size' => [
                'total_size' => $torrents->getTotalSize(),
                'completed_size' => $torrents->getCompletedSize(),
                'remaining_size' => $torrents->getRemainingSize(),
                'total_downloaded' => $torrents->getTotalDownloaded(),
                'total_uploaded' => $torrents->getTotalUploaded(),
            ],
            'speed' => [
                'download_speed' => $transferInfo['dl_info_speed'] ?? 0,
                'upload_speed' => $transferInfo['up_info_speed'] ?? 0,
            ],
        ];
    }

    /**
     * 获取健康状态
     *
     * @return array<string, mixed> 健康状态
     */
    public function getHealthStatus(): array
    {
        $torrents = $this->getTorrents();
        $erroredTorrents = $torrents->getErrored();
        $stalledTorrents = $torrents->getStalled();

        $health = [
            'status' => 'healthy',
            'issues' => [],
        ];

        if (!$erroredTorrents->isEmpty()) {
            $health['status'] = 'error';
            $health['issues'][] = "有 {$erroredTorrents->count()} 个错误的种子";
        }

        if (!$stalledTorrents->isEmpty()) {
            if ($health['status'] === 'healthy') {
                $health['status'] = 'warning';
            }
            $health['issues'][] = "有 {$stalledTorrents->count()} 个停滞的种子";
        }

        $health['torrents'] = [
            'errored' => $erroredTorrents->count(),
            'stalled' => $stalledTorrents->count(),
        ];

        return $health;
    }

    // ========================================
    // 配置管理方法
    // ========================================

    /**
     * 获取配置管理器
     *
     * @return ConfigurationManager 配置管理器
     */
    public function getConfig(): ConfigurationManager
    {
        return $this->config;
    }

    /**
     * 获取底层客户端
     *
     * @return Client 底层客户端
     */
    public function getClient(): Client
    {
        return $this->client;
    }

    /**
     * 设置调试模式
     *
     * @param bool $debug 是否启用调试
     * @return self 返回自身以支持链式调用
     */
    public function setDebug(bool $debug): self
    {
        $this->config->setLogConfig($debug, $debug, $debug);
        return $this;
    }

    /**
     * 设置缓存配置
     *
     * @param bool $enabled 是否启用缓存
     * @param int $ttl 缓存TTL（秒）
     * @return self 返回自身以支持链式调用
     */
    public function setCache(bool $enabled, int $ttl = 300): self
    {
        $this->config->setCacheConfig($enabled, $ttl);
        return $this;
    }
}