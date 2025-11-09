<?php
declare(strict_types=1);

namespace PhpQbittorrent\API;

use PhpQbittorrent\Transport\TransportInterface;
use PhpQbittorrent\Exception\ClientException;

/**
 * Torrent API类
 *
 * 处理qBittorrent Torrent相关的API操作，包括添加、删除、管理 torrents等
 */
final class TorrentAPI
{
    private TransportInterface $transport;

    public function __construct(TransportInterface $transport)
    {
        $this->transport = $transport;
    }

    /**
     * 获取所有torrent列表
     *
     * @param string|null $filter 过滤器 (all, downloading, seeding, completed, paused, active, inactive, resumed, paused, stalled)
     * @param string|null $category 分类过滤
     * @param string|null $sort 排序字段 (name, size, progress, dl_speed, up_speed, priority, num_seeds, num_leechs, etc.)
     * @param bool $reverse 是否反向排序
     * @param int|null $limit 限制返回数量
     * @param int|null $offset 偏移量
     * @param string|null $tagHashes Torrent hash列表，用|分隔
     * @return array Torrent列表
     * @throws ClientException 获取失败
     */
    public function getTorrents(
        ?string $filter = null,
        ?string $category = null,
        ?string $sort = null,
        bool $reverse = false,
        ?int $limit = null,
        ?int $offset = null,
        ?string $tagHashes = null
    ): array {
        $params = [];

        if ($filter !== null) {
            $params['filter'] = $filter;
        }

        if ($category !== null) {
            $params['category'] = $category;
        }

        if ($sort !== null) {
            $params['sort'] = $sort;
        }

        if ($reverse) {
            $params['reverse'] = 'true';
        }

        if ($limit !== null) {
            $params['limit'] = (string) $limit;
        }

        if ($offset !== null) {
            $params['offset'] = (string) $offset;
        }

        if ($tagHashes !== null) {
            $params['hashes'] = $tagHashes;
        }

        return $this->transport->request('GET', '/api/v2/torrents/info', [
            'query' => $params
        ]);
    }

    /**
     * 获取特定torrent的详细信息
     *
     * @param string $hash Torrent hash
     * @return array Torrent详细信息
     * @throws ClientException 获取失败
     */
    public function getTorrentInfo(string $hash): array
    {
        $response = $this->transport->request('GET', '/api/v2/torrents/info', [
            'query' => ['hashes' => $hash]
        ]);

        return $response[0] ?? [];
    }

    /**
     * 获取torrent的属性
     *
     * @param string $hash Torrent hash
     * @return array Torrent属性
     * @throws ClientException 获取失败
     */
    public function getTorrentProperties(string $hash): array
    {
        return $this->transport->request('GET', '/api/v2/torrents/properties', [
            'query' => ['hash' => $hash]
        ]);
    }

    /**
     * 获取torrent的文件列表
     *
     * @param string $hash Torrent hash
     * @return array 文件列表
     * @throws ClientException 获取失败
     */
    public function getTorrentFiles(string $hash): array
    {
        return $this->transport->request('GET', '/api/v2/torrents/files', [
            'query' => ['hash' => $hash]
        ]);
    }

    /**
     * 获取torrent的tracker列表
     *
     * @param string $hash Torrent hash
     * @return array Tracker列表
     * @throws ClientException 获取失败
     */
    public function getTorrentTrackers(string $hash): array
    {
        return $this->transport->request('GET', '/api/v2/torrents/trackers', [
            'query' => ['hash' => $hash]
        ]);
    }

    /**
     * 获取torrent的Web种子列表
     *
     * @param string $hash Torrent hash
     * @return array Web种子列表
     * @throws ClientException 获取失败
     */
    public function getTorrentWebSeeds(string $hash): array
    {
        return $this->transport->request('GET', '/api/v2/torrents/webseeds', [
            'query' => ['hash' => $hash]
        ]);
    }

    /**
     * 获取torrent的片段优先级
     *
     * @param string $hash Torrent hash
     * @return array 片段优先级
     * @throws ClientException 获取失败
     */
    public function getTorrentPiecePriorities(string $hash): array
    {
        return $this->transport->request('GET', '/api/v2/torrents/pieceStates', [
            'query' => ['hash' => $hash]
        ]);
    }

