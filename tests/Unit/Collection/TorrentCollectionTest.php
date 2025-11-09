<?php
declare(strict_types=1);

namespace PhpQbittorrent\Tests\Unit\Collection;

use PhpQbittorrent\Collection\TorrentCollection;
use PhpQbittorrent\Enum\TorrentState;
use PhpQbittorrent\Model\TorrentInfoV2;
use PhpQbittorrent\Tests\TestCase;

/**
 * TorrentCollection 单元测试
 */
class TorrentCollectionTest extends TestCase
{
    /**
     * 创建测试用的Torrent数据
     */
    private function createTestTorrents(): array
    {
        return [
            new TorrentInfoV2([
                'hash' => 'hash1',
                'name' => 'Test Torrent 1',
                'size' => 1073741824,
                'progress' => 1.0,
                'state' => 'uploading',
                'category' => 'movies',
                'tags' => 'hd,1080p',
                'dlspeed' => 0,
                'upspeed' => 1048576,
                'ratio' => 2.5,
                'added_on' => 1609459200,
            ]),
            new TorrentInfoV2([
                'hash' => 'hash2',
                'name' => 'Test Torrent 2',
                'size' => 2147483648,
                'progress' => 0.75,
                'state' => 'downloading',
                'category' => 'series',
                'tags' => 'tv,season1',
                'dlspeed' => 2097152,
                'upspeed' => 524288,
                'ratio' => 1.2,
                'added_on' => 1609545600,
            ]),
            new TorrentInfoV2([
                'hash' => 'hash3',
                'name' => 'Test Torrent 3',
                'size' => 536870912,
                'progress' => 1.0,
                'state' => 'pausedUP',
                'category' => 'movies',
                'tags' => 'hd,720p',
                'dlspeed' => 0,
                'upspeed' => 0,
                'ratio' => 1.0,
                'added_on' => 1609632000,
            ]),
            new TorrentInfoV2([
                'hash' => 'hash4',
                'name' => 'Test Torrent 4',
                'size' => 1073741824,
                'progress' => 0.25,
                'state' => 'stalledDL',
                'category' => '',
                'tags' => '',
                'dlspeed' => 0,
                'upspeed' => 0,
                'ratio' => 0.0,
                'added_on' => 1609718400,
            ]),
        ];
    }

    /**
     * 测试添加Torrent
     */
    public function testAddTorrent(): void
    {
        $collection = new TorrentCollection();
        $torrents = $this->createTestTorrents();

        $this->assertTrue($collection->isEmpty());
        $this->assertSame(0, $collection->count());

        $collection->addTorrent($torrents[0]);

        $this->assertFalse($collection->isEmpty());
        $this->assertSame(1, $collection->count());

        $collection->addTorrents([$torrents[1], $torrents[2]]);

        $this->assertSame(3, $collection->count());
    }

    /**
     * 测试从数组创建集合
     */
    public function testFromArray(): void
    {
        $data = [
            [
                'hash' => 'hash1',
                'name' => 'Test 1',
                'size' => 1000,
                'progress' => 1.0,
                'state' => 'completed',
            ],
            [
                'hash' => 'hash2',
                'name' => 'Test 2',
                'size' => 2000,
                'progress' => 0.5,
                'state' => 'downloading',
            ],
        ];

        $collection = TorrentCollection::fromArray($data);

        $this->assertSame(2, $collection->count());
        $this->assertSame('hash1', $collection->getFirst()->getHash());
        $this->assertSame('hash2', $collection->getLast()->getHash());
    }

    /**
     * 测试根据哈希查找
     */
    public function testFindByHash(): void
    {
        $collection = new TorrentCollection();
        $torrents = $this->createTestTorrents();
        $collection->addTorrents($torrents);

        $found = $collection->findByHash('hash2');
        $this->assertNotNull($found);
        $this->assertSame('hash2', $found->getHash());

        $notFound = $collection->findByHash('nonexistent');
        $this->assertNull($notFound);
    }

