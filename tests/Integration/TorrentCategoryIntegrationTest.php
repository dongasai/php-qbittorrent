<?php
declare(strict_types=1);

namespace PhpQbittorrent\Tests\Integration;

use PhpQbittorrent\Client;
use PhpQbittorrent\Tests\TestCase;
use PhpQbittorrent\Exception\AuthenticationException;
use PhpQbittorrent\Exception\NetworkException;

/**
 * Torrent 分类管理集成测试
 *
 * 使用真实的qBittorrent连接测试分类管理功能
 */
class TorrentCategoryIntegrationTest extends TestCase
{
    /** @var Client qBittorrent客户端 */
    private Client $client;

    /** @var array<string> 测试期间创建的分类列表 */
    private array $testCategories = [];

    protected function setUp(): void
    {
        parent::setUp();
        
        // 从环境变量获取配置
        $url = $_ENV['QBITTORRENT_URL'] ?? 'http://localhost:8080';
        $username = $_ENV['QBITTORRENT_USERNAME'] ?? 'admin';
        $password = $_ENV['QBITTORRENT_PASSWORD'] ?? 'adminpass';
        
        try {
            $this->client = new Client($url, $username, $password);
            $this->client->login();
        } catch (AuthenticationException $e) {
            $this->markTestSkipped('无法连接到qBittorrent: ' . $e->getMessage());
        } catch (NetworkException $e) {
            $this->markTestSkipped('网络连接失败: ' . $e->getMessage());
        }
    }

    protected function tearDown(): void
    {
        // 清理测试期间创建的分类
        if (!empty($this->testCategories)) {
            try {
                $categories = implode("\n", $this->testCategories);
                $this->client->torrent()->removeCategories($categories);
            } catch (\Exception $e) {
                // 忽略清理错误
            }
        }
        
        parent::tearDown();
    }

    /**
     * 测试获取所有分类
     */
    public function testGetCategories(): void
    {
        $categories = $this->client->torrent()->getCategories();
        
        $this->assertIsArray($categories);
        
        // 验证分类结构
        foreach ($categories as $name => $category) {
            $this->assertIsString($name);
            $this->assertIsArray($category);
            $this->assertArrayHasKey('name', $category);
            $this->assertArrayHasKey('savePath', $category);
            $this->assertEquals($name, $category['name']);
        }
    }

    /**
     * 测试创建分类
     */
    public function testCreateCategory(): void
    {
        $categoryName = 'test_category_' . uniqid();
        $savePath = '/tmp/test_downloads';
        
        // 创建分类
        $response = $this->client->torrent()->createCategory($categoryName, $savePath);
        $this->assertTrue($response->isSuccess());
        
        // 记录以便清理
        $this->testCategories[] = $categoryName;
        
        // 验证分类是否存在
        $categories = $this->client->torrent()->getCategories();
        $this->assertArrayHasKey($categoryName, $categories);
        $this->assertEquals($savePath, $categories[$categoryName]['savePath']);
    }

    /**
     * 测试编辑分类
     */
    public function testEditCategory(): void
    {
        $categoryName = 'test_category_edit_' . uniqid();
        $originalSavePath = '/tmp/original_path';
        $newSavePath = '/tmp/new_path';
        
        // 先创建分类
        $this->client->torrent()->createCategory($categoryName, $originalSavePath);
        $this->testCategories[] = $categoryName;
        
        // 编辑分类
        $response = $this->client->torrent()->editCategory($categoryName, $newSavePath);
        $this->assertTrue($response->isSuccess());
        
        // 验证编辑结果
        $categories = $this->client->torrent()->getCategories();
        $this->assertArrayHasKey($categoryName, $categories);
        $this->assertEquals($newSavePath, $categories[$categoryName]['savePath']);
    }