    /**
     * 获取torrent的片段状态
     *
     * @param string $hash Torrent hash
     * @return array 片段状态
     * @throws ClientException 获取失败
     */
    public function getTorrentPieceStates(string $hash): array
    {
        return $this->transport->request('GET', '/api/v2/torrents/pieceStates', [
            'query' => ['hash' => $hash]
        ]);
    }

    /**
     * 添加torrent（通过URL或磁力链接）
     *
     * @param array $urls URL列表
     * @param array $options 选项
     * @return bool 添加是否成功
     * @throws ClientException 添加失败
     */
    public function addTorrents(array $urls, array $options = []): bool
    {
        $params = [
            'urls' => implode("\n", $urls)
        ];

        // 添加可选参数
        if (isset($options['save_path'])) {
            $params['savepath'] = $options['save_path'];
        }

        if (isset($options['cookie'])) {
            $params['cookie'] = $options['cookie'];
        }

        if (isset($options['category'])) {
            $params['category'] = $options['category'];
        }

        if (isset($options['tags'])) {
            $params['tags'] = $options['tags'];
        }

        if (isset($options['skip_checking']) && $options['skip_checking']) {
            $params['skip_checking'] = 'true';
        }

        if (isset($options['paused']) && $options['paused']) {
            $params['paused'] = 'true';
        }

        if (isset($options['root_folder']) && $options['root_folder']) {
            $params['root_folder'] = 'true';
        }

        if (isset($options['ratio_limit'])) {
            $params['ratioLimit'] = (string) $options['ratio_limit'];
        }

        if (isset($options['seeding_time_limit'])) {
            $params['seedingTimeLimit'] = (string) $options['seeding_time_limit'];
        }

        if (isset($options['dl_limit'])) {
            $params['dlLimit'] = (string) $options['dl_limit'];
        }

        if (isset($options['up_limit'])) {
            $params['upLimit'] = (string) $options['up_limit'];
        }

        $this->transport->request('POST', '/api/v2/torrents/add', [
            'form_params' => $params
        ]);

        return true;
    }

    /**
     * 添加torrent（通过文件）
     *
     * @param array $files 文件数组，每个元素可以是文件路径或文件内容
     * @param array $options 选项
     * @return bool 添加是否成功
     * @throws ClientException 添加失败
     */
    public function addTorrentFiles(array $files, array $options = []): bool
    {
        $multipart = [];

        foreach ($files as $index => $file) {
            if (is_string($file) && file_exists($file)) {
                // 文件路径
                $multipart[] = [
                    'name' => 'torrents',
                    'filename' => basename($file),
                    'contents' => file_get_contents($file),
                    'content_type' => 'application/x-bittorrent'
                ];
            } else {
                // 文件内容
                $filename = "torrent_{$index}.torrent";
                $content = is_string($file) ? $file : serialize($file);

                $multipart[] = [
                    'name' => 'torrents',
                    'filename' => $filename,
                    'contents' => $content,
                    'content_type' => 'application/x-bittorrent'
                ];
            }
        }

        // 添加其他选项
        $optionMap = [
            'save_path' => 'savepath',
            'cookie' => 'cookie',
            'category' => 'category',
            'tags' => 'tags',
            'skip_checking' => 'skip_checking',
            'paused' => 'paused',
            'root_folder' => 'root_folder',
            'ratio_limit' => 'ratioLimit',
            'seeding_time_limit' => 'seedingTimeLimit',
            'dl_limit' => 'dlLimit',
            'up_limit' => 'upLimit'
        ];

        foreach ($optionMap as $optionKey => $formKey) {
            if (isset($options[$optionKey])) {
                $value = $options[$optionKey];
                if (is_bool($value)) {
                    $value = $value ? 'true' : 'false';
                } elseif (!is_string($value)) {
                    $value = (string) $value;
                }

                $multipart[] = [
                    'name' => $formKey,
                    'contents' => $value
                ];
            }
        }

        $this->transport->request('POST', '/api/v2/torrents/add', [
            'multipart' => $multipart
        ]);

        return true;
    }

