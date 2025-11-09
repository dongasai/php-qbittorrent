<?php
declare(strict_types=1);

namespace Dongasai\qBittorrent\Collection;

use Dongasai\qBittorrent\Collection\AbstractCollection;
use Dongasai\qBittorrent\Model\TorrentInfoV2;
use Dongasai\qBittorrent\Enum\TorrentState;
use Dongasai\qBittorrent\Enum\TorrentFilter;

/**
 * Torrent集合类
 *
 * 提供强大的Torrent数据操作和查询功能
 */
class TorrentCollection extends AbstractCollection
{
    /**
     * 添加Torrent到集合
     *
     * @param TorrentInfoV2 $torrent Torrent信息
     * @return self 返回自身以支持链式调用
     */
    public function addTorrent(TorrentInfoV2 $torrent): self
    {
        $this->items[] = $torrent;
        return $this;
    }

    /**
     * 添加多个Torrent到集合
     *
     * @param array<TorrentInfoV2> $torrents Torrent列表
     * @return self 返回自身以支持链式调用
     */
    public function addTorrents(array $torrents): self
    {
        foreach ($torrents as $torrent) {
            $this->addTorrent($torrent);
        }
        return $this;
    }

    /**
     * 根据哈希查找Torrent
     *
     * @param string $hash 哈希值
     * @return TorrentInfoV2|null Torrent信息，未找到返回null
     */
    public function findByHash(string $hash): ?TorrentInfoV2
    {
        foreach ($this->items as $torrent) {
            if ($torrent->getHash() === $hash) {
                return $torrent;
            }
        }
        return null;
    }

    /**
     * 根据名称查找Torrent
     *
     * @param string $name 名称
     * @param bool $exact 是否精确匹配
     * @return self 匹配的Torrent集合
     */
    public function findByName(string $name, bool $exact = true): self
    {
        if ($exact) {
            return $this->filter(fn($torrent) => $torrent->getName() === $name);
        } else {
            return $this->filter(fn($torrent) => str_contains(strtolower($torrent->getName()), strtolower($name)));
        }
    }

    /**
     * 根据分类过滤
     *
     * @param string $category 分类名称
     * @param bool $includeEmpty 是否包含无分类的Torrent
     * @return self 过滤后的集合
     */
    public function filterByCategory(string $category, bool $includeEmpty = false): self
    {
        return $this->filter(function ($torrent) use ($category, $includeEmpty) {
            if ($includeEmpty && empty($category)) {
                return empty($torrent->getCategory());
            }
            return $torrent->getCategory() === $category;
        });
    }

    /**
     * 根据标签过滤
     *
     * @param string $tag 标签名称
     * @return self 过滤后的集合
     */
    public function filterByTag(string $tag): self
    {
        return $this->filter(fn($torrent) => $torrent->hasTag($tag));
    }

    /**
     * 根据多个标签过滤（包含任一标签）
     *
     * @param array<string> $tags 标签列表
     * @return self 过滤后的集合
     */
    public function filterByTags(array $tags): self
    {
        return $this->filter(function ($torrent) use ($tags) {
            foreach ($tags as $tag) {
                if ($torrent->hasTag($tag)) {
                    return true;
                }
            }
            return false;
        });
    }

    /**
     * 根据多个标签过滤（包含所有标签）
     *
     * @param array<string> $tags 标签列表
     * @return self 过滤后的集合
     */
    public function filterByAllTags(array $tags): self
    {
        return $this->filter(function ($torrent) use ($tags) {
            foreach ($tags as $tag) {
                if (!$torrent->hasTag($tag)) {
                    return false;
                }
            }
            return true;
        });
    }

    /**
     * 获取活跃的Torrent
     *
     * @return self 活跃的Torrent集合
     */
    public function getActive(): self
    {
        return $this->filter(fn($torrent) => $torrent->isActive());
    }

