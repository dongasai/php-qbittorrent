<?php
declare(strict_types=1);

namespace Tests\Unit\Auth;

use PHPUnit\Framework\TestCase;
use PhpQbittorrent\Request\Auth\LoginRequest;
use PhpQbittorrent\Exception\ValidationException;

/**
 * 登录请求单元测试
 */
class LoginRequestTest extends TestCase
{
    /**
     * 测试使用Builder模式创建有效的登录请求
     */
    public function testCreateValidLoginRequestWithBuilder(): void
    {
        $request = LoginRequest::builder()
            ->username('testuser')
            ->password('testpass123')
            ->build();

        $this->assertEquals('testuser', $request->getUsername());
        $this->assertEquals('testpass123', $request->getPassword());
        $this->assertEquals('/auth/login', $request->getEndpoint());
        $this->assertEquals('POST', $request->getMethod());
        $this->assertFalse($request->requiresAuthentication());
    }

    /**
     * 测试直接创建有效的登录请求
     */
    public function testCreateValidLoginRequestDirectly(): void
    {
        $request = LoginRequest::create('testuser', 'testpass123');

        $this->assertEquals('testuser', $request->getUsername());
        $this->assertEquals('testpass123', $request->getPassword());
    }

    /**
     * 测试设置来源URL
     */
    public function testSetOrigin(): void
    {
        $request = LoginRequest::builder()
            ->username('testuser')
            ->password('testpass123')
            ->origin('https://example.com')
            ->build();

        $this->assertEquals('https://example.com', $request->getOrigin());

        $headers = $request->getHeaders();
        $this->assertArrayHasKey('Origin', $headers);
        $this->assertEquals('https://example.com', $headers['Origin']);
        $this->assertArrayHasKey('Referer', $headers);
        $this->assertEquals('https://example.com', $headers['Referer']);
    }

    /**
     * 测试空用户名验证失败
     */
    public function testEmptyUsernameValidation(): void
    {
        $this->expectException(ValidationException::class);
        LoginRequest::create('', 'password');
    }

    /**
     * 测试空密码验证失败
     */
    public function testEmptyPasswordValidation(): void
    {
        $this->expectException(ValidationException::class);
        LoginRequest::create('username', '');
    }

    /**
     * 测试用户名过长验证失败
     */
    public function testUsernameTooLongValidation(): void
    {
        $this->expectException(ValidationException::class);
        $longUsername = str_repeat('a', 256);
        LoginRequest::create($longUsername, 'password');
    }

    /**
     * 测试密码过长验证失败
     */
    public function testPasswordTooLongValidation(): void
    {
        $this->expectException(ValidationException::class);
        $longPassword = str_repeat('a', 256);
        LoginRequest::create('username', $longPassword);
    }

    /**
     * 测试Builder模式缺少用户名
     */
    public function testBuilderMissingUsername(): void
    {
        $this->expectException(ValidationException::class);
        LoginRequest::builder()
            ->password('testpass123')
            ->build();
    }

    /**
     * 测试Builder模式缺少密码
     */
    public function testBuilderMissingPassword(): void
    {
        $this->expectException(ValidationException::class);
        LoginRequest::builder()
            ->username('testuser')
            ->build();
    }

    /**
     * 测试无效的来源URL
     */
    public function testInvalidOriginUrl(): void
    {
        $this->expectException(ValidationException::class);
        LoginRequest::builder()
            ->username('testuser')
            ->password('testpass123')
            ->origin('invalid-url')
            ->build();
    }

    /**
     * 测试toArray方法
     */
    public function testToArray(): void
    {
        $request = LoginRequest::create('testuser', 'testpass123');
        $array = $request->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('testuser', $array['username']);
        $this->assertEquals('testpass123', $array['password']);
    }

    /**
     * 测试getSummary方法
     */
    public function testGetSummary(): void
    {
        $request = LoginRequest::create('testuser', 'testpass123');
        $summary = $request->getSummary();

        $this->assertIsArray($summary);
        $this->assertEquals('testuser', $summary['username']);
        $this->assertEquals(11, $summary['password_length']); // 'testpass123' 长度
        $this->assertEquals('/auth/login', $summary['endpoint']);
        $this->assertEquals('POST', $summary['method']);
        $this->assertFalse($summary['requires_auth']);
    }

    /**
     * 测试包含不安全字符的用户名
     */
    public function testUsernameWithUnsafeCharacters(): void
    {
        // 应该允许但产生警告
        $request = LoginRequest::create('test<user>', 'testpass123');
        $validation = $request->validate();

        $this->assertTrue($validation->isValid());
        $this->assertNotEmpty($validation->getWarnings());
    }

    /**
     * 测试请求唯一ID生成
     */
    public function testRequestIdGeneration(): void
    {
        $request1 = LoginRequest::create('user1', 'pass1');
        $request2 = LoginRequest::create('user1', 'pass1');
        $request3 = LoginRequest::create('user2', 'pass1');

        // 相同参数应该生成相同的ID
        $this->assertEquals($request1->getRequestId(), $request2->getRequestId());
        // 不同参数应该生成不同的ID
        $this->assertNotEquals($request1->getRequestId(), $request3->getRequestId());
    }

    /**
     * 测试默认请求头
     */
    public function testDefaultHeaders(): void
    {
        $request = LoginRequest::create('testuser', 'testpass123');
        $headers = $request->getHeaders();

        $this->assertArrayHasKey('Referer', $headers);
        $this->assertEquals('/', $headers['Referer']);
    }

    /**
     * 测试UTF-8编码验证
     */
    public function testUtf8EncodingValidation(): void
    {
        // 有效的UTF-8字符应该通过
        $request = LoginRequest::create('用户名', '密码123');
        $this->assertEquals('用户名', $request->getUsername());
        $this->assertEquals('密码123', $request->getPassword());

        // 无效编码应该失败（这个测试可能需要根据实际实现调整）
        $this->expectException(ValidationException::class);
        $invalidString = "\xFF\xFE"; // 无效的UTF-8序列
        LoginRequest::create($invalidString, 'password');
    }
}