    /**
     * 测试根据名称查找
     */
    public function testFindByName(): void
    {
        $collection = new TorrentCollection();
        $torrents = $this->createTestTorrents();
        $collection->addTorrents($torrents);

        // 精确匹配
        $exact = $collection->findByName('Test Torrent 2', true);
        $this->assertSame(1, $exact->count());
        $this->assertSame('hash2', $exact->getFirst()->getHash());

        // 模糊匹配
        $fuzzy = $collection->findByName('Torrent', false);
        $this->assertSame(4, $fuzzy->count());

        // 未找到
        $notFound = $collection->findByName('Nonexistent', true);
        $this->assertSame(0, $notFound->count());
    }

    /**
     * 测试按分类过滤
     */
    public function testFilterByCategory(): void
    {
        $collection = new TorrentCollection();
        $torrents = $this->createTestTorrents();
        $collection->addTorrents($torrents);

        $movies = $collection->filterByCategory('movies');
        $this->assertSame(2, $movies->count());
        foreach ($movies as $torrent) {
            $this->assertSame('movies', $torrent->getCategory());
        }

        $noCategory = $collection->filterByCategory('', true);
        $this->assertSame(1, $noCategory->count());
        $this->assertSame('hash4', $noCategory->getFirst()->getHash());
    }

    /**
     * 测试按标签过滤
     */
    public function testFilterByTag(): void
    {
        $collection = new TorrentCollection();
        $torrents = $this->createTestTorrents();
        $collection->addTorrents($torrents);

        $hdTorrents = $collection->filterByTag('hd');
        $this->assertSame(2, $hdTorrents->count());

        $tvTorrents = $collection->filterByTag('tv');
        $this->assertSame(1, $tvTorrents->count());

        // 测试多标签过滤（包含任一标签）
        $multiTag = $collection->filterByTags(['hd', 'tv']);
        $this->assertSame(3, $multiTag->count());

        // 测试多标签过滤（包含所有标签）
        $allTags = $collection->filterByAllTags(['hd', '1080p']);
        $this->assertSame(1, $allTags->count());
        $this->assertSame('hash1', $allTags->getFirst()->getHash());
    }

    /**
     * 测试按状态过滤
     */
    public function testFilterByState(): void
    {
        $collection = new TorrentCollection();
        $torrents = $this->createTestTorrents();
        $collection->addTorrents($torrents);

        $downloading = $collection->filterByState(TorrentState::DOWNLOADING);
        $this->assertSame(1, $downloading->count());
        $this->assertSame('hash2', $downloading->getFirst()->getHash());

        $completed = $collection->filterByStates([
            TorrentState::UPLOADING,
            TorrentState::PAUSED_UP,
        ]);
        $this->assertSame(2, $completed->count());
    }

    /**
     * 测试按进度过滤
     */
    public function testFilterByProgress(): void
    {
        $collection = new TorrentCollection();
        $torrents = $this->createTestTorrents();
        $collection->addTorrents($torrents);

        $completed = $collection->filterByProgress(1.0, 1.0);
        $this->assertSame(2, $completed->count());

        $halfProgress = $collection->filterByProgress(0.5, 0.8);
        $this->assertSame(1, $halfProgress->count());
        $this->assertSame('hash2', $halfProgress->getFirst()->getHash());

        $lowProgress = $collection->filterByProgress(0.0, 0.5);
        $this->assertSame(2, $lowProgress->count());
    }

    /**
     * 测试按大小过滤
     */
    public function testFilterBySize(): void
    {
        $collection = new TorrentCollection();
        $torrents = $this->createTestTorrents();
        $collection->addTorrents($torrents);

        $largeTorrents = $collection->filterBySize(1073741824); // >= 1GB
        $this->assertSame(3, $largeTorrents->count());

        $mediumTorrents = $collection->filterBySize(536870912, 1073741824); // 512MB - 1GB
        $this->assertSame(2, $mediumTorrents->count());
    }

    /**
     * 测试按速度过滤
     */
    public function testFilterBySpeed(): void
    {
        $collection = new TorrentCollection();
        $torrents = $this->createTestTorrents();
        $collection->addTorrents($torrents);

        $downloading = $collection->filterByDownloadSpeed(1048576); // >= 1MB/s
        $this->assertSame(1, $downloading->count());

        $uploading = $collection->filterByUploadSpeed(524288); // >= 512KB/s
        $this->assertSame(2, $uploading->count());
    }

