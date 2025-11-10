<?php
declare(strict_types=1);

namespace Tests\Integration\Auth;

use PHPUnit\Framework\TestCase;
use PhpQbittorrent\API\AuthAPI;
use PhpQbittorrent\Request\Auth\LoginRequest;
use PhpQbittorrent\Request\Auth\LogoutRequest;
use PhpQbittorrent\Response\Auth\LoginResponse;
use PhpQbittorrent\Response\Auth\LogoutResponse;
use PhpQbittorrent\Exception\NetworkException;
use PhpQbittorrent\Exception\ValidationException;
use Tests\Mock\MockTransport;

/**
 * 认证API集成测试
 */
class AuthAPIIntegrationTest extends TestCase
{
    private AuthAPI $authAPI;
    private MockTransport $mockTransport;

    protected function setUp(): void
    {
        $this->mockTransport = new MockTransport();
        $this->authAPI = new AuthAPI($this->mockTransport);
    }

    /**
     * 测试成功的登录流程
     */
    public function testSuccessfulLogin(): void
    {
        // 设置模拟响应
        $this->mockTransport->setMockResponse(
            200,
            ['Set-Cookie' => 'SID=test_session_12345; path=/'],
            'OK'
        );

        $request = LoginRequest::create('testuser', 'testpass123');
        $response = $this->authAPI->login($request);

        $this->assertInstanceOf(LoginResponse::class, $response);
        $this->assertTrue($response->isSuccess());
        $this->assertTrue($response->isLoggedIn());
        $this->assertEquals('test_session_12345', $response->getSessionId());
        $this->assertEquals('testuser', $response->getUserInfo()['username']);

        // 验证API会话状态已更新
        $sessionInfo = $this->authAPI->getCurrentSessionInfo();
        $this->assertEquals('test_session_12345', $sessionInfo['session_id']);
        $this->assertEquals('testuser', $sessionInfo['username']);
    }

    /**
     * 测试失败的登录流程 - 错误的凭据
     */
    public function testFailedLoginInvalidCredentials(): void
    {
        // 设置模拟响应 - 401状态码
        $this->mockTransport->setMockResponse(401, [], 'Unauthorized');

        $request = LoginRequest::create('wronguser', 'wrongpass');
        $response = $this->authAPI->login($request);

        $this->assertInstanceOf(LoginResponse::class, $response);
        $this->assertFalse($response->isSuccess());
        $this->assertFalse($response->isLoggedIn());
        $this->assertNull($response->getSessionId());
        $this->assertContains('用户名或密码错误', $response->getErrors());
    }

    /**
     * 测试失败的登录流程 - IP被禁止
     */
    public function testFailedLoginIpBanned(): void
    {
        // 设置模拟响应 - 403状态码
        $this->mockTransport->setMockResponse(403, [], 'Forbidden');

        $request = LoginRequest::create('testuser', 'testpass123');
        $response = $this->authAPI->login($request);

        $this->assertInstanceOf(LoginResponse::class, $response);
        $this->assertFalse($response->isSuccess());
        $this->assertContains('用户IP因登录失败次数过多而被禁止访问', $response->getErrors());
    }

    /**
     * 测试成功的登出流程
     */
    public function testSuccessfulLogout(): void
    {
        // 首先登录
        $this->mockTransport->setMockResponse(
            200,
            ['Set-Cookie' => 'SID=test_session_12345; path=/'],
            'OK'
        );
        $loginRequest = LoginRequest::create('testuser', 'testpass123');
        $this->authAPI->login($loginRequest);

        // 然后登出
        $this->mockTransport->setMockResponse(200, [], 'OK');
        $logoutRequest = LogoutRequest::create();
        $response = $this->authAPI->logout($logoutRequest);

        $this->assertInstanceOf(LogoutResponse::class, $response);
        $this->assertTrue($response->isSuccess());
        $this->assertTrue($response->isSessionCleared());

        // 验证本地会话状态已清除
        $sessionInfo = $this->authAPI->getCurrentSessionInfo();
        $this->assertNull($sessionInfo['session_id']);
        $this->assertNull($sessionInfo['username']);
    }

    /**
     * 测试网络错误处理
     */
    public function testNetworkErrorHandling(): void
    {
        // 设置模拟网络错误
        $this->mockTransport->setMockException(new NetworkException('Connection failed'));

        $request = LoginRequest::create('testuser', 'testpass123');

        $this->expectException(\PhpQbittorrent\Exception\ApiRuntimeException::class);
        $this->expectExceptionMessage('Login failed due to network error');

        $this->authAPI->login($request);
    }

