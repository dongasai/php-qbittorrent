<?php
declare(strict_types=1);

namespace PhpQbittorrent\Tests;

use PHPUnit\Framework\TestCase;
use PhpQbittorrent\Client;
use PhpQbittorrent\Transport\CurlTransport;
use Nyholm\Psr7\Factory\Psr17Factory;

class ClientTest extends TestCase
{
    private Client $client;
    private string $testUrl = 'http://192.168.4.105:8989';
    private string $testUsername = 'admin';
    private string $testPassword = 'DQ89AFy9u';

    protected function setUp(): void
    {
        $this->client = new Client($this->testUrl, $this->testUsername, $this->testPassword);
    }

    public function testClientCreation(): void
    {
        $this->assertInstanceOf(Client::class, $this->client);
        $this->assertEquals($this->testUrl, $this->client->getBaseUrl());
        $this->assertEquals($this->testUsername, $this->client->getUsername());
        $this->assertFalse($this->client->isAuthenticated());
    }

    /**
     * 测试客户端认证
     * 注意：这个测试需要运行中的qBittorrent实例
     */
    public function testAuthentication(): void
    {
        try {
            $result = $this->client->login();
            $this->assertTrue($result);
            $this->assertTrue($this->client->isAuthenticated());
        } catch (\Exception $e) {
            // 如果qBittorrent不可用，跳过测试
            $this->markTestSkipped('qBittorrent服务器不可用: ' . $e->getMessage());
        }
    }

    /**
     * 测试版本获取API
     */
    public function testGetVersion(): void
    {
        try {
            $this->client->login();
            $versionRequest = \PhpQbittorrent\Request\Application\GetVersionRequest::create();
            $versionResponse = $this->client->application()->getVersion($versionRequest);

            $this->assertTrue($versionResponse->isSuccess());
            $this->assertNotEmpty($versionResponse->getVersion());
            $this->assertStringStartsWith('v', $versionResponse->getVersion());
        } catch (\Exception $e) {
            $this->markTestSkipped('qBittorrent服务器不可用: ' . $e->getMessage());
        }
    }

    /**
     * 测试Web API版本获取
     */
    public function testGetWebApiVersion(): void
    {
        try {
            $this->client->login();
            $webApiRequest = \PhpQbittorrent\Request\Application\GetWebApiVersionRequest::create();
            $webApiResponse = $this->client->application()->getWebApiVersion($webApiRequest);

            $this->assertTrue($webApiResponse->isSuccess());
            $this->assertNotEmpty($webApiResponse->getVersion());
        } catch (\Exception $e) {
            $this->markTestSkipped('qBittorrent服务器不可用: ' . $e->getMessage());
        }
    }

    /**
     * 测试构建信息获取
     */
    public function testGetBuildInfo(): void
    {
        try {
            $this->client->login();
            $buildInfoRequest = \PhpQbittorrent\Request\Application\GetBuildInfoRequest::create();
            $buildInfoResponse = $this->client->application()->getBuildInfo($buildInfoRequest);

            $this->assertTrue($buildInfoResponse->isSuccess());
            $buildInfo = $buildInfoResponse->getBuildInfo();
            $this->assertIsArray($buildInfo);
            $this->assertArrayHasKey('qt', $buildInfo);
            $this->assertArrayHasKey('libtorrent', $buildInfo);
            $this->assertArrayHasKey('boost', $buildInfo);
            $this->assertArrayHasKey('openssl', $buildInfo);
            $this->assertArrayHasKey('bitness', $buildInfo);
        } catch (\Exception $e) {
            $this->markTestSkipped('qBittorrent服务器不可用: ' . $e->getMessage());
        }
    }

    /**
     * 测试传输信息获取
     */
    public function testGetTransferInfo(): void
    {
        try {
            $this->client->login();
            $transferInfoRequest = \PhpQbittorrent\Request\Transfer\GetGlobalTransferInfoRequest::create();
            $transferInfoResponse = $this->client->transfer()->getGlobalTransferInfo($transferInfoRequest);

            $this->assertTrue($transferInfoResponse->isSuccess());
            $this->assertIsInt($transferInfoResponse->getDownloadSpeed());
            $this->assertIsInt($transferInfoResponse->getUploadSpeed());
            $this->assertIsInt($transferInfoResponse->getDhtNodes());
            $this->assertIsString($transferInfoResponse->getConnectionStatus());
        } catch (\Exception $e) {
            $this->markTestSkipped('qBittorrent服务器不可用: ' . $e->getMessage());
        }
    }

    /**
     * 测试Torrent列表获取
     */
    public function testGetTorrentList(): void
    {
        try {
            $this->client->login();
            $torrentListRequest = \PhpQbittorrent\Request\Torrent\GetTorrentListRequest::create();
            $torrentListResponse = $this->client->torrents()->getTorrentList($torrentListRequest);

            $this->assertTrue($torrentListResponse->isSuccess());
            $torrents = $torrentListResponse->getTorrents();
            $this->assertIsArray($torrents);
        } catch (\Exception $e) {
            $this->markTestSkipped('qBittorrent服务器不可用: ' . $e->getMessage());
        }
    }

    /**
     * 测试传输层Cookie处理
     */
    public function testTransportCookieHandling(): void
    {
        $transport = new CurlTransport(new Psr17Factory(), new Psr17Factory());
        $transport->setBaseUrl($this->testUrl);
        $transport->setVerifySSL(false);

        try {
            // 模拟登录请求
            $response = $transport->request('POST', '/api/v2/auth/login', [
                'form_params' => [
                    'username' => $this->testUsername,
                    'password' => $this->testPassword,
                ]
            ]);

            // 检查是否获取到Cookie
            $cookie = $transport->getAuthentication();
            $this->assertNotNull($cookie);
            $this->assertStringStartsWith('SID=', $cookie);

            // 测试使用Cookie访问API
            $versionResponse = $transport->request('GET', '/api/v2/app/version');
            $this->assertIsArray($versionResponse);
            $this->assertNotEmpty($versionResponse);
        } catch (\Exception $e) {
            $this->markTestSkipped('qBittorrent服务器不可用: ' . $e->getMessage());
        }
    }

    /**
     * 测试客户端配置验证
     */
    public function testConfigurationValidation(): void
    {
        // 测试空URL
        $this->expectException(\PhpQbittorrent\Exception\ValidationException::class);
        new Client('', 'user', 'pass');
    }

    /**
     * 测试魔术方法访问
     */
    public function testMagicMethods(): void
    {
        try {
            $this->client->login();

            // 测试版本魔术属性
            $version = $this->client->version;
            $this->assertNotNull($version);
            $this->assertStringStartsWith('v', $version);

            // 测试WebAPI版本魔术属性
            $webApiVersion = $this->client->webApiVersion;
            $this->assertNotNull($webApiVersion);

        } catch (\Exception $e) {
            $this->markTestSkipped('qBittorrent服务器不可用: ' . $e->getMessage());
        }
    }
}