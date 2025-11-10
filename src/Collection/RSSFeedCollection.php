<?php
declare(strict_types=1);

namespace PhpQbittorrent\Collection;

use PhpQbittorrent\Collection\AbstractCollection;
use PhpQbittorrent\Model\RSSFeed;
use PhpQbittorrent\Model\RSSItem;

/**
 * RSS订阅源集合类
 *
 * 提供强大的RSS数据操作和查询功能
 */
class RSSFeedCollection extends AbstractCollection
{
    /**
     * 添加RSS订阅源到集合
     *
     * @param RSSFeed $feed RSS订阅源
     * @return self 返回自身以支持链式调用
     */
    public function addFeed(RSSFeed $feed): self
    {
        $this->items[] = $feed;
        return $this;
    }

    /**
     * 添加多个RSS订阅源到集合
     *
     * @param array<RSSFeed> $feeds RSS订阅源列表
     * @return self 返回自身以支持链式调用
     */
    public function addFeeds(array $feeds): self
    {
        foreach ($feeds as $feed) {
            $this->addFeed($feed);
        }
        return $this;
    }

    /**
     * 根据URL查找RSS订阅源
     *
     * @param string $url URL
     * @return RSSFeed|null RSS订阅源，未找到返回null
     */
    public function findByUrl(string $url): ?RSSFeed
    {
        foreach ($this->items as $feed) {
            if ($feed->getUrl() === $url) {
                return $feed;
            }
        }
        return null;
    }

    /**
     * 根据路径查找RSS订阅源
     *
     * @param string $path 路径
     * @return self 匹配的RSS订阅源集合
     */
    public function findByPath(string $path): self
    {
        return $this->filter(fn($feed) => $feed->getPath() === $path);
    }

    /**
     * 根据标题查找RSS订阅源
     *
     * @param string $title 标题
     * @param bool $exact 是否精确匹配
     * @return self 匹配的RSS订阅源集合
     */
    public function findByTitle(string $title, bool $exact = true): self
    {
        if ($exact) {
            return $this->filter(fn($feed) => $feed->getTitle() === $title);
        } else {
            return $this->filter(fn($feed) => str_contains(strtolower($feed->getTitle()), strtolower($title)));
        }
    }

    /**
     * 获取活跃的RSS订阅源
     *
     * @return self 活跃的RSS订阅源集合
     */
    public function getActive(): self
    {
        return $this->filter(fn($feed) => $feed->isActive());
    }

    /**
     * 获取不活跃的RSS订阅源
     *
     * @return self 不活跃的RSS订阅源集合
     */
    public function getInactive(): self
    {
        return $this->filter(fn($feed) => !$feed->isActive());
    }

    /**
     * 获取启用了自动下载的RSS订阅源
     *
     * @return self 启用自动下载的RSS订阅源集合
     */
    public function getAutoDownloadEnabled(): self
    {
        return $this->filter(fn($feed) => $feed->isAutoDownloadEnabled());
    }

    /**
     * 获取有错误的RSS订阅源
     *
     * @return self 有错误的RSS订阅源集合
     */
    public function getWithErrors(): self
    {
        return $this->filter(fn($feed) => $feed->hasError());
    }

    /**
     * 获取健康的RSS订阅源
     *
     * @return self 健康的RSS订阅源集合
     */
    public function getHealthy(): self
    {
        return $this->filter(fn($feed) => $feed->isHealthy());
    }

    /**
     * 获取需要更新的RSS订阅源
     *
     * @return self 需要更新的RSS订阅源集合
     */
    public function getDueForUpdate(): self
    {
        return $this->filter(fn($feed) => $feed->isDueForUpdate());
    }

    /**
     * 获取最近更新的RSS订阅源
     *
     * @param int $minutes 最近多少分钟内
     * @return self 最近更新的RSS订阅源集合
     */
    public function getRecentlyUpdated(int $minutes = 60): self
    {
        return $this->filter(fn($feed) => $feed->isRecentlyUpdated($minutes));
    }

    /**
     * 获取有未读项目的RSS订阅源
     *
     * @return self 有未读项目的RSS订阅源集合
     */
    public function getWithUnreadItems(): self
    {
        return $this->filter(fn($feed) => $feed->hasUnreadItems());
    }

    /**
     * 获取有下载规则的RSS订阅源
     *
     * @return self 有下载规则的RSS订阅源集合
     */
    public function getWithDownloadRules(): self
    {
        return $this->filter(fn($feed) => $feed->hasDownloadRules());
    }

    /**
     * 根据路径分组
     *
     * @return array<string, self> 分组后的集合
     */
    public function groupByPath(): array
    {
        return $this->groupBy(fn($feed) => $feed->getPath() ?: '根目录');
    }

    /**
     * 根据状态分组
     *
     * @return array<string, self> 分组后的集合
     */
    public function groupByStatus(): array
    {
        return $this->groupBy(function ($feed) {
            if ($feed->hasError()) return '有错误';
            if (!$feed->isActive()) return '不活跃';
            if ($feed->isHealthy()) return '健康';
            return '其他';
        });
    }

    /**
     * 按标题排序
     *
     * @param bool $descending 是否降序
     * @return self 排序后的集合
     */
    public function sortByTitle(bool $descending = false): self
    {
        return $this->sort(function ($a, $b) {
            return strcasecmp($a->getTitle(), $b->getTitle());
        }, $descending);
    }

    /**
     * 按最后更新时间排序
     *
     * @param bool $descending 是否降序
     * @return self 排序后的集合
     */
    public function sortByLastUpdate(bool $descending = false): self
    {
        return $this->sort(function ($a, $b) {
            $timeA = $a->getLastUpdate() ?? 0;
            $timeB = $b->getLastUpdate() ?? 0;
            return $timeA <=> $timeB;
        }, $descending);
    }