    /**
     * 获取已完成的Torrent
     *
     * @return self 已完成的Torrent集合
     */
    public function getCompleted(): self
    {
        return $this->filter(fn($torrent) => $torrent->isCompleted());
    }

    /**
     * 获取正在下载的Torrent
     *
     * @return self 正在下载的Torrent集合
     */
    public function getDownloading(): self
    {
        return $this->filter(fn($torrent) => $torrent->isDownloading());
    }

    /**
     * 获取正在上传的Torrent
     *
     * @return self 正在上传的Torrent集合
     */
    public function getUploading(): self
    {
        return $this->filter(fn($torrent) => $torrent->isUploading());
    }

    /**
     * 获取已暂停的Torrent
     *
     * @return self 已暂停的Torrent集合
     */
    public function getPaused(): self
    {
        return $this->filter(fn($torrent) => $torrent->isPaused());
    }

    /**
     * 获取停滞的Torrent
     *
     * @return self 停滞的Torrent集合
     */
    public function getStalled(): self
    {
        return $this->filter(fn($torrent) => $torrent->isStalled());
    }

    /**
     * 获取有错误的Torrent
     *
     * @return self 有错误的Torrent集合
     */
    public function getErrored(): self
    {
        return $this->filter(fn($torrent) => $torrent->hasError());
    }

    /**
     * 根据状态过滤
     *
     * @param TorrentState $state 状态
     * @return self 过滤后的集合
     */
    public function filterByState(TorrentState $state): self
    {
        return $this->filter(fn($torrent) => $torrent->getState() === $state);
    }

    /**
     * 根据状态过滤（多个状态）
     *
     * @param array<TorrentState> $states 状态列表
     * @return self 过滤后的集合
     */
    public function filterByStates(array $states): self
    {
        return $this->filter(function ($torrent) use ($states) {
            foreach ($states as $state) {
                if ($torrent->getState() === $state) {
                    return true;
                }
            }
            return false;
        });
    }

    /**
     * 根据进度过滤
     *
     * @param float $minProgress 最小进度（0-1）
     * @param float $maxProgress 最大进度（0-1）
     * @return self 过滤后的集合
     */
    public function filterByProgress(float $minProgress = 0, float $maxProgress = 1): self
    {
        return $this->filter(function ($torrent) use ($minProgress, $maxProgress) {
            $progress = $torrent->getProgress();
            return $progress >= $minProgress && $progress <= $maxProgress;
        });
    }

    /**
     * 根据大小过滤
     *
     * @param int $minSize 最小大小（字节）
     * @param int|null $maxSize 最大大小（字节），null表示无限制
     * @return self 过滤后的集合
     */
    public function filterBySize(int $minSize = 0, ?int $maxSize = null): self
    {
        return $this->filter(function ($torrent) use ($minSize, $maxSize) {
            $size = $torrent->getSize();
            if ($size < $minSize) return false;
            if ($maxSize !== null && $size > $maxSize) return false;
            return true;
        });
    }

    /**
     * 根据下载速度过滤
     *
     * @param int $minSpeed 最小速度（字节/秒）
     * @return self 过滤后的集合
     */
    public function filterByDownloadSpeed(int $minSpeed = 0): self
    {
        return $this->filter(fn($torrent) => $torrent->getDownloadSpeed() >= $minSpeed);
    }

    /**
     * 根据上传速度过滤
     *
     * @param int $minSpeed 最小速度（字节/秒）
     * @return self 过滤后的集合
     */
    public function filterByUploadSpeed(int $minSpeed = 0): self
    {
        return $this->filter(fn($torrent) => $torrent->getUploadSpeed() >= $minSpeed);
    }

    /**
     * 根据优先级过滤
     *
     * @param int $priority 优先级
     * @param string $operator 比较运算符 ('=', '>', '<', '>=', '<=')
     * @return self 过滤后的集合
     */
    public function filterByPriority(int $priority, string $operator = '='): self
    {
        return $this->filter(function ($torrent) use ($priority, $operator) {
            $torrentPriority = $torrent->getPriority();
            return match($operator) {
                '=' => $torrentPriority === $priority,
                '>' => $torrentPriority > $priority,
                '<' => $torrentPriority < $priority,
                '>=' => $torrentPriority >= $priority,
                '<=' => $torrentPriority <= $priority,
                default => false,
            };
        });
    }

