<?php
declare(strict_types=1);

namespace PhpQbittorrent\Tests\Sync;

use PHPUnit\Framework\TestCase;
use PhpQbittorrent\API\SyncAPI;
use PhpQbittorrent\Contract\TransportInterface;
use PhpQbittorrent\Contract\TransportResponse;
use PhpQbittorrent\Request\Sync\GetMainDataRequest;
use PhpQbittorrent\Request\Sync\GetTorrentPeersRequest;
use PhpQbittorrent\Response\Sync\MainDataResponse;
use PhpQbittorrent\Response\Sync\TorrentPeersResponse;

/**
 * SyncAPI单元测试
 *
 * 测试同步API的各种功能
 *
 * @package PhpQbittorrent\Tests\Sync
 */
class SyncAPITest extends TestCase
{
    private SyncAPI $syncAPI;
    private TransportInterface $transport;

    protected function setUp(): void
    {
        $this->transport = $this->createMockTransport();
        $this->syncAPI = new SyncAPI($this->transport);
    }

    /**
     * 创建模拟传输层
     */
    private function createMockTransport(): TransportInterface
    {
        $transport = $this->createMock(TransportInterface::class);

        // 模拟send方法返回不同的响应
        $transport->method('send')
            ->willReturnCallback(function ($request) {
                if ($request instanceof GetMainDataRequest) {
                    return $this->createMockTransportResponse([
                        'rid' => 15,
                        'full_update' => false,
                        'torrents' => [
                            'hash1' => ['state' => 'downloading'],
                            'hash2' => ['state' => 'pausedUP']
                        ],
                        'torrents_removed' => ['hash3'],
                        'categories' => [
                            'movies' => ['savePath' => '/downloads/movies']
                        ],
                        'tags' => ['movie', 'hd'],
                        'server_state' => [
                            'dl_info_speed' => 1024000,
                            'up_info_speed' => 512000
                        ]
                    ]);
                } elseif ($request instanceof GetTorrentPeersRequest) {
                    return $this->createMockTransportResponse([
                        'rid' => 10,
                        'full_update' => true,
                        'hash' => 'hash1',
                        'peers' => [
                            [
                                'ip' => '192.168.1.100',
                                'port' => 51413,
                                'country' => 'China',
                                'country_code' => 'CN',
                                'client' => 'qBittorrent/4.5.0',
                                'progress' => 0.85,
                                'dl_speed' => 1024000,
                                'up_speed' => 512000,
                                'downloaded' => 1073741824,
                                'uploaded' => 536870912
                            ]
                        ]
                    ]);
                }

                return $this->createMockTransportResponse([]);
            });

        return $transport;
    }

    /**
     * 创建模拟传输响应
     */
    private function createMockTransportResponse(array $data): TransportResponse
    {
        $response = $this->createMock(TransportResponse::class);
        $response->method('getBody')->willReturn(json_encode($data));
        $response->method('getStatusCode')->willReturn(200);
        $response->method('isSuccess')->willReturn(true);
        $response->method('getHeaders')->willReturn(['Content-Type' => 'application/json']);

        return $response;
    }

    /**
     * 测试获取主要数据同步 - 完整更新
     */
    public function testGetMainDataWithFullUpdate(): void
    {
        // 请求获取主要数据
        $response = $this->syncAPI->getMainData(0);

        // 验证响应
        $this->assertInstanceOf(MainDataResponse::class, $response);
        $this->assertEquals(15, $response->getRid());
        $this->assertFalse($response->isFullUpdate());

        $torrents = $response->getTorrents();
        $this->assertArrayHasKey('hash1', $torrents);
        $this->assertArrayHasKey('hash2', $torrents);

        $removedTorrents = $response->getTorrentsRemoved();
        $this->assertContains('hash3', $removedTorrents);

        $categories = $response->getCategories();
        $this->assertArrayHasKey('movies', $categories);

        $tags = $response->getTags();
        $this->assertContains('movie', $tags);
        $this->assertContains('hd', $tags);
    }

    /**
     * 测试获取主要数据同步 - 增量更新
     */
    public function testGetMainDataWithIncrementalUpdate(): void
    {
        // 请求增量更新
        $response = $this->syncAPI->getMainData(14);

        // 验证响应
        $this->assertInstanceOf(MainDataResponse::class, $response);
        $this->assertEquals(15, $response->getRid());
        $this->assertFalse($response->isFullUpdate());
    }

    /**
     * 测试获取Torrent Peers数据
     */
    public function testGetTorrentPeers(): void
    {
        // 请求获取torrent peers
        $response = $this->syncAPI->getTorrentPeers('hash1', 5);

        // 验证响应
        $this->assertInstanceOf(TorrentPeersResponse::class, $response);
        $this->assertEquals('hash1', $response->getHash());
        $this->assertEquals(10, $response->getRid());
        $this->assertTrue($response->isFullUpdate());

        $peers = $response->getPeers();
        $this->assertCount(1, $peers);

        $peer = $peers[0];
        $this->assertEquals('192.168.1.100', $peer->getIP());
        $this->assertEquals(51413, $peer->getPort());
        $this->assertEquals('China', $peer->getCountry());
        $this->assertEquals('CN', $peer->getCountryCode());
        $this->assertEquals('qBittorrent/4.5.0', $peer->getClient());
        $this->assertEquals(0.85, $peer->getProgress());
        $this->assertEquals(1024000, $peer->getDownloadSpeed());
        $this->assertEquals(512000, $peer->getUploadSpeed());
    }

    /**
     * 测试获取Torrent Peers数据 - 空哈希
     */
    public function testGetTorrentPeersWithEmptyHash(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Torrent hash cannot be empty');

        $this->syncAPI->getTorrentPeers('', 0);
    }

    /**
     * 测试获取传输层
     */
    public function testGetTransport(): void
    {
        $transport = $this->syncAPI->getTransport();
        $this->assertSame($this->transport, $transport);
    }

    /**
     * 测试响应数组转换
     */
    public function testResponseToArray(): void
    {
        $response = $this->syncAPI->getMainData(0);
        $array = $response->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('rid', $array);
        $this->assertArrayHasKey('full_update', $array);
        $this->assertArrayHasKey('torrents', $array);
        $this->assertArrayHasKey('torrents_removed', $array);
        $this->assertArrayHasKey('categories', $array);
        $this->assertArrayHasKey('tags', $array);
        $this->assertArrayHasKey('server_state', $array);
    }
}