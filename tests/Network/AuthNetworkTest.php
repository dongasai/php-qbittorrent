<?php
declare(strict_types=1);

namespace PhpQbittorrent\Tests\Network;

use PhpQbittorrent\Tests\TestCase;
use PhpQbittorrent\Client;
use PhpQbittorrent\Config\ClientConfig;
use PhpQbittorrent\API\AuthAPI;
use PhpQbittorrent\Request\Auth\LoginRequest;
use PhpQbittorrent\Request\Auth\LogoutRequest;
use PhpQbittorrent\Response\Auth\LoginResponse;
use PhpQbittorrent\Response\Auth\LogoutResponse;
use PhpQbittorrent\Exception\{
    NetworkException,
    AuthenticationException,
    ValidationException,
    ApiRuntimeException
};

/**
 * 认证API网络请求测试
 *
 * 测试真实的登录、登出和认证状态管理功能
 */
class AuthNetworkTest extends TestCase
{
    private Client $client;
    private ClientConfig $config;
    private AuthAPI $authAPI;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * 初始化网络客户端（仅在需要时调用）
     */
    protected function initializeNetworkClient(): void
    {
        if (isset($this->client)) {
            return;
        }
        
        // 从环境变量获取测试配置
        $qbUrl = $_ENV['QBITTORRENT_URL'] ?? 'http://localhost:8080';
        $qbUsername = $_ENV['QBITTORRENT_USERNAME'] ?? 'admin';
        $qbPassword = $_ENV['QBITTORRENT_PASSWORD'] ?? 'adminpass';
        
        $this->config = new ClientConfig($qbUrl, $qbUsername, $qbPassword);
        $this->config->setTimeout(10.0);
        $this->config->setVerifySSL(false);
        
        $this->client = new Client(
            $this->config->getUrl(),
            $this->config->getUsername(),
            $this->config->getPassword()
        );
        $this->authAPI = new AuthAPI($this->client->getTransport());
    }

    protected function tearDown(): void
    {
        if (isset($this->client)) {
            try {
                $this->client->logout();
            } catch (\Exception $e) {
                // 忽略登出错误
            }
            $this->client->close();
        }
        parent::tearDown();
    }

    /**
     * 标记为需要真实网络环境的测试
     */
    protected function markTestNeedsNetwork(): void
    {
        if (!getenv('RUN_NETWORK_TESTS')) {
            $this->markTestSkipped('跳过网络测试 - 未设置RUN_NETWORK_TESTS环境变量');
        }
        
        $this->initializeNetworkClient();
    }

