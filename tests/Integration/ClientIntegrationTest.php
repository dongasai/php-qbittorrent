<?php
declare(strict_types=1);

namespace PhpQbittorrent\Tests\Integration;

use PhpQbittorrent\Tests\TestCase;
use PhpQbittorrent\Client;
use PhpQbittorrent\Config\ClientConfig;
use PhpQbittorrent\Transport\CurlTransport;
use PhpQbittorrent\Exception\{
    ClientException,
    AuthenticationException,
    NetworkException,
    ValidationException
};

/**
 * 客户端集成测试
 *
 * 测试完整的API交互流程
 */
class ClientIntegrationTest extends TestCase
{
    private Client $client;
    private ClientConfig $config;

    protected function setUp(): void
    {
        parent::setUp();

        // 从环境变量获取测试配置
        $qbUrl = $_ENV['QBITTORRENT_URL'] ?? 'http://localhost:8080';
        $qbUsername = $_ENV['QBITTORRENT_USERNAME'] ?? 'admin';
        $qbPassword = $_ENV['QBITTORRENT_PASSWORD'] ?? 'adminpass';

        $this->config = new ClientConfig($qbUrl, $qbUsername, $qbPassword);
        $this->config->setTimeout(10.0); // 较短的超时用于测试
        $this->config->setVerifySSL(false); // 测试环境可能使用自签名证书

        $this->client = new Client($this->config);
    }

    protected function tearDown(): void
    {
        if (isset($this->client)) {
            $this->client->close();
        }
        parent::tearDown();
    }

    /**
     * 标记为需要qBittorrent服务器的测试
     */
    private function markTestNeedsQbittorrent(): void
    {
        if (!getenv('RUN_INTEGRATION_TESTS')) {
            $this->markTestSkipped('跳过集成测试 - 未设置RUN_INTEGRATION_TESTS环境变量');
        }
    }

    public function testClientCreation(): void
    {
        $this->markTestNeedsQbittorrent();

        $this->assertInstanceOf(Client::class, $this->client);
        $this->assertEquals($this->config->getUrl(), $this->client->getConfig()->getUrl());
        $this->assertFalse($this->client->isLoggedIn());
    }

    public function testAuthenticationFlow(): void
    {
        $this->markTestNeedsQbittorrent();

        try {
            // 测试登录
            $this->client->login();
            $this->assertTrue($this->client->isLoggedIn());

            // 测试认证API
            $authAPI = $this->client->getAuthAPI();
            $this->assertTrue($authAPI->isLoggedIn());

            // 测试登出
            $this->client->logout();
            $this->assertFalse($this->client->isLoggedIn());

        } catch (NetworkException $e) {
            $this->markTestSkipped("无法连接到qBittorrent服务器: " . $e->getMessage());
        } catch (AuthenticationException $e) {
            $this->fail("认证失败: " . $e->getMessage());
        }
    }

    public function testApplicationAPI(): void
    {
        $this->markTestNeedsQbittorrent();

        try {
            $this->client->login();
            $this->assertTrue($this->client->isLoggedIn());

            $appAPI = $this->client->getApplicationAPI();

            // 测试获取版本信息
            $version = $appAPI->getVersion();
            $this->assertIsString($version);
            $this->assertNotEmpty($version);

            // 测试获取Web API版本
            $webApiVersion = $appAPI->getWebApiVersion();
            $this->assertIsString($webApiVersion);
            $this->assertNotEmpty($webApiVersion);

            // 测试获取构建信息
            $buildInfo = $appAPI->getBuildInfo();
            $this->assertIsArray($buildInfo);
            $this->assertArrayHasKey('version', $buildInfo);
            $this->assertArrayHasKey('qt', $buildInfo);
            $this->assertArrayHasKey('libtorrent', $buildInfo);

            // 测试获取偏好设置
            $preferences = $appAPI->getPreferences();
            $this->assertIsArray($preferences);
            $this->assertNotEmpty($preferences);

            // 测试获取服务器信息
            $serverInfo = $this->client->getServerInfo();
            $this->assertIsArray($serverInfo);
            $this->assertArrayHasKey('version', $serverInfo);
            $this->assertArrayHasKey('web_api_version', $serverInfo);
            $this->assertArrayHasKey('build_info', $serverInfo);
            $this->assertArrayHasKey('preferences', $serverInfo);

        } catch (NetworkException $e) {
            $this->markTestSkipped("无法连接到qBittorrent服务器: " . $e->getMessage());
        } catch (AuthenticationException $e) {
            $this->fail("认证失败: " . $e->getMessage());
        }
    }