    /**
     * 跟踪torrent（通过磁力链接）
     *
     * @param array $magnetLinks 磁力链接列表
     * @param array $options 选项
     * @return bool 跟踪是否成功
     * @throws ClientException 跟踪失败
     */
    public function trackTorrents(array $magnetLinks, array $options = []): bool
    {
        return $this->addTorrents($magnetLinks, $options);
    }

    /**
     * 删除torrents
     *
     * @param string|array $hashes Torrent hash列表或'all'
     * @param bool $deleteFiles 是否同时删除文件
     * @return bool 删除是否成功
     * @throws ClientException 删除失败
     */
    public function deleteTorrents($hashes, bool $deleteFiles = false): bool
    {
        if (is_array($hashes)) {
            $hashes = implode('|', $hashes);
        }

        $params = [
            'hashes' => $hashes,
            'deleteFiles' => $deleteFiles ? 'true' : 'false'
        ];

        $this->transport->request('POST', '/api/v2/torrents/delete', [
            'form_params' => $params
        ]);

        return true;
    }

    /**
     * 暂停torrents
     *
     * @param string|array $hashes Torrent hash列表或'all'
     * @return bool 暂停是否成功
     * @throws ClientException 暂停失败
     */
    public function pauseTorrents($hashes): bool
    {
        if (is_array($hashes)) {
            $hashes = implode('|', $hashes);
        }

        $this->transport->request('POST', '/api/v2/torrents/stop', [
            'form_params' => ['hashes' => $hashes]
        ]);

        return true;
    }

    /**
     * 恢复torrents
     *
     * @param string|array $hashes Torrent hash列表或'all'
     * @return bool 恢复是否成功
     * @throws ClientException 恢复失败
     */
    public function resumeTorrents($hashes): bool
    {
        if (is_array($hashes)) {
            $hashes = implode('|', $hashes);
        }

        $this->transport->request('POST', '/api/v2/torrents/start', [
            'form_params' => ['hashes' => $hashes]
        ]);

        return true;
    }

    /**
     * 强制重新检查torrents
     *
     * @param string|array $hashes Torrent hash列表或'all'
     * @return bool 重新检查是否成功
     * @throws ClientException 重新检查失败
     */
    public function recheckTorrents($hashes): bool
    {
        if (is_array($hashes)) {
            $hashes = implode('|', $hashes);
        }

        $this->transport->request('POST', '/api/v2/torrents/recheck', [
            'form_params' => ['hashes' => $hashes]
        ]);

        return true;
    }

    /**
     * 重新公告torrents到tracker
     *
     * @param string|array $hashes Torrent hash列表或'all'
     * @return bool 重新公告是否成功
     * @throws ClientException 重新公告失败
     */
    public function reannounceTorrents($hashes): bool
    {
        if (is_array($hashes)) {
            $hashes = implode('|', $hashes);
        }

        $this->transport->request('POST', '/api/v2/torrents/reannounce', [
            'form_params' => ['hashes' => $hashes]
        ]);

        return true;
    }

    /**
     * 设置torrent位置（上移/下移）
     *
     * @param string $hash Torrent hash
     * @param string $direction 方向: 'top', 'bottom', 'up', 'down'
     * @return bool 设置是否成功
     * @throws ClientException 设置失败
     */
    public function setTorrentPosition(string $hash, string $direction): bool
    {
        $validDirections = ['top', 'bottom', 'up', 'down'];
        if (!in_array($direction, $validDirections, true)) {
            throw new ClientException(
                "无效的方向: {$direction}，必须是: " . implode(', ', $validDirections)
            );
        }

        $this->transport->request('POST', "/api/v2/torrents/{$direction}Prio", [
            'form_params' => ['hashes' => $hash]
        ]);

        return true;
    }

