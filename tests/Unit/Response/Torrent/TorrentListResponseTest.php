<?php
declare(strict_types=1);

namespace PhpQbittorrent\Tests\Unit\Response\Torrent;

use PhpQbittorrent\Collection\TorrentCollection;
use PhpQbittorrent\Model\TorrentInfoV2;
use PhpQbittorrent\Response\Torrent\TorrentListResponse;
use PhpQbittorrent\Tests\TestCase;

/**
 * TorrentListResponse 单元测试
 */
class TorrentListResponseTest extends TestCase
{
    /**
     * 创建测试用的Torrent数据
     */
    private function createTestTorrentsData(): array
    {
        return [
            [
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
            ],
            [
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
            ],
            [
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
            ],
        ];
    }

    /**
     * 测试创建成功的响应
     */
    public function testCreateSuccessResponse(): void
    {
        $torrentsData = $this->createTestTorrentsData();
        $torrents = TorrentCollection::fromArray($torrentsData);
        $statistics = $torrents->getStatistics();

        $response = TorrentListResponse::success(
            $torrents,
            ['Content-Type' => 'application/json'],
            200,
            json_encode($torrentsData),
            $statistics
        );

        $this->assertTrue($response->isSuccess());
        $this->assertSame(3, $response->getTotalCount());
        $this->assertTrue($response->hasTorrents());
        $this->assertSame($torrents, $response->getTorrents());
        $this->assertSame($statistics, $response->getStatistics());
        $this->assertSame(200, $response->getStatusCode());
    }

    /**
     * 测试创建失败的响应
     */
    public function testCreateFailureResponse(): void
    {
        $errors = ['Network error', 'Authentication failed'];
        $response = TorrentListResponse::failure(
            $errors,
            ['Content-Type' => 'application/json'],
            500,
            'Internal Server Error'
        );

        $this->assertFalse($response->isSuccess());
        $this->assertSame($errors, $response->getErrors());
        $this->assertFalse($response->hasTorrents());
        $this->assertSame(0, $response->getTotalCount());
        $this->assertTrue($response->getTorrents()->isEmpty());
        $this->assertSame(500, $response->getStatusCode());
    }

    /**
     * 测试从数组创建响应
     */
    public function testFromArray(): void
    {
        $torrentsData = $this->createTestTorrentsData();
        $data = [
            'success' => true,
            'data' => [
                'torrents' => $torrentsData,
                'filter' => 'all',
                'category' => '',
                'tag' => '',
                'sort' => 'name',
                'reverse' => false,
                'limit' => 100,
                'offset' => 0,
                'statistics' => TorrentCollection::fromArray($torrentsData)->getStatistics(),
            ],
            'errors' => [],
            'statusCode' => 200,
            'rawResponse' => json_encode($torrentsData),
            'headers' => ['Content-Type' => 'application/json'],
        ];

        $response = TorrentListResponse::fromArray($data);

        $this->assertTrue($response->isSuccess());
        $this->assertSame(3, $response->getTotalCount());
        $this->assertSame('all', $response->getFilter());
        $this->assertSame('', $response->getCategory());
        $this->assertSame('', $response->getTag());
        $this->assertSame('name', $response->getSort());
        $this->assertFalse($response->isReverse());
        $this->assertSame(100, $response->getLimit());
        $this->assertSame(0, $response->getOffset());
    }

    /**
     * 测试从API响应创建响应对象
     */
    public function testFromApiResponse(): void
    {
        $torrentsData = $this->createTestTorrentsData();
        $requestParams = [
            'filter' => 'active',
            'category' => 'movies',
            'sort' => 'progress',
            'reverse' => true,
            'limit' => 50,
            'offset' => 10,
        ];

        $response = TorrentListResponse::fromApiResponse($torrentsData, $requestParams);

        $this->assertTrue($response->isSuccess());
        $this->assertSame(3, $response->getTotalCount());
        $this->assertSame('active', $response->getFilter());
        $this->assertSame('movies', $response->getCategory());
        $this->assertSame('progress', $response->getSort());
        $this->assertTrue($response->isReverse());
        $this->assertSame(50, $response->getLimit());
        $this->assertSame(10, $response->getOffset());
    }

