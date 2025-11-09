<?php
declare(strict_types=1);

namespace PhpQbittorrent\API;

use PhpQbittorrent\Transport\TransportInterface;
use PhpQbittorrent\Exception\ClientException;

/**
 * RSS API类
 *
 * 处理qBittorrent RSS相关的API操作
 */
final class RSSAPI
{
    private TransportInterface $transport;

    public function __construct(TransportInterface $transport)
    {
        $this->transport = $transport;
    }

    /**
     * 获取所有RSS订阅
     *
     * @return array RSS订阅列表
     * @throws ClientException 获取失败
     */
    public function getRssItems(): array
    {
        return $this->transport->request('GET', '/api/v2/rss/items');
    }

    /**
     * 添加RSS订阅
     *
     * @param string $url RSS URL
     * @param string|null $path 订阅路径
     * @return bool 添加是否成功
     * @throws ClientException 添加失败
     */
    public function addRssItem(string $url, ?string $path = null): bool
    {
        $params = [
            'url' => $url,
            'path' => $path ?? ''
        ];

        $this->transport->request('POST', '/api/v2/rss/addItem', [
            'form_params' => $params
        ]);

        return true;
    }

    /**
     * 删除RSS订阅
     *
     * @param string $path 订阅路径
     * @return bool 删除是否成功
     * @throws ClientException 删除失败
     */
    public function removeRssItem(string $path): bool
    {
        $this->transport->request('POST', '/api/v2/rss/removeItem', [
            'form_params' => ['path' => $path]
        ]);

        return true;
    }

    /**
     * 刷新RSS订阅
     *
     * @param string|null $itemPath 订阅路径，null表示刷新所有
     * @return bool 刷新是否成功
     * @throws ClientException 刷新失败
     */
    public function refreshRssItem(?string $itemPath = null): bool
    {
        $params = [];
        if ($itemPath !== null) {
            $params['itemPath'] = $itemPath;
        }

        $this->transport->request('POST', '/api/v2/rss/refreshItem', [
            'form_params' => $params
        ]);

        return true;
    }

    /**
     * 移动RSS订阅
     *
     * @param string $itemPath 原订阅路径
     * @param string $destPath 目标路径
     * @return bool 移动是否成功
     * @throws ClientException 移动失败
     */
    public function moveRssItem(string $itemPath, string $destPath): bool
    {
        $this->transport->request('POST', '/api/v2/rss/moveItem', [
            'form_params' => [
                'itemPath' => $itemPath,
                'destPath' => $destPath
            ]
        ]);

        return true;
    }
}