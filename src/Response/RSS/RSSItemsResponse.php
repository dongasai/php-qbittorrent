<?php
declare(strict_types=1);

namespace PhpQbittorrent\Response\RSS;

use PhpQbittorrent\Response\AbstractResponse;
use PhpQbittorrent\Collection\RSSFeedCollection;
use PhpQbittorrent\Model\RSSFeed;
use PhpQbittorrent\Model\RSSItem;

/**
 * RSS项目响应对象
 */
class RSSItemsResponse extends AbstractResponse
{
    /** @var RSSFeedCollection RSS订阅源集合 */
    private RSSFeedCollection $feeds;

    /** @var array<RSSItem> RSS项目列表 */
    private array $items;

    /** @var bool 是否包含详细数据 */
    private bool $withData;

    /**
     * 创建成功的RSS项目响应
     *
     * @param RSSFeedCollection $feeds RSS订阅源集合
     * @param array<RSSItem> $items RSS项目列表
     * @param bool $withData 是否包含详细数据
     * @param array<string, string> $headers 响应头
     * @param int $statusCode HTTP状态码
     * @param string $rawResponse 原始响应内容
     * @return self RSS项目响应实例
     */
    public static function success(
        RSSFeedCollection $feeds,
        array $items,
        bool $withData = false,
        array $headers = [],
        int $statusCode = 200,
        string $rawResponse = ''
    ): self {
        $instance = parent::success([], $headers, $statusCode, $rawResponse);
        $instance->feeds = $feeds;
        $instance->items = $items;
        $instance->withData = $withData;

        return $instance;
    }

    /**
     * 创建失败的RSS项目响应
     *
     * @param array<string> $errors 错误信息
     * @param array<string, string> $headers 响应头
     * @param int $statusCode HTTP状态码
     * @param string $rawResponse 原始响应内容
     * @return self RSS项目响应实例
     */
    public static function failure(
        array $errors = [],
        array $headers = [],
        int $statusCode = 400,
        string $rawResponse = ''
    ): self {
        $instance = parent::failure($errors, $headers, $statusCode, $rawResponse);
        $instance->feeds = RSSFeedCollection::empty();
        $instance->items = [];
        $instance->withData = false;

        return $instance;
    }

    /**
     * 从数组数据创建响应对象
     *
     * @param array<string, mixed> $data 响应数据
     * @return static 响应对象实例
     */
    public static function fromArray(array $data): static
    {
        $success = ($data['success'] ?? false);
        $headers = $data['headers'] ?? [];
        $statusCode = $data['statusCode'] ?? 200;
        $rawResponse = $data['rawResponse'] ?? '';
        $errors = $data['errors'] ?? [];
        $responseData = $data['data'] ?? [];

        if ($success) {
            $feedsArray = $responseData['feeds'] ?? [];
            $itemsArray = $responseData['items'] ?? [];
            $withData = $responseData['withData'] ?? false;

            $feeds = RSSFeedCollection::fromArray($feedsArray);
            $items = array_map(fn($itemData) => RSSItem::fromArray($itemData), $itemsArray);

            return self::success($feeds, $items, $withData, $headers, $statusCode, $rawResponse);
        } else {
            return self::failure($errors, $headers, $statusCode, $rawResponse);
        }
    }

    /**
     * 从API响应创建响应对象
     *
     * @param array<string, mixed> $apiData API返回的数据
     * @param bool $withData 是否包含详细数据
     * @param array<string, string> $headers 响应头
     * @param int $statusCode HTTP状态码
     * @param string $rawResponse 原始响应内容
     * @return static 响应对象实例
     */
    public static function fromApiResponse(
        array $apiData,
        bool $withData = false,
        array $headers = [],
        int $statusCode = 200,
        string $rawResponse = ''
    ): static {
        $feeds = RSSFeedCollection::empty();
        $items = [];

        // 解析嵌套的RSS数据结构
        self::parseRSSData($apiData, $feeds, $items, $withData);

        return self::success($feeds, $items, $withData, $headers, $statusCode, $rawResponse);
    }