    /**
     * 根据添加时间过滤
     *
     * @param int $minTime 最小时间（Unix时间戳）
     * @param int|null $maxTime 最大时间（Unix时间戳），null表示无限制
     * @return self 过滤后的集合
     */
    public function filterByAddedTime(int $minTime = 0, ?int $maxTime = null): self
    {
        return $this->filter(function ($torrent) use ($minTime, $maxTime) {
            $addedTime = $torrent->getAddedOn();
            if ($addedTime < $minTime) return false;
            if ($maxTime !== null && $addedTime > $maxTime) return false;
            return true;
        });
    }

    /**
     * 按字段排序
     *
     * @param string $field 排序字段
     * @param bool $descending 是否降序
     * @return self 排序后的集合
     */
    public function sortByField(string $field, bool $descending = false): self
    {
        return $this->sort(function ($a, $b) use ($field) {
            $aValue = $this->getFieldValue($a, $field);
            $bValue = $this->getFieldValue($b, $field);
            return $aValue <=> $bValue;
        }, $descending);
    }

    /**
     * 按进度排序
     *
     * @param bool $descending 是否降序
     * @return self 排序后的集合
     */
    public function sortByProgress(bool $descending = false): self
    {
        return $this->sortByField('progress', $descending);
    }

    /**
     * 按大小排序
     *
     * @param bool $descending 是否降序
     * @return self 排序后的集合
     */
    public function sortBySize(bool $descending = false): self
    {
        return $this->sortByField('size', $descending);
    }

    /**
     * 按下载速度排序
     *
     * @param bool $descending 是否降序
     * @return self 排序后的集合
     */
    public function sortByDownloadSpeed(bool $descending = false): self
    {
        return $this->sortByField('dlspeed', $descending);
    }

    /**
     * 按上传速度排序
     *
     * @param bool $descending 是否降序
     * @return self 排序后的集合
     */
    public function sortByUploadSpeed(bool $descending = false): self
    {
        return $this->sortByField('upspeed', $descending);
    }

    /**
     * 按添加时间排序
     *
     * @param bool $descending 是否降序
     * @return self 排序后的集合
     */
    public function sortByAddedTime(bool $descending = false): self
    {
        return $this->sortByField('added_on', $descending);
    }

    /**
     * 按名称排序
     *
     * @param bool $descending 是否降序
     * @return self 排序后的集合
     */
    public function sortByName(bool $descending = false): self
    {
        return $this->sort(function ($a, $b) {
            return strcasecmp($a->getName(), $b->getName());
        }, $descending);
    }

    /**
     * 按比率排序
     *
     * @param bool $descending 是否降序
     * @return self 排序后的集合
     */
    public function sortByRatio(bool $descending = false): self
    {
        return $this->sortByField('ratio', $descending);
    }

    /**
     * 计算总大小
     *
     * @return int 总大小（字节）
     */
    public function getTotalSize(): int
    {
        return $this->reduce(fn($total, $torrent) => $total + $torrent->getSize(), 0);
    }

    /**
     * 计算总下载速度
     *
     * @return int 总下载速度（字节/秒）
     */
    public function getTotalDownloadSpeed(): int
    {
        return $this->reduce(fn($total, $torrent) => $total + $torrent->getDownloadSpeed(), 0);
    }

    /**
     * 计算总上传速度
     *
     * @return int 总上传速度（字节/秒）
     */
    public function getTotalUploadSpeed(): int
    {
        return $this->reduce(fn($total, $torrent) => $total + $torrent->getUploadSpeed(), 0);
    }

