<?php
declare(strict_types=1);

namespace PhpQbittorrent\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Nyholm\Psr7\Factory\Psr17Factory;

/**
 * 基础测试类
 *
 * 为所有测试类提供通用的测试工具和方法
 */
abstract class TestCase extends BaseTestCase
{
    protected ?Psr17Factory $factory = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new Psr17Factory();
    }

    protected function tearDown(): void
    {
        // 清理测试环境
        $this->factory = null;
        parent::tearDown();
    }

    /**
     * 创建模拟的HTTP请求对象
     */
    protected function createMockRequest(
        string $method = 'GET',
        string $uri = '/',
        array $headers = [],
        ?string $body = null
    ): MockObject|RequestInterface {
        $request = $this->createMock(RequestInterface::class);
        $request->method('getMethod')->willReturn($method);
        $request->method('getUri')->willReturn($this->factory->createUri($uri));
        $request->method('getHeaders')->willReturn($headers);

        foreach ($headers as $name => $values) {
            $request->method('getHeader')->with($name)->willReturn($values);
            $request->method('getHeaderLine')->with($name)->willReturn(implode(', ', $values));
        }

        if ($body !== null) {
            $stream = $this->createMockStream($body);
            $request->method('getBody')->willReturn($stream);
        }

        return $request;
    }

    /**
     * 创建模拟的HTTP响应对象
     */
    protected function createMockResponse(
        int $statusCode = 200,
        array $headers = [],
        ?string $body = null
    ): MockObject|ResponseInterface {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn($statusCode);
        $response->method('getHeaders')->willReturn($headers);

        foreach ($headers as $name => $values) {
            $response->method('getHeader')->with($name)->willReturn($values);
            $response->method('getHeaderLine')->with($name)->willReturn(implode(', ', $values));
        }

        if ($body !== null) {
            $stream = $this->createMockStream($body);
            $response->method('getBody')->willReturn($stream);
        } else {
            $stream = $this->createMockStream('');
            $response->method('getBody')->willReturn($stream);
        }

        return $response;
    }

    /**
     * 创建模拟的Stream对象
     */
    protected function createMockStream(string $content = ''): MockObject|StreamInterface
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('__toString')->willReturn($content);
        $stream->method('getContents')->willReturn($content);
        $stream->method('getSize')->willReturn(strlen($content));
        $stream->method('isReadable')->willReturn(true);
        $stream->method('isWritable')->willReturn(true);
        $stream->method('isSeekable')->willReturn(true);

        return $stream;
    }

    /**
     * 创建JSON响应数据
     */
    protected function createJsonResponse(array $data, int $statusCode = 200): MockObject|ResponseInterface
    {
        $jsonBody = json_encode($data, JSON_UNESCAPED_UNICODE);
        return $this->createMockResponse(
            $statusCode,
            ['Content-Type' => ['application/json']],
            $jsonBody
        );
    }

    /**
     * 创建错误响应
     */
    protected function createErrorResponse(
        string $message,
        int $statusCode = 400,
        array $details = []
    ): MockObject|ResponseInterface {
        $error = [
            'error' => $message,
            'status' => $statusCode,
            'timestamp' => time()
        ];

        if (!empty($details)) {
            $error['details'] = $details;
        }

        return $this->createJsonResponse($error, $statusCode);
    }

    /**
     * 创建成功的认证响应
     */
    protected function createAuthResponse(): MockObject|ResponseInterface
    {
        return $this->createMockResponse(
            200,
            ['Set-Cookie' => ['SID=your_session_id_here; Path=/']]
        );
    }

    /**
     * 创建qBittorrent API响应
     */
    protected function createQbittorrentResponse(array $data, string $endpoint = ''): MockObject|ResponseInterface
    {
        return $this->createJsonResponse($data);
    }

    /**
     * 断言数组具有指定的键
     */
    protected function assertArrayHasKeys(array $keys, array $array, string $message = ''): void
    {
        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $array, $message ?: "Array should have key: {$key}");
        }
    }

    /**
     * 断言数组具有指定的键值对
     */
    protected function assertArrayHasKeyValuePair(string $key, $expected, array $array, string $message = ''): void
    {
        $this->assertArrayHasKey($key, $array, $message ?: "Array should have key: {$key}");
        $this->assertEquals($expected, $array[$key], $message ?: "Array key '{$key}' should equal expected value");
    }

    /**
     * 断言两个数组大致相等（忽略顺序）
     */
    protected function assertArrayEqualsUnordered(array $expected, array $actual, string $message = ''): void
    {
        $this->assertEqualsCanonicalizing($expected, $actual, $message);
    }

    /**
     * 创建临时文件
     */
    protected function createTempFile(string $content = ''): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'qbittorrent_test_');
        if ($content !== '') {
            file_put_contents($tempFile, $content);
        }
        return $tempFile;
    }

    /**
     * 清理临时文件
     */
    protected function cleanupTempFile(string $filePath): void
    {
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    /**
     * 生成随机字符串
     */
    protected function generateRandomString(int $length = 10): string
    {
        return bin2hex(random_bytes($length / 2));
    }

    /**
     * 生成测试用的Torrent hash
     */
    protected function generateTorrentHash(): string
    {
        return strtoupper(bin2hex(random_bytes(20)));
    }

    /**
     * 创建测试用的Torrent数据
     */
    protected function createTestTorrentData(array $overrides = []): array
    {
        $defaultData = [
            'hash' => $this->generateTorrentHash(),
            'name' => 'Test Torrent ' . $this->generateRandomString(5),
            'size' => 1073741824, // 1GB
            'progress' => 0.5,
            'dlspeed' => 1048576, // 1MB/s
            'upspeed' => 0,
            'priority' => 1,
            'num_seeds' => 10,
            'num_complete' => 15,
            'num_leechs' => 5,
            'num_incomplete' => 8,
            'ratio' => 2.5,
            'eta' => 3600, // 1 hour
            'state' => 'downloading',
            'seq_dl' => false,
            'f_l_piece_prio' => false,
            'tracker' => 'http://tracker.example.com/announce',
            'addition_date' => time(),
            'completion_date' => 0,
            'tracker_tier' => 0,
            'tags' => 'test',
            'save_path' => '/downloads/test'
        ];

        return array_merge($defaultData, $overrides);
    }

    /**
     * 创建测试用的Transfer Info数据
     */
    protected function createTestTransferInfo(array $overrides = []): array
    {
        $defaultData = [
            'dl_info_speed' => 1048576,
            'dl_info_data' => 1073741824,
            'up_info_speed' => 524288,
            'up_info_data' => 2147483648,
            'dl_rate_limit' => 10485760,
            'up_rate_limit' => 5242880,
            'dht_nodes' => 150,
            'connection_status' => 'connected'
        ];

        return array_merge($defaultData, $overrides);
    }

    /**
     * 验证JSON结构
     */
    protected function assertJsonStructure(array $structure, array $data, string $message = ''): void
    {
        foreach ($structure as $key => $value) {
            if (is_array($value)) {
                $this->assertArrayHasKey($key, $data, $message ?: "JSON should have key: {$key}");
                if (is_array($data[$key])) {
                    $this->assertJsonStructure($value, $data[$key], $message);
                }
            } else {
                $this->assertArrayHasKey($value, $data, $message ?: "JSON should have key: {$value}");
            }
        }
    }

    /**
     * 断言异常类型
     */
    protected function assertExceptionType(string $expectedType, callable $callback): void
    {
        $exception = null;

        try {
            $callback();
        } catch (\Throwable $e) {
            $exception = $e;
        }

        $this->assertNotNull($exception, 'Expected an exception to be thrown');
        $this->assertInstanceOf($expectedType, $exception, 'Exception type mismatch');
    }

    /**
     * 创建HTTP响应头模拟
     */
    protected function createResponseHeaders(array $headers): array
    {
        $formattedHeaders = [];
        foreach ($headers as $name => $value) {
            if (is_array($value)) {
                $formattedHeaders[$name] = $value;
            } else {
                $formattedHeaders[$name] = [$value];
            }
        }
        return $formattedHeaders;
    }
}