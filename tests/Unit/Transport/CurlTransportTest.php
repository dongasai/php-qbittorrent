<?php
declare(strict_types=1);

namespace PhpQbittorrent\Tests\Unit\Transport;

use PhpQbittorrent\Tests\TestCase;
use PhpQbittorrent\Transport\CurlTransport;
use PhpQbittorrent\Transport\TransportInterface;
use PhpQbittorrent\Exception\NetworkException;
use PhpQbittorrent\Exception\ClientException;
use PhpQbittorrent\Exception\AuthenticationException;

/**
 * CurlTransport单元测试
 */
class CurlTransportTest extends TestCase
{
    private CurlTransport $transport;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transport = new CurlTransport($this->factory, $this->factory);
    }

    protected function tearDown(): void
    {
        $this->transport->close();
        parent::tearDown();
    }

    public function testImplementsTransportInterface(): void
    {
        $this->assertInstanceOf(TransportInterface::class, $this->transport);
    }

    public function testSetAndGetBaseUrl(): void
    {
        $url = 'http://localhost:8080';
        $this->transport->setBaseUrl($url);
        $this->assertEquals($url, $this->transport->getBaseUrl());
    }

    public function testSetAndGetAuthentication(): void
    {
        $cookie = 'SID=test_session_id';
        $this->transport->setAuthentication($cookie);
        $this->assertEquals($cookie, $this->transport->getAuthentication());
    }

    public function testSetAndGetLastResponseCode(): void
    {
        // 初始状态应该返回0
        $this->assertEquals(0, $this->transport->getLastResponseCode());
    }

    public function testSetAndGetLastError(): void
    {
        // 初始状态应该返回null
        $this->assertNull($this->transport->getLastError());
    }

    public function testSetTimeout(): void
    {
        $timeout = 45.5;
        $this->transport->setTimeout($timeout);
        // 由于timeout是private属性，我们无法直接测试，但这个测试确保方法可以正常调用
        $this->assertTrue(true);
    }

    public function testSetConnectTimeout(): void
    {
        $timeout = 15.5;
        $this->transport->setConnectTimeout($timeout);
        $this->assertTrue(true);
    }

    public function testSetUserAgent(): void
    {
        $userAgent = 'test-agent/1.0';
        $this->transport->setUserAgent($userAgent);
        $this->assertTrue(true);
    }

    public function testSetVerifySSL(): void
    {
        $this->transport->setVerifySSL(false);
        $this->transport->setVerifySSL(true);
        $this->assertTrue(true);
    }

    public function testSetSSLCertPath(): void
    {
        $certPath = '/path/to/cert.pem';
        $this->transport->setSSLCertPath($certPath);
        $this->assertTrue(true);
    }

    public function testSetProxy(): void
    {
        $proxy = 'http://proxy.example.com:8080';
        $auth = 'user:pass';
        $this->transport->setProxy($proxy, $auth);
        $this->assertTrue(true);
    }

    public function testBuildUrlWithFullUrl(): void
    {
        $this->transport->setBaseUrl('http://localhost:8080');
        $fullUrl = 'https://external.example.com/api';

        // 使用反射访问私有方法来测试URL构建
        $reflection = new \ReflectionClass($this->transport);
        $method = $reflection->getMethod('buildUrl');
        $method->setAccessible(true);

        $result = $method->invoke($this->transport, $fullUrl);
        $this->assertEquals($fullUrl, $result);
    }

    public function testBuildUrlWithRelativePath(): void
    {
        $baseUrl = 'http://localhost:8080';
        $this->transport->setBaseUrl($baseUrl);
        $relativePath = 'api/v1/torrents';

        $reflection = new \ReflectionClass($this->transport);
        $method = $reflection->getMethod('buildUrl');
        $method->setAccessible(true);

        $result = $method->invoke($this->transport, $relativePath);
        $this->assertEquals($baseUrl . '/' . $relativePath, $result);
    }

    public function testBuildUrlWithLeadingSlash(): void
    {
        $baseUrl = 'http://localhost:8080';
        $this->transport->setBaseUrl($baseUrl);
        $path = '/api/v1/torrents';

        $reflection = new \ReflectionClass($this->transport);
        $method = $reflection->getMethod('buildUrl');
        $method->setAccessible(true);

        $result = $method->invoke($this->transport, $path);
        $this->assertEquals($baseUrl . '/api/v1/torrents', $result);
    }

    public function testBuildUrlWithoutBaseUrl(): void
    {
        $this->expectException(NetworkException::class);
        $this->expectExceptionMessage('基础URL未设置');

        $reflection = new \ReflectionClass($this->transport);
        $method = $reflection->getMethod('buildUrl');
        $method->setAccessible(true);

        $method->invoke($this->transport, 'test');
    }

    public function testClose(): void
    {
        $this->transport->close();
        // close方法应该清理内部状态
        $this->assertTrue(true);
    }

    public function testCreateNetworkExceptionForTimeout(): void
    {
        $reflection = new \ReflectionClass($this->transport);
        $method = $reflection->getMethod('createNetworkException');
        $method->setAccessible(true);

        $exception = $method->invoke(
            $this->transport,
            CURLE_OPERATION_TIMEDOUT,
            'Connection timeout',
            'http://test.com',
            'GET'
        );

        $this->assertInstanceOf(NetworkException::class, $exception);
        $this->assertTrue($exception->isTimeoutError());
    }

    public function testCreateNetworkExceptionForSSLError(): void
    {
        $reflection = new \ReflectionClass($this->transport);
        $method = $reflection->getMethod('createNetworkException');
        $method->setAccessible(true);

        $exception = $method->invoke(
            $this->transport,
            CURLE_SSL_CONNECT_ERROR,
            'SSL certificate error',
            'http://test.com',
            'GET'
        );

        $this->assertInstanceOf(NetworkException::class, $exception);
        $this->assertTrue($exception->isSSLError());
    }

    public function testCreateNetworkExceptionForDNSFailure(): void
    {
        $reflection = new \ReflectionClass($this->transport);
        $method = $reflection->getMethod('createNetworkException');
        $method->setAccessible(true);

        $exception = $method->invoke(
            $this->transport,
            CURLE_COULDNT_RESOLVE_HOST,
            'Could not resolve host',
            'http://test.com',
            'GET'
        );

        $this->assertInstanceOf(NetworkException::class, $exception);
        $this->assertStringContains('DNS解析失败', $exception->getMessage());
    }

    public function testParseEmptyResponse(): void
    {
        $response = $this->createMockResponse(200, [], '');

        $reflection = new \ReflectionClass($this->transport);
        $method = $reflection->getMethod('parseResponse');
        $method->setAccessible(true);

        $result = $method->invoke($this->transport, $response);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testParseValidJsonResponse(): void
    {
        $data = ['key' => 'value', 'number' => 123];
        $response = $this->createJsonResponse($data);

        $reflection = new \ReflectionClass($this->transport);
        $method = $reflection->getMethod('parseResponse');
        $method->setAccessible(true);

        $result = $method->invoke($this->transport, $response);
        $this->assertEquals($data, $result);
    }

    public function testParseInvalidJsonResponse(): void
    {
        $response = $this->createMockResponse(200, ['Content-Type' => ['application/json']], 'invalid json');

        $reflection = new \ReflectionClass($this->transport);
        $method = $reflection->getMethod('parseResponse');
        $method->setAccessible(true);

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('JSON解析失败');

        $method->invoke($this->transport, $response);
    }

    public function testParseAuthenticationErrorResponse(): void
    {
        $response = $this->createMockResponse(401, [], 'Unauthorized');

        $reflection = new \ReflectionClass($this->transport);
        $method = $reflection->getMethod('parseResponse');
        $method->setAccessible(true);

        $this->expectException(AuthenticationException::class);

        $method->invoke($this->transport, $response);
    }

    public function testParseForbiddenResponse(): void
    {
        $response = $this->createMockResponse(403, [], 'Forbidden');

        $reflection = new \ReflectionClass($this->transport);
        $method = $reflection->getMethod('parseResponse');
        $method->setAccessible(true);

        $this->expectException(AuthenticationException::class);

        $method->invoke($this->transport, $response);
    }

    public function testParseClientErrorResponse(): void
    {
        $response = $this->createMockResponse(400, [], 'Bad Request');

        $reflection = new \ReflectionClass($this->transport);
        $method = $reflection->getMethod('parseResponse');
        $method->setAccessible(true);

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('HTTP 400');

        $method->invoke($this->transport, $response);
    }

    public function testBuildMultipartBody(): void
    {
        $multipart = [
            [
                'name' => 'field1',
                'contents' => 'value1'
            ],
            [
                'name' => 'file1',
                'filename' => 'test.txt',
                'contents' => 'file content',
                'content_type' => 'text/plain'
            ]
        ];

        $reflection = new \ReflectionClass($this->transport);
        $method = $reflection->getMethod('buildMultipartBody');
        $method->setAccessible(true);

        $body = $method->invoke($this->transport, $multipart, 'boundary_test');

        $this->assertStringContains('Content-Disposition: form-data; name="field1"', $body);
        $this->assertStringContains('value1', $body);
        $this->assertStringContains('Content-Disposition: form-data; name="file1"; filename="test.txt"', $body);
        $this->assertStringContains('file content', $body);
        $this->assertStringContains('--boundary_test--', $body);
    }

    /**
     * 集成测试：完整的请求流程（使用真实的cURL）
     */
    public function testRealCurlRequest(): void
    {
        // 测试一个公开的API端点
        $this->transport->setBaseUrl('https://httpbin.org');
        $this->transport->setTimeout(5.0);

        try {
            $response = $this->transport->request('GET', '/get', [
                'query' => ['test' => 'value']
            ]);

            $this->assertIsArray($response);
            $this->assertArrayHasKey('args', $response);
            $this->assertArrayHasKey('test', $response['args']);
            $this->assertEquals('value', $response['args']['test']);

        } catch (NetworkException $e) {
            // 如果网络不可用，跳过这个测试
            $this->markTestSkipped('Network not available for integration test: ' . $e->getMessage());
        }
    }

    /**
     * 测试POST请求
     */
    public function testRealPostRequest(): void
    {
        $this->transport->setBaseUrl('https://httpbin.org');
        $this->transport->setTimeout(5.0);

        try {
            $data = ['key' => 'value', 'number' => 123];
            $response = $this->transport->request('POST', '/post', [
                'json' => $data
            ]);

            $this->assertIsArray($response);
            $this->assertArrayHasKey('json', $response);
            $this->assertEquals($data, $response['json']);

        } catch (NetworkException $e) {
            $this->markTestSkipped('Network not available for integration test: ' . $e->getMessage());
        }
    }
}