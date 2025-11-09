<?php
declare(strict_types=1);

namespace PhpQbittorrent\API;

use PhpQbittorrent\Transport\TransportInterface;
use PhpQbittorrent\Exception\ClientException;

/**
 * 搜索API类
 *
 * 处理qBittorrent搜索相关的API操作
 */
final class SearchAPI
{
    private TransportInterface $transport;

    public function __construct(TransportInterface $transport)
    {
        $this->transport = $transport;
    }

    /**
     * 开始搜索
     *
     * @param string $pattern 搜索模式
     * @param array $options 搜索选项
     * @return int 搜索ID
     * @throws ClientException 搜索失败
     */
    public function startSearch(string $pattern, array $options = []): int
    {
        $params = [
            'pattern' => $pattern,
            'plugins' => $options['plugins'] ?? 'all',
            'category' => $options['category'] ?? 'all',
        ];

        if (isset($options['plugins'])) {
            $params['plugins'] = implode('|', (array) $options['plugins']);
        }

        $response = $this->transport->request('POST', '/api/v2/search/start', [
            'form_params' => $params
        ]);

        return (int) ($response[0] ?? 0);
    }

    /**
     * 停止搜索
     *
     * @param int $searchId 搜索ID
     * @return bool 停止是否成功
     * @throws ClientException 停止失败
     */
    public function stopSearch(int $searchId): bool
    {
        $this->transport->request('POST', '/api/v2/search/stop', [
            'form_params' => ['id' => (string) $searchId]
        ]);

        return true;
    }

    /**
     * 获取搜索状态
     *
     * @param int|null $searchId 搜索ID，null表示获取所有
     * @return array 搜索状态
     * @throws ClientException 获取失败
     */
    public function getSearchStatus(?int $searchId = null): array
    {
        $params = [];
        if ($searchId !== null) {
            $params['id'] = (string) $searchId;
        }

        return $this->transport->request('GET', '/api/v2/search/status', [
            'query' => $params
        ]);
    }

    /**
     * 获取搜索结果
     *
     * @param int $searchId 搜索ID
     * @param int $limit 限制数量
     * @param int $offset 偏移量
     * @return array 搜索结果
     * @throws ClientException 获取失败
     */
    public function getSearchResults(int $searchId, int $limit = 50, int $offset = 0): array
    {
        return $this->transport->request('GET', '/api/v2/search/results', [
            'query' => [
                'id' => (string) $searchId,
                'limit' => (string) $limit,
                'offset' => (string) $offset
            ]
        ]);
    }

    /**
     * 删除搜索
     *
     * @param int $searchId 搜索ID
     * @return bool 删除是否成功
     * @throws ClientException 删除失败
     */
    public function deleteSearch(int $searchId): bool
    {
        $this->transport->request('POST', '/api/v2/search/delete', [
            'form_params' => ['id' => (string) $searchId]
        ]);

        return true;
    }

    /**
     * 获取搜索插件
     *
     * @return array 搜索插件列表
     * @throws ClientException 获取失败
     */
    public function getSearchPlugins(): array
    {
        return $this->transport->request('GET', '/api/v2/search/plugins');
    }

    /**
     * 启用/禁用搜索插件
     *
     * @param array $plugins 插件名称列表
     * @param bool $enable 是否启用
     * @return bool 设置是否成功
     * @throws ClientException 设置失败
     */
    public function toggleSearchPlugins(array $plugins, bool $enable): bool
    {
        $params = [
            'plugins' => implode('|', $plugins),
            'enabled' => $enable ? 'true' : 'false'
        ];

        $this->transport->request('POST', '/api/v2/search/togglePlugins', [
            'form_params' => $params
        ]);

        return true;
    }

    /**
     * 更新搜索插件
     *
     * @return bool 更新是否成功
     * @throws ClientException 更新失败
     */
    public function updateSearchPlugins(): bool
    {
        $this->transport->request('POST', '/api/v2/search/updatePlugins');
        return true;
    }
}