    /**
     * 测试根据哈希获取Torrent
     */
    public function testGetTorrentByHash(): void
    {
        $torrentsData = $this->createTestTorrentsData();
        $response = TorrentListResponse::fromApiResponse($torrentsData);

        $torrent = $response->getTorrentByHash('hash2');
        $this->assertNotNull($torrent);
        $this->assertSame('hash2', $torrent->getHash());
        $this->assertSame('Test Torrent 2', $torrent->getName());

        $notFound = $response->getTorrentByHash('nonexistent');
        $this->assertNull($notFound);
    }

    /**
     * 测试检查是否包含指定哈希
     */
    public function testHasHash(): void
    {
        $torrentsData = $this->createTestTorrentsData();
        $response = TorrentListResponse::fromApiResponse($torrentsData);

        $this->assertTrue($response->hasHash('hash1'));
        $this->assertTrue($response->hasHash('hash2'));
        $this->assertTrue($response->hasHash('hash3'));
        $this->assertFalse($response->hasHash('nonexistent'));
    }

    /**
     * 测试获取指定分类的Torrent
     */
    public function testGetTorrentsByCategory(): void
    {
        $torrentsData = $this->createTestTorrentsData();
        $response = TorrentListResponse::fromApiResponse($torrentsData);

        $movies = $response->getTorrentsByCategory('movies');
        $this->assertSame(2, $movies->count());
        foreach ($movies as $torrent) {
            $this->assertSame('movies', $torrent->getCategory());
        }

        $series = $response->getTorrentsByCategory('series');
        $this->assertSame(1, $series->count());
        $this->assertSame('hash2', $series->getFirst()->getHash());

        $emptyCategory = $response->getTorrentsByCategory('nonexistent');
        $this->assertSame(0, $emptyCategory->count());
    }

    /**
     * 测试获取指定标签的Torrent
     */
    public function testGetTorrentsByTag(): void
    {
        $torrentsData = $this->createTestTorrentsData();
        $response = TorrentListResponse::fromApiResponse($torrentsData);

        $hdTorrents = $response->getTorrentsByTag('hd');
        $this->assertSame(2, $hdTorrents->count());

        $tvTorrents = $response->getTorrentsByTag('tv');
        $this->assertSame(1, $tvTorrents->count());
        $this->assertSame('hash2', $tvTorrents->getFirst()->getHash());

        $nonexistentTag = $response->getTorrentsByTag('nonexistent');
        $this->assertSame(0, $nonexistentTag->count());
    }

    /**
     * 测试获取活跃的Torrent
     */
    public function testGetActiveTorrents(): void
    {
        $torrentsData = $this->createTestTorrentsData();
        $response = TorrentListResponse::fromApiResponse($torrentsData);

        $activeTorrents = $response->getActiveTorrents();
        $this->assertSame(1, $activeTorrents->count());
        $this->assertSame('hash2', $activeTorrents->getFirst()->getHash());

        $this->assertTrue($response->hasActiveTorrents());
    }

    /**
     * 测试获取已完成的Torrent
     */
    public function testGetCompletedTorrents(): void
    {
        $torrentsData = $this->createTestTorrentsData();
        $response = TorrentListResponse::fromApiResponse($torrentsData);

        $completedTorrents = $response->getCompletedTorrents();
        $this->assertSame(2, $completedTorrents->count());

        $this->assertTrue($response->hasTorrents());
    }

    /**
     * 测试获取正在下载的Torrent
     */
    public function testGetDownloadingTorrents(): void
    {
        $torrentsData = $this->createTestTorrentsData();
        $response = TorrentListResponse::fromApiResponse($torrentsData);

        $downloadingTorrents = $response->getDownloadingTorrents();
        $this->assertSame(1, $downloadingTorrents->count());
        $this->assertSame('hash2', $downloadingTorrents->getFirst()->getHash());

        $this->assertTrue($response->hasDownloadingTorrents());
    }

    /**
     * 测试获取正在上传的Torrent
     */
    public function testGetUploadingTorrents(): void
    {
        $torrentsData = $this->createTestTorrentsData();
        $response = TorrentListResponse::fromApiResponse($torrentsData);

        $uploadingTorrents = $response->getUploadingTorrents();
        $this->assertSame(1, $uploadingTorrents->count());
        $this->assertSame('hash1', $uploadingTorrents->getFirst()->getHash());

        $this->assertTrue($response->hasUploadingTorrents());
    }

