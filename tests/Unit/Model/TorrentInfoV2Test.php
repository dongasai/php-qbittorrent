<?php
declare(strict_types=1);

namespace PhpQbittorrent\Tests\Unit\Model;

use PhpQbittorrent\Enum\TorrentState;
use PhpQbittorrent\Model\TorrentInfoV2;
use PhpQbittorrent\Tests\TestCase;

/**
 * TorrentInfoV2 单元测试
 */
class TorrentInfoV2Test extends TestCase
{
    /**
     * 测试从数组创建TorrentInfoV2对象
     */
    public function testFromArray(): void
    {
        $data = [
            'hash' => '8c212779b4abde7c6bc608063a0d008b7e40ce32',
            'name' => 'Test Torrent',
            'size' => 1073741824,
            'progress' => 0.75,
            'state' => 'downloading',
            'dlspeed' => 1048576,
            'upspeed' => 524288,
            'added_on' => 1609459200,
            'category' => 'test',
            'tags' => 'tag1,tag2',
            'tracker' => 'http://tracker.example.com/announce',
            'save_path' => '/downloads/test',
            'ratio' => 1.5,
            'num_seeds' => 10,
            'num_leechs' => 5,
        ];

        $torrent = TorrentInfoV2::fromArray($data);

        $this->assertSame($data['hash'], $torrent->getHash());
        $this->assertSame($data['name'], $torrent->getName());
        $this->assertSame($data['size'], $torrent->getSize());
        $this->assertSame($data['progress'], $torrent->getProgress());
        $this->assertSame(TorrentState::DOWNLOADING, $torrent->getState());
        $this->assertSame($data['dlspeed'], $torrent->getDownloadSpeed());
        $this->assertSame($data['upspeed'], $torrent->getUploadSpeed());
        $this->assertSame($data['added_on'], $torrent->getAddedOn());
        $this->assertSame($data['category'], $torrent->getCategory());
        $this->assertSame($data['tags'], $torrent->getTags());
        $this->assertSame($data['tracker'], $torrent->getTracker());
        $this->assertSame($data['save_path'], $torrent->getSavePath());
        $this->assertSame($data['ratio'], $torrent->getRatio());
        $this->assertSame($data['num_seeds'], $torrent->getSeedCount());
        $this->assertSame($data['num_leechs'], $torrent->getLeechCount());
    }

    /**
     * 测试默认值处理
     */
    public function testDefaultValues(): void
    {
        $data = [
            'hash' => 'testhash',
            'name' => 'Test',
        ];

        $torrent = TorrentInfoV2::fromArray($data);

        $this->assertSame(0, $torrent->getSize());
        $this->assertSame(0.0, $torrent->getProgress());
        $this->assertSame(TorrentState::UNKNOWN, $torrent->getState());
        $this->assertSame(0, $torrent->getDownloadSpeed());
        $this->assertSame(0, $torrent->getUploadSpeed());
        $this->assertSame('', $torrent->getCategory());
        $this->assertSame('', $torrent->getTags());
        $this->assertSame(0.0, $torrent->getRatio());
        $this->assertSame(0, $torrent->getSeedCount());
        $this->assertSame(0, $torrent->getLeechCount());
    }

    /**
     * 测试状态判断方法
     */
    public function testStateCheckers(): void
    {
        $testCases = [
            ['state' => 'downloading', 'isDownloading' => true, 'isCompleted' => false, 'isUploading' => false],
            ['state' => 'uploading', 'isDownloading' => false, 'isCompleted' => true, 'isUploading' => true],
            ['state' => 'pausedUP', 'isDownloading' => false, 'isCompleted' => true, 'isUploading' => false],
            ['state' => 'completed', 'isDownloading' => false, 'isCompleted' => true, 'isUploading' => false],
            ['state' => 'unknown', 'isDownloading' => false, 'isCompleted' => false, 'isUploading' => false],
        ];

        foreach ($testCases as $case) {
            $torrent = TorrentInfoV2::fromArray([
                'hash' => 'test',
                'name' => 'test',
                'state' => $case['state'],
                'progress' => $case['state'] === 'downloading' ? 0.5 : 1.0,
            ]);

            $this->assertSame(
                $case['isDownloading'],
                $torrent->isDownloading(),
                "State {$case['state']} downloading check failed"
            );
            $this->assertSame(
                $case['isCompleted'],
                $torrent->isCompleted(),
                "State {$case['state']} completed check failed"
            );
            $this->assertSame(
                $case['isUploading'],
                $torrent->isUploading(),
                "State {$case['state']} uploading check failed"
            );
        }
    }