    /**
     * 设置文件优先级
     *
     * @param string $hash Torrent hash
     * @param array $fileIds 文件ID列表
     * @param int $priority 优先级 (0: 不下载, 1: 正常, 6: 高, 7: 最高)
     * @return bool 设置是否成功
     * @throws ClientException 设置失败
     */
    public function setFilePriority(string $hash, array $fileIds, int $priority): bool
    {
        $params = [
            'hash' => $hash,
            'id' => implode('|', array_map('strval', $fileIds)),
            'priority' => (string) $priority
        ];

        $this->transport->request('POST', '/api/v2/torrents/filePrio', [
            'form_params' => $params
        ]);

        return true;
    }

    /**
     * 设置torrent下载限制
     *
     * @param string $hash Torrent hash
     * @param int $limit 下载速度限制（字节/秒）
     * @return bool 设置是否成功
     * @throws ClientException 设置失败
     */
    public function setTorrentDownloadLimit(string $hash, int $limit): bool
    {
        $this->transport->request('POST', '/api/v2/torrents/setDownloadLimit', [
            'form_params' => [
                'hashes' => $hash,
                'limit' => (string) $limit
            ]
        ]);

        return true;
    }

    /**
     * 设置torrent上传限制
     *
     * @param string $hash Torrent hash
     * @param int $limit 上传速度限制（字节/秒）
     * @return bool 设置是否成功
     * @throws ClientException 设置失败
     */
    public function setTorrentUploadLimit(string $hash, int $limit): bool
    {
        $this->transport->request('POST', '/api/v2/torrents/setUploadLimit', [
            'form_params' => [
                'hashes' => $hash,
                'limit' => (string) $limit
            ]
        ]);

        return true;
    }

    /**
     * 设置torrent分享比例限制
     *
     * @param string $hash Torrent hash
     * @param float $ratio 分享比例
     * @return bool 设置是否成功
     * @throws ClientException 设置失败
     */
    public function setTorrentShareLimit(string $hash, float $ratio): bool
    {
        $this->transport->request('POST', '/api/v2/torrents/setShareLimits', [
            'form_params' => [
                'hashes' => $hash,
                'ratioLimit' => (string) $ratio
            ]
        ]);

        return true;
    }

    /**
     * 添加tracker到torrent
     *
     * @param string $hash Torrent hash
     * @param array $trackers Tracker URL列表
     * @return bool 添加是否成功
     * @throws ClientException 添加失败
     */
    public function addTrackers(string $hash, array $trackers): bool
    {
        $params = [
            'hash' => $hash,
            'urls' => implode("\n", $trackers)
        ];

        $this->transport->request('POST', '/api/v2/torrents/addTrackers', [
            'form_params' => $params
        ]);

        return true;
    }

    /**
     * 编辑tracker
     *
     * @param string $hash Torrent hash
     * @param string $originalUrl 原始tracker URL
     * @param string $newUrl 新tracker URL
     * @return bool 编辑是否成功
     * @throws ClientException 编辑失败
     */
    public function editTracker(string $hash, string $originalUrl, string $newUrl): bool
    {
        $params = [
            'hash' => $hash,
            'origUrl' => $originalUrl,
            'newUrl' => $newUrl
        ];

        $this->transport->request('POST', '/api/v2/torrents/editTracker', [
            'form_params' => $params
        ]);

        return true;
    }

    /**
     * 移除trackers
     *
     * @param string $hash Torrent hash
     * @param array $urls 要移除的tracker URL列表
     * @return bool 移除是否成功
     * @throws ClientException 移除失败
     */
    public function removeTrackers(string $hash, array $urls): bool
    {
        $params = [
            'hash' => $hash,
            'urls' => implode("\n", $urls)
        ];

        $this->transport->request('POST', '/api/v2/torrents/removeTrackers', [
            'form_params' => $params
        ]);

        return true;
    }

    /**
     * 设置torrent分类
     *
     * @param string|array $hashes Torrent hash列表或'all'
     * @param string $category 分类名称
     * @return bool 设置是否成功
     * @throws ClientException 设置失败
     */
    public function setTorrentCategory($hashes, string $category): bool
    {
        if (is_array($hashes)) {
            $hashes = implode('|', $hashes);
        }

        $this->transport->request('POST', '/api/v2/torrents/setCategory', [
            'form_params' => [
                'hashes' => $hashes,
                'category' => $category
            ]
        ]);

        return true;
    }

