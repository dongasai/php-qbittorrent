<?php
declare(strict_types=1);

namespace PhpQbittorrent\Tests\Unit\API\v2;

use PhpQbittorrent\API\v2\TorrentAPI;
use PhpQbittorrent\Contract\ResponseInterface;
use PhpQbittorrent\Contract\TransportInterface;
use PhpQbittorrent\Contract\TransportResponse;
use PhpQbittorrent\Exception\ApiRuntimeException;
use PhpQbittorrent\Exception\NetworkException;
use PhpQbittorrent\Exception\ValidationException;
use PhpQbittorrent\Request\Torrent\GetTorrentsRequest;
use PhpQbittorrent\Request\Torrent\AddTorrentRequest;
use PhpQbittorrent\Request\Torrent\DeleteTorrentsRequest;
use PhpQbittorrent\Request\Torrent\PauseTorrentsRequest;
use PhpQbittorrent\Request\Torrent\ResumeTorrentsRequest;
use PhpQbittorrent\Response\Torrent\TorrentListResponse;
use PhpQbittorrent\Tests\TestCase;
use Mockery;

/**
 * TorrentAPI 单元测试
 */
class TorrentAPITest extends TestCase
{
    private $mockTransport;
    private $torrentAPI;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockTransport = Mockery::mock(TransportInterface::class);
        $this->torrentAPI = new TorrentAPI($this->mockTransport);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * 创建模拟的传输响应
     */
    private function createMockTransportResponse(
        int $statusCode = 200,
        array $data = [],
        string $body = '',
        array $headers = []
    ): TransportResponse {
        $mockResponse = Mockery::mock(TransportResponse::class);
        $mockResponse->shouldReceive('getStatusCode')->andReturn($statusCode);
        $mockResponse->shouldReceive('getJson')->andReturn($data);
        $mockResponse->shouldReceive('getBody')->andReturn($body ?: json_encode($data));
        $mockResponse->shouldReceive('getHeaders')->andReturn($headers);
        $mockResponse->shouldReceive('isSuccess')->andReturn($statusCode >= 200 && $statusCode < 300);
        $mockResponse->shouldReceive('isJson')->andReturn(!empty($data));

        return $mockResponse;
    }

    /**
     * 测试获取基础路径
     */
    public function testGetBasePath(): void
    {
        $this->assertSame('/api/v2/torrents', $this->torrentAPI->getBasePath());
    }

    /**
     * 测试获取和设置传输层
     */
    public function testGetSetTransport(): void
    {
        $this->assertSame($this->mockTransport, $this->torrentAPI->getTransport());

        $newTransport = Mockery::mock(TransportInterface::class);
        $returnedAPI = $this->torrentAPI->setTransport($newTransport);

        $this->assertSame($newTransport, $this->torrentAPI->getTransport());
        $this->assertSame($this->torrentAPI, $returnedAPI);
    }

    /**
     * 测试执行GET请求
     */
    public function testGet(): void
    {
        $mockResponse = $this->createMockTransportResponse(200, ['result' => 'success']);
        $this->mockTransport
            ->shouldReceive('get')
            ->once()
            ->with('/api/v2/torrents/test', ['param1' => 'value1'], ['Header' => 'Value'])
            ->andReturn($mockResponse);

        $response = $this->torrentAPI->get('/test', ['param1' => 'value1'], ['Header' => 'Value']);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertTrue($response->isSuccess());
        $this->assertSame(['result' => 'success'], $response->getData());
    }

    /**
     * 测试执行POST请求
     */
    public function testPost(): void
    {
        $mockResponse = $this->createMockTransportResponse(200, ['result' => 'created']);
        $this->mockTransport
            ->shouldReceive('post')
            ->once()
            ->with('/api/v2/torrents/test', ['data' => 'value'], ['Header' => 'Value'])
            ->andReturn($mockResponse);

        $response = $this->torrentAPI->post('/test', ['data' => 'value'], ['Header' => 'Value']);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertTrue($response->isSuccess());
        $this->assertSame(['result' => 'created'], $response->getData());
    }

    /**
     * 测试获取Torrent列表 - 成功情况
     */
    public function testGetTorrentsSuccess(): void
    {
        $torrentsData = [
            [
                'hash' => 'hash1',
                'name' => 'Test Torrent 1',
                'size' => 1073741824,
                'progress' => 1.0,
                'state' => 'uploading',
            ],
            [
                'hash' => 'hash2',
                'name' => 'Test Torrent 2',
                'size' => 2147483648,
                'progress' => 0.75,
                'state' => 'downloading',
            ],
        ];

        $mockResponse = $this->createMockTransportResponse(200, $torrentsData);
        $this->mockTransport
            ->shouldReceive('get')
            ->once()
            ->with('/api/v2/torrents/info', ['filter' => 'active'], ['Accept' => 'application/json'])
            ->andReturn($mockResponse);

        $request = GetTorrentsRequest::create()
            ->setFilter('active');

        $response = $this->torrentAPI->getTorrents($request);

        $this->assertInstanceOf(TorrentListResponse::class, $response);
        $this->assertTrue($response->isSuccess());
        $this->assertSame(2, $response->getTotalCount());
        $this->assertTrue($response->hasTorrents());
    }

