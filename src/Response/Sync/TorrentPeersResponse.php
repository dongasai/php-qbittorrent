<?php
declare(strict_types=1);

namespace PhpQbittorrent\Response\Sync;

use PhpQbittorrent\Response\AbstractResponse;
use PhpQbittorrent\Util\JsonHelper;
use PhpQbittorrent\Model\Sync\TorrentPeers;

/**
 * TorrentPeersResponse - Torrent Peers同步响应
 *
 * 处理/api/v2/sync/torrentPeers端点的响应数据
 *
 * @package PhpQbittorrent\Response\Sync
 */
class TorrentPeersResponse extends AbstractResponse
{
    private ?TorrentPeers $torrentPeers = null;

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

        // API文档提到这个响应还是TODO，但我们根据实际情况来设计
        $this->torrentPeers = TorrentPeers::fromArray([
            'hash' => $parsedData['hash'] ?? '',
            'rid' => $parsedData['rid'] ?? 0,
            'full_update' => $parsedData['full_update'] ?? false,
            'peers' => $parsedData['peers'] ?? []
        ]);
    }

    /**
     * 获取Torrent Peers数据
     *
     * @return TorrentPeers|null 如果解析失败则返回null
     */
    public function getTorrentPeers(): ?TorrentPeers
    {
        return $this->torrentPeers;
    }

    /**
     * 获取Torrent哈希值
     *
     * @return string
     */
    public function getHash(): string
    {
        return $this->torrentPeers ? $this->torrentPeers->getHash() : '';
    }

    /**
     * 获取所有Peers
     *
     * @return array
     */
    public function getPeers(): array
    {
        return $this->torrentPeers ? $this->torrentPeers->getPeers() : [];
    }

    /**
     * 获取响应ID
     *
     * @return int
     */
    public function getRid(): int
    {
        return $this->torrentPeers ? $this->torrentPeers->getRid() : 0;
    }

    /**
     * 是否为完整更新
     *
     * @return bool
     */
    public function isFullUpdate(): bool
    {
        return $this->torrentPeers ? $this->torrentPeers->isFullUpdate() : false;
    }

    /**
     * 获取Peer数量
     *
     * @return int
     */
    public function count(): int
    {
        return $this->torrentPeers ? $this->torrentPeers->count() : 0;
    }

    /**
     * 按国家分组获取Peers
     *
     * @return array key为国家代码，value为Peer数组
     */
    public function groupByCountry(): array
    {
        return $this->torrentPeers ? $this->torrentPeers->groupByCountry() : [];
    }

    /**
     * 按客户端分组获取Peers
     *
     * @return array key为客户端名称，value为Peer数组
     */
    public function groupByClient(): array
    {
        return $this->torrentPeers ? $this->torrentPeers->groupByClient() : [];
    }

    /**
     * 获取总下载速度
     *
     * @return int 总下载速度（bytes/s）
     */
    public function getTotalDownloadSpeed(): int
    {
        return $this->torrentPeers ? $this->torrentPeers->getTotalDownloadSpeed() : 0;
    }

    /**
     * 获取总上传速度
     *
     * @return int 总上传速度（bytes/s）
     */
    public function getTotalUploadSpeed(): int
    {
        return $this->torrentPeers ? $this->torrentPeers->getTotalUploadSpeed() : 0;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        return $this->torrentPeers ? $this->torrentPeers->toArray() : [];
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }
}