    /**
     * 测试获取有错误的Torrent
     */
    public function testGetErroredTorrents(): void
    {
        $torrentsData = $this->createTestTorrentsData();
        $response = TorrentListResponse::fromApiResponse($torrentsData);

        $erroredTorrents = $response->getErroredTorrents();
        $this->assertSame(0, $erroredTorrents->count());

        $this->assertFalse($response->hasErroredTorrents());
    }

    /**
     * 测试获取摘要信息
     */
    public function testGetSummary(): void
    {
        $torrentsData = $this->createTestTorrentsData();
        $response = TorrentListResponse::fromApiResponse($torrentsData, [
            'filter' => 'all',
            'category' => 'movies',
            'sort' => 'name',
            'reverse' => false,
            'limit' => 100,
            'offset' => 0,
        ]);

        $summary = $response->getSummary();

        $this->assertIsArray($summary);
        $this->assertArrayHasKey('success', $summary);
        $this->assertArrayHasKey('total_count', $summary);
        $this->assertArrayHasKey('has_torrents', $summary);
        $this->assertArrayHasKey('active_count', $summary);
        $this->assertArrayHasKey('completed_count', $summary);
        $this->assertArrayHasKey('downloading_count', $summary);
        $this->assertArrayHasKey('uploading_count', $summary);
        $this->assertArrayHasKey('errored_count', $summary);
        $this->assertArrayHasKey('total_size', $summary);
        $this->assertArrayHasKey('total_download_speed', $summary);
        $this->assertArrayHasKey('total_upload_speed', $summary);
        $this->assertArrayHasKey('average_progress', $summary);
        $this->assertArrayHasKey('filter', $summary);
        $this->assertArrayHasKey('category', $summary);
        $this->assertArrayHasKey('sort', $summary);
        $this->assertArrayHasKey('reverse', $summary);
        $this->assertArrayHasKey('limit', $summary);
        $this->assertArrayHasKey('offset', $summary);
        $this->assertArrayHasKey('status_code', $summary);
        $this->assertArrayHasKey('error_count', $summary);

        $this->assertTrue($summary['success']);
        $this->assertSame(3, $summary['total_count']);
        $this->assertTrue($summary['has_torrents']);
        $this->assertSame(1, $summary['active_count']);
        $this->assertSame(2, $summary['completed_count']);
        $this->assertSame(1, $summary['downloading_count']);
        $this->assertSame(1, $summary['uploading_count']);
        $this->assertSame(0, $summary['errored_count']);
        $this->assertSame('all', $summary['filter']);
        $this->assertSame('movies', $summary['category']);
        $this->assertSame('name', $summary['sort']);
        $this->assertFalse($summary['reverse']);
        $this->assertSame(100, $summary['limit']);
        $this->assertSame(0, $summary['offset']);
        $this->assertSame(200, $summary['status_code']);
        $this->assertSame(0, $summary['error_count']);
    }

    /**
     * 测试获取格式化的统计摘要
     */
    public function testGetFormattedSummary(): void
    {
        $torrentsData = $this->createTestTorrentsData();
        $response = TorrentListResponse::fromApiResponse($torrentsData);

        $formattedSummary = $response->getFormattedSummary();

        $this->assertIsArray($formattedSummary);
        $this->assertArrayHasKey('torrent_count', $formattedSummary);
        $this->assertArrayHasKey('active_torrents', $formattedSummary);
        $this->assertArrayHasKey('completed_torrents', $formattedSummary);
        $this->assertArrayHasKey('downloading_torrents', $formattedSummary);
        $this->assertArrayHasKey('uploading_torrents', $formattedSummary);
        $this->assertArrayHasKey('paused_torrents', $formattedSummary);
        $this->assertArrayHasKey('errored_torrents', $formattedSummary);
        $this->assertArrayHasKey('total_size', $formattedSummary);
        $this->assertArrayHasKey('download_speed', $formattedSummary);
        $this->assertArrayHasKey('upload_speed', $formattedSummary);
        $this->assertArrayHasKey('average_progress', $formattedSummary);
        $this->assertArrayHasKey('categories', $formattedSummary);
        $this->assertArrayHasKey('tags', $formattedSummary);

        $this->assertSame(3, $formattedSummary['torrent_count']);
        $this->assertSame(1, $formattedSummary['active_torrents']);
        $this->assertSame(2, $formattedSummary['completed_torrents']);
        $this->assertSame(1, $formattedSummary['downloading_torrents']);
        $this->assertSame(1, $formattedSummary['uploading_torrents']);
        $this->assertSame(1, $formattedSummary['paused_torrents']);
        $this->assertSame(0, $formattedSummary['errored_torrents']);
        $this->assertStringContains('GB', $formattedSummary['total_size']);
        $this->assertStringContains('MB/s', $formattedSummary['download_speed']);
        $this->assertStringContains('MB/s', $formattedSummary['upload_speed']);
        $this->assertStringContains('%', $formattedSummary['average_progress']);
    }