    /**
     * 测试格式化方法
     */
    public function testFormattingMethods(): void
    {
        $torrent = TorrentInfoV2::fromArray([
            'hash' => 'test',
            'name' => 'Test Torrent',
            'size' => 1073741824, // 1GB
            'progress' => 0.75,
            'dlspeed' => 1048576, // 1MB/s
            'upspeed' => 524288, // 512KB/s
            'ratio' => 1.5,
            'eta' => 3600, // 1 hour
            'added_on' => time() - 86400, // 1 day ago
        ]);

        $this->assertSame('1 GB', $torrent->getFormattedSize());
        $this->assertSame('75.00%', $torrent->getFormattedProgress());
        $this->assertSame('1 MB/s', $torrent->getFormattedDownloadSpeed());
        $this->assertSame('512 KB/s', $torrent->getFormattedUploadSpeed());
        $this->assertSame('1.500', $torrent->getFormattedRatio());
        $this->assertSame('1小时', $torrent->getFormattedEta());
        $this->assertStringContains('天', $torrent->getFormattedAge());
    }

    /**
     * 测试标签处理
     */
    public function testTagHandling(): void
    {
        $torrent = TorrentInfoV2::fromArray([
            'hash' => 'test',
            'name' => 'Test',
            'tags' => 'tag1,tag2,tag3',
        ]);

        $this->assertSame(['tag1', 'tag2', 'tag3'], $torrent->getTagArray());
        $this->assertTrue($torrent->hasTag('tag1'));
        $this->assertTrue($torrent->hasTag('tag2'));
        $this->assertTrue($torrent->hasTag('tag3'));
        $this->assertFalse($torrent->hasTag('nonexistent'));

        // 测试空标签
        $emptyTorrent = TorrentInfoV2::fromArray([
            'hash' => 'test2',
            'name' => 'Test2',
            'tags' => '',
        ]);

        $this->assertSame([], $emptyTorrent->getTagArray());
        $this->assertFalse($emptyTorrent->hasTag('any'));
    }

    /**
     * 测试分类处理
     */
    public function testCategoryHandling(): void
    {
        $torrent = TorrentInfoV2::fromArray([
            'hash' => 'test',
            'name' => 'Test',
            'category' => 'movies',
        ]);

        $this->assertTrue($torrent->hasCategory());
        $this->assertSame('movies', $torrent->getCategory());

        $emptyTorrent = TorrentInfoV2::fromArray([
            'hash' => 'test2',
            'name' => 'Test2',
            'category' => '',
        ]);

        $this->assertFalse($emptyTorrent->hasCategory());
        $this->assertSame('', $emptyTorrent->getCategory());
    }

    /**
     * 测试进度和完成度计算
     */
    public function testProgressCalculations(): void
    {
        $torrent = TorrentInfoV2::fromArray([
            'hash' => 'test',
            'name' => 'Test',
            'size' => 1000,
            'total_size' => 1000,
            'amount_left' => 250,
            'progress' => 0.75,
        ]);

        $this->assertSame(75.0, $torrent->getCompletionPercentage());
        $this->assertSame(250, $torrent->getRemainingSize());
        $this->assertTrue($torrent->isCompletedOrSeeding());

        $downloadingTorrent = TorrentInfoV2::fromArray([
            'hash' => 'test2',
            'name' => 'Test2',
            'size' => 1000,
            'total_size' => 1000,
            'amount_left' => 500,
            'progress' => 0.5,
            'state' => 'downloading',
        ]);

        $this->assertSame(50.0, $downloadingTorrent->getCompletionPercentage());
        $this->assertSame(500, $downloadingTorrent->getRemainingSize());
        $this->assertFalse($downloadingTorrent->isCompletedOrSeeding());
    }