    /**
     * 按未读数量排序
     *
     * @param bool $descending 是否降序
     * @return self 排序后的集合
     */
    public function sortByUnreadCount(bool $descending = false): self
    {
        return $this->sort(function ($a, $b) {
            return $a->getUnreadCount() <=> $b->getUnreadCount();
        }, $descending);
    }

    /**
     * 计算总未读数量
     *
     * @return int 总未读数量
     */
    public function getTotalUnreadCount(): int
    {
        return $this->reduce(fn($total, $feed) => $total + $feed->getUnreadCount(), 0);
    }

    /**
     * 计算总项目数量
     *
     * @return int 总项目数量
     */
    public function getTotalItemCount(): int
    {
        return $this->reduce(fn($total, $feed) => $total + $feed->getTotalItems(), 0);
    }

    /**
     * 计算总已读数量
     *
     * @return int 总已读数量
     */
    public function getTotalReadCount(): int
    {
        return $this->reduce(fn($total, $feed) => $total + $feed->getReadCount(), 0);
    }

    /**
     * 获取所有路径
     *
     * @return array<string> 路径列表
     */
    public function getAllPaths(): array
    {
        $paths = $this->reduce(function ($paths, $feed) {
            $path = $feed->getPath();
            if (!empty($path) && !in_array($path, $paths)) {
                $paths[] = $path;
            }
            return $paths;
        }, []);

        sort($paths);
        return $paths;
    }

    /**
     * 获取所有URL
     *
     * @return array<string> URL列表
     */
    public function getAllUrls(): array
    {
        return $this->map(fn($feed) => $feed->getUrl())->toArray();
    }

    /**
     * 检查URL是否存在
     *
     * @param string $url URL
     * @return bool 是否存在
     */
    public function hasUrl(string $url): bool
    {
        return $this->findByUrl($url) !== null;
    }

    /**
     * 获取统计信息
     *
     * @return array<string, mixed> 统计信息
     */
    public function getStatistics(): array
    {
        return [
            'total_feeds' => $this->count(),
            'active_feeds' => $this->getActive()->count(),
            'inactive_feeds' => $this->getInactive()->count(),
            'auto_download_feeds' => $this->getAutoDownloadEnabled()->count(),
            'feeds_with_errors' => $this->getWithErrors()->count(),
            'healthy_feeds' => $this->getHealthy()->count(),
            'feeds_due_for_update' => $this->getDueForUpdate()->count(),
            'recently_updated_feeds' => $this->getRecentlyUpdated()->count(),
            'feeds_with_unread_items' => $this->getWithUnreadItems()->count(),
            'feeds_with_download_rules' => $this->getWithDownloadRules()->count(),
            'total_unread_items' => $this->getTotalUnreadCount(),
            'total_items' => $this->getTotalItemCount(),
            'total_read_items' => $this->getTotalReadCount(),
            'average_read_percentage' => $this->getAverageReadPercentage(),
            'unique_paths' => count($this->getAllPaths()),
        ];
    }

    /**
     * 计算平均已读百分比
     *
     * @return float 平均已读百分比
     */
    public function getAverageReadPercentage(): float
    {
        if ($this->isEmpty()) return 100.0;

        $totalPercentage = $this->reduce(fn($total, $feed) => $total + $feed->getReadPercentage(), 0);
        return $totalPercentage / $this->count();
    }

    /**
     * 获取所有错误信息
     *
     * @return array<string, string> 错误信息数组（URL => 错误消息）
     */
    public function getAllErrors(): array
    {
        return $this->reduce(function ($errors, $feed) {
            if ($feed->hasError()) {
                $errors[$feed->getUrl()] = $feed->getErrorMessage() ?? '未知错误';
            }
            return $errors;
        }, []);
    }

    /**
     * 检查是否有任何错误
     *
     * @return bool 是否有错误
     */
    public function hasAnyErrors(): bool
    {
        return $this->some(fn($feed) => $feed->hasError());
    }

    /**
     * 检查是否需要更新
     *
     * @return bool 是否需要更新
     */
    public function needsUpdate(): bool
    {
        return $this->some(fn($feed) => $feed->isDueForUpdate());
    }

    /**
     * 批量设置活跃状态
     *
     * @param array<string> $urls URL列表
     * @param bool $active 是否活跃
     * @return self 返回自身以支持链式调用
     */
    public function setActiveStatus(array $urls, bool $active): self
    {
        foreach ($this->items as $feed) {
            if (in_array($feed->getUrl(), $urls)) {
                $feed->setActive($active);
            }
        }
        return $this;
    }

    /**
     * 批量设置自动下载状态
     *
     * @param array<string> $urls URL列表
     * @param bool $enabled 是否启用
     * @return self 返回自身以支持链式调用
     */
    public function setAutoDownloadStatus(array $urls, bool $enabled): self
    {
        foreach ($this->items as $feed) {
            if (in_array($feed->getUrl(), $urls)) {
                $feed->setAutoDownloadEnabled($enabled);
            }
        }
        return $this;
    }

    /**
     * 从数组创建集合
     *
     * @param array<array<string, mixed>> $data 数据数组
     * @return self RSS订阅源集合
     */
    public static function fromArray(array $data): self
    {
        $collection = new self();
        foreach ($data as $feedData) {
            $collection->addFeed(RSSFeed::fromArray($feedData));
        }
        return $collection;
    }

    /**
     * 创建空集合
     *
     * @return self 空集合
     */
    public static function empty(): self
    {
        return new self();
    }

    /**
     * 转换为数组
     *
     * @return array<int, array<string, mixed>> 数组格式
     */
    public function toArray(): array
    {
        return array_map(fn($feed) => $feed->toArray(), $this->items);
    }
}