    /**
     * 设置torrent标签
     *
     * @param string|array $hashes Torrent hash列表或'all'
     * @param array $tags 标签列表
     * @return bool 设置是否成功
     * @throws ClientException 设置失败
     */
    public function setTorrentTags($hashes, array $tags): bool
    {
        if (is_array($hashes)) {
            $hashes = implode('|', $hashes);
        }

        $this->transport->request('POST', '/api/v2/torrents/setTags', [
            'form_params' => [
                'hashes' => $hashes,
                'tags' => implode(', ', $tags)
            ]
        ]);

        return true;
    }

    /**
     * 添加torrent标签
     *
     * @param string|array $hashes Torrent hash列表或'all'
     * @param array $tags 要添加的标签列表
     * @return bool 添加是否成功
     * @throws ClientException 添加失败
     */
    public function addTorrentTags($hashes, array $tags): bool
    {
        if (is_array($hashes)) {
            $hashes = implode('|', $hashes);
        }

        $this->transport->request('POST', '/api/v2/torrents/addTags', [
            'form_params' => [
                'hashes' => $hashes,
                'tags' => implode(', ', $tags)
            ]
        ]);

        return true;
    }

    /**
     * 移除torrent标签
     *
     * @param string|array $hashes Torrent hash列表或'all'
     * @param array $tags 要移除的标签列表
     * @return bool 移除是否成功
     * @throws ClientException 移除失败
     */
    public function removeTorrentTags($hashes, array $tags): bool
    {
        if (is_array($hashes)) {
            $hashes = implode('|', $hashes);
        }

        $this->transport->request('POST', '/api/v2/torrents/removeTags', [
            'form_params' => [
                'hashes' => $hashes,
                'tags' => implode(', ', $tags)
            ]
        ]);

        return true;
    }

    /**
     * 获取所有分类
     *
     * @return array 分类列表
     * @throws ClientException 获取失败
     */
    public function getCategories(): array
    {
        return $this->transport->request('GET', '/api/v2/torrents/categories');
    }

    /**
     * 创建分类
     *
     * @param string $name 分类名称
     * @param string $savePath 保存路径
     * @return bool 创建是否成功
     * @throws ClientException 创建失败
     */
    public function createCategory(string $name, string $savePath = ''): bool
    {
        $params = [
            'category' => $name,
            'savePath' => $savePath
        ];

        $this->transport->request('POST', '/api/v2/torrents/createCategory', [
            'form_params' => $params
        ]);

        return true;
    }

    /**
     * 编辑分类
     *
     * @param string $name 分类名称
     * @param string $savePath 保存路径
     * @return bool 编辑是否成功
     * @throws ClientException 编辑失败
     */
    public function editCategory(string $name, string $savePath = ''): bool
    {
        $params = [
            'category' => $name,
            'savePath' => $savePath
        ];

        $this->transport->request('POST', '/api/v2/torrents/editCategory', [
            'form_params' => $params
        ]);

        return true;
    }

    /**
     * 删除分类
     *
     * @param string|array $categories 分类名称列表
     * @return bool 删除是否成功
     * @throws ClientException 删除失败
     */
    public function removeCategories($categories): bool
    {
        if (is_array($categories)) {
            $categories = implode("\n", $categories);
        }

        $this->transport->request('POST', '/api/v2/torrents/removeCategories', [
            'form_params' => ['categories' => $categories]
        ]);

        return true;
    }

    /**
     * 获取所有标签
     *
     * @return array 标签列表
     * @throws ClientException 获取失败
     */
    public function getTags(): array
    {
        return $this->transport->request('GET', '/api/v2/torrents/tags');
    }

    /**
     * 创建标签
     *
     * @param array $tags 标签列表
     * @return bool 创建是否成功
     * @throws ClientException 创建失败
     */
    public function createTags(array $tags): bool
    {
        $this->transport->request('POST', '/api/v2/torrents/createTags', [
            'form_params' => ['tags' => implode(', ', $tags)]
        ]);

        return true;
    }

