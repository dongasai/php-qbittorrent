<?php
declare(strict_types=1);

namespace PhpQbittorrent\Request\Sync;

use PhpQbittorrent\Request\AbstractRequest;

/**
 * GetMainDataRequest - 获取主要数据同步请求
 *
 * 用于获取qBittorrent的主要数据，支持通过rid参数进行增量更新
 *
 * @package PhpQbittorrent\Request\Sync
 */
class GetMainDataRequest extends AbstractRequest
{
    private int $rid;

    /**
     * 构造函数
     *
     * @param int $rid 响应ID，用于增量更新
     */
    public function __construct(int $rid = 0)
    {
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
        $query = [];
        if ($this->rid > 0) {
            $query['rid'] = $this->rid;
        }

        return '/api/v2/sync/maindata' . (!empty($query) ? '?' . http_build_query($query) : '');
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