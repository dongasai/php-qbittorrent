<?php
declare(strict_types=1);

namespace PhpQbittorrent\Tests\Unit;

use PhpQbittorrent\Tests\TestCase;
use PhpQbittorrent\Client;
use PhpQbittorrent\Config\ClientConfig;
use PhpQbittorrent\Transport\CurlTransport;
use PhpQbittorrent\Exception\{
    ClientException,
    AuthenticationException,
    ValidationException
};

/**
 * Client单元测试
 */
class ClientTest extends TestCase
{
    private ClientConfig $config;
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->config = new ClientConfig('http://localhost:8080', 'admin', 'adminpass');
        $this->client = new Client($this->config);
    }

    protected function tearDown(): void
    {
        if (isset($this->client)) {
            $this->client->close();
        }
        parent::tearDown();
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(Client::class, $this->client);
        $this->assertSame($this->config, $this->client->getConfig());
        $this->assertFalse($this->client->isLoggedIn());
    }

    public function testConstructorWithInvalidConfig(): void
    {
        $this->expectException(ValidationException::class);
        $invalidConfig = new ClientConfig(''); // 空URL应该验证失败
        new Client($invalidConfig);
    }

    public function testGetConfig(): void
    {
        $config = $this->client->getConfig();
        $this->assertSame($this->config, $config);
        $this->assertEquals('http://localhost:8080', $config->getUrl());
        $this->assertEquals('admin', $config->getUsername());
        $this->assertEquals('adminpass', $config->getPassword());
    }

    public function testGetTransport(): void
    {
        $transport = $this->client->getTransport();
        $this->assertInstanceOf(CurlTransport::class, $transport);
    }

    public function testSetTransport(): void
    {
        $newTransport = new CurlTransport();
        $result = $this->client->setTransport($newTransport);

        $this->assertSame($this->client, $result);
        $this->assertSame($newTransport, $this->client->getTransport());
    }

    public function testUpdateConfig(): void
    {
        $newConfig = new ClientConfig('https://test.example.com:9090', 'newuser', 'newpass');
        $result = $this->client->updateConfig($newConfig);

        $this->assertSame($this->client, $result);
        $this->assertEquals('https://test.example.com:9090', $this->client->getConfig()->getUrl());
        $this->assertFalse($this->client->isLoggedIn()); // 配置更新应该清除登录状态
    }

    public function testUpdateConfigWithInvalidConfig(): void
    {
        $this->expectException(ValidationException::class);
        $invalidConfig = new ClientConfig(''); // 无效配置
        $this->client->updateConfig($invalidConfig);
    }

    public function testCreate(): void
    {
        $client = Client::create('http://localhost:8080', 'admin', 'adminpass');
        $this->assertInstanceOf(Client::class, $client);

        $config = $client->getConfig();
        $this->assertEquals('http://localhost:8080', $config->getUrl());
        $this->assertEquals('admin', $config->getUsername());
        $this->assertEquals('adminpass', $config->getPassword());
    }

    public function testFromArray(): void
    {
        $configArray = [
            'url' => 'https://test.example.com',
            'username' => 'testuser',
            'password' => 'testpass',
            'timeout' => 45.5,
            'verify_ssl' => false
        ];

        $client = Client::fromArray($configArray);
        $this->assertInstanceOf(Client::class, $client);

        $config = $client->getConfig();
        $this->assertEquals('https://test.example.com', $config->getUrl());
        $this->assertEquals('testuser', $config->getUsername());
        $this->assertEquals('testpass', $config->getPassword());
        $this->assertEquals(45.5, $config->getTimeout());
        $this->assertFalse($config->isVerifySSL());
    }

    public function testFromArrayWithMinimalConfig(): void
    {
        $configArray = ['url' => 'http://localhost:8080'];
        $client = Client::fromArray($configArray);

        $this->assertInstanceOf(Client::class, $client);
        $this->assertEquals('http://localhost:8080', $client->getConfig()->getUrl());
    }

    public function testGetAuthAPI(): void
    {
        $authAPI = $this->client->getAuthAPI();
        $this->assertInstanceOf(\PhpQbittorrent\API\AuthAPI::class, $authAPI);

        // 测试单例模式
        $authAPI2 = $this->client->getAuthAPI();
        $this->assertSame($authAPI, $authAPI2);
    }

    public function testGetTorrentAPIWithoutLogin(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('需要进行认证登录');
        $this->client->getTorrentAPI();
    }

    public function testGetApplicationAPIWithoutLogin(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('需要进行认证登录');
        $this->client->getApplicationAPI();
    }

    public function testGetTransferAPIWithoutLogin(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('需要进行认证登录');
        $this->client->getTransferAPI();
    }

    public function testGetRSSAPIWithoutLogin(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('需要进行认证登录');
        $this->client->getRSSAPI();
    }

    public function testGetSearchAPIWithoutLogin(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('需要进行认证登录');
        $this->client->getSearchAPI();
    }

    public function testMagicGetAuth(): void
    {
        $authAPI = $this->client->auth;
        $this->assertInstanceOf(\PhpQbittorrent\API\AuthAPI::class, $authAPI);
    }

    public function testMagicGetInvalidAPI(): void
    {
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('未知的API: invalid');
        $this->client->invalid;
    }

    public function testMockLoginSuccess(): void
    {
        // 创建模拟传输层
        $mockTransport = $this->createMock(\PhpQbittorrent\Transport\TransportInterface::class);

        // 模拟成功的登录响应
        $mockTransport->expects($this->once())
            ->method('request')
            ->with('POST', '/api/v2/auth/login', $this->anything())
            ->willReturn([]);

        $mockTransport->expects($this->once())
            ->method('getLastResponseCode')
            ->willReturn(200);

        $client = new Client($this->config, $mockTransport);
        $client->login();

        $this->assertTrue($client->isLoggedIn());
    }

    public function testMockLoginFailure(): void
    {
        $mockTransport = $this->createMock(\PhpQbittorrent\Transport\TransportInterface::class);

        // 模拟失败的登录响应
        $mockTransport->expects($this->once())
            ->method('request')
            ->with('POST', '/api/v2/auth/login', $this->anything())
            ->willReturn(['error' => 'Invalid credentials']);

        $mockTransport->expects($this->once())
            ->method('getLastResponseCode')
            ->willReturn(401);

        $client = new Client($this->config, $mockTransport);

        $this->expectException(AuthenticationException::class);
        $client->login();
    }

    public function testMockLogout(): void
    {
        $mockTransport = $this->createMock(\PhpQbittorrent\Transport\TransportInterface::class);

        // 模拟成功登出
        $mockTransport->expects($this->once())
            ->method('request')
            ->with('POST', '/api/v2/auth/logout')
            ->willReturn([]);

        $client = new Client($this->config, $mockTransport);

        // 手动设置登录状态以测试登出
        $reflection = new \ReflectionClass($client);
        $isLoggedInProperty = $reflection->getProperty('isLoggedIn');
        $isLoggedInProperty->setAccessible(true);
        $isLoggedInProperty->setValue($client, true);

        $client->logout();
        $this->assertFalse($client->isLoggedIn());
    }

    public function testClose(): void
    {
        $mockTransport = $this->createMock(\PhpQbittorrent\Transport\TransportInterface::class);

        $mockTransport->expects($this->once())
            ->method('close');

        $client = new Client($this->config, $mockTransport);
        $client->close();
    }

    public function testDestructor(): void
    {
        $mockTransport = $this->createMock(\PhpQbittorrent\Transport\TransportInterface::class);

        $mockTransport->expects($this->once())
            ->method('close');

        $client = new Client($this->config, $mockTransport);
        unset($client); // 触发析构函数
    }

    public function testAPIInstanceResetAfterTransportChange(): void
    {
        $client = new Client($this->config);

        $authAPI1 = $client->getAuthAPI();
        $newTransport = new CurlTransport();

        // 更换传输层
        $client->setTransport($newTransport);

        // API实例应该被重置
        $authAPI2 = $client->getAuthAPI();
        $this->assertNotSame($authAPI1, $authAPI2);
    }

    public function testAPIInstanceResetAfterConfigChange(): void
    {
        $client = new Client($this->config);

        $authAPI1 = $client->getAuthAPI();
        $newConfig = new ClientConfig('https://test.example.com');

        // 更新配置
        $client->updateConfig($newConfig);

        // API实例应该被重置
        $authAPI2 = $client->getAuthAPI();
        $this->assertNotSame($authAPI1, $authAPI2);
    }

    public function testChainedMethodCalls(): void
    {
        $newTransport = new CurlTransport();
        $newConfig = new ClientConfig('https://test.example.com');

        $client = Client::create('http://localhost:8080')
            ->setTransport($newTransport)
            ->updateConfig($newConfig);

        $this->assertInstanceOf(Client::class, $client);
        $this->assertEquals('https://test.example.com', $client->getConfig()->getUrl());
        $this->assertSame($newTransport, $client->getTransport());
    }
}