    /**
     * 解析RSS数据
     *
     * @param array<string, mixed> $data 数据
     * @param RSSFeedCollection $feeds RSS订阅源集合
     * @param array<RSSItem> $items RSS项目列表
     * @param bool $withData 是否包含详细数据
     */
    private static function parseRSSData(array $data, RSSFeedCollection &$feeds, array &$items, bool $withData): void
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                // 检查是否为RSS订阅源（包含url字段）
                if (isset($value['url'])) {
                    $feedData = $value;
                    if ($withData && isset($value['articles'])) {
                        $feedData['totalItems'] = count($value['articles']);
                        $feedData['unreadCount'] = count(array_filter($value['articles'], fn($article) => !($article['read'] ?? false)));
                    }
                    $feeds->addFeed(RSSFeed::fromArray($feedData));
                }
                // 递归处理嵌套结构
                else {
                    self::parseRSSData($value, $feeds, $items, $withData);
                }
            }
        }
    }

    /**
     * 获取RSS订阅源集合
     *
     * @return RSSFeedCollection RSS订阅源集合
     */
    public function getFeeds(): RSSFeedCollection
    {
        return $this->feeds;
    }

    /**
     * 获取RSS项目列表
     *
     * @return array<RSSItem> RSS项目列表
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * 获取是否包含详细数据
     *
     * @return bool 是否包含详细数据
     */
    public function isWithData(): bool
    {
        return $this->withData;
    }

    /**
     * 根据URL查找RSS订阅源
     *
     * @param string $url URL
     * @return RSSFeed|null RSS订阅源，未找到返回null
     */
    public function findFeedByUrl(string $url): ?RSSFeed
    {
        return $this->feeds->findByUrl($url);
    }

    /**
     * 根据路径查找RSS订阅源
     *
     * @param string $path 路径
     * @return RSSFeedCollection 匹配的RSS订阅源集合
     */
    public function findFeedsByPath(string $path): RSSFeedCollection
    {
        return $this->feeds->findByPath($path);
    }

    /**
     * 获取活跃的RSS订阅源
     *
     * @return RSSFeedCollection 活跃的RSS订阅源集合
     */
    public function getActiveFeeds(): RSSFeedCollection
    {
        return $this->feeds->getActive();
    }

    /**
     * 获取有未读项目的RSS订阅源
     *
     * @return RSSFeedCollection 有未读项目的RSS订阅源集合
     */
    public function getFeedsWithUnreadItems(): RSSFeedCollection
    {
        return $this->feeds->getWithUnreadItems();
    }

    /**
     * 获取启用了自动下载的RSS订阅源
     *
     * @return RSSFeedCollection 启用自动下载的RSS订阅源集合
     */
    public function getAutoDownloadFeeds(): RSSFeedCollection
    {
        return $this->feeds->getAutoDownloadEnabled();
    }

    /**
     * 获取有错误的RSS订阅源
     *
     * @return RSSFeedCollection 有错误的RSS订阅源集合
     */
    public function getFeedsWithErrors(): RSSFeedCollection
    {
        return $this->feeds->getWithErrors();
    }

    /**
     * 获取所有路径
     *
     * @return array<string> 路径列表
     */
    public function getAllPaths(): array
    {
        return $this->feeds->getAllPaths();
    }

    /**
     * 获取统计信息
     *
     * @return array<string, mixed> 统计信息
     */
    public function getStatistics(): array
    {
        $feedStats = $this->feeds->getStatistics();

        return array_merge($feedStats, [
            'total_items_count' => count($this->items),
            'with_data' => $this->withData,
            'has_feeds' => !$this->feeds->isEmpty(),
            'has_items' => !empty($this->items),
            'feeds_count' => $this->feeds->count(),
            'active_feeds_count' => $this->feeds->getActive()->count(),
            'unread_feeds_count' => $this->feeds->getWithUnreadItems()->count(),
            'auto_download_feeds_count' => $this->feeds->getAutoDownloadEnabled()->count(),
            'error_feeds_count' => $this->feeds->getWithErrors()->count(),
        ]);
    }

    /**
     * 获取格式化的统计摘要
     *
     * @return array<string, mixed> 格式化的统计摘要
     */
    public function getFormattedSummary(): array
    {
        $stats = $this->getStatistics();

        return [
            'feeds_summary' => [
                'total_feeds' => $stats['total_feeds'],
                'active_feeds' => $stats['active_feeds'],
                'inactive_feeds' => $stats['inactive_feeds'],
                'healthy_feeds' => $stats['healthy_feeds'],
                'feeds_with_errors' => $stats['feeds_with_errors'],
                'auto_download_feeds' => $stats['auto_download_feeds'],
                'feeds_with_unread' => $stats['feeds_with_unread_items'],
            ],
            'content_summary' => [
                'total_unread_items' => $stats['total_unread_items'],
                'total_read_items' => $stats['total_read_items'],
                'average_read_percentage' => round($stats['average_read_percentage'], 2) . '%',
                'items_with_data' => $this->withData ? '是' : '否',
            ],
            'status_summary' => [
                'has_any_errors' => $stats['feeds_with_errors'] > 0,
                'needs_attention' => $stats['feeds_with_errors'] > 0 || $stats['inactive_feeds'] > 0,
                'is_healthy' => $stats['healthy_feeds'] === $stats['total_feeds'],
            ],
            'unique_paths' => $stats['unique_paths'],
            'path_distribution' => $this->getPathDistribution(),
        ];
    }

    /**
     * 获取路径分布
     *
     * @return array<string, int> 路径分布统计
     */
    private function getPathDistribution(): array
    {
        $distribution = [];
        foreach ($this->feeds as $feed) {
            $path = $feed->getPath() ?: '根目录';
            $distribution[$path] = ($distribution[$path] ?? 0) + 1;
        }
        arsort($distribution);
        return $distribution;
    }

    /**
     * 检查是否有任何RSS订阅源
     *
     * @return bool 是否有RSS订阅源
     */
    public function hasFeeds(): bool
    {
        return !$this->feeds->isEmpty();
    }

    /**
     * 检查是否有任何RSS项目
     *
     * @return bool 是否有RSS项目
     */
    public function hasItems(): bool
    {
        return !empty($this->items);
    }

    /**
     * 检查是否有未读项目
     *
     * @return bool 是否有未读项目
     */
    public function hasUnreadItems(): bool
    {
        return $this->feeds->getTotalUnreadCount() > 0;
    }

    /**
     * 检查是否有错误
     *
     * @return bool 是否有错误
     */
    public function hasErrors(): bool
    {
        return $this->feeds->hasAnyErrors();
    }

    /**
     * 转换为数组格式
     *
     * @return array<string, mixed> 响应数据数组
     */
    public function toArray(): array
    {
        $data = parent::toArray();

        $data['feeds'] = $this->feeds->toArray();
        $data['items'] = array_map(fn($item) => $item->toArray(), $this->items);
        $data['withData'] = $this->withData;
        $data['statistics'] = $this->getStatistics();
        $data['formatted_summary'] = $this->getFormattedSummary();

        return $data;
    }

    /**
     * 获取响应的摘要信息
     *
     * @return array<string, mixed> 摘要信息
     */
    public function getSummary(): array
    {
        return [
            'success' => $this->isSuccess(),
            'feeds_count' => $this->feeds->count(),
            'items_count' => count($this->items),
            'with_data' => $this->withData,
            'has_feeds' => $this->hasFeeds(),
            'has_items' => $this->hasItems(),
            'has_unread_items' => $this->hasUnreadItems(),
            'has_errors' => $this->hasErrors(),
            'total_unread_items' => $this->feeds->getTotalUnreadCount(),
            'status_code' => $this->getStatusCode(),
            'error_count' => count($this->getErrors()),
        ];
    }
}