    /**
     * 测试按优先级过滤
     */
    public function testFilterByPriority(): void
    {
        $collection = new TorrentCollection();
        $torrents = $this->createTestTorrents();
        $collection->addTorrents($torrents);

        $highPriority = $collection->filterByPriority(1, '>=');
        $this->assertSame(4, $highPriority->count()); // 所有测试数据优先级都为1

        $lowPriority = $collection->filterByPriority(5, '>=');
        $this->assertSame(0, $lowPriority->count());
    }

    /**
     * 测试按添加时间过滤
     */
    public function testFilterByAddedTime(): void
    {
        $collection = new TorrentCollection();
        $torrents = $this->createTestTorrents();
        $collection->addTorrents($torrents);

        $recent = $collection->filterByAddedTime(1609600000); // 最近添加的
        $this->assertSame(2, $recent->count());
    }

    /**
     * 测试排序功能
     */
    public function testSorting(): void
    {
        $collection = new TorrentCollection();
        $torrents = $this->createTestTorrents();
        $collection->addTorrents($torrents);

        // 按名称排序
        $byName = $collection->sortByName(true);
        $names = $byName->map(fn($t) => $t->getName())->toArray();
        $this->assertSame(['Test Torrent 4', 'Test Torrent 3', 'Test Torrent 2', 'Test Torrent 1'], $names);

        // 按大小排序
        $bySize = $collection->sortBySize(true);
        $sizes = $bySize->map(fn($t) => $t->getSize())->toArray();
        $this->assertSame([2147483648, 1073741824, 1073741824, 536870912], $sizes);

        // 按进度排序
        $byProgress = $collection->sortByProgress(true);
        $progress = $byProgress->map(fn($t) => $t->getProgress())->toArray();
        $this->assertSame([1.0, 1.0, 0.75, 0.25], $progress);

        // 按下载速度排序
        $byDlSpeed = $collection->sortByDownloadSpeed(true);
        $dlSpeeds = $byDlSpeed->map(fn($t) => $t->getDownloadSpeed())->toArray();
        $this->assertSame([2097152, 0, 0, 0], $dlSpeeds);

        // 按上传速度排序
        $byUpSpeed = $collection->sortByUploadSpeed(true);
        $upSpeeds = $byUpSpeed->map(fn($t) => $t->getUploadSpeed())->toArray();
        $this->assertSame([1048576, 524288, 0, 0], $upSpeeds);

        // 按比率排序
        $byRatio = $collection->sortByRatio(true);
        $ratios = $byRatio->map(fn($t) => $t->getRatio())->toArray();
        $this->assertSame([2.5, 1.2, 1.0, 0.0], $ratios);
    }

    /**
     * 测试状态获取方法
     */
    public function testStateGetters(): void
    {
        $collection = new TorrentCollection();
        $torrents = $this->createTestTorrents();
        $collection->addTorrents($torrents);

        $active = $collection->getActive();
        $this->assertSame(1, $active->count());

        $completed = $collection->getCompleted();
        $this->assertSame(2, $completed->count());

        $downloading = $collection->getDownloading();
        $this->assertSame(1, $downloading->count());

        $uploading = $collection->getUploading();
        $this->assertSame(1, $uploading->count());

        $paused = $collection->getPaused();
        $this->assertSame(1, $paused->count());

        $stalled = $collection->getStalled();
        $this->assertSame(1, $stalled->count());

        $errored = $collection->getErrored();
        $this->assertSame(0, $errored->count());
    }

