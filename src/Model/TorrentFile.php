<?php
declare(strict_types=1);

namespace PhpQbittorrent\Model;

use PhpQbittorrent\Contract\JsonSerializable;

/**
 * Torrent文件模型
 *
 * 表示qBittorrent中单个文件的信息
 */
class TorrentFile implements JsonSerializable
{
    /** @var int 文件索引 */
    private int $index;

    /** @var string 文件名称（包含相对路径） */
    private string $name;

    /** @var int 文件大小（字节） */
    private int $size;

    /** @var float 文件进度（百分比/100） */
    private float $progress;

    /** @var int 文件优先级 */
    private int $priority;

    /** @var bool 是否已完成/做种 */
    private bool $isSeed;

    /** @var array<int>|null 分片范围（起始分片索引，结束分片索引） */
    private ?array $pieceRange;

    /** @var float 可用性（百分比/100） */
    private float $availability;

    // 文件优先级常量
    public const PRIORITY_DO_NOT_DOWNLOAD = 0;
    public const PRIORITY_NORMAL = 1;
    public const PRIORITY_HIGH = 6;
    public const PRIORITY_MAXIMAL = 7;

    /**
     * 从API响应数据创建TorrentFile实例
     *
     * @param array<string, mixed> $data API响应数据
     * @return self TorrentFile实例
     * @throws \InvalidArgumentException 如果数据格式无效
     */
    public static function fromApiData(array $data): self
    {
        $instance = new self();

        $instance->index = (int) ($data['index'] ?? 0);
        $instance->name = (string) ($data['name'] ?? '');
        $instance->size = (int) ($data['size'] ?? 0);
        $instance->progress = (float) ($data['progress'] ?? 0.0);
        $instance->priority = (int) ($data['priority'] ?? self::PRIORITY_NORMAL);
        $instance->isSeed = (bool) ($data['is_seed'] ?? false);
        $instance->pieceRange = isset($data['piece_range']) ? (array) $data['piece_range'] : null;
        $instance->availability = (float) ($data['availability'] ?? 0.0);

        return $instance;
    }

    /**
     * 私有构造函数
     */
    private function __construct()
    {
    }

    // Getter方法

    public function getIndex(): int
    {
        return $this->index;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getProgress(): float
    {
        return $this->progress;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function isSeed(): bool
    {
        return $this->isSeed;
    }

    public function getPieceRange(): ?array
    {
        return $this->pieceRange;
    }

    public function getAvailability(): float
    {
        return $this->availability;
    }

    /**
     * 获取格式化的文件大小
     *
     * @return string 格式化的文件大小
     */
    public function getFormattedSize(): string
    {
        return $this->formatBytes($this->size);
    }

    /**
     * 获取格式化的文件进度
     *
     * @return string 格式化的文件进度
     */
    public function getFormattedProgress(): string
    {
        return round($this->progress * 100, 2) . '%';
    }

    /**
     * 获取优先级描述
     *
     * @return string 优先级描述
     */
    public function getPriorityDescription(): string
    {
        return match ($this->priority) {
            self::PRIORITY_DO_NOT_DOWNLOAD => '不下载',
            self::PRIORITY_NORMAL => '普通',
            self::PRIORITY_HIGH => '高',
            self::PRIORITY_MAXIMAL => '最高',
            default => '未知',
        };
    }

    /**
     * 检查是否为最高优先级
     *
     * @return bool 是否为最高优先级
     */
    public function isMaxPriority(): bool
    {
        return $this->priority === self::PRIORITY_MAXIMAL;
    }

    /**
     * 检查是否为高优先级
     *
     * @return bool 是否为高优先级
     */
    public function isHighPriority(): bool
    {
        return $this->priority === self::PRIORITY_HIGH;
    }

    /**
     * 检查是否为普通优先级
     *
     * @return bool 是否为普通优先级
     */
    public function isNormalPriority(): bool
    {
        return $this->priority === self::PRIORITY_NORMAL;
    }

    /**
     * 检查是否不下载
     *
     * @return bool 是否不下载
     */
    public function isNotDownloading(): bool
    {
        return $this->priority === self::PRIORITY_DO_NOT_DOWNLOAD;
    }

    /**
     * 检查文件是否已完成
     *
     * @return bool 文件是否已完成
     */
    public function isCompleted(): bool
    {
        return $this->progress >= 1.0 || $this->isSeed;
    }

    /**
     * 转换为数组
     *
     * @return array<string, mixed> 属性数组
     */
    public function toArray(): array
    {
        return [
            'index' => $this->index,
            'name' => $this->name,
            'size' => $this->size,
            'progress' => $this->progress,
            'priority' => $this->priority,
            'priority_description' => $this->getPriorityDescription(),
            'is_seed' => $this->isSeed,
            'is_completed' => $this->isCompleted(),
            'piece_range' => $this->pieceRange,
            'availability' => $this->availability,
            'formatted_size' => $this->getFormattedSize(),
            'formatted_progress' => $this->getFormattedProgress(),
        ];
    }

    /**
     * JSON序列化
     *
     * @return array<string, mixed> 序列化数据
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * 格式化字节数
     *
     * @param int $bytes 字节数
     * @return string 格式化后的字符串
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $unitIndex = 0;

        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }

        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }
}