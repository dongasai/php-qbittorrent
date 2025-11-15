<?php
declare(strict_types=1);

namespace PhpQbittorrent\Tests\Model\Sync;

use PHPUnit\Framework\TestCase;
use PhpQbittorrent\Model\Sync\TorrentPeer;

/**
 * TorrentPeer模型单元测试
 *
 * 测试Torrent Peer连接信息模型的各种功能
 *
 * @package PhpQbittorrent\Tests\Model\Sync
 */
class TorrentPeerTest extends TestCase
{
    /**
     * 测试构造函数和基本getter方法
     */
    public function testConstructorAndGetters(): void
    {
        $peer = new TorrentPeer(
            '192.168.1.100',
            51413,
            'China',
            'CN',
            'qBittorrent/4.5.0',
            0.85,
            1024000,
            512000,
            1073741824,
            536870912,
            1,
            2,
            'active'
        );

        // 验证基本属性
        $this->assertEquals('192.168.1.100', $peer->getIP());
        $this->assertEquals(51413, $peer->getPort());
        $this->assertEquals('China', $peer->getCountry());
        $this->assertEquals('CN', $peer->getCountryCode());
        $this->assertEquals('qBittorrent/4.5.0', $peer->getClient());
        $this->assertEquals(0.85, $peer->getProgress());
        $this->assertEquals(1024000, $peer->getDownloadSpeed());
        $this->assertEquals(512000, $peer->getUploadSpeed());
        $this->assertEquals(1073741824, $peer->getDownloaded());
        $this->assertEquals(536870912, $peer->getUploaded());
        $this->assertEquals(1, $peer->getConnectionType());
        $this->assertEquals(2, $peer->getFlags());
        $this->assertEquals('active', $peer->getRelevant());
    }

    /**
     * 测试默认值构造
     */
    public function testConstructorWithDefaults(): void
    {
        $peer = new TorrentPeer('10.0.0.1', 6881, 'United States', 'US');

        $this->assertEquals('10.0.0.1', $peer->getIP());
        $this->assertEquals(6881, $peer->getPort());
        $this->assertEquals('United States', $peer->getCountry());
        $this->assertEquals('US', $peer->getCountryCode());
        $this->assertNull($peer->getClient());
        $this->assertEquals(0.0, $peer->getProgress());
        $this->assertEquals(0, $peer->getDownloadSpeed());
        $this->assertEquals(0, $peer->getUploadSpeed());
        $this->assertEquals(0, $peer->getDownloaded());
        $this->assertEquals(0, $peer->getUploaded());
        $this->assertNull($peer->getConnectionType());
        $this->assertNull($peer->getFlags());
        $this->assertNull($peer->getRelevant());
    }

    /**
     * 测试getAddress方法
     */
    public function testGetAddress(): void
    {
        $peer = new TorrentPeer('192.168.1.100', 51413, 'China', 'CN');
        $this->assertEquals('192.168.1.100:51413', $peer->getAddress());
    }

    /**
     * 测试toArray方法
     */
    public function testToArray(): void
    {
        $expectedArray = [
            'ip' => '192.168.1.100',
            'port' => 51413,
            'country' => 'China',
            'country_code' => 'CN',
            'client' => 'qBittorrent/4.5.0',
            'progress' => 0.85,
            'dl_speed' => 1024000,
            'up_speed' => 512000,
            'downloaded' => 1073741824,
            'uploaded' => 536870912,
            'connection_type' => 1,
            'flags' => 2,
            'relevant' => 'active'
        ];

        $peer = new TorrentPeer(
            $expectedArray['ip'],
            $expectedArray['port'],
            $expectedArray['country'],
            $expectedArray['country_code'],
            $expectedArray['client'],
            $expectedArray['progress'],
            $expectedArray['dl_speed'],
            $expectedArray['up_speed'],
            $expectedArray['downloaded'],
            $expectedArray['uploaded'],
            $expectedArray['connection_type'],
            $expectedArray['flags'],
            $expectedArray['relevant']
        );

        $this->assertEquals($expectedArray, $peer->toArray());
    }

    /**
     * 测试fromArray方法
     */
    public function testFromArray(): void
    {
        $data = [
            'ip' => '10.0.0.1',
            'port' => 6881,
            'country' => 'Japan',
            'country_code' => 'JP',
            'client' => 'Transmission/4.0.2',
            'progress' => 0.65,
            'dl_speed' => 2048000,
            'up_speed' => 1024000
        ];

        $peer = TorrentPeer::fromArray($data);

        $this->assertEquals('10.0.0.1', $peer->getIP());
        $this->assertEquals(6881, $peer->getPort());
        $this->assertEquals('Japan', $peer->getCountry());
        $this->assertEquals('JP', $peer->getCountryCode());
        $this->assertEquals('Transmission/4.0.2', $peer->getClient());
        $this->assertEquals(0.65, $peer->getProgress());
        $this->assertEquals(2048000, $peer->getDownloadSpeed());
        $this->assertEquals(1024000, $peer->getUploadSpeed());
        $this->assertEquals(0, $peer->getDownloaded()); // 默认值
        $this->assertEquals(0, $peer->getUploaded()); // 默认值
        $this->assertNull($peer->getConnectionType()); // 未设置的字段
        $this->assertNull($peer->getFlags()); // 未设置的字段
        $this->assertNull($peer->getRelevant()); // 未设置的字段
    }

    /**
     * 测试fromArray方法 - 缺失字段
     */
    public function testFromArrayWithMissingFields(): void
    {
        $data = [
            'ip' => '172.16.0.1'
        ];

        $peer = TorrentPeer::fromArray($data);

        $this->assertEquals('172.16.0.1', $peer->getIP());
        $this->assertEquals(0, $peer->getPort()); // 默认值
        $this->assertEquals('', $peer->getCountry()); // 默认值
        $this->assertEquals('', $peer->getCountryCode()); // 默认值
        $this->assertNull($peer->getClient()); // 默认值
        $this->assertEquals(0.0, $peer->getProgress()); // 默认值
    }
}