    /**
     * 执行带重试的网络请求
     */
    protected function executeWithRetry(callable $operation, int $maxRetries = 3): mixed
    {
        $lastException = null;
        
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                return $operation();
            } catch (NetworkException $e) {
                $lastException = $e;
                if ($attempt < $maxRetries) {
                    sleep(1);
                }
            }
        }
        
        throw $lastException;
    }

    /**
     * 测试基本登录功能
     */
    public function testBasicLogin(): void
    {
        $this->markTestNeedsNetwork();

        $this->executeWithRetry(function () {
            // 测试连接
            $isConnected = $this->client->testConnection();
            $this->assertTrue($isConnected, '无法连接到qBittorrent服务器');

            // 创建登录请求
            $loginRequest = LoginRequest::create(
                $this->config->getUsername(),
                $this->config->getPassword()
            );

            // 执行登录
            $loginResponse = $this->authAPI->login($loginRequest);

            // 验证登录响应
            $this->assertInstanceOf(LoginResponse::class, $loginResponse);
            $this->assertTrue($loginResponse->isSuccess(), '登录应该成功');
            $this->assertNotEmpty($loginResponse->getSessionId(), '应该返回会话ID');

            // 验证会话信息
            $sessionInfo = $this->authAPI->getCurrentSessionInfo();
            $this->assertTrue($sessionInfo['is_logged_in'], '应该处于登录状态');
            $this->assertEquals($this->config->getUsername(), $sessionInfo['username']);
            $this->assertNotNull($sessionInfo['session_id']);

            // 验证客户端登录状态
            $this->client->login();
            $this->assertTrue($this->client->isLoggedIn(), '客户端应该处于登录状态');
        });
    }

    /**
     * 测试登录失败场景
     */
    public function testLoginFailure(): void
    {
        $this->markTestNeedsNetwork();

        $this->executeWithRetry(function () {
            // 测试错误的用户名密码
            $wrongLoginRequest = LoginRequest::create('wronguser', 'wrongpass');
            $loginResponse = $this->authAPI->login($wrongLoginRequest);

            $this->assertInstanceOf(LoginResponse::class, $loginResponse);
            $this->assertFalse($loginResponse->isSuccess(), '错误的用户名密码应该登录失败');
            $this->assertNotEmpty($loginResponse->getErrors(), '应该返回错误信息');
            $this->assertEquals(401, $loginResponse->getStatusCode(), '应该返回401状态码');
        });
    }

    /**
     * 测试登录请求验证
     */
    public function testLoginRequestValidation(): void
    {
        // 测试空用户名
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageMatches('/用户名不能为空/');
        LoginRequest::create('', 'password');
    }

    /**
     * 测试Builder模式登录
     */
    public function testLoginWithBuilder(): void
    {
        $this->markTestNeedsNetwork();

        $this->executeWithRetry(function () {
            // 使用Builder模式创建登录请求
            $loginRequest = LoginRequest::create(
                $this->config->getUsername(),
                $this->config->getPassword()
            );
            $loginRequest->setOrigin('http://localhost:8080');

            // 验证请求配置
            $this->assertEquals($this->config->getUsername(), $loginRequest->getUsername());
            $this->assertEquals($this->config->getPassword(), $loginRequest->getPassword());
            $this->assertEquals('http://localhost:8080', $loginRequest->getOrigin());

            // 执行登录
            $loginResponse = $this->authAPI->login($loginRequest);
            $this->assertTrue($loginResponse->isSuccess(), 'Builder模式登录应该成功');

            // 验证请求头
            $headers = $loginRequest->getHeaders();
            $this->assertArrayHasKey('Origin', $headers);
            $this->assertArrayHasKey('Referer', $headers);
        });
    }

    /**
     * 测试登出功能
     */
    public function testLogout(): void
    {
        $this->markTestNeedsNetwork();

        $this->executeWithRetry(function () {
            // 先登录
            $this->client->login();
            $this->assertTrue($this->client->isLoggedIn(), '登录应该成功');

            // 创建登出请求
            $logoutRequest = LogoutRequest::create();

            // 执行登出
            $logoutResponse = $this->authAPI->logout($logoutRequest);

            // 验证登出响应
            $this->assertInstanceOf(LogoutResponse::class, $logoutResponse);
            $this->assertTrue($logoutResponse->isSuccess(), '登出应该成功');
            $this->assertTrue($logoutResponse->isSessionCleared(), '会话应该被清除');

            // 验证会话状态
            $sessionInfo = $this->authAPI->getCurrentSessionInfo();
            $this->assertFalse($sessionInfo['is_logged_in'], '应该处于登出状态');
            $this->assertNull($sessionInfo['session_id'], '会话ID应该被清除');
        });
    }

    /**
     * 测试认证状态检查
     */
    public function testAuthenticationStatus(): void
    {
        $this->markTestNeedsNetwork();

        $this->executeWithRetry(function () {
            // 未登录状态检查
            $this->assertFalse($this->authAPI->isLoggedIn(), '初始状态应该未登录');

            // 登录后状态检查
            $this->client->login();
            $this->assertTrue($this->authAPI->isLoggedIn(), '登录后应该显示已登录');

            // 登出后状态检查
            $this->client->logout();
            $this->assertFalse($this->authAPI->isLoggedIn(), '登出后应该显示未登录');
        });
    }

    /**
     * 测试会话信息管理
     */
    public function testSessionInfoManagement(): void
    {
        $this->markTestNeedsNetwork();

        $this->executeWithRetry(function () {
            // 登录
            $this->client->login();

            // 获取会话信息
            $sessionInfo = $this->authAPI->getCurrentSessionInfo();
            
            $this->assertIsArray($sessionInfo);
            $this->assertArrayHasKey('session_id', $sessionInfo);
            $this->assertArrayHasKey('username', $sessionInfo);
            $this->assertArrayHasKey('expires_at', $sessionInfo);
            $this->assertArrayHasKey('is_logged_in', $sessionInfo);
            $this->assertArrayHasKey('remaining_time', $sessionInfo);

            // 验证会话时间
            $this->assertNotNull($sessionInfo['expires_at'], '应该设置过期时间');
            $this->assertIsInt($sessionInfo['remaining_time'], '剩余时间应该是整数');
            $this->assertGreaterThanOrEqual(0, $sessionInfo['remaining_time'], '剩余时间应该非负');

            // 测试会话过期检查
            $this->assertFalse($this->authAPI->isSessionExpired(), '新会话不应该过期');

            // 清除本地会话
            $this->authAPI->clearLocalSession();
            $clearedSessionInfo = $this->authAPI->getCurrentSessionInfo();
            $this->assertNull($clearedSessionInfo['session_id'], '清除后会话ID应该为空');
            $this->assertNull($clearedSessionInfo['username'], '清除后用户名应该为空');
        });
    }

    /**
     * 测试网络错误处理
     */
    public function testNetworkErrorHandling(): void
    {
        $this->markTestNeedsNetwork();

        // 创建无效的客户端配置
        $invalidConfig = new ClientConfig('http://localhost:9999', 'admin', 'adminpass');
        $invalidClient = new Client(
            $invalidConfig->getUrl(),
            $invalidConfig->getUsername(),
            $invalidConfig->getPassword()
        );
        $invalidAuthAPI = new AuthAPI($invalidClient->getTransport());

        $loginRequest = LoginRequest::create('admin', 'adminpass');

        // 应该抛出网络异常
        $this->expectException(ApiRuntimeException::class);
        $this->expectExceptionMessageMatches('/Login failed due to network error/');
        
        $invalidAuthAPI->login($loginRequest);
    }

    /**
     * 测试并发登录
     */
    public function testConcurrentLogin(): void
    {
        $this->markTestNeedsNetwork();

        $this->executeWithRetry(function () {
            // 创建多个客户端实例
            $clients = [];
            $authAPIs = [];
            
            for ($i = 0; $i < 3; $i++) {
                $client = new Client(
                    $this->config->getUrl(),
                    $this->config->getUsername(),
                    $this->config->getPassword()
                );
                $authAPI = new AuthAPI($client->getTransport());
                $clients[] = $client;
                $authAPIs[] = $authAPI;
            }

            // 并发登录
            $loginRequests = [];
            foreach ($authAPIs as $authAPI) {
                $loginRequest = LoginRequest::create(
                    $this->config->getUsername(),
                    $this->config->getPassword()
                );
                $loginRequests[] = $authAPI->login($loginRequest);
            }

            // 验证所有登录都成功
            foreach ($loginRequests as $response) {
                $this->assertTrue($response->isSuccess(), '并发登录应该都成功');
            }

            // 清理
            foreach ($clients as $client) {
                try {
                    $client->logout();
                } catch (\Exception $e) {
                    // 忽略清理错误
                }
                $client->close();
            }
        });
    }

    /**
     * 测试登录性能
     */
    public function testLoginPerformance(): void
    {
        $this->markTestNeedsNetwork();

        $this->executeWithRetry(function () {
            $startTime = microtime(true);

            // 执行多次登录登出
            for ($i = 0; $i < 5; $i++) {
                $this->client->login();
                $this->assertTrue($this->client->isLoggedIn());
                $this->client->logout();
                $this->assertFalse($this->client->isLoggedIn());
            }

            $endTime = microtime(true);
            $duration = $endTime - $startTime;

            // 5次登录登出应该在10秒内完成
            $this->assertLessThan(10.0, $duration, '5次登录登出应该在10秒内完成');
            
            $averageTime = $duration / 10; // 5次登录 + 5次登出
            echo "\n平均登录/登出时间: " . round($averageTime * 1000, 2) . "ms\n";
        });
    }

    /**
     * 测试登录请求摘要信息
     */
    public function testLoginRequestSummary(): void
    {
        $this->markTestNeedsNetwork();

        $loginRequest = LoginRequest::create(
            $this->config->getUsername(),
            $this->config->getPassword()
        );
        $loginRequest->setOrigin('https://test.example.com');

        $summary = $loginRequest->getSummary();

        $this->assertIsArray($summary);
        $this->assertArrayHasKey('username', $summary);
        $this->assertArrayHasKey('password_length', $summary);
        $this->assertArrayHasKey('origin', $summary);
        $this->assertArrayHasKey('endpoint', $summary);
        $this->assertArrayHasKey('method', $summary);
        $this->assertArrayHasKey('requires_auth', $summary);

        $this->assertEquals($this->config->getUsername(), $summary['username']);
        $this->assertEquals(strlen($this->config->getPassword()), $summary['password_length']);
        $this->assertEquals('https://test.example.com', $summary['origin']);
        $this->assertEquals('/auth/login', $summary['endpoint']);
        $this->assertEquals('POST', $summary['method']);
        $this->assertFalse($summary['requires_auth']);
    }
}