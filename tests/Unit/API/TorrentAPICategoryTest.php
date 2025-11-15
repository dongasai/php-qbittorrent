<?php
declare(strict_types=1);

namespace PhpQbittorrent\Tests\Unit\API;

use PhpQbittorrent\API\TorrentAPI;
use PhpQbittorrent\Tests\TestCase;
use PhpQbittorrent\Contract\TransportInterface;
use PhpQbittorrent\Exception\NetworkException;
use PhpQbittorrent\Exception\ApiRuntimeException;

/**
 * Torrent API 分类管理单元测试
 *
 * 测试分类的创建、编辑、删除和查询功能
 */
class TorrentAPICategoryTest extends TestCase
{
    /** @var TorrentAPI|\PHPUnit\Framework\MockObject\MockObject Torrent API模拟对象 */
    private $torrentAPI;

    /** @var TransportInterface|\PHPUnit\Framework\MockObject\MockObject 传输层模拟对象 */
    private $transport;

    protected function setUp(): void
    {
        // 不调用parent::setUp()以避免factory属性问题
        $this->transport = $this->createMock(TransportInterface::class);
        $this->torrentAPI = new TorrentAPI($this->transport);
    }

    /**
     * 测试获取所有分类 - 成功情况
     */
    public function testGetCategoriesSuccess(): void
    {
        // 模拟响应数据
        $expectedCategories = [
            'movies' => [
                'name' => 'movies',
                'savePath' => '/downloads/movies'
            ],
            'tv' => [
                'name' => 'tv',
                'savePath' => '/downloads/tv'
            ],
            'music' => [
                'name' => 'music',
                'savePath' => '/downloads/music'
            ]
        ];

        // 创建模拟响应
        $mockResponse = $this->createMockTransportResponse(200, [], json_encode($expectedCategories));
        
        // 设置传输层期望
        $this->transport->expects($this->once())
            ->method('get')
            ->with(
                $this->equalTo('/api/v2/torrents/categories'),
                $this->equalTo([]),
                $this->equalTo([
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept' => 'application/json',
                ])
            )
            ->willReturn($mockResponse);

        // 执行测试
        $result = $this->torrentAPI->getCategories();

        // 断言结果
        $this->assertEquals($expectedCategories, $result);
    }

    /**
     * 测试获取所有分类 - 网络错误
     */
    public function testGetCategoriesNetworkError(): void
    {
        // 设置传输层抛出网络异常
        $this->transport->expects($this->once())
            ->method('get')
            ->with(
                $this->equalTo('/api/v2/torrents/categories'),
                $this->equalTo([]),
                $this->equalTo([
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept' => 'application/json',
                ])
            )
            ->willThrowException(new NetworkException('Connection failed'));

        // 断言抛出API运行时异常
        $this->expectException(ApiRuntimeException::class);
        $this->expectExceptionMessage('Get categories failed due to network error: Connection failed');
        $this->expectExceptionCode('GET_CATEGORIES_NETWORK_ERROR');

        $this->torrentAPI->getCategories();
    }

    /**
     * 测试获取所有分类 - 空响应
     */
    public function testGetCategoriesEmptyResponse(): void
    {
        // 创建模拟响应
        $mockResponse = $this->createMockTransportResponse(200, [], 'invalid json');
        
        // 设置传输层期望
        $this->transport->expects($this->once())
            ->method('get')
            ->with(
                $this->equalTo('/api/v2/torrents/categories'),
                $this->equalTo([]),
                $this->equalTo([
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept' => 'application/json',
                ])
            )
            ->willReturn($mockResponse);

        // 执行测试
        $result = $this->torrentAPI->getCategories();

        // 断言结果为空数组
        $this->assertEquals([], $result);
    }

    /**
     * 测试创建分类 - 成功情况
     */
    public function testCreateCategorySuccess(): void
    {
        $categoryName = 'test_category';
        $savePath = '/downloads/test';

        // 创建模拟响应
        $mockResponse = $this->createMockTransportResponse(200, [], 'OK');
        
        // 设置传输层期望
        $this->transport->expects($this->once())
            ->method('post')
            ->with(
                $this->equalTo('/api/v2/torrents/createCategory'),
                $this->equalTo([
                    'category' => $categoryName,
                    'savePath' => $savePath
                ]),
                $this->equalTo([
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept' => 'application/json',
                ])
            )
            ->willReturn($mockResponse);

        // 执行测试
        $response = $this->torrentAPI->createCategory($categoryName, $savePath);

        // 断言响应
        $this->assertTrue($response->isSuccess());
    }

    /**
     * 测试创建分类 - 网络错误
     */
    public function testCreateCategoryNetworkError(): void
    {
        $categoryName = 'test_category';
        $savePath = '/downloads/test';

        // 设置传输层抛出网络异常
        $this->transport->expects($this->once())
            ->method('post')
            ->with(
                $this->equalTo('/api/v2/torrents/createCategory'),
                $this->equalTo([
                    'category' => $categoryName,
                    'savePath' => $savePath
                ]),
                $this->equalTo([
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept' => 'application/json',
                ])
            )
            ->willThrowException(new NetworkException('Connection failed'));

        // 断言抛出API运行时异常
        $this->expectException(ApiRuntimeException::class);
        $this->expectExceptionMessage('Create category failed due to network error: Connection failed');
        $this->expectExceptionCode('CREATE_CATEGORY_NETWORK_ERROR');

        $this->torrentAPI->createCategory($categoryName, $savePath);
    }

