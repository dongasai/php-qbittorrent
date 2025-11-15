<?php
declare(strict_types=1);

namespace PhpQbittorrent\Tests\Model\Sync;

use PHPUnit\Framework\TestCase;
use PhpQbittorrent\Model\Sync\MainData;

/**
 * MainData模型单元测试
 *
 * 测试主要数据同步模型的各种功能
 *
 * @package PhpQbittorrent\Tests\Model\Sync
 */
class MainDataTest extends TestCase
{
    /**
     * 测试构造函数和基本getter方法
     */
    public function testConstructorAndGetters(): void
    {
        $torrents = [
            'hash1' => ['state' => 'downloading'],
            'hash2' => ['state' => 'pausedUP']
        ];
        $torrentsRemoved = ['hash3'];
        $categories = [
            'movies' => ['savePath' => '/downloads/movies']
        ];
        $categoriesRemoved = ['tv'];
        $tags = ['movie', 'hd'];
        $tagsRemoved = ['documentary'];
        $serverState = [
            'dl_info_speed' => 1024000,
            'up_info_speed' => 512000
        ];

        $mainData = new MainData(
            15,
            false,
            $torrents,
            $torrentsRemoved,
            $categories,
            $categoriesRemoved,
            $tags,
            $tagsRemoved,
            $serverState
        );

        // 验证基本属性
        $this->assertEquals(15, $mainData->getRid());
        $this->assertFalse($mainData->isFullUpdate());
        $this->assertEquals($torrents, $mainData->getTorrents());
        $this->assertEquals($torrentsRemoved, $mainData->getTorrentsRemoved());
        $this->assertEquals($categories, $mainData->getCategories());
        $this->assertEquals($categoriesRemoved, $mainData->getCategoriesRemoved());
        $this->assertEquals($tags, $mainData->getTags());
        $this->assertEquals($tagsRemoved, $mainData->getTagsRemoved());
        $this->assertEquals($serverState, $mainData->getServerState());
    }

    /**
     * 测试完整更新情况
     */
    public function testFullUpdate(): void
    {
        $mainData = new MainData(20, true);

        $this->assertTrue($mainData->isFullUpdate());
        $this->assertEquals(20, $mainData->getRid());
    }

    /**
     * 测试空数据情况
     */
    public function testEmptyData(): void
    {
        $mainData = new MainData(0, true);

        $this->assertTrue($mainData->isFullUpdate());
        $this->assertEquals(0, $mainData->getRid());
        $this->assertEmpty($mainData->getTorrents());
        $this->assertEmpty($mainData->getTorrentsRemoved());
        $this->assertEmpty($mainData->getCategories());
        $this->assertEmpty($mainData->getCategoriesRemoved());
        $this->assertEmpty($mainData->getTags());
        $this->assertEmpty($mainData->getTagsRemoved());
        $this->assertNull($mainData->getServerState());
    }

    /**
     * 测试toArray方法
     */
    public function testToArray(): void
    {
        $data = [
            'rid' => 10,
            'full_update' => false,
            'torrents' => ['hash1' => ['state' => 'downloading']],
            'torrents_removed' => ['hash2'],
            'categories' => ['movies' => ['savePath' => '/downloads/movies']],
            'categories_removed' => ['tv'],
            'tags' => ['movie'],
            'tags_removed' => ['show'],
            'server_state' => ['dl_info_speed' => 2048000]
        ];

        $mainData = new MainData(
            $data['rid'],
            $data['full_update'],
            $data['torrents'],
            $data['torrents_removed'],
            $data['categories'],
            $data['categories_removed'],
            $data['tags'],
            $data['tags_removed'],
            $data['server_state']
        );

        $array = $mainData->toArray();

        $this->assertEquals($data, $array);
    }

    /**
     * 测试toArray方法 - 空数据
     */
    public function testToArrayWithEmptyData(): void
    {
        $mainData = new MainData(5, false);

        $array = $mainData->toArray();

        $this->assertEquals(5, $array['rid']);
        $this->assertFalse($array['full_update']);
        $this->assertEmpty($array['torrents']);
        $this->assertEmpty($array['torrents_removed']);
        $this->assertEmpty($array['categories']);
        $this->assertEmpty($array['categories_removed']);
        $this->assertEmpty($array['tags']);
        $this->assertEmpty($array['tags_removed']);
        $this->assertNull($array['server_state']);
    }
}