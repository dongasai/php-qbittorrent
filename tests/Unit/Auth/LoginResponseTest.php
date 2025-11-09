<?php
declare(strict_types=1);

namespace Tests\Unit\Auth;

use PHPUnit\Framework\TestCase;
use Dongasai\qBittorrent\Response\Auth\LoginResponse;

/**
 * 登录响应单元测试
 */
class LoginResponseTest extends TestCase
{
    /**
     * 测试创建成功的登录响应
     */
    public function testCreateSuccessResponse(): void
    {
        $sessionId = 'test_session_12345';
        $userInfo = [
            'username' => 'testuser',
            'login_time' => time(),
            'login_method' => 'password'
        ];

        $response = LoginResponse::success(
            $sessionId,
            ['Set-Cookie' => "SID={$sessionId}; path=/"],
            200,
            'OK',
            $userInfo
        );

        $this->assertTrue($response->isSuccess());
        $this->assertTrue($response->isLoggedIn());
        $this->assertEquals($sessionId, $response->getSessionId());
        $this->assertEquals($userInfo, $response->getUserInfo());
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * 测试创建失败的登录响应
     */
    public function testCreateFailureResponse(): void
    {
        $errors = ['用户名或密码错误'];
        $response = LoginResponse::failure($errors, [], 401, 'Unauthorized');

        $this->assertFalse($response->isSuccess());
        $this->assertFalse($response->isLoggedIn());
        $this->assertNull($response->getSessionId());
        $this->assertEquals($errors, $response->getErrors());
        $this->assertEquals(401, $response->getStatusCode());
    }

    /**
     * 测试从数组创建响应
     */
    public function testCreateFromArray(): void
    {
        $data = [
            'success' => true,
            'sessionId' => 'test_session_12345',
            'userInfo' => ['username' => 'testuser'],
            'statusCode' => 200,
            'headers' => [],
            'rawResponse' => 'OK'
        ];

        $response = LoginResponse::fromArray($data);

        $this->assertTrue($response->isSuccess());
        $this->assertTrue($response->isLoggedIn());
        $this->assertEquals('test_session_12345', $response->getSessionId());
        $this->assertEquals(['username' => 'testuser'], $response->getUserInfo());
    }

    /**
     * 测试会话过期时间功能
     */
    public function testSessionExpiration(): void
    {
        $futureTime = time() + 3600; // 1小时后
        $pastTime = time() - 3600;   // 1小时前

        // 未过期的会话
        $response = LoginResponse::success('session123');
        $response->setAdditionalData('sessionExpiresAt', $futureTime);

        $this->assertFalse($response->isSessionExpired());
        $this->assertEqualsWithDelta(3600, $response->getRemainingSessionTime(), 10);

        // 已过期的会话
        $expiredResponse = LoginResponse::success('session456');
        $expiredResponse->setAdditionalData('sessionExpiresAt', $pastTime);

        $this->assertTrue($expiredResponse->isSessionExpired());
        $this->assertEquals(0, $expiredResponse->getRemainingSessionTime());
    }

    /**
     * 测试首次登录标志
     */
    public function testFirstLogin(): void
    {
        // 首次登录
        $response = LoginResponse::success('session123');
        $response->setAdditionalData('isFirstLogin', true);

        $this->assertTrue($response->isFirstLogin());

        // 非首次登录
        $regularResponse = LoginResponse::success('session456');
        $response->setAdditionalData('isFirstLogin', false);

        $this->assertFalse($regularResponse->isFirstLogin());
    }

    /**
     * 测试获取认证Cookie
     */
    public function testGetAuthCookie(): void
    {
        $sessionId = 'test_session_12345';
        $response = LoginResponse::success($sessionId);

        $this->assertEquals("SID={$sessionId}", $response->getAuthCookie());

        // 空会话ID的情况
        $emptyResponse = LoginResponse::failure(['No session']);
        $this->assertEquals('', $emptyResponse->getAuthCookie());
    }

    /**
     * 测试获取认证头
     */
    public function testGetAuthHeaders(): void
    {
        $sessionId = 'test_session_12345';
        $response = LoginResponse::success($sessionId);

        $headers = $response->getAuthHeaders();
        $this->assertArrayHasKey('Cookie', $headers);
        $this->assertEquals("SID={$sessionId}", $headers['Cookie']);

        // 空会话ID的情况
        $emptyResponse = LoginResponse::failure(['No session']);
        $emptyHeaders = $emptyResponse->getAuthHeaders();
        $this->assertArrayNotHasKey('Cookie', $emptyHeaders);
    }

    /**
     * 测试额外数据功能
     */
    public function testAdditionalData(): void
    {
        $response = LoginResponse::success('session123');

        // 设置额外数据
        $response->setAdditionalData('login_ip', '192.168.1.1');
        $response->setAdditionalData('device_info', ['os' => 'Windows', 'browser' => 'Chrome']);

        // 获取所有额外数据
        $allData = $response->getAdditionalData();
        $this->assertArrayHasKey('login_ip', $allData);
        $this->assertArrayHasKey('device_info', $allData);

        // 获取特定数据
        $this->assertEquals('192.168.1.1', $response->getAdditionalData('login_ip'));
        $this->assertEquals(['os' => 'Windows', 'browser' => 'Chrome'], $response->getAdditionalData('device_info'));
        $this->assertNull($response->getAdditionalData('non_existent'));
    }

    /**
     * 测试toArray方法
     */
    public function testToArray(): void
    {
        $sessionId = 'test_session_12345';
        $userInfo = ['username' => 'testuser'];
        $response = LoginResponse::success($sessionId, [], 200, 'OK', $userInfo);

        $array = $response->toArray();

        $this->assertIsArray($array);
        $this->assertTrue($array['success']);
        $this->assertTrue($array['isLoggedIn']);
        $this->assertEquals($sessionId, $array['sessionId']);
        $this->assertEquals($userInfo, $array['userInfo']);
    }

    /**
     * 测试getSummary方法
     */
    public function testGetSummary(): void
    {
        $sessionId = 'test_session_12345';
        $response = LoginResponse::success($sessionId);

        $summary = $response->getSummary();

        $this->assertIsArray($summary);
        $this->assertTrue($summary['success']);
        $this->assertTrue($summary['logged_in']);
        $this->assertEquals('test_sess***', $summary['session_id']); // 应该被截断
        $this->assertEquals(200, $summary['status_code']);
        $this->assertEquals(0, $summary['error_count']);
    }

    /**
     * 测试响应验证
     */
    public function testResponseValidation(): void
    {
        // 正常的响应应该验证通过
        $response = LoginResponse::success('valid_session_123');
        $validation = $response->validate();
        $this->assertTrue($validation->isValid());

        // 包含特殊字符的会话ID应该产生警告
        $warningResponse = LoginResponse::success('session@#$%^&*()');
        $validation = $warningResponse->validate();
        $this->assertTrue($validation->isValid());
        $this->assertNotEmpty($validation->getWarnings());

        // 过长的过期时间应该产生警告
        $longTimeResponse = LoginResponse::success('session123');
        $longTimeResponse->setAdditionalData('sessionExpiresAt', time() + 86400 * 35); // 35天
        $validation = $longTimeResponse->validate();
        $this->assertTrue($validation->isValid());
        $this->assertNotEmpty($validation->getWarnings());
    }

    /**
     * 测试403状态码的错误处理
     */
    public function test403StatusCodeHandling(): void
    {
        $response = LoginResponse::failure([], [], 403);
        $errors = $response->getErrors();

        $this->assertContains('用户IP因登录失败次数过多而被禁止访问', $errors);
    }

    /**
     * 测试401状态码的错误处理
     */
    public function test401StatusCodeHandling(): void
    {
        $response = LoginResponse::failure([], [], 401);
        $errors = $response->getErrors();

        $this->assertContains('用户名或密码错误', $errors);
    }
}