    /**
     * 测试编辑分类 - 成功情况
     */
    public function testEditCategorySuccess(): void
    {
        $categoryName = 'test_category';
        $newSavePath = '/downloads/test_updated';

        // 创建模拟响应
        $mockResponse = $this->createMockTransportResponse(200, [], 'OK');
        
        // 设置传输层期望
        $this->transport->expects($this->once())
            ->method('post')
            ->with(
                $this->equalTo('/api/v2/torrents/editCategory'),
                $this->equalTo([
                    'category' => $categoryName,
                    'savePath' => $newSavePath
                ]),
                $this->equalTo([
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept' => 'application/json',
                ])
            )
            ->willReturn($mockResponse);

        // 执行测试
        $response = $this->torrentAPI->editCategory($categoryName, $newSavePath);

        // 断言响应
        $this->assertTrue($response->isSuccess());
    }

    /**
     * 测试编辑分类 - 网络错误
     */
    public function testEditCategoryNetworkError(): void
    {
        $categoryName = 'test_category';
        $newSavePath = '/downloads/test_updated';

        // 设置传输层抛出网络异常
        $this->transport->expects($this->once())
            ->method('post')
            ->with(
                $this->equalTo('/api/v2/torrents/editCategory'),
                $this->equalTo([
                    'category' => $categoryName,
                    'savePath' => $newSavePath
                ]),
                $this->equalTo([
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept' => 'application/json',
                ])
            )
            ->willThrowException(new NetworkException('Connection failed'));

        // 断言抛出API运行时异常
        $this->expectException(ApiRuntimeException::class);
        $this->expectExceptionMessage('Edit category failed due to network error: Connection failed');
        $this->expectExceptionCode('EDIT_CATEGORY_NETWORK_ERROR');

        $this->torrentAPI->editCategory($categoryName, $newSavePath);
    }

    /**
     * 测试删除分类 - 成功情况
     */
    public function testRemoveCategoriesSuccess(): void
    {
        $categories = "category1\ncategory2\ncategory3";

        // 创建模拟响应
        $mockResponse = $this->createMockTransportResponse(200, [], 'OK');
        
        // 设置传输层期望
        $this->transport->expects($this->once())
            ->method('post')
            ->with(
                $this->equalTo('/api/v2/torrents/removeCategories'),
                $this->equalTo([
                    'categories' => $categories
                ]),
                $this->equalTo([
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept' => 'application/json',
                ])
            )
            ->willReturn($mockResponse);

        // 执行测试
        $response = $this->torrentAPI->removeCategories($categories);

        // 断言响应
        $this->assertTrue($response->isSuccess());
    }

    /**
     * 测试删除分类 - 网络错误
     */
    public function testRemoveCategoriesNetworkError(): void
    {
        $categories = "category1\ncategory2";

        // 设置传输层抛出网络异常
        $this->transport->expects($this->once())
            ->method('post')
            ->with(
                $this->equalTo('/api/v2/torrents/removeCategories'),
                $this->equalTo([
                    'categories' => $categories
                ]),
                $this->equalTo([
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept' => 'application/json',
                ])
            )
            ->willThrowException(new NetworkException('Connection failed'));

        // 断言抛出API运行时异常
        $this->expectException(ApiRuntimeException::class);
        $this->expectExceptionMessage('Remove categories failed due to network error: Connection failed');
        $this->expectExceptionCode('REMOVE_CATEGORIES_NETWORK_ERROR');

        $this->torrentAPI->removeCategories($categories);
    }

    /**
     * 测试设置种子分类 - 成功情况
     */
    public function testSetTorrentCategorySuccess(): void
    {
        $hashes = 'hash1|hash2|hash3';
        $category = 'test_category';

        // 创建模拟响应
        $mockResponse = $this->createMockTransportResponse(200, [], 'OK');
        
        // 设置传输层期望
        $this->transport->expects($this->once())
            ->method('post')
            ->with(
                $this->equalTo('/api/v2/torrents/setCategory'),
                $this->equalTo([
                    'hashes' => $hashes,
                    'category' => $category
                ]),
                $this->equalTo([
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept' => 'application/json',
                ])
            )
            ->willReturn($mockResponse);

        // 执行测试
        $response = $this->torrentAPI->setTorrentCategory($hashes, $category);

        // 断言响应
        $this->assertTrue($response->isSuccess());
    }

    /**
     * 测试设置种子分类 - 网络错误
     */
    public function testSetTorrentCategoryNetworkError(): void
    {
        $hashes = 'hash1|hash2';
        $category = 'test_category';

        // 设置传输层抛出网络异常
        $this->transport->expects($this->once())
            ->method('post')
            ->with(
                $this->equalTo('/api/v2/torrents/setCategory'),
                $this->equalTo([
                    'hashes' => $hashes,
                    'category' => $category
                ]),
                $this->equalTo([
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept' => 'application/json',
                ])
            )
            ->willThrowException(new NetworkException('Connection failed'));

        // 断言抛出API运行时异常
        $this->expectException(ApiRuntimeException::class);
        $this->expectExceptionMessage('Set torrent category failed due to network error: Connection failed');
        $this->expectExceptionCode('SET_TORRENT_CATEGORY_NETWORK_ERROR');

        $this->torrentAPI->setTorrentCategory($hashes, $category);
    }

    /**
     * 创建模拟传输响应对象
     */
    private function createMockTransportResponse(int $statusCode, array $headers, string $body): \PHPUnit\Framework\MockObject\MockObject
    {
        $response = $this->createMock(\PhpQbittorrent\Contract\TransportResponse::class);
        $response->method('getStatusCode')->willReturn($statusCode);
        $response->method('getHeaders')->willReturn($headers);
        $response->method('getBody')->willReturn($body);
        
        if (json_decode($body) !== null) {
            $response->method('getJson')->willReturn(json_decode($body, true));
        } else {
            $response->method('getJson')->willReturn(null);
        }
        
        return $response;
    }
}