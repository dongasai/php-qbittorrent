<?php
declare(strict_types=1);

namespace Dongasai\qBittorrent\Response\Torrent;

use Dongasai\qBittorrent\Response\AbstractResponse;
use Dongasai\qBittorrent\Contract\ValidationResult;
use Dongasai\qBittorrent\Validation\BasicValidationResult;
use Dongasai\qBittorrent\Model\TorrentInfoV2;
use Dongasai\qBittorrent\Collection\TorrentCollection;
use Dongasai\qBittorrent\Exception\ValidationException;

/**
 * Torrent列表响应对象
 *
 * 封装Torrent列表请求的响应数据和状态信息
 */
class TorrentListResponse extends AbstractResponse
{
    /** @var TorrentCollection Torrent集合 */
    private TorrentCollection $torrents;

    /** @var int 总数量 */
    private int $totalCount;

    /** @var int|null 过滤条件 */
    private ?string $filter;

    /** @var string|null 分类过滤 */
    private ?string $category;

    /** @var string|null 标签过滤 */
    private ?string $tag;

    /** @var string|null 排序字段 */
    private ?string $sort;

    /** @var bool 是否反向排序 */
    private bool $reverse;

    /** @var int|null 限制数量 */
    private ?int $limit;

    /** @var int|null 偏移量 */
    private ?int $offset;

    /** @var array<string, mixed> 响应统计信息 */
    private array $statistics;

    /**
     * 创建成功的Torrent列表响应
     *
     * @param TorrentCollection $torrents Torrent集合
     * @param array<string, string> $headers 响应头
     * @param int $statusCode HTTP状态码
     * @param string $rawResponse 原始响应内容
     * @param array<string, mixed> $statistics 统计信息
     * @return self Torrent列表响应实例
     */
    public static function success(
        TorrentCollection $torrents,
        array $headers = [],
        int $statusCode = 200,
        string $rawResponse = '',
        array $statistics = []
    ): self {
        $instance = parent::success([], $headers, $statusCode, $rawResponse);
        $instance->torrents = $torrents;
        $instance->totalCount = $torrents->count();
        $instance->statistics = $statistics;

        return $instance;
    }

