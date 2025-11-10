<?php
declare(strict_types=1);

namespace PhpQbittorrent\Model;

use JsonSerializable;

/**
 * RSS项目模型
 *
 * 封装RSS项目的完整信息
 */
class RSSItem implements JsonSerializable
{
    // 基本信息
    private string $id;
    private string $title;
    private string $url;
    private string $description;
    private string $link;

    // 时间信息
    private ?int $publishDate;
    private ?int $readDate;
    private bool $isRead;

    // 下载信息
    private ?string $torrentUrl;
    private ?string $magnetUri;
    private ?int $size;
    private ?float $progress;

    // 状态信息
    private bool $isDownloaded;
    private bool $isAutoDownloaded;
    private ?string $downloadPath;

    /**
     * 构造函数
     */
    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? '';
        $this->title = $data['title'] ?? '';
        $this->url = $data['url'] ?? '';
        $this->description = $data['description'] ?? '';
        $this->link = $data['link'] ?? '';
        $this->publishDate = $data['publishDate'] ?? null;
        $this->readDate = $data['readDate'] ?? null;
        $this->isRead = $data['isRead'] ?? false;
        $this->torrentUrl = $data['torrentUrl'] ?? null;
        $this->magnetUri = $data['magnetUri'] ?? null;
        $this->size = $data['size'] ?? null;
        $this->progress = $data['progress'] ?? null;
        $this->isDownloaded = $data['isDownloaded'] ?? false;
        $this->isAutoDownloaded = $data['isAutoDownloaded'] ?? false;
        $this->downloadPath = $data['downloadPath'] ?? null;
    }

    // 基本信息 getter/setter
    public function getId(): string { return $this->id; }
    public function getTitle(): string { return $this->title; }
    public function getUrl(): string { return $this->url; }
    public function getDescription(): string { return $this->description; }
    public function getLink(): string { return $this->link; }

    // 时间信息 getter/setter
    public function getPublishDate(): ?int { return $this->publishDate; }
    public function getReadDate(): ?int { return $this->readDate; }
    public function isRead(): bool { return $this->isRead; }
    public function markAsRead(): void { $this->isRead = true; $this->readDate = time(); }
    public function markAsUnread(): void { $this->isRead = false; $this->readDate = null; }

    // 下载信息 getter/setter
    public function getTorrentUrl(): ?string { return $this->torrentUrl; }
    public function getMagnetUri(): ?string { return $this->magnetUri; }
    public function getSize(): ?int { return $this->size; }
    public function getProgress(): ?float { return $this->progress; }

    // 状态信息 getter/setter
    public function isDownloaded(): bool { return $this->isDownloaded; }
    public function isAutoDownloaded(): bool { return $this->isAutoDownloaded; }
    public function getDownloadPath(): ?string { return $this->downloadPath; }
    public function markAsDownloaded(?string $downloadPath = null): void {
        $this->isDownloaded = true;
        $this->downloadPath = $downloadPath;
    }

    // 格式化方法
    public function getFormattedSize(): string
    {
        if ($this->size === null) return '未知';
        return $this->formatBytes($this->size);
    }

    public function getFormattedProgress(): string
    {
        if ($this->progress === null) return '未知';
        return number_format($this->progress * 100, 2) . '%';
    }

    public function getFormattedPublishDate(): string
    {
        if ($this->publishDate === null) return '未知';
        return date('Y-m-d H:i:s', $this->publishDate);
    }

    public function getFormattedReadDate(): string
    {
        if ($this->readDate === null) return '未读';
        return date('Y-m-d H:i:s', $this->readDate);
    }

    public function getAge(): ?int
    {
        if ($this->publishDate === null) return null;
        return time() - $this->publishDate;
    }

    public function getFormattedAge(): string
    {
        $age = $this->getAge();
        if ($age === null) return '未知';
        return $this->formatDuration($age);
    }

    // 状态判断方法
    public function hasTorrent(): bool
    {
        return !empty($this->torrentUrl) || !empty($this->magnetUri);
    }

    public function isRecent(int $hours = 24): bool
    {
        $age = $this->getAge();
        return $age !== null && $age < ($hours * 3600);
    }

    public function isLarge(int $minSizeBytes = 1073741824): bool
    {
        return $this->size !== null && $this->size >= $minSizeBytes;
    }

    public function containsKeywords(array $keywords): bool
    {
        $text = strtolower($this->title . ' ' . $this->description);
        foreach ($keywords as $keyword) {
            if (str_contains($text, strtolower($keyword))) {
                return true;
            }
        }
        return false;
    }

    public function matchesFilter(?string $filter): bool
    {
        if ($filter === null || trim($filter) === '') return true;

        $text = strtolower($this->title . ' ' . $this->description);
        return str_contains($text, strtolower($filter));
    }

    // 静态创建方法
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    public static function create(
        string $title,
        string $url,
        string $description = '',
        string $link = ''
    ): self {
        return new self([
            'id' => uniqid('rss_', true),
            'title' => $title,
            'url' => $url,
            'description' => $description,
            'link' => $link,
            'publishDate' => time(),
        ]);
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

    private function formatDuration(int $seconds): string
    {
        if ($seconds < 60) return "{$seconds}秒";
        if ($seconds < 3600) return floor($seconds / 60) . '分钟';
        if ($seconds < 86400) return floor($seconds / 3600) . '小时';
        return floor($seconds / 86400) . '天';
    }

    /**
     * 转换为数组
     */
    public function toArray(): array
    {
        return [
            // 基本信息
            'id' => $this->id,
            'title' => $this->title,
            'url' => $this->url,
            'description' => $this->description,
            'link' => $this->link,

            // 时间信息
            'publishDate' => $this->publishDate,
            'readDate' => $this->readDate,
            'isRead' => $this->isRead,

            // 下载信息
            'torrentUrl' => $this->torrentUrl,
            'magnetUri' => $this->magnetUri,
            'size' => $this->size,
            'progress' => $this->progress,

            // 状态信息
            'isDownloaded' => $this->isDownloaded,
            'isAutoDownloaded' => $this->isAutoDownloaded,
            'downloadPath' => $this->downloadPath,

            // 格式化字段
            'formattedSize' => $this->getFormattedSize(),
            'formattedProgress' => $this->getFormattedProgress(),
            'formattedPublishDate' => $this->getFormattedPublishDate(),
            'formattedReadDate' => $this->getFormattedReadDate(),
            'age' => $this->getAge(),
            'formattedAge' => $this->getFormattedAge(),
            'hasTorrent' => $this->hasTorrent(),
            'isRecent' => $this->isRecent(),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}