    public function testTransferAPI(): void
    {
        $this->markTestNeedsQbittorrent();

        try {
            $this->client->login();
            $this->assertTrue($this->client->isLoggedIn());

            $transferAPI = $this->client->getTransferAPI();

            // 测试获取传输信息
            $transferInfo = $transferAPI->getTransferInfo();
            $this->assertIsArray($transferInfo);
            $this->assertArrayHasKey('dl_info_speed', $transferInfo);
            $this->assertArrayHasKey('up_info_speed', $transferInfo);
            $this->assertArrayHasKey('dl_info_data', $transferInfo);
            $this->assertArrayHasKey('up_info_data', $transferInfo);
            $this->assertArrayHasKey('connection_status', $transferInfo);
            $this->assertArrayHasKey('dht_nodes', $transferInfo);

            // 测试获取下载速度统计
            $downloadStats = $transferAPI->getDownloadSpeedStats();
            $this->assertIsArray($downloadStats);
            $this->assertArrayHasKey('dl_info_speed', $downloadStats);
            $this->assertArrayHasKey('dl_info_data', $downloadStats);
            $this->assertArrayHasKey('dl_rate_limit', $downloadStats);

            // 测试获取上传速度统计
            $uploadStats = $transferAPI->getUploadSpeedStats();
            $this->assertIsArray($uploadStats);
            $this->assertArrayHasKey('up_info_speed', $uploadStats);
            $this->assertArrayHasKey('up_info_data', $uploadStats);
            $this->assertArrayHasKey('up_rate_limit', $uploadStats);

            // 测试获取连接信息
            $connectionInfo = $transferAPI->getConnectionInfo();
            $this->assertIsArray($connectionInfo);
            $this->assertArrayHasKey('connection_status', $connectionInfo);
            $this->assertArrayHasKey('dht_nodes', $connectionInfo);

            // 测试获取当前速度限制
            $speedLimits = $transferAPI->getCurrentSpeedLimits();
            $this->assertIsArray($speedLimits);
            $this->assertArrayHasKey('dl_rate_limit', $speedLimits);
            $this->assertArrayHasKey('up_rate_limit', $speedLimits);

        } catch (NetworkException $e) {
            $this->markTestSkipped("无法连接到qBittorrent服务器: " . $e->getMessage());
        } catch (AuthenticationException $e) {
            $this->fail("认证失败: " . $e->getMessage());
        }
    }

    public function testTorrentAPI(): void
    {
        $this->markTestNeedsQbittorrent();

        try {
            $this->client->login();
            $this->assertTrue($this->client->isLoggedIn());

            $torrentAPI = $this->client->getTorrentAPI();

            // 测试获取torrent列表
            $torrents = $torrentAPI->getTorrents();
            $this->assertIsArray($torrents);

            if (!empty($torrents)) {
                $firstTorrent = $torrents[0];
                $this->assertArrayHasKey('hash', $firstTorrent);
                $this->assertArrayHasKey('name', $firstTorrent);
                $this->assertArrayHasKey('size', $firstTorrent);
                $this->assertArrayHasKey('progress', $firstTorrent);
                $this->assertArrayHasKey('state', $firstTorrent);

                // 测试获取特定torrent信息
                $hash = $firstTorrent['hash'];
                $torrentInfo = $torrentAPI->getTorrentInfo($hash);
                $this->assertIsArray($torrentInfo);
                $this->assertEquals($hash, $torrentInfo['hash']);

                // 测试获取torrent属性
                $properties = $torrentAPI->getTorrentProperties($hash);
                $this->assertIsArray($properties);
                $this->assertArrayHasKey('save_path', $properties);

                // 测试获取torrent文件列表
                $files = $torrentAPI->getTorrentFiles($hash);
                $this->assertIsArray($files);

                // 测试获取torrent tracker列表
                $trackers = $torrentAPI->getTorrentTrackers($hash);
                $this->assertIsArray($trackers);

                // 测试获取Web种子列表
                $webSeeds = $torrentAPI->getTorrentWebSeeds($hash);
                $this->assertIsArray($webSeeds);

                // 测试获取torrent摘要
                $summary = $torrentAPI->getTorrentSummary($hash);
                $this->assertIsArray($summary);
                $this->assertArrayHasKey('basic_info', $summary);
                $this->assertArrayHasKey('properties', $summary);
                $this->assertArrayHasKey('files', $summary);
                $this->assertArrayHasKey('trackers', $summary);
            }

            // 测试获取分类
            $categories = $torrentAPI->getCategories();
            $this->assertIsArray($categories);

            // 测试获取标签
            $tags = $torrentAPI->getTags();
            $this->assertIsArray($tags);

            // 测试获取统计信息
            $stats = $torrentAPI->getTorrentStats();
            $this->assertIsArray($stats);

        } catch (NetworkException $e) {
            $this->markTestSkipped("无法连接到qBittorrent服务器: " . $e->getMessage());
        } catch (AuthenticationException $e) {
            $this->fail("认证失败: " . $e->getMessage());
        }
    }

