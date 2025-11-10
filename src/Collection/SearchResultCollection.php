<?php
declare(strict_types=1);

namespace PhpQbittorrent\Collection;

use PhpQbittorrent\Collection\AbstractCollection;
use PhpQbittorrent\Model\SearchResult;

/**
 * 搜索结果集合类
 *
 * 提供强大的搜索结果操作和查询功能
 */
class SearchResultCollection extends AbstractCollection
{
    /**
     * 添加搜索结果到集合
     *
     * @param SearchResult $result 搜索结果
     * @return self 返回自身以支持链式调用
     */
    public function addResult(SearchResult $result): self
    {
        $this->items[] = $result;
        return $this;
    }

    /**
     * 添加多个搜索结果到集合
     *
     * @param array<SearchResult> $results 搜索结果列表
     * @return self 返回自身以支持链式调用
     */
    public function addResults(array $results): self
    {
        foreach ($results as $result) {
            $this->addResult($result);
        }
        return $this;
    }

    /**
     * 根据文件名查找搜索结果
     *
     * @param string $fileName 文件名
     * @param bool $exact 是否精确匹配
     * @return self 匹配的搜索结果集合
     */
    public function findByFileName(string $fileName, bool $exact = true): self
    {
        if ($exact) {
            return $this->filter(fn($result) => $result->getFileName() === $fileName);
        } else {
            return $this->filter(fn($result) => str_contains(strtolower($result->getFileName()), strtolower($fileName)));
        }
    }

    /**
     * 根据站点URL查找搜索结果
     *
     * @param string $siteUrl 站点URL
     * @return self 匹配的搜索结果集合
     */
    public function findBySiteUrl(string $siteUrl): self
    {
        return $this->filter(fn($result) => $result->getSiteUrl() === $siteUrl);
    }

    /**
     * 根据站点域名查找搜索结果
     *
     * @param string $domain 站点域名
     * @return self 匹配的搜索结果集合
     */
    public function findByDomain(string $domain): self
    {
        return $this->filter(function ($result) use ($domain) {
            $siteUrl = parse_url($result->getSiteUrl(), PHP_URL_HOST);
            return $siteUrl && str_contains(strtolower($siteUrl), strtolower($domain));
        });
    }

    /**
     * 根据分类查找搜索结果
     *
     * @param string $category 分类
     * @return self 匹配的搜索结果集合
     */
    public function findByCategory(string $category): self
    {
        return $this->filter(fn($result) => $result->getCategory() === $category);
    }

    /**
     * 根据大小范围过滤
     *
     * @param int $minSize 最小大小（字节）
     * @param int|null $maxSize 最大大小（字节），null表示无限制
     * @return self 过滤后的集合
     */
    public function filterBySize(int $minSize = 0, ?int $maxSize = null): self
    {
        return $this->filter(function ($result) use ($minSize, $maxSize) {
            $size = $result->getFileSize();
            if ($size < $minSize) return false;
            if ($maxSize !== null && $size > $maxSize) return false;
            return true;
        });
    }

    /**
     * 根据种子数量过滤
     *
     * @param int $minSeeders 最小种子数
     * @return self 过滤后的集合
     */
    public function filterByMinSeeders(int $minSeeders): self
    {
        return $this->filter(fn($result) => $result->getNbSeeders() >= $minSeeders);
    }

    /**
     * 根据下载者数量过滤
     *
     * @param int $minLeechers 最小下载数
     * @return self 过滤后的集合
     */
    public function filterByMinLeechers(int $minLeechers): self
    {
        return $this->filter(fn($result) => $result->getNbLeechers() >= $minLeechers);
    }

    /**
     * 获取有种子数的搜索结果
     *
     * @return self 有种子数的搜索结果集合
     */
    public function getWithSeeders(): self
    {
        return $this->filter(fn($result) => $result->hasSeeders());
    }

    /**
     * 获取健康的搜索结果
     *
     * @return self 健康的搜索结果集合
     */
    public function getHealthy(): self
    {
        return $this->filter(fn($result) => $result->isHealthy());
    }

    /**
     * 获取热门的搜索结果
     *
     * @return self 热门的搜索结果集合
     */
    public function getPopular(): self
    {
        return $this->filter(fn($result) => $result->isPopular());
    }

    /**
     * 获取最近的搜索结果
     *
     * @param int $days 最近多少天内
     * @return self 最近的搜索结果集合
     */
    public function getRecent(int $days = 7): self
    {
        return $this->filter(fn($result) => $result->isRecent($days));
    }

