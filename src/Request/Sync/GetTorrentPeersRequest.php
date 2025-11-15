<?php
declare(strict_types=1);

namespace PhpQbittorrent\Request\Sync;

use PhpQbittorrent\Request\AbstractRequest;

/**
 * GetTorrentPeersRequest - 获取Torrent Peers数据请求
 *
 * 用于获取特定torrent的peers连接信息，支持增量更新
 *
 * @package PhpQbittorrent\Request\Sync
 */
class GetTorrentPeersRequest extends AbstractRequest
{
    private string $hash;
    private int $rid;

    /**
     * 构造函数
     *
     * @param string $hash Torrent哈希值
     * @param int $rid 响应ID，用于增量更新
     */
    public function __construct(string $hash, int $rid = 0)
    {
        $this->hash = $hash;
        $this->rid = $rid;
    }

    /**
     * {@inheritdoc}
     */
    public function getMethod(): string
    {
        return 'GET';
    }

    /**
     * {@inheritdoc}
     */
    public function getEndpoint(): string
    {
        $query = [
            'hash' => $this->hash
        ];

        if ($this->rid > 0) {
            $query['rid'] = $this->rid;
        }

        return '/api/v2/sync/torrentPeers?' . http_build_query($query);
    }

    /**
     * {@inheritdoc}
     */
    public function getRequestData(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaders(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions(): array
    {
        return [];
    }
}