    public function testMagicAPIAccess(): void
    {
        $this->markTestNeedsQbittorrent();

        try {
            $this->client->login();

            // 测试魔术方法访问API
            $this->assertInstanceOf(\PhpQbittorrent\API\AuthAPI::class, $this->client->auth);
            $this->assertInstanceOf(\PhpQbittorrent\API\TorrentAPI::class, $this->client->torrent);
            $this->assertInstanceOf(\PhpQbittorrent\API\ApplicationAPI::class, $this->client->application);
            $this->assertInstanceOf(\PhpQbittorrent\API\TransferAPI::class, $this->client->transfer);

            // 测试无效API访问
            $this->expectException(\PhpQbittorrent\Exception\ClientException::class);
            $this->client->invalidApi;

        } catch (NetworkException $e) {
            $this->markTestSkipped("无法连接到qBittorrent服务器: " . $e->getMessage());
        } catch (AuthenticationException $e) {
            $this->fail("认证失败: " . $e->getMessage());
        }
    }

    public function testErrorHandling(): void
    {
        $this->markTestNeedsQbittorrent();

        // 测试无效配置
        $this->expectException(ValidationException::class);
        new ClientConfig(''); // 空URL应该抛出异常
    }

    public function testStaticFactory(): void
    {
        $this->markTestNeedsQbittorrent();

        $url = $_ENV['QBITTORRENT_URL'] ?? 'http://localhost:8080';
        $username = $_ENV['QBITTORRENT_USERNAME'] ?? 'admin';
        $password = $_ENV['QBITTORRENT_PASSWORD'] ?? 'adminpass';

        // 测试静态工厂方法
        $client = Client::create($url, $username, $password);
        $this->assertInstanceOf(Client::class, $client);

        // 测试数组配置创建
        $configArray = [
            'url' => $url,
            'username' => $username,
            'password' => $password,
            'timeout' => 15.0
        ];
        $clientFromArray = Client::fromArray($configArray);
        $this->assertInstanceOf(Client::class, $clientFromArray);
        $this->assertEquals(15.0, $clientFromArray->getConfig()->getTimeout());
    }

    public function testConnectionTest(): void
    {
        $this->markTestNeedsQbittorrent();

        try {
            // 测试连接功能（不登录）
            $isConnected = $this->client->testConnection();
            $this->assertIsBool($isConnected);

            // 如果连接成功，测试完整的登录流程
            if ($isConnected) {
                $this->client->login();
                $this->assertTrue($this->client->isLoggedIn());
            }

        } catch (NetworkException $e) {
            $this->markTestSkipped("无法连接到qBittorrent服务器: " . $e->getMessage());
        }
    }

    /**
     * 测试客户端配置更新
     */
    public function testConfigUpdate(): void
    {
        $newConfig = new ClientConfig('http://test.example.com', 'testuser', 'testpass');
        $this->assertTrue($newConfig->validate());

        $updatedClient = $this->client->updateConfig($newConfig);
        $this->assertSame($this->client, $updatedClient); // 应该返回同一个实例
        $this->assertEquals('http://test.example.com', $this->client->getConfig()->getUrl());
        $this->assertFalse($this->client->isLoggedIn()); // 配置更新应该清除登录状态
    }

    /**
     * 测试传输层设置
     */
    public function testTransportSet(): void
    {
        $newTransport = new CurlTransport();
        $updatedClient = $this->client->setTransport($newTransport);
        $this->assertSame($this->client, $updatedClient); // 应该返回同一个实例
        $this->assertSame($newTransport, $this->client->getTransport());
    }

    /**
     * 测试API访问要求认证
     */
    public function testAuthenticationRequired(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('需要进行认证登录');

        // 未登录状态下访问需要认证的API
        $this->client->getTorrentAPI();
    }
}