    /**
     * 测试验证功能
     */
    public function testValidation(): void
    {
        $torrentsData = $this->createTestTorrentsData();
        $response = TorrentListResponse::fromApiResponse($torrentsData, [
            'offset' => 0,
            'limit' => 100,
            'sort' => 'name',
        ]);

        $validation = $response->validate();
        $this->assertTrue($validation->isValid());

        // 测试无效的偏移量
        $invalidResponse = TorrentListResponse::fromApiResponse($torrentsData, [
            'offset' => -1,
            'limit' => 100,
        ]);

        $invalidValidation = $invalidResponse->validate();
        $this->assertFalse($invalidValidation->isValid());
        $this->assertContains('偏移量不能为负数', $invalidValidation->getErrors());

        // 测试无效的限制数量
        $invalidResponse2 = TorrentListResponse::fromApiResponse($torrentsData, [
            'offset' => 0,
            'limit' => 0,
        ]);

        $invalidValidation2 = $invalidResponse2->validate();
        $this->assertFalse($invalidValidation2->isValid());
        $this->assertContains('限制数量必须大于0', $invalidValidation2->getErrors());
    }

    /**
     * 测试toArray方法
     */
    public function testToArray(): void
    {
        $torrentsData = $this->createTestTorrentsData();
        $requestParams = [
            'filter' => 'all',
            'category' => 'movies',
            'sort' => 'name',
            'reverse' => false,
            'limit' => 100,
            'offset' => 0,
        ];

        $response = TorrentListResponse::fromApiResponse($torrentsData, $requestParams);
        $array = $response->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('success', $array);
        $this->assertArrayHasKey('torrents', $array);
        $this->assertArrayHasKey('totalCount', $array);
        $this->assertArrayHasKey('filter', $array);
        $this->assertArrayHasKey('category', $array);
        $this->assertArrayHasKey('tag', $array);
        $this->assertArrayHasKey('sort', $array);
        $this->assertArrayHasKey('reverse', $array);
        $this->assertArrayHasKey('limit', $array);
        $this->assertArrayHasKey('offset', $array);
        $this->assertArrayHasKey('statistics', $array);

        $this->assertTrue($array['success']);
        $this->assertSame(3, $array['totalCount']);
        $this->assertSame('all', $array['filter']);
        $this->assertSame('movies', $array['category']);
        $this->assertSame('name', $array['sort']);
        $this->assertFalse($array['reverse']);
        $this->assertSame(100, $array['limit']);
        $this->assertSame(0, $array['offset']);
    }

    /**
     * 测试空响应
     */
    public function testEmptyResponse(): void
    {
        $response = TorrentListResponse::fromApiResponse([]);

        $this->assertTrue($response->isSuccess());
        $this->assertFalse($response->hasTorrents());
        $this->assertSame(0, $response->getTotalCount());
        $this->assertTrue($response->getTorrents()->isEmpty());
        $this->assertFalse($response->hasActiveTorrents());
        $this->assertFalse($response->hasDownloadingTorrents());
        $this->assertFalse($response->hasUploadingTorrents());
        $this->assertFalse($response->hasErroredTorrents());

        $summary = $response->getSummary();
        $this->assertFalse($summary['has_torrents']);
        $this->assertSame(0, $summary['total_count']);
    }
}