    /**
     * 测试删除分类
     */
    public function testRemoveCategories(): void
    {
        $categoryName1 = 'test_category_remove1_' . uniqid();
        $categoryName2 = 'test_category_remove2_' . uniqid();
        
        // 创建两个分类
        $this->client->torrent()->createCategory($categoryName1, '/tmp/path1');
        $this->client->torrent()->createCategory($categoryName2, '/tmp/path2');
        
        // 验证分类存在
        $categories = $this->client->torrent()->getCategories();
        $this->assertArrayHasKey($categoryName1, $categories);
        $this->assertArrayHasKey($categoryName2, $categories);
        
        // 删除分类
        $categoriesToDelete = "{$categoryName1}\n{$categoryName2}";
        $response = $this->client->torrent()->removeCategories($categoriesToDelete);
        $this->assertTrue($response->isSuccess());
        
        // 验证分类已删除
        $categories = $this->client->torrent()->getCategories();
        $this->assertArrayNotHasKey($categoryName1, $categories);
        $this->assertArrayNotHasKey($categoryName2, $categories);
    }

    /**
     * 测试设置种子分类
     */
    public function testSetTorrentCategory(): void
    {
        $categoryName = 'test_category_torrent_' . uniqid();
        $savePath = '/tmp/torrent_test';
        
        // 创建分类
        $this->client->torrent()->createCategory($categoryName, $savePath);
        $this->testCategories[] = $categoryName;
        
        // 添加一个测试种子（如果有的话）
        $torrents = $this->client->torrent()->getTorrentList();
        if (!empty($torrents)) {
            $firstTorrent = $torrents->getTorrents()[0];
            $hash = $firstTorrent->getHash();
            
            // 设置种子分类
            $response = $this->client->torrent()->setTorrentCategory($hash, $categoryName);
            $this->assertTrue($response->isSuccess());
            
            // 验证分类已设置
            $updatedTorrents = $this->client->torrent()->getTorrentList();
            $updatedTorrent = null;
            foreach ($updatedTorrents->getTorrents() as $torrent) {
                if ($torrent->getHash() === $hash) {
                    $updatedTorrent = $torrent;
                    break;
                }
            }
            
            if ($updatedTorrent) {
                $this->assertEquals($categoryName, $updatedTorrent->getCategory());
            }
        } else {
            $this->markTestSkipped('没有可用的种子进行测试');
        }
    }

    /**
     * 测试分类管理完整流程
     */
    public function testCategoryManagementWorkflow(): void
    {
        $categoryName = 'workflow_test_' . uniqid();
        $originalSavePath = '/tmp/workflow_original';
        $updatedSavePath = '/tmp/workflow_updated';
        
        // 1. 创建分类
        $createResponse = $this->client->torrent()->createCategory($categoryName, $originalSavePath);
        $this->assertTrue($createResponse->isSuccess());
        $this->testCategories[] = $categoryName;
        
        // 2. 验证分类存在
        $categories = $this->client->torrent()->getCategories();
        $this->assertArrayHasKey($categoryName, $categories);
        $this->assertEquals($originalSavePath, $categories[$categoryName]['savePath']);
        
        // 3. 编辑分类
        $editResponse = $this->client->torrent()->editCategory($categoryName, $updatedSavePath);
        $this->assertTrue($editResponse->isSuccess());
        
        // 4. 验证编辑结果
        $categories = $this->client->torrent()->getCategories();
        $this->assertEquals($updatedSavePath, $categories[$categoryName]['savePath']);
        
        // 5. 删除分类
        $removeResponse = $this->client->torrent()->removeCategories($categoryName);
        $this->assertTrue($removeResponse->isSuccess());
        
        // 从清理列表中移除（因为我们已经手动删除了）
        $key = array_search($categoryName, $this->testCategories);
        if ($key !== false) {
            unset($this->testCategories[$key]);
        }
        
        // 6. 验证分类已删除
        $categories = $this->client->torrent()->getCategories();
        $this->assertArrayNotHasKey($categoryName, $categories);
    }

    /**
     * 测试分类名称验证
     */
    public function testCategoryNameValidation(): void
    {
        // 测试空分类名称
        $this->expectException(\PhpQbittorrent\Exception\ValidationException::class);
        $this->client->torrent()->createCategory('', '/tmp/test');
    }

    /**
     * 测试分类路径验证
     */
    public function testCategoryPathValidation(): void
    {
        $categoryName = 'test_path_validation_' . uniqid();
        
        // 测试空路径（应该允许）
        $response = $this->client->torrent()->createCategory($categoryName, '');
        $this->assertTrue($response->isSuccess());
        $this->testCategories[] = $categoryName;
        
        // 验证分类创建成功
        $categories = $this->client->torrent()->getCategories();
        $this->assertArrayHasKey($categoryName, $categories);
    }
}