    /**
     * 测试统计方法
     */
    public function testStatistics(): void
    {
        $collection = new TorrentCollection();
        $torrents = $this->createTestTorrents();
        $collection->addTorrents($torrents);

        $this->assertSame(4, $collection->count());
        $this->assertSame(1073741824 + 2147483648 + 536870912 + 1073741824, $collection->getTotalSize());
        $this->assertSame(2097152, $collection->getTotalDownloadSpeed());
        $this->assertSame(1572864, $collection->getTotalUploadSpeed());
        $this->assertSame(0.75, $collection->getAverageProgress());
        $this->assertSame(1.175, $collection->getAverageRatio());

        $stats = $collection->getStatistics();
        $this->assertArrayHasKey('total_count', $stats);
        $this->assertArrayHasKey('active_count', $stats);
        $this->assertArrayHasKey('completed_count', $stats);
        $this->assertArrayHasKey('downloading_count', $stats);
        $this->assertArrayHasKey('uploading_count', $stats);
        $this->assertArrayHasKey('paused_count', $stats);
        $this->assertArrayHasKey('stalled_count', $stats);
        $this->assertArrayHasKey('errored_count', $stats);
        $this->assertArrayHasKey('total_size', $stats);
        $this->assertArrayHasKey('total_download_speed', $stats);
        $this->assertArrayHasKey('total_upload_speed', $stats);
        $this->assertArrayHasKey('average_progress', $stats);
        $this->assertArrayHasKey('average_ratio', $stats);
        $this->assertArrayHasKey('categories', $stats);
        $this->assertArrayHasKey('tags', $stats);
    }

    /**
     * 测试分类和标签获取
     */
    public function testGetCategoriesAndTags(): void
    {
        $collection = new TorrentCollection();
        $torrents = $this->createTestTorrents();
        $collection->addTorrents($torrents);

        $categories = $collection->getAllCategories();
        $this->assertSame(['movies', 'series'], $categories);

        $tags = $collection->getAllTags();
        $this->assertSame(['1080p', '720p', 'hd', 'season1', 'tv'], $tags);
    }

    /**
     * 测试分组功能
     */
    public function testGrouping(): void
    {
        $collection = new TorrentCollection();
        $torrents = $this->createTestTorrents();
        $collection->addTorrents($torrents);

        $byCategory = $collection->groupByCategory();
        $this->assertArrayHasKey('movies', $byCategory);
        $this->assertArrayHasKey('series', $byCategory);
        $this->assertArrayHasKey('未分类', $byCategory);
        $this->assertSame(2, $byCategory['movies']->count());
        $this->assertSame(1, $byCategory['series']->count());
        $this->assertSame(1, $byCategory['未分类']->count());

        $byState = $collection->groupByState();
        $this->assertArrayHasKey('uploading', $byState);
        $this->assertArrayHasKey('downloading', $byState);
        $this->assertArrayHasKey('pausedUP', $byState);
        $this->assertArrayHasKey('stalledDL', $byState);
    }

    /**
     * 测试toArray方法
     */
    public function testToArray(): void
    {
        $collection = new TorrentCollection();
        $torrents = $this->createTestTorrents();
        $collection->addTorrents($torrents);

        $array = $collection->toArray();
        $this->assertIsArray($array);
        $this->assertSame(4, count($array));
        $this->assertArrayHasKey('hash', $array[0]);
        $this->assertArrayHasKey('name', $array[0]);
        $this->assertArrayHasKey('size', $array[0]);
    }

    /**
     * 测试空集合
     */
    public function testEmptyCollection(): void
    {
        $collection = TorrentCollection::empty();
        $this->assertTrue($collection->isEmpty());
        $this->assertSame(0, $collection->count());
        $this->assertSame([], $collection->getAllCategories());
        $this->assertSame([], $collection->getAllTags());
        $this->assertSame(0, $collection->getTotalSize());
        $this->assertSame(0, $collection->getTotalDownloadSpeed());
        $this->assertSame(0, $collection->getTotalUploadSpeed());
        $this->assertSame(0.0, $collection->getAverageProgress());
    }

    /**
     * 测试链式调用
     */
    public function testChaining(): void
    {
        $collection = new TorrentCollection();
        $torrents = $this->createTestTorrents();
        $collection->addTorrents($torrents);

        $result = $collection
            ->filterByCategory('movies')
            ->filterByTag('hd')
            ->sortBySize(true);

        $this->assertSame(2, $result->count());
        $this->assertSame(['hash1', 'hash3'], $result->map(fn($t) => $t->getHash())->toArray());
    }
}