    /**
     * 创建失败的Torrent列表响应
     *
     * @param array<string> $errors 错误信息
     * @param array<string, string> $headers 响应头
     * @param int $statusCode HTTP状态码
     * @param string $rawResponse 原始响应内容
     * @return self Torrent列表响应实例
     */
    public static function failure(
        array $errors = [],
        array $headers = [],
        int $statusCode = 400,
        string $rawResponse = ''
    ): self {
        $instance = parent::failure($errors, $headers, $statusCode, $rawResponse);
        $instance->torrents = new TorrentCollection();
        $instance->totalCount = 0;
        $instance->statistics = [];

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
            $torrentsArray = $responseData['torrents'] ?? [];
            $torrents = TorrentCollection::fromArray($torrentsArray);
            $statistics = $responseData['statistics'] ?? [];

            $instance = self::success($torrents, $headers, $statusCode, $rawResponse, $statistics);

            // 设置请求参数信息
            $instance->filter = $responseData['filter'] ?? null;
            $instance->category = $responseData['category'] ?? null;
            $instance->tag = $responseData['tag'] ?? null;
            $instance->sort = $responseData['sort'] ?? null;
            $instance->reverse = $responseData['reverse'] ?? false;
            $instance->limit = $responseData['limit'] ?? null;
            $instance->offset = $responseData['offset'] ?? null;

            return $instance;
        } else {
            return self::failure($errors, $headers, $statusCode, $rawResponse);
        }
    }

    /**
     * 从API响应创建响应对象
     *
     * @param array<array<string, mixed>> $torrentsData API返回的Torrent数据
     * @param array<string, mixed> $requestParams 请求参数
     * @return static 响应对象实例
     */
    public static function fromApiResponse(array $torrentsData, array $requestParams = []): self
    {
        $torrents = TorrentCollection::fromArray($torrentsData);
        $statistics = $torrents->getStatistics();

        $instance = self::success($torrents, [], 200, json_encode($torrentsData), $statistics);

        // 设置请求参数信息
        $instance->filter = $requestParams['filter'] ?? null;
        $instance->category = $requestParams['category'] ?? null;
        $instance->tag = $requestParams['tag'] ?? null;
        $instance->sort = $requestParams['sort'] ?? null;
        $instance->reverse = $requestParams['reverse'] ?? false;
        $instance->limit = $requestParams['limit'] ?? null;
        $instance->offset = $requestParams['offset'] ?? null;

        return $instance;
    }

    /**
     * 获取Torrent集合
     *
     * @return TorrentCollection Torrent集合
     */
    public function getTorrents(): TorrentCollection
    {
        return $this->torrents;
    }

    /**
     * 获取总数量
     *
     * @return int 总数量
     */
    public function getTotalCount(): int
    {
        return $this->totalCount;
    }

    /**
     * 获取过滤条件
     *
     * @return string|null 过滤条件
     */
    public function getFilter(): ?string
    {
        return $this->filter;
    }

    /**
     * 获取分类过滤
     *
     * @return string|null 分类过滤
     */
    public function getCategory(): ?string
    {
        return $this->category;
    }

    /**
     * 获取标签过滤
     *
     * @return string|null 标签过滤
     */
    public function getTag(): ?string
    {
        return $this->tag;
    }

    /**
     * 获取排序字段
     *
     * @return string|null 排序字段
     */
    public function getSort(): ?string
    {
        return $this->sort;
    }

    /**
     * 是否反向排序
     *
     * @return bool 是否反向排序
     */
    public function isReverse(): bool
    {
        return $this->reverse;
    }

    /**
     * 获取限制数量
     *
     * @return int|null 限制数量
     */
    public function getLimit(): ?int
    {
        return $this->limit;
    }

    /**
     * 获取偏移量
     *
     * @return int|null 偏移量
     */
    public function getOffset(): ?int
    {
        return $this->offset;
    }

    /**
     * 获取统计信息
     *
     * @return array<string, mixed> 统计信息
     */
    public function getStatistics(): array
    {
        return $this->statistics;
    }

    /**
     * 根据哈希获取Torrent
     *
     * @param string $hash 哈希值
     * @return TorrentInfoV2|null Torrent信息，未找到返回null
     */
    public function getTorrentByHash(string $hash): ?TorrentInfoV2
    {
        return $this->torrents->findByHash($hash);
    }

    /**
     * 检查是否包含指定哈希的Torrent
     *
     * @param string $hash 哈希值
     * @return bool 是否包含
     */
    public function hasHash(string $hash): bool
    {
        return $this->getTorrentByHash($hash) !== null;
    }

    /**
     * 获取指定分类的Torrent
     *
     * @param string $category 分类名称
     * @return TorrentCollection 分类Torrent集合
     */
    public function getTorrentsByCategory(string $category): TorrentCollection
    {
        return $this->torrents->filterByCategory($category);
    }

    /**
     * 获取指定标签的Torrent
     *
     * @param string $tag 标签名称
     * @return TorrentCollection 标签Torrent集合
     */
    public function getTorrentsByTag(string $tag): TorrentCollection
    {
        return $this->torrents->filterByTag($tag);
    }

    /**
     * 获取活跃的Torrent
     *
     * @return TorrentCollection 活跃Torrent集合
     */
    public function getActiveTorrents(): TorrentCollection
    {
        return $this->torrents->getActive();
    }

    /**
     * 获取已完成的Torrent
     *
     * @return TorrentCollection 已完成Torrent集合
     */
    public function getCompletedTorrents(): TorrentCollection
    {
        return $this->torrents->getCompleted();
    }

    /**
     * 获取正在下载的Torrent
     *
     * @return TorrentCollection 下载中Torrent集合
     */
    public function getDownloadingTorrents(): TorrentCollection
    {
        return $this->torrents->getDownloading();
    }

    /**
     * 获取正在上传的Torrent
     *
     * @return TorrentCollection 上传中Torrent集合
     */
    public function getUploadingTorrents(): TorrentCollection
    {
        return $this->torrents->getUploading();
    }

    /**
     * 获取有错误的Torrent
     *
     * @return TorrentCollection 错误Torrent集合
     */
    public function getErroredTorrents(): TorrentCollection
    {
        return $this->torrents->getErrored();
    }

    /**
     * 检查是否有任何Torrent
     *
     * @return bool 是否有Torrent
     */
    public function hasTorrents(): bool
    {
        return !$this->torrents->isEmpty();
    }

    /**
     * 检查是否有活跃的Torrent
     *
     * @return bool 是否有活跃Torrent
     */
    public function hasActiveTorrents(): bool
    {
        return !$this->getActiveTorrents()->isEmpty();
    }

    /**
     * 检查是否有下载中的Torrent
     *
     * @return bool 是否有下载中Torrent
     */
    public function hasDownloadingTorrents(): bool
    {
        return !$this->getDownloadingTorrents()->isEmpty();
    }

    /**
     * 检查是否有上传中的Torrent
     *
     * @return bool 是否有上传中Torrent
     */
    public function hasUploadingTorrents(): bool
    {
        return !$this->getUploadingTorrents()->isEmpty();
    }

    /**
     * 检查是否有错误的Torrent
     *
     * @return bool 是否有错误Torrent
     */
    public function hasErroredTorrents(): bool
    {
        return !$this->getErroredTorrents()->isEmpty();
    }

    /**
     * 验证响应数据
     *
     * @return ValidationResult 验证结果
     */
    public function validate(): ValidationResult
    {
        $result = BasicValidationResult::success();

        if ($this->isSuccess()) {
            // 验证Torrent数据一致性
            if ($this->totalCount !== $this->torrents->count()) {
                $result->addWarning('报告的总数量与实际Torrent数量不一致');
            }

            // 验证分页参数
            if ($this->offset !== null && $this->offset < 0) {
                $result->addError('偏移量不能为负数');
            }

            if ($this->limit !== null && $this->limit <= 0) {
                $result->addError('限制数量必须大于0');
            }

            // 验证排序字段
            if ($this->sort !== null) {
                $allowedSortFields = [
                    'hash', 'name', 'size', 'progress', 'dlspeed', 'upspeed',
                    'priority', 'num_seeds', 'num_leechs', 'ratio', 'eta',
                    'state', 'category', 'tags', 'added_on', 'tracker'
                ];

                if (!in_array($this->sort, $allowedSortFields)) {
                    $result->addWarning("排序字段可能无效: {$this->sort}");
                }
            }
        }

        return $result;
    }

    /**
     * 转换为数组格式
     *
     * @return array<string, mixed> 响应数据数组
     */
    public function toArray(): array
    {
        $data = parent::toArray();

        $data['torrents'] = $this->torrents->toArray();
        $data['totalCount'] = $this->totalCount;
        $data['filter'] = $this->filter;
        $data['category'] = $this->category;
        $data['tag'] = $this->tag;
        $data['sort'] = $this->sort;
        $data['reverse'] = $this->reverse;
        $data['limit'] = $this->limit;
        $data['offset'] = $this->offset;
        $data['statistics'] = $this->statistics;

        return $data;
    }

    /**
     * 获取响应的摘要信息
     *
     * @return array<string, mixed> 摘要信息
     */
    public function getSummary(): array
    {
        $statistics = $this->torrents->getStatistics();

        return [
            'success' => $this->isSuccess(),
            'total_count' => $this->totalCount,
            'has_torrents' => $this->hasTorrents(),
            'active_count' => $statistics['active_count'],
            'completed_count' => $statistics['completed_count'],
            'downloading_count' => $statistics['downloading_count'],
            'uploading_count' => $statistics['uploading_count'],
            'errored_count' => $statistics['errored_count'],
            'total_size' => $statistics['total_size'],
            'total_download_speed' => $statistics['total_download_speed'],
            'total_upload_speed' => $statistics['total_upload_speed'],
            'average_progress' => $statistics['average_progress'],
            'filter' => $this->filter,
            'category' => $this->category,
            'tag' => $this->tag,
            'sort' => $this->sort,
            'reverse' => $this->reverse,
            'limit' => $this->limit,
            'offset' => $this->offset,
            'status_code' => $this->getStatusCode(),
            'error_count' => count($this->getErrors()),
        ];
    }

    /**
     * 获取格式化的统计摘要
     *
     * @return array<string, mixed> 格式化的统计摘要
     */
    public function getFormattedSummary(): array
    {
        $statistics = $this->torrents->getStatistics();

        return [
            'torrent_count' => $this->totalCount,
            'active_torrents' => $statistics['active_count'],
            'completed_torrents' => $statistics['completed_count'],
            'downloading_torrents' => $statistics['downloading_count'],
            'uploading_torrents' => $statistics['uploading_count'],
            'paused_torrents' => $statistics['paused_count'],
            'errored_torrents' => $statistics['errored_count'],
            'total_size' => $this->formatBytes($statistics['total_size']),
            'download_speed' => $this->formatSpeed($statistics['total_download_speed']),
            'upload_speed' => $this->formatSpeed($statistics['total_upload_speed']),
            'average_progress' => round($statistics['average_progress'] * 100, 2) . '%',
            'categories' => $statistics['categories'],
            'tags' => $statistics['tags'],
        ];
    }

    /**
     * 格式化字节数
     *
     * @param int $bytes 字节数
     * @return string 格式化后的字符串
     */
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
     * 格式化速度
     *
     * @param int $bytesPerSecond 速度（字节/秒）
     * @return string 格式化后的速度字符串
     */
    private function formatSpeed(int $bytesPerSecond): string
    {
        return $this->formatBytes($bytesPerSecond) . '/s';
    }
}