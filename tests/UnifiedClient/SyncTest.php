<?php
declare(strict_types=1);

namespace PhpQbittorrent\Tests\UnifiedClient;

use PHPUnit\Framework\TestCase;
use PhpQbittorrent\UnifiedClient;
use PhpQbittorrent\Tests\MockHelper;

/**
 * UnifiedClient Sync功能单元测试
 *
 * 测试统一客户端中同步相关的方法
 *
 * @package PhpQbittorrent\Tests\UnifiedClient
 */
class SyncTest extends TestCase
{
    private UnifiedClient $client;

    protected function setUp(): void
    {
        $this->client = MockHelper::createUnifiedClient();
    }

    /**
     * 测试获取主要数据同步
     */
    public function testGetMainData(): void
    {
        $mainData = $this->client->getMainData(14);

        // 验证返回数据结构
        $this->assertIsArray($mainData);
        $this->assertArrayHasKey('rid', $mainData);
        $this->assertArrayHasKey('full_update', $mainData);
        $this->assertArrayHasKey('torrents', $mainData);
        $this->assertArrayHasKey('torrents_removed', $mainData);
        $this->assertArrayHasKey('categories', $mainData);
        $this->assertArrayHasKey('tags', $mainData);
        $this->assertArrayHasKey('server_state', $mainData);

        // 验证rid值
        $this->assertEquals(15, $mainData['rid']);
    }

    /**
     * 测试获取主要数据同步 - 默认rid
     */
    public function testGetMainDataWithDefaultRid(): void
    {
        $mainData = $this->client->getMainData();

        // 验证默认情况
        $this->assertIsArray($mainData);
        $this->assertArrayHasKey('rid', $mainData);
        $this->assertEquals(15, $mainData['rid']); // Mock返回的值
    }

    /**
     * 测试获取Torrent Peers数据
     */
    public function testGetTorrentPeers(): void
    {
        $peersData = $this->client->getTorrentPeers('test_hash_123', 10);

        // 验证返回数据结构
        $this->assertIsArray($peersData);
        $this->assertArrayHasKey('rid', $peersData);
        $this->assertArrayHasKey('full_update', $peersData);
        $this->assertArrayHasKey('hash', $peersData);
        $this->assertArrayHasKey('peers', $peersData);
        $this->assertArrayHasKey('peers_count', $peersData);

        // 验证基本值
        $this->assertEquals('test_hash_123', $peersData['hash']);
        $this->assertEquals(10, $peersData['rid']);
        $this->assertTrue($peersData['full_update']);
        $this->assertEquals(1, $peersData['peers_count']);
    }

    /**
     * 测试获取Torrent Peers数据 - 默认rid
     */
    public function testGetTorrentPeersWithDefaultRid(): void
    {
        $peersData = $this->client->getTorrentPeers('test_hash_456');

        // 验证默认情况
        $this->assertIsArray($peersData);
        $this->assertArrayHasKey('hash', $peersData);
        $this->assertEquals('test_hash_456', $peersData['hash']);
        $this->assertEquals(10, $peersData['rid']); // Mock返回的默认值
    }

    /**
     * 测试获取实时统计信息
     */
    public function testGetRealtimeStats(): void
    {
        $stats = $this->client->getRealtimeStats();

        // 验证统计数据结构
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('timestamp', $stats);
        $this->assertArrayHasKey('total_torrents', $stats);
        $this->assertArrayHasKey('active_torrents', $stats);
        $this->assertArrayHasKey('downloading_torrents', $stats);
        $this->assertArrayHasKey('seeding_torrents', $stats);
        $this->assertArrayHasKey('paused_torrents', $stats);
        $this->assertArrayHasKey('total_size', $stats);
        $this->assertArrayHasKey('total_download_speed', $stats);
        $this->assertArrayHasKey('total_upload_speed', $stats);

        // 验证基本统计值（基于Mock数据）
        $this->assertEquals(2, $stats['total_torrents']);
        $this->assertEquals(1, $stats['downloading_torrents']);
        $this->assertEquals(1, $stats['seeding_torrents']);
        $this->assertEquals(0, $stats['paused_torrents']);
        $this->assertGreaterThan(0, $stats['total_size']);
        $this->assertGreaterThan(0, $stats['total_download_speed']);
        $this->assertGreaterThan(0, $stats['total_upload_speed']);
    }

    /**
     * 测试监控数据变化的参数验证
     */
    public function testMonitorChangesWithInvalidInterval(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('监控间隔必须大于0');

        $this->client->monitorChanges(0);
    }

    /**
     * 测试监控数据变化的参数验证 - 负数
     */
    public function testMonitorChangesWithNegativeInterval(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('监控间隔必须大于0');

        $this->client->monitorChanges(-5);
    }

    /**
     * 测试监控数据变化 - 有效间隔
     */
    public function testMonitorChangesWithValidInterval(): void
    {
        $startTime = time();
        $callCount = 0;
        $callbackCalled = false;

        // 使用很短的超时时间来避免无限循环
        try {
            $this->client->monitorChanges(1, function ($data) use (&$callCount, &$callbackCalled) {
                $callCount++;
                $callbackCalled = true;

                // 验证回调数据结构
                $this->assertIsArray($data);
                $this->assertArrayHasKey('rid', $data);
                $this->assertArrayHasKey('torrents', $data);

                // 如果调用次数超过1次，说明循环正常，中断测试
                if ($callCount > 1) {
                    throw new \RuntimeException('Test completed');
                }
            });
        } catch (\RuntimeException $e) {
            // 预期的异常，测试成功
            $this->assertEquals('Test completed', $e->getMessage());
        }

        // 验证至少调用了一次
        $this->assertGreaterThanOrEqual(1, $callCount);
        $this->assertTrue($callbackCalled);

        // 验证监控持续时间（至少1秒）
        $this->assertGreaterThanOrEqual(1, time() - $startTime);
    }

    /**
     * 测试获取传输层
     */
    public function testGetClient(): void
    {
        $client = $this->client->getClient();
        $this->assertNotNull($client);
        $this->assertInstanceOf(\PhpQbittorrent\Client::class, $client);
    }

    /**
     * 测试获取配置
     */
    public function testGetConfig(): void
    {
        $config = $this->client->getConfig();
        $this->assertNotNull($config);
        $this->assertInstanceOf(\PhpQbittorrent\Config\ConfigurationManager::class, $config);
    }

    /**
     * 测试链式调用
     */
    public function testChainedCalls(): void
    {
        // 测试可以连续调用多个方法
        $stats = $this->client
            ->getRealtimeStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_torrents', $stats);
    }
}