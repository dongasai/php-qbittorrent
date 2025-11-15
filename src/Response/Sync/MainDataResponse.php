<?php
declare(strict_types=1);

namespace PhpQbittorrent\Response\Sync;

use PhpQbittorrent\Response\AbstractResponse;
use PhpQbittorrent\Util\JsonHelper;
use PhpQbittorrent\Model\Sync\MainData;

/**
 * MainDataResponse - 主要数据同步响应
 *
 * 处理/api/v2/sync/maindata端点的响应数据
 *
 * @package PhpQbittorrent\Response\Sync
 */
class MainDataResponse extends AbstractResponse
{
    private ?MainData $mainData = null;

    /**
     * {@inheritdoc}
     */
    protected function parseData(): void
    {
        $data = $this->response->getBody();

        if (empty($data)) {
            return;
        }

        $parsedData = JsonHelper::decode($data);

        if (!is_array($parsedData)) {
            return;
        }

        $this->mainData = new MainData(
            $parsedData['rid'] ?? 0,
            $parsedData['full_update'] ?? false,
            $parsedData['torrents'] ?? [],
            $parsedData['torrents_removed'] ?? [],
            $parsedData['categories'] ?? [],
            $parsedData['categories_removed'] ?? [],
            $parsedData['tags'] ?? [],
            $parsedData['tags_removed'] ?? [],
            $parsedData['server_state'] ?? null
        );
    }

    /**
     * 获取主要数据
     *
     * @return MainData|null 如果解析失败则返回null
     */
    public function getMainData(): ?MainData
    {
        return $this->mainData;
    }

    /**
     * 获取响应ID
     *
     * @return int
     */
    public function getRid(): int
    {
        return $this->mainData ? $this->mainData->getRid() : 0;
    }

    /**
     * 是否为完整更新
     *
     * @return bool
     */
    public function isFullUpdate(): bool
    {
        return $this->mainData ? $this->mainData->isFullUpdate() : false;
    }

    /**
     * 获取torrents数据
     *
     * @return array key为torrent hash，value为torrent信息数组
     */
    public function getTorrents(): array
    {
        return $this->mainData ? $this->mainData->getTorrents() : [];
    }

    /**
     * 获取已删除的torrent哈希列表
     *
     * @return string[]
     */
    public function getTorrentsRemoved(): array
    {
        return $this->mainData ? $this->mainData->getTorrentsRemoved() : [];
    }

    /**
     * 获取分类信息
     *
     * @return array key为分类名称，value为分类信息数组
     */
    public function getCategories(): array
    {
        return $this->mainData ? $this->mainData->getCategories() : [];
    }

    /**
     * 获取已删除的分类名称列表
     *
     * @return string[]
     */
    public function getCategoriesRemoved(): array
    {
        return $this->mainData ? $this->mainData->getCategoriesRemoved() : [];
    }

    /**
     * 获取标签列表
     *
     * @return string[]
     */
    public function getTags(): array
    {
        return $this->mainData ? $this->mainData->getTags() : [];
    }

    /**
     * 获取已删除的标签列表
     *
     * @return string[]
     */
    public function getTagsRemoved(): array
    {
        return $this->mainData ? $this->mainData->getTagsRemoved() : [];
    }

    /**
     * 获取服务器状态信息
     *
     * @return array|null 服务器状态信息，如果不存在则为null
     */
    public function getServerState(): ?array
    {
        return $this->mainData ? $this->mainData->getServerState() : null;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        return $this->mainData ? $this->mainData->toArray() : [];
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }
}