    /**
     * 测试活动状态
     */
    public function testActivityStatus(): void
    {
        $activeTorrent = TorrentInfoV2::fromArray([
            'hash' => 'test',
            'name' => 'Test',
            'dlspeed' => 1048576,
            'upspeed' => 0,
        ]);

        $this->assertTrue($activeTorrent->hasActivity());
        $this->assertSame('中速', $activeTorrent->getSpeedRank());

        $highSpeedTorrent = TorrentInfoV2::fromArray([
            'hash' => 'test2',
            'name' => 'Test2',
            'dlspeed' => 10485760, // 10MB/s
            'upspeed' => 10485760, // 10MB/s
        ]);

        $this->assertTrue($highSpeedTorrent->hasActivity());
        $this->assertSame('高速', $highSpeedTorrent->getSpeedRank());

        $idleTorrent = TorrentInfoV2::fromArray([
            'hash' => 'test3',
            'name' => 'Test3',
            'dlspeed' => 0,
            'upspeed' => 0,
        ]);

        $this->assertFalse($idleTorrent->hasActivity());
        $this->assertSame('静止', $idleTorrent->getSpeedRank());
    }

    /**
     * 测试toArray方法
     */
    public function testToArray(): void
    {
        $data = [
            'hash' => 'testhash',
            'name' => 'Test Torrent',
            'size' => 1073741824,
            'progress' => 0.75,
            'state' => 'downloading',
            'category' => 'test',
            'tags' => 'tag1,tag2',
        ];

        $torrent = TorrentInfoV2::fromArray($data);
        $array = $torrent->toArray();

        $this->assertIsArray($array);
        $this->assertSame($data['hash'], $array['hash']);
        $this->assertSame($data['name'], $array['name']);
        $this->assertSame($data['size'], $array['size']);
        $this->assertSame($data['progress'], $array['progress']);
        $this->assertSame($data['state'], $array['state']);
        $this->assertSame($data['category'], $array['category']);
        $this->assertSame($data['tags'], $array['tags']);

        // 检查格式化字段是否存在
        $this->assertArrayHasKey('formatted_size', $array);
        $this->assertArrayHasKey('formatted_progress', $array);
        $this->assertArrayHasKey('tag_array', $array);
        $this->assertArrayHasKey('completion_percentage', $array);
    }

    /**
     * 测试JSON序列化
     */
    public function testJsonSerialization(): void
    {
        $data = [
            'hash' => 'testhash',
            'name' => 'Test Torrent',
            'size' => 1073741824,
            'progress' => 0.75,
            'state' => 'downloading',
        ];

        $torrent = TorrentInfoV2::fromArray($data);
        $json = json_encode($torrent);
        $decoded = json_decode($json, true);

        $this->assertIsArray($decoded);
        $this->assertSame($data['hash'], $decoded['hash']);
        $this->assertSame($data['name'], $decoded['name']);
        $this->assertSame($data['size'], $decoded['size']);
        $this->assertSame($data['progress'], $decoded['progress']);
        $this->assertSame($data['state'], $decoded['state']);
    }

    /**
     * 测试停滞状态判断
     */
    public function testStalledStatus(): void
    {
        $stalledDL = TorrentInfoV2::fromArray([
            'hash' => 'test',
            'name' => 'Test',
            'state' => 'stalledDL',
        ]);

        $this->assertTrue($stalledDL->isStalled());

        $stalledUP = TorrentInfoV2::fromArray([
            'hash' => 'test2',
            'name' => 'Test2',
            'state' => 'stalledUP',
        ]);

        $this->assertTrue($stalledUP->isStalled());

        $activeTorrent = TorrentInfoV2::fromArray([
            'hash' => 'test3',
            'name' => 'Test3',
            'state' => 'downloading',
        ]);

        $this->assertFalse($activeTorrent->isStalled());
    }

    /**
     * 测试错误状态判断
     */
    public function testErrorStatus(): void
    {
        $errorTorrent = TorrentInfoV2::fromArray([
            'hash' => 'test',
            'name' => 'Test',
            'state' => 'error',
        ]);

        $this->assertTrue($errorTorrent->hasError());

        $missingFilesTorrent = TorrentInfoV2::fromArray([
            'hash' => 'test2',
            'name' => 'Test2',
            'state' => 'missingFiles',
        ]);

        $this->assertTrue($missingFilesTorrent->hasError());

        $normalTorrent = TorrentInfoV2::fromArray([
            'hash' => 'test3',
            'name' => 'Test3',
            'state' => 'downloading',
        ]);

        $this->assertFalse($normalTorrent->hasError());
    }
}