    /**
     * 计算平均进度
     *
     * @return float 平均进度（0-1）
     */
    public function getAverageProgress(): float
    {
        if ($this->isEmpty()) return 0;

        $totalProgress = $this->reduce(fn($total, $torrent) => $total + $torrent->getProgress(), 0);
        return $totalProgress / $this->count();
    }

    /**
     * 计算平均比率
     *
     * @return float 平均比率
     */
    public function getAverageRatio(): float
    {
        if ($this->isEmpty()) return 0;

        $totalRatio = $this->reduce(fn($total, $torrent) => $total + $torrent->getRatio(), 0);
        return $totalRatio / $this->count();
    }

    /**
     * 获取所有分类
     *
     * @return array<string> 分类列表
     */
    public function getAllCategories(): array
    {
        $categories = $this->reduce(function ($categories, $torrent) {
            $category = $torrent->getCategory();
            if (!empty($category) && !in_array($category, $categories)) {
                $categories[] = $category;
            }
            return $categories;
        }, []);

        sort($categories);
        return $categories;
    }

    /**
     * 获取所有标签
     *
     * @return array<string> 标签列表
     */
    public function getAllTags(): array
    {
        $tags = $this->reduce(function ($tags, $torrent) {
            $torrentTags = $torrent->getTagArray();
            foreach ($torrentTags as $tag) {
                if (!in_array($tag, $tags)) {
                    $tags[] = $tag;
                }
            }
            return $tags;
        }, []);

        sort($tags);
        return $tags;
    }

    /**
     * 按分类分组
     *
     * @return array<string, self> 分组后的集合
     */
    public function groupByCategory(): array
    {
        return $this->groupBy(fn($torrent) => $torrent->getCategory() ?: '未分类');
    }

    /**
     * 按状态分组
     *
     * @return array<string, self> 分组后的集合
     */
    public function groupByState(): array
    {
        return $this->groupBy(fn($torrent) => $torrent->getState()->getDisplayName());
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
            'active_count' => $this->getActive()->count(),
            'completed_count' => $this->getCompleted()->count(),
            'downloading_count' => $this->getDownloading()->count(),
            'uploading_count' => $this->getUploading()->count(),
            'paused_count' => $this->getPaused()->count(),
            'stalled_count' => $this->getStalled()->count(),
            'errored_count' => $this->getErrored()->count(),
            'total_size' => $this->getTotalSize(),
            'total_download_speed' => $this->getTotalDownloadSpeed(),
            'total_upload_speed' => $this->getTotalUploadSpeed(),
            'average_progress' => $this->getAverageProgress(),
            'average_ratio' => $this->getAverageRatio(),
            'categories' => $this->getAllCategories(),
            'tags' => $this->getAllTags(),
        ];
    }

    /**
     * 获取Torrent字段的值
     *
     * @param TorrentInfoV2 $torrent Torrent对象
     * @param string $field 字段名
     * @return mixed 字段值
     */
    private function getFieldValue(TorrentInfoV2 $torrent, string $field): mixed
    {
        return match($field) {
            'name' => $torrent->getName(),
            'size' => $torrent->getSize(),
            'progress' => $torrent->getProgress(),
            'dlspeed' => $torrent->getDownloadSpeed(),
            'upspeed' => $torrent->getUploadSpeed(),
            'ratio' => $torrent->getRatio(),
            'added_on' => $torrent->getAddedOn(),
            'priority' => $torrent->getPriority(),
            default => 0,
        };
    }

    /**
     * 转换为数组
     *
     * @return array<int, array<string, mixed>> 数组格式
     */
    public function toArray(): array
    {
        return array_map(fn($torrent) => $torrent->toArray(), $this->items);
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
     * 从数组创建集合
     *
     * @param array<array<string, mixed>> $data 数据数组
     * @return self Torrent集合
     */
    public static function fromArray(array $data): self
    {
        $collection = new self();
        foreach ($data as $torrentData) {
            $collection->addTorrent(TorrentInfoV2::fromArray($torrentData));
        }
        return $collection;
    }
}