    /**
     * 删除标签
     *
     * @param array $tags 标签列表
     * @return bool 删除是否成功
     * @throws ClientException 删除失败
     */
    public function deleteTags(array $tags): bool
    {
        $this->transport->request('POST', '/api/v2/torrents/deleteTags', [
            'form_params' => ['tags' => implode(', ', $tags)]
        ]);

        return true;
    }

    /**
     * 获取torrent统计信息
     *
     * @param string|null $filter 过滤器
     * @return array 统计信息
     * @throws ClientException 获取失败
     */
    public function getTorrentStats(?string $filter = null): array
    {
        $params = [];
        if ($filter !== null) {
            $params['filter'] = $filter;
        }

        return $this->transport->request('GET', '/api/v2/torrents/stats', [
            'query' => $params
        ]);
    }

    /**
     * 查找torrent
     *
     * @param string $pattern 搜索模式
     * @param string|null $category 分类过滤
     * @param string|null $plugin 插件过滤
     * @return array 搜索结果
     * @throws ClientException 搜索失败
     */
    public function searchTorrents(string $pattern, ?string $category = null, ?string $plugin = null): array
    {
        $params = ['pattern' => $pattern];

        if ($category !== null) {
            $params['category'] = $category;
        }

        if ($plugin !== null) {
            $params['plugin'] = $plugin;
        }

        return $this->transport->request('POST', '/api/v2/search/start', [
            'form_params' => $params
        ]);
    }

    /**
     * 获取下载位置
     *
     * @param string $hash Torrent hash
     * @return array 下载位置信息
     * @throws ClientException 获取失败
     */
    public function getDownloadLocation(string $hash): array
    {
        return $this->transport->request('GET', '/api/v2/torrents/downloadLocation', [
            'query' => ['hash' => $hash]
        ]);
    }

    /**
     * 设置下载位置
     *
     * @param string|array $hashes Torrent hash列表或'all'
     * @param string $location 下载位置
     * @return bool 设置是否成功
     * @throws ClientException 设置失败
     */
    public function setDownloadLocation($hashes, string $location): bool
    {
        if (is_array($hashes)) {
            $hashes = implode('|', $hashes);
        }

        $this->transport->request('POST', '/api/v2/torrents/setLocation', [
            'form_params' => [
                'hashes' => $hashes,
                'location' => $location
            ]
        ]);

        return true;
    }

    /**
     * 设置自动管理
     *
     * @param string|array $hashes Torrent hash列表或'all'
     * @param bool $enabled 是否启用自动管理
     * @return bool 设置是否成功
     * @throws ClientException 设置失败
     */
    public function setAutoManagement($hashes, bool $enabled): bool
    {
        if (is_array($hashes)) {
            $hashes = implode('|', $hashes);
        }

        $this->transport->request('POST', '/api/v2/torrents/setAutoManagement', [
            'form_params' => [
                'hashes' => $hashes,
                'enabled' => $enabled ? 'true' : 'false'
            ]
        ]);

        return true;
    }

    /**
     * 获取torrent的完整状态摘要
     *
     * @param string $hash Torrent hash
     * @return array 状态摘要
     */
    public function getTorrentSummary(string $hash): array
    {
        try {
            $info = $this->getTorrentInfo($hash);
            $properties = $this->getTorrentProperties($hash);
            $files = $this->getTorrentFiles($hash);
            $trackers = $this->getTorrentTrackers($hash);

            return [
                'basic_info' => $info,
                'properties' => $properties,
                'files' => [
                    'count' => count($files),
                    'total_size' => array_sum(array_column($files, 'size')),
                    'files' => $files
                ],
                'trackers' => [
                    'count' => count($trackers),
                    'working' => array_filter($trackers, fn($t) => $t['status'] === 1),
                    'trackers' => $trackers
                ],
                'progress_percentage' => round(($info['progress'] ?? 0) * 100, 2),
                'estimated_completion' => $info['eta'] > 0 ? date('Y-m-d H:i:s', time() + $info['eta']) : 'Unknown'
            ];
        } catch (ClientException $e) {
            return [
                'error' => $e->getMessage(),
                'hash' => $hash
            ];
        }
    }
}