    /**
     * 获取大文件搜索结果
     *
     * @param int $minSizeGB 最小大小（GB）
     * @return self 大文件搜索结果集合
     */
    public function getLargeFiles(int $minSizeGB = 1): self
    {
        return $this->filter(fn($result) => $result->isLarge($minSizeGB));
    }

    /**
     * 根据关键词过滤
     *
     * @param array<string> $keywords 关键词列表
     * @return self 过滤后的集合
     */
    public function filterByKeywords(array $keywords): self
    {
        return $this->filter(fn($result) => $result->containsKeywords($keywords));
    }

    /**
     * 根据过滤条件过滤
     *
     * @param string|null $filter 过滤条件
     * @return self 过滤后的集合
     */
    public function filterByFilter(?string $filter): self
    {
        if ($filter === null || trim($filter) === '') {
            return $this;
        }

        return $this->filter(fn($result) => $result->matchesFilter($filter));
    }

    /**
     * 根据评分排序
     *
     * @param bool $descending 是否降序
     * @return self 排序后的集合
     */
    public function sortByScore(bool $descending = true): self
    {
        return $this->sort(function ($a, $b) {
            return $a->getScore() <=> $b->getScore();
        }, $descending);
    }

    /**
     * 根据大小排序
     *
     * @param bool $descending 是否降序
     * @return self 排序后的集合
     */
    public function sortBySize(bool $descending = true): self
    {
        return $this->sort(function ($a, $b) {
            return $a->getFileSize() <=> $b->getFileSize();
        }, $descending);
    }

    /**
     * 根据种子数排序
     *
     * @param bool $descending 是否降序
     * @return self 排序后的集合
     */
    public function sortBySeeders(bool $descending = true): self
    {
        return $this->sort(function ($a, $b) {
            return $a->getNbSeeders() <=> $b->getNbSeeders();
        }, $descending);
    }

    /**
     * 根据下载者数排序
     *
     * @param bool $descending 是否降序
     * @return self 排序后的集合
     */
    public function sortByLeechers(bool $descending = true): self
    {
        return $this->sort(function ($a, $b) {
            return $a->getNbLeechers() <=> $b->getNbLeechers();
        }, $descending);
    }

    /**
     * 根据时间排序
     *
     * @param bool $descending 是否降序
     * @return self 排序后的集合
     */
    public function sortByTime(bool $descending = true): self
    {
        return $this->sort(function ($a, $b) {
            $timeA = $a->getAddedTime() ?? 0;
            $timeB = $b->getAddedTime() ?? 0;
            return $timeA <=> $timeB;
        }, $descending);
    }

    /**
     * 根据文件名排序
     *
     * @param bool $descending 是否降序
     * @return self 排序后的集合
     */
    public function sortByFileName(bool $descending = false): self
    {
        return $this->sort(function ($a, $b) {
            return strcasecmp($a->getFormattedFileName(), $b->getFormattedFileName());
        }, $descending);
    }

    /**
     * 计算总大小
     *
     * @return int 总大小（字节）
     */
    public function getTotalSize(): int
    {
        return $this->reduce(fn($total, $result) => $total + $result->getFileSize(), 0);
    }

    /**
     * 计算总种子数
     *
     * @return int 总种子数
     */
    public function getTotalSeeders(): int
    {
        return $this->reduce(fn($total, $result) => $total + $result->getNbSeeders(), 0);
    }

    /**
     * 计算总下载数
     *
     * @return int 总下载数
     */
    public function getTotalLeechers(): int
    {
        return $this->reduce(fn($total, $result) => $total + $result->getNbLeechers(), 0);
    }

    /**
     * 计算平均种子数
     *
     * @return float 平均种子数
     */
    public function getAverageSeeders(): float
    {
        if ($this->isEmpty()) return 0.0;

        return $this->getTotalSeeders() / $this->count();
    }

    /**
     * 计算平均下载数
     *
     * @return float 平均下载数
     */
    public function getAverageLeechers(): float
    {
        if ($this->isEmpty()) return 0.0;

        return $this->getTotalLeechers() / $this->count();
    }

    /**
     * 获取所有站点URL
     *
     * @return array<string> 站点URL列表
     */
    public function getAllSiteUrls(): array
    {
        return $this->map(fn($result) => $result->getSiteUrl())->toArray();
    }

    /**
     * 获取所有域名
     *
     * @return array<string> 域名列表
     */
    public function getAllDomains(): array
    {
        $domains = $this->reduce(function ($domains, $result) {
            $siteUrl = $result->getSiteUrl();
            $domain = parse_url($siteUrl, PHP_URL_HOST);
            if ($domain && !in_array($domain, $domains)) {
                $domains[] = $domain;
            }
            return $domains;
        }, []);

        sort($domains);
        return $domains;
    }

