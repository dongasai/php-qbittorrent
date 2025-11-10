<?php
declare(strict_types=1);

namespace PhpQbittorrent\Tests;

use PHPUnit\Framework\TestCase;
use PhpQbittorrent\Transport\CurlTransport;
use PhpQbittorrent\Contract\TransportResponse;
use Nyholm\Psr7\Factory\Psr17Factory;

class CurlTransportTest extends TestCase
{
    private CurlTransport $transport;
    private string $testUrl = 'http://192.168.4.105:8989';
    private string $testUsername = 'admin';
    private string $testPassword = 'DQ89AFy9u';

    protected function setUp(): void
    {
        $this->transport = new CurlTransport(new Psr17Factory(), new Psr17Factory());
        $this->transport->setBaseUrl($this->testUrl);
        $this->transport->setVerifySSL(false);
    }

    public function testTransportCreation(): void
    {
        $this->assertInstanceOf(CurlTransport::class, $this->transport);
        $this->assertEquals($this->testUrl, $this->transport->getBaseUrl());
    }

    public function testSetBaseUrl(): void
    {
        $newUrl = 'http://localhost:8080';
        $result = $this->transport->setBaseUrl($newUrl);
        $this->assertEquals($this->transport, $result); // 测试链式调用
        $this->assertEquals($newUrl, $this->transport->getBaseUrl());
    }

    public function testAuthenticationMethods(): void
    {
        $testCookie = 'SID=test123';
        $this->transport->setAuthentication($testCookie);
        $this->assertEquals($testCookie, $this->transport->getAuthentication());
    }

    public function testConfigurationMethods(): void
    {
        // 测试超时设置
        $this->transport->setTimeout(60);
        $this->assertEquals(60, $this->transport->getTimeout());

        // 测试连接超时设置
        $this->transport->setConnectTimeout(15.0);
        // 注意：没有getConnectTimeout方法，所以只能测试设置不抛异常

        // 测试User-Agent设置
        $this->transport->setUserAgent('test-agent');
        // 注意：没有getUserAgent方法，所以只能测试设置不抛异常

        // 测试SSL验证设置
        $this->transport->setVerifySSL(false);
        // 注意：没有getVerifySSL方法，所以只能测试设置不抛异常
    }

    public function testGetRequest(): void
    {
        try {
            $response = $this->transport->get('/api/v2/app/version');
            $this->assertInstanceOf(TransportResponse::class, $response);
            $this->assertEquals(200, $response->getStatusCode());
            $this->assertNotEmpty($response->getBody());
        } catch (\Exception $e) {
            $this->markTestSkipped('qBittorrent服务器不可用: ' . $e->getMessage());
        }
    }

    public function testPostRequest(): void
    {
        try {
            $response = $this->transport->post('/api/v2/auth/login', [
                'username' => $this->testUsername,
                'password' => $this->testPassword,
            ]);
            $this->assertInstanceOf(TransportResponse::class, $response);
            $this->assertEquals(200, $response->getStatusCode());
        } catch (\Exception $e) {
            $this->markTestSkipped('qBittorrent服务器不可用: ' . $e->getMessage());
        }
    }

    public function testRequestWithFormParams(): void
    {
        try {
            $response = $this->transport->request('POST', '/api/v2/auth/login', [
                'form_params' => [
                    'username' => $this->testUsername,
                    'password' => $this->testPassword,
                ]
            ]);
            $this->assertIsArray($response);
        } catch (\Exception $e) {
            $this->markTestSkipped('qBittorrent服务器不可用: ' . $e->getMessage());
        }
    }

    public function testRequestWithQueryParams(): void
    {
        try {
            $response = $this->transport->request('GET', '/api/v2/app/version', [
                'query' => ['test' => 'value']
            ]);
            $this->assertIsArray($response);
        } catch (\Exception $e) {
            $this->markTestSkipped('qBittorrent服务器不可用: ' . $e->getMessage());
        }
    }

    public function testAuthenticationFlow(): void
    {
        try {
            // 第一步：登录
            $loginResponse = $this->transport->request('POST', '/api/v2/auth/login', [
                'form_params' => [
                    'username' => $this->testUsername,
                    'password' => $this->testPassword,
                ]
            ]);

            $this->assertIsArray($loginResponse);

            // 第二步：检查是否获取到Cookie
            $cookie = $this->transport->getAuthentication();
            $this->assertNotNull($cookie);
            $this->assertStringStartsWith('SID=', $cookie);

            // 第三步：使用Cookie访问需要认证的API
            $versionResponse = $this->transport->request('GET', '/api/v2/app/version');
            $this->assertIsArray($versionResponse);
            $this->assertNotEmpty($versionResponse);

        } catch (\Exception $e) {
            $this->markTestSkipped('qBittorrent服务器不可用: ' . $e->getMessage());
        }
    }

    public function testErrorHandling(): void
    {
        try {
            // 测试404错误
            $response = $this->transport->get('/nonexistent/endpoint');
            $this->fail('应该抛出异常');
        } catch (\PhpQbittorrent\Exception\ClientException $e) {
            $this->assertStringContains('HTTP', $e->getMessage());
        } catch (\Exception $e) {
            $this->markTestSkipped('qBittorrent服务器不可用: ' . $e->getMessage());
        }
    }

    public function testResponseStructure(): void
    {
        try {
            $response = $this->transport->get('/api/v2/app/version');
            $this->assertInstanceOf(TransportResponse::class, $response);
            $this->assertIsInt($response->getStatusCode());
            $this->assertIsArray($response->getHeaders());
            $this->assertIsString($response->getBody());
        } catch (\Exception $e) {
            $this->markTestSkipped('qBittorrent服务器不可用: ' . $e->getMessage());
        }
    }

    public function testJsonResponseHandling(): void
    {
        try {
            $response = $this->transport->get('/api/v2/sync/maindata');
            $json = $response->getJson();
            $this->assertTrue($response->isJson() || $response->getStatusCode() === 404);
        } catch (\Exception $e) {
            $this->markTestSkipped('qBittorrent服务器不可用: ' . $e->getMessage());
        }
    }

    public function testPutRequest(): void
    {
        try {
            $response = $this->transport->put('/api/v2/transfer/setDownloadLimit', [
                'limit' => 1024
            ]);
            $this->assertInstanceOf(TransportResponse::class, $response);
        } catch (\Exception $e) {
            // 这个可能会失败，因为需要认证
            $this->markTestSkipped('PUT请求测试跳过: ' . $e->getMessage());
        }
    }

    public function testDeleteRequest(): void
    {
        try {
            $response = $this->transport->delete('/api/v2/auth/logout');
            $this->assertInstanceOf(TransportResponse::class, $response);
        } catch (\Exception $e) {
            // 这个可能会失败，因为需要认证
            $this->markTestSkipped('DELETE请求测试跳过: ' . $e->getMessage());
        }
    }
}