    /**
     * 测试验证错误处理
     */
    public function testValidationErrorHandling(): void
    {
        // 创建无效的登录请求
        $this->expectException(ValidationException::class);
        $request = LoginRequest::create('', ''); // 空用户名和密码
    }

    /**
     * 测试检查登录状态
     */
    public function testIsLoggedIn(): void
    {
        // 设置成功的应用版本响应（表示已登录）
        $this->mockTransport->setMockResponse(200, [], 'v4.5.0');

        $this->assertTrue($this->authAPI->isLoggedIn());

        // 设置失败的响应（表示未登录）
        $this->mockTransport->setMockException(new NetworkException('Not authenticated'));

        $this->assertFalse($this->authAPI->isLoggedIn());
    }

    /**
     * 测试会话管理功能
     */
    public function testSessionManagement(): void
    {
        // 登录以建立会话
        $this->mockTransport->setMockResponse(
            200,
            ['Set-Cookie' => 'SID=test_session_12345; path=/'],
            'OK'
        );
        $loginRequest = LoginRequest::create('testuser', 'testpass123');
        $this->authAPI->login($loginRequest);

        // 测试获取会话信息
        $sessionInfo = $this->authAPI->getCurrentSessionInfo();
        $this->assertEquals('test_session_12345', $sessionInfo['session_id']);
        $this->assertEquals('testuser', $sessionInfo['username']);
        $this->assertTrue($sessionInfo['is_logged_in']);
        $this->assertIsInt($sessionInfo['remaining_time']);

        // 测试清除本地会话
        $this->authAPI->clearLocalSession();
        $clearedSessionInfo = $this->authAPI->getCurrentSessionInfo();
        $this->assertNull($clearedSessionInfo['session_id']);
        $this->assertNull($clearedSessionInfo['username']);
    }

    /**
     * 测试Builder模式登录
     */
    public function testLoginWithBuilder(): void
    {
        $this->mockTransport->setMockResponse(
            200,
            ['Set-Cookie' => 'SID=test_session_12345; path=/'],
            'OK'
        );

        $request = LoginRequest::builder()
            ->username('testuser')
            ->password('testpass123')
            ->origin('https://example.com')
            ->build();

        $response = $this->authAPI->login($request);

        $this->assertTrue($response->isSuccess());

        // 验证请求包含正确的headers
        $lastRequest = $this->mockTransport->getLastRequest();
        $this->assertArrayHasKey('Origin', $lastRequest['headers']);
        $this->assertEquals('https://example.com', $lastRequest['headers']['Origin']);
    }

    /**
     * 测试登出所有会话
     */
    public function testLogoutAllSessions(): void
    {
        // 先登录
        $this->mockTransport->setMockResponse(
            200,
            ['Set-Cookie' => 'SID=test_session_12345; path=/'],
            'OK'
        );
        $loginRequest = LoginRequest::create('testuser', 'testpass123');
        $this->authAPI->login($loginRequest);

        // 登出所有会话
        $this->mockTransport->setMockResponse(200, [], 'OK');
        $logoutRequest = LogoutRequest::builder()
            ->clearAllSessions(true)
            ->build();

        $response = $this->authAPI->logout($logoutRequest);

        $this->assertTrue($response->isSuccess());
        $this->assertTrue($response->areAllSessionsCleared());
    }

    /**
     * 测试会话过期时间计算
     */
    public function testSessionExpirationCalculation(): void
    {
        $this->mockTransport->setMockResponse(
            200,
            ['Set-Cookie' => 'SID=test_session_12345; path=/'],
            'OK'
        );

        $loginRequest = LoginRequest::create('testuser', 'testpass123');
        $this->authAPI->login($loginRequest);

        // 测试剩余时间计算
        $remainingTime = $this->authAPI->getRemainingSessionTime();
        $this->assertIsInt($remainingTime);
        $this->assertGreaterThan(0, $remainingTime);

        // 测试过期状态
        $this->assertFalse($this->authAPI->isSessionExpired());
    }

    /**
     * 测试API基础路径
     */
    public function testBasePath(): void
    {
        $this->assertEquals('/api/v2/auth', $this->authAPI->getBasePath());
    }

    /**
     * 测试传输层设置和获取
     */
    public function testTransportManagement(): void
    {
        $newTransport = new MockTransport();
        $this->authAPI->setTransport($newTransport);

        $this->assertSame($newTransport, $this->authAPI->getTransport());
    }
}