    /**
     * 获取所有分类
     *
     * @return array<string> 分类列表
     */
    public function getAllCategories(): array
    {
        $categories = $this->reduce(function ($categories, $result) {
            $category = $result->getCategory();
            if ($category && !in_array($category, $categories)) {
                $categories[] = $category;
            }
            return $categories;
        }, []);

        sort($categories);
        return $categories;
    }

    /**
     * 根据站点分组
     *
     * @return array<string, self> 分组后的集合
     */
    public function groupBySite(): array
    {
        return $this->groupBy(fn($result) => $result->getSiteUrl() ?: '未知站点');
    }

    /**
     * 根据域名分组
     *
     * @return array<string, self> 分组后的集合
     */
    public function groupByDomain(): array
    {
        return $this->groupBy(function ($result) {
            $siteUrl = $result->getSiteUrl();
            $domain = parse_url($siteUrl, PHP_URL_HOST);
            return $domain ?: '未知域名';
        });
    }

    /**
     * 根据分类分组
     *
     * @return array<string, self> 分组后的集合
     */
    public function groupByCategory(): array
    {
        return $this->groupBy(fn($result) => $result->getCategory() ?: '未分类');
    }

    /**
     * 根据大小分组
     *
     * @return array<string, self> 分组后的集合
     */
    public function groupBySize(): array
    {
        return $this->groupBy(function ($result) {
            $size = $result->getFileSize();
            if ($size < 1073741824) return '小于1GB';
            if ($size < 10737418240) return '1-10GB';
            if ($size < 107374182400) return '10-100GB';
            return '大于100GB';
        });
    }

    /**
     * 根据健康状态分组
     *
     * @return array<string, self> 分组后的集合
     */
    public function groupByHealth(): array
    {
        return $this->groupBy(function ($result) {
            if (!$result->hasSeeders()) return '无种子';
            if (!$result->isHealthy()) return '不健康';
            if ($result->isPopular()) return '热门';
            return '正常';
        });
    }

    /**
     * 获取统计信息
     *
     * @return array<string, mixed> 统计信息
     */
    public function getStatistics(): array
    {
        return [
            'total_count' => $this->count(),
            'with_seeders_count' => $this->getWithSeeders()->count(),
            'healthy_count' => $this->getHealthy()->count(),
            'popular_count' => $this->getPopular()->count(),
            'recent_count' => $this->getRecent()->count(),
            'large_files_count' => $this->getLargeFiles()->count(),
            'total_size' => $this->getTotalSize(),
            'total_seeders' => $this->getTotalSeeders(),
            'total_leechers' => $this->getTotalLeechers(),
            'average_seeders' => $this->getAverageSeeders(),
            'average_leechers' => $this->getAverageLeechers(),
            'unique_sites' => count($this->getAllSiteUrls()),
            'unique_domains' => count($this->getAllDomains()),
            'unique_categories' => count($this->getAllCategories()),
        ];
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
            'search_summary' => [
                'total_results' => $stats['total_count'],
                'with_seeders' => $stats['with_seeders_count'],
                'healthy' => $stats['healthy_count'],
                'popular' => $stats['popular_count'],
                'recent' => $stats['recent_count'],
                'large_files' => $stats['large_files_count'],
            ],
            'size_summary' => [
                'total_size' => $this->formatBytes($stats['total_size']),
                'average_size' => $this->formatBytes($stats['total_size'] / max($stats['total_count'], 1)),
            ],
            'peer_summary' => [
                'total_seeders' => $stats['total_seeders'],
                'total_leechers' => $stats['total_leechers'],
                'average_seeders' => round($stats['average_seeders'], 2),
                'average_leechers' => round($stats['average_leechers'], 2),
                'seed_lech_ratio' => $stats['total_leechers'] > 0
                    ? round($stats['total_seeders'] / $stats['total_leechers'], 2)
                    : '∞',
            ],
            'diversity_summary' => [
                'unique_sites' => $stats['unique_sites'],
                'unique_domains' => $stats['unique_domains'],
                'unique_categories' => $stats['unique_categories'],
            ],
        ];
    }

    // 私有格式化方法
    private function formatBytes(int $bytes): string
    {
        if ($bytes == 0) return '0 B';

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * 从数组创建集合
     *
     * @param array<array<string, mixed>> $data 数据数组
     * @return self 搜索结果集合
     */
    public static function fromArray(array $data): self
    {
        $collection = new self();
        foreach ($data as $resultData) {
            $collection->addResult(SearchResult::fromArray($resultData));
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
        return array_map(fn($result) => $result->toArray(), $this->items);
    }
}