    /**
     * 测试获取Torrent列表 - 验证失败
     */
    public function testGetTorrentsValidationFailure(): void
    {
        $request = GetTorrentsRequest::create()
            ->setLimit(-1); // 无效的限制数量

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('GetTorrents request validation failed');

        $this->torrentAPI->getTorrents($request);
    }

    /**
     * 测试获取Torrent列表 - 网络错误
     */
    public function testGetTorrentsNetworkError(): void
    {
        $this->mockTransport
            ->shouldReceive('get')
            ->once()
            ->andThrow(new NetworkException('Connection failed'));

        $request = GetTorrentsRequest::create();

        $this->expectException(ApiRuntimeException::class);
        $this->expectExceptionMessage('Get torrents failed due to network error: Connection failed');

        $this->torrentAPI->getTorrents($request);
    }

    /**
     * 测试添加Torrent - 成功情况（URL）
     */
    public function testAddTorrentsSuccessByUrl(): void
    {
        $mockResponse = $this->createMockTransportResponse(200, ['status' => 'ok']);
        $this->mockTransport
            ->shouldReceive('post')
            ->once()
            ->with('/api/v2/torrents/add', Mockery::type('array'), Mockery::type('array'))
            ->andReturn($mockResponse);

        $request = AddTorrentRequest::create()
            ->addUrl('magnet:?xt=urn:btih:test')
            ->setSavePath('/downloads')
            ->setCategory('test');

        $response = $this->torrentAPI->addTorrents($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertTrue($response->isSuccess());
    }

    /**
     * 测试添加Torrent - 成功情况（文件）
     */
    public function testAddTorrentsSuccessByFile(): void
    {
        $mockResponse = $this->createMockTransportResponse(200, ['status' => 'ok']);
        $this->mockTransport
            ->shouldReceive('post')
            ->once()
            ->with('/api/v2/torrents/add', Mockery::type('array'), Mockery::type('array'), Mockery::type('array'))
            ->andReturn($mockResponse);

        $request = AddTorrentRequest::create()
            ->addFile('test.torrent', 'file content here')
            ->setSavePath('/downloads')
            ->setPaused(true);

        $response = $this->torrentAPI->addTorrents($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertTrue($response->isSuccess());
    }

    /**
     * 测试添加Torrent - 文件无效错误
     */
    public function testAddTorrentsInvalidFileError(): void
    {
        $mockResponse = $this->createMockTransportResponse(415, ['error' => 'Invalid torrent file']);
        $this->mockTransport
            ->shouldReceive('post')
            ->once()
            ->andReturn($mockResponse);

        $request = AddTorrentRequest::create()
            ->addFile('invalid.torrent', 'invalid content');

        $response = $this->torrentAPI->addTorrents($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertFalse($response->isSuccess());
        $this->assertSame(['error' => 'Torrent文件无效'], $response->getData());
    }

    /**
     * 测试添加Torrent - 验证失败
     */
    public function testAddTorrentsValidationFailure(): void
    {
        $request = AddTorrentRequest::create(); // 既没有URL也没有文件

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('AddTorrents request validation failed');

        $this->torrentAPI->addTorrents($request);
    }

    /**
     * 测试删除Torrent - 成功情况
     */
    public function testDeleteTorrentsSuccess(): void
    {
        $mockResponse = $this->createMockTransportResponse(200);
        $this->mockTransport
            ->shouldReceive('post')
            ->once()
            ->with('/api/v2/torrents/delete', Mockery::type('array'), Mockery::type('array'))
            ->andReturn($mockResponse);

        $request = DeleteTorrentsRequest::create()
            ->addHash('hash1')
            ->addHash('hash2')
            ->setDeleteFiles(false);

        $response = $this->torrentAPI->deleteTorrents($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertTrue($response->isSuccess());
    }

    /**
     * 测试删除Torrent - 验证失败
     */
    public function testDeleteTorrentsValidationFailure(): void
    {
        $request = DeleteTorrentsRequest::create(); // 没有添加哈希

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('DeleteTorrents request validation failed');

        $this->torrentAPI->deleteTorrents($request);
    }

    /**
     * 测试暂停Torrent - 成功情况
     */
    public function testPauseTorrentsSuccess(): void
    {
        $mockResponse = $this->createMockTransportResponse(200);
        $this->mockTransport
            ->shouldReceive('post')
            ->once()
            ->with('/api/v2/torrents/pause', Mockery::type('array'), Mockery::type('array'))
            ->andReturn($mockResponse);

        $request = PauseTorrentsRequest::create()
            ->addHash('hash1')
            ->addHash('hash2');

        $response = $this->torrentAPI->pauseTorrents($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertTrue($response->isSuccess());
    }

    /**
     * 测试暂停Torrent - 验证失败
     */
    public function testPauseTorrentsValidationFailure(): void
    {
        $request = PauseTorrentsRequest::create(); // 没有添加哈希

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('PauseTorrents request validation failed');

        $this->torrentAPI->pauseTorrents($request);
    }

    /**
     * 测试恢复Torrent - 成功情况
     */
    public function testResumeTorrentsSuccess(): void
    {
        $mockResponse = $this->createMockTransportResponse(200);
        $this->mockTransport
            ->shouldReceive('post')
            ->once()
            ->with('/api/v2/torrents/start', Mockery::type('array'), Mockery::type('array'))
            ->andReturn($mockResponse);

        $request = ResumeTorrentsRequest::create()
            ->addHash('hash1')
            ->addHash('hash2');

        $response = $this->torrentAPI->resumeTorrents($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertTrue($response->isSuccess());
    }

    /**
     * 测试恢复Torrent - 验证失败
     */
    public function testResumeTorrentsValidationFailure(): void
    {
        $request = ResumeTorrentsRequest::create(); // 没有添加哈希

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('ResumeTorrents request validation failed');

        $this->torrentAPI->resumeTorrents($request);
    }

    /**
     * 测试网络异常处理
     */
    public function testNetworkExceptionHandling(): void
    {
        $this->mockTransport
            ->shouldReceive('post')
            ->once()
            ->andThrow(new NetworkException('Network timeout'));

        $request = DeleteTorrentsRequest::create()->addHash('hash1');

        $this->expectException(ApiRuntimeException::class);
        $this->expectExceptionMessage('Delete torrents failed due to network error: Network timeout');

        $this->torrentAPI->deleteTorrents($request);
    }

    /**
     * 测试HTTP错误状态码处理
     */
    public function testHttpErrorStatusHandling(): void
    {
        $mockResponse = $this->createMockTransportResponse(500, ['error' => 'Internal server error']);
        $this->mockTransport
            ->shouldReceive('post')
            ->once()
            ->andReturn($mockResponse);

        $request = DeleteTorrentsRequest::create()->addHash('hash1');
        $response = $this->torrentAPI->deleteTorrents($request);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertFalse($response->isSuccess());
        $this->assertArrayHasKey('error', $response->getData());
    }

    /**
     * 测试请求参数正确传递
     */
    public function testRequestParametersCorrectlyPassed(): void
    {
        $mockResponse = $this->createMockTransportResponse(200);
        $this->mockTransport
            ->shouldReceive('post')
            ->once()
            ->with(
                '/api/v2/torrents/delete',
                ['hashes' => 'hash1|hash2', 'deleteFiles' => 'false'],
                ['Content-Type' => 'application/x-www-form-urlencoded']
            )
            ->andReturn($mockResponse);

        $request = DeleteTorrentsRequest::create()
            ->addHash('hash1')
            ->addHash('hash2')
            ->setDeleteFiles(false);

        $this->torrentAPI->deleteTorrents($request);
    }

    /**
     * 测试请求头正确传递
     */
    public function testRequestHeadersCorrectlyPassed(): void
    {
        $mockResponse = $this->createMockTransportResponse(200);
        $this->mockTransport
            ->shouldReceive('get')
            ->once()
            ->with(
                '/api/v2/torrents/info',
                ['filter' => 'completed'],
                ['Accept' => 'application/json', 'User-Agent' => 'Test Client']
            )
            ->andReturn($mockResponse);

        $request = GetTorrentsRequest::create()
            ->setFilter('completed')
            ->setHeaders(['User-Agent' => 'Test Client']);

        $this->torrentAPI->getTorrents($request);
    }

    /**
     * 测试multipart请求处理
     */
    public function testMultipartRequestHandling(): void
    {
        $mockResponse = $this->createMockTransportResponse(200);
        $this->mockTransport
            ->shouldReceive('post')
            ->once()
            ->with(
                '/api/v2/torrents/add',
                Mockery::type('array'),
                Mockery::type('array'),
                Mockery::type('array') // 文件字段参数
            )
            ->andReturn($mockResponse);

        $request = AddTorrentRequest::create()
            ->addFile('test.torrent', 'file content')
            ->addUrl('magnet:?xt=urn:btih:test')
            ->setSavePath('/downloads');

        $this->torrentAPI->addTorrents($request);
    }
}