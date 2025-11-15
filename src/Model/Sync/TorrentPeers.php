<?php
declare(strict_types=1);

namespace PhpQbittorrent\Model\Sync;

use PhpQbittorrent\Collection\AbstractCollection;

/**
 * TorrentPeers - Torrent Peers集合模型
 *
 * 表示连接到特定torrent的所有peers信息集合
 *
 * @package PhpQbittorrent\Model\Sync
 */
class TorrentPeers
{
    private string $hash;
    private array $peers;
    private int $rid;
    private bool $fullUpdate;

    /**
     * 构造函数
     *
     * @param string $hash Torrent哈希值
     * @param TorrentPeer[] $peers Peer连接信息数组
     * @param int $rid 响应ID
     * @param bool $fullUpdate 是否为完整更新
     */
    public function __construct(
        string $hash,
        array $peers = [],
        int $rid = 0,
        bool $fullUpdate = false
    ) {
        $this->hash = $hash;
        $this->peers = $peers;
        $this->rid = $rid;
        $this->fullUpdate = $fullUpdate;
    }

    /**
     * 获取Torrent哈希值
     */
    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * 获取所有Peers
     *
     * @return TorrentPeer[]
     */
    public function getPeers(): array
    {
        return $this->peers;
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
     * 获取Peer数量
     */
    public function count(): int
    {
        return count($this->peers);
    }

    /**
     * 按国家分组获取Peers
     *
     * @return array key为国家代码，value为Peer数组
     */
    public function groupByCountry(): array
    {
        $grouped = [];
        foreach ($this->peers as $peer) {
            $countryCode = $peer->getCountryCode();
            if (!isset($grouped[$countryCode])) {
                $grouped[$countryCode] = [];
            }
            $grouped[$countryCode][] = $peer;
        }
        return $grouped;
    }

    /**
     * 按客户端分组获取Peers
     *
     * @return array key为客户端名称，value为Peer数组
     */
    public function groupByClient(): array
    {
        $grouped = [];
        foreach ($this->peers as $peer) {
            $client = $peer->getClient() ?? 'Unknown';
            if (!isset($grouped[$client])) {
                $grouped[$client] = [];
            }
            $grouped[$client][] = $peer;
        }
        return $grouped;
    }

    /**
     * 获取总下载速度
     *
     * @return int 总下载速度（bytes/s）
     */
    public function getTotalDownloadSpeed(): int
    {
        $total = 0;
        foreach ($this->peers as $peer) {
            $total += $peer->getDownloadSpeed();
        }
        return $total;
    }

    /**
     * 获取总上传速度
     *
     * @return int 总上传速度（bytes/s）
     */
    public function getTotalUploadSpeed(): int
    {
        $total = 0;
        foreach ($this->peers as $peer) {
            $total += $peer->getUploadSpeed();
        }
        return $total;
    }

    /**
     * 获取完成度最高的Peers
     *
     * @param int $limit 返回数量限制
     * @return TorrentPeer[] 完成度最高的Peers
     */
    public function getMostCompletePeers(int $limit = 5): array
    {
        $peers = $this->peers;
        usort($peers, function (TorrentPeer $a, TorrentPeer $b) {
            return $b->getProgress() <=> $a->getProgress();
        });
        return array_slice($peers, 0, $limit);
    }

    /**
     * 获取速度最快的Peers
     *
     * @param int $limit 返回数量限制
     * @return TorrentPeer[] 速度最快的Peers
     */
    public function getFastestPeers(int $limit = 5): array
    {
        $peers = $this->peers;
        usort($peers, function (TorrentPeer $a, TorrentPeer $b) {
            $speedA = $a->getDownloadSpeed() + $a->getUploadSpeed();
            $speedB = $b->getDownloadSpeed() + $b->getUploadSpeed();
            return $speedB <=> $speedA;
        });
        return array_slice($peers, 0, $limit);
    }

    /**
     * 转换为数组
     *
     * @return array
     */
    public function toArray(): array
    {
        $peersArray = [];
        foreach ($this->peers as $peer) {
            $peersArray[] = $peer->toArray();
        }

        return [
            'hash' => $this->hash,
            'rid' => $this->rid,
            'full_update' => $this->fullUpdate,
            'peers' => $peersArray,
            'peers_count' => $this->count(),
            'total_download_speed' => $this->getTotalDownloadSpeed(),
            'total_upload_speed' => $this->getTotalUploadSpeed(),
        ];
    }

    /**
     * 从数组创建TorrentPeers对象
     *
     * @param array $data 原始数据数组
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $peers = [];
        foreach ($data['peers'] ?? [] as $peerData) {
            $peers[] = TorrentPeer::fromArray($peerData);
        }

        return new self(
            $data['hash'] ?? '',
            $peers,
            $data['rid'] ?? 0,
            $data['full_update'] ?? false
        );
    }
}