<?php
declare(strict_types=1);

namespace PhpQbittorrent\Model\Sync;

/**
 * MainData - 主要数据同步模型
 *
 * 表示qBittorrent的主要同步数据，包括响应ID、更新类型和各种数据
 *
 * @package PhpQbittorrent\Model\Sync
 */
class MainData
{
    private int $rid;
    private bool $fullUpdate;
    private array $torrents;
    private array $torrentsRemoved;
    private array $categories;
    private array $categoriesRemoved;
    private array $tags;
    private array $tagsRemoved;
    private ?array $serverState;

    /**
     * 构造函数
     *
     * @param int $rid 响应ID
     * @param bool $fullUpdate 是否为完整更新
     * @param array $torrents torrents数据，key为hash，value为torrent信息
     * @param array $torrentsRemoved 已删除的torrent哈希列表
     * @param array $categories 分类信息
     * @param array $categoriesRemoved 已删除的分类名称列表
     * @param array $tags 标签列表
     * @param array $tagsRemoved 已删除的标签列表
     * @param array|null $serverState 服务器状态信息
     */
    public function __construct(
        int $rid,
        bool $fullUpdate,
        array $torrents = [],
        array $torrentsRemoved = [],
        array $categories = [],
        array $categoriesRemoved = [],
        array $tags = [],
        array $tagsRemoved = [],
        ?array $serverState = null
    ) {
        $this->rid = $rid;
        $this->fullUpdate = $fullUpdate;
        $this->torrents = $torrents;
        $this->torrentsRemoved = $torrentsRemoved;
        $this->categories = $categories;
        $this->categoriesRemoved = $categoriesRemoved;
        $this->tags = $tags;
        $this->tagsRemoved = $tagsRemoved;
        $this->serverState = $serverState;
    }

    /**
     * 获取响应ID
     */
    public function getRid(): int
    {
        return $this->rid;
    }

    /**
     * 是否为完整更新
     */
    public function isFullUpdate(): bool
    {
        return $this->fullUpdate;
    }

    /**
     * 获取torrents数据
     *
     * @return array key为torrent hash，value为torrent信息数组
     */
    public function getTorrents(): array
    {
        return $this->torrents;
    }

    /**
     * 获取已删除的torrent哈希列表
     *
     * @return string[]
     */
    public function getTorrentsRemoved(): array
    {
        return $this->torrentsRemoved;
    }

    /**
     * 获取分类信息
     *
     * @return array key为分类名称，value为分类信息数组
     */
    public function getCategories(): array
    {
        return $this->categories;
    }

    /**
     * 获取已删除的分类名称列表
     *
     * @return string[]
     */
    public function getCategoriesRemoved(): array
    {
        return $this->categoriesRemoved;
    }

    /**
     * 获取标签列表
     *
     * @return string[]
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * 获取已删除的标签列表
     *
     * @return string[]
     */
    public function getTagsRemoved(): array
    {
        return $this->tagsRemoved;
    }

    /**
     * 获取服务器状态信息
     *
     * @return array|null 服务器状态信息，如果不存在则为null
     */
    public function getServerState(): ?array
    {
        return $this->serverState;
    }

    /**
     * 转换为数组
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'rid' => $this->rid,
            'full_update' => $this->fullUpdate,
            'torrents' => $this->torrents,
            'torrents_removed' => $this->torrentsRemoved,
            'categories' => $this->categories,
            'categories_removed' => $this->categoriesRemoved,
            'tags' => $this->tags,
            'tags_removed' => $this->tagsRemoved,
            'server_state' => $this->serverState,
        ];
    }
}