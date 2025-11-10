<?php
declare(strict_types=1);

namespace PhpQbittorrent\Model;

use JsonSerializable;

/**
 * RSS订阅源模型
 *
 * 封装RSS订阅源的完整信息
 */
class RSSFeed implements JsonSerializable
{
    // 基本信息
    private string $url;
    private string $title;
    private string $description;
    private string $link;
    private string $path;

    // 配置信息
    private bool $autoDownloadEnabled;
    private bool $isActive;
    private ?int $updateInterval;
    private ?int $lastUpdate;
    private ?int $nextUpdate;

    // 状态信息
    private bool $hasError;
    private ?string $errorMessage;
    private int $unreadCount;
    private int $totalItems;

    // 下载规则
    private array $downloadRules;
    private ?string $savePath;

    /**
     * 构造函数
     */
    public function __construct(array $data = [])
    {
        $this->url = $data['url'] ?? '';
        $this->title = $data['title'] ?? '';
        $this->description = $data['description'] ?? '';
        $this->link = $data['link'] ?? '';
        $this->path = $data['path'] ?? '';

        $this->autoDownloadEnabled = $data['autoDownloadEnabled'] ?? false;
        $this->isActive = $data['isActive'] ?? true;
        $this->updateInterval = $data['updateInterval'] ?? null;
        $this->lastUpdate = $data['lastUpdate'] ?? null;
        $this->nextUpdate = $data['nextUpdate'] ?? null;

        $this->hasError = $data['hasError'] ?? false;
        $this->errorMessage = $data['errorMessage'] ?? null;
        $this->unreadCount = $data['unreadCount'] ?? 0;
        $this->totalItems = $data['totalItems'] ?? 0;

        $this->downloadRules = $data['downloadRules'] ?? [];
        $this->savePath = $data['savePath'] ?? null;
    }

    // 基本信息 getter/setter
    public function getUrl(): string { return $this->url; }
    public function getTitle(): string { return $this->title; }
    public function getDescription(): string { return $this->description; }
    public function getLink(): string { return $this->link; }
    public function getPath(): string { return $this->path; }
    public function setTitle(string $title): void { $this->title = $title; }
    public function setDescription(string $description): void { $this->description = $description; }
    public function setSavePath(?string $savePath): void { $this->savePath = $savePath; }

    // 配置信息 getter/setter
    public function isAutoDownloadEnabled(): bool { return $this->autoDownloadEnabled; }
    public function isActive(): bool { return $this->isActive; }
    public function getUpdateInterval(): ?int { return $this->updateInterval; }
    public function getLastUpdate(): ?int { return $this->lastUpdate; }
    public function getNextUpdate(): ?int { return $this->nextUpdate; }

    public function setAutoDownloadEnabled(bool $enabled): void { $this->autoDownloadEnabled = $enabled; }
    public function setActive(bool $active): void { $this->isActive = $active; }
    public function setUpdateInterval(?int $interval): void { $this->updateInterval = $interval; }
    public function setLastUpdate(?int $timestamp): void { $this->lastUpdate = $timestamp; }
    public function setNextUpdate(?int $timestamp): void { $this->nextUpdate = $timestamp; }

    // 状态信息 getter/setter
    public function hasError(): bool { return $this->hasError; }
    public function getErrorMessage(): ?string { return $this->errorMessage; }
    public function getUnreadCount(): int { return $this->unreadCount; }
    public function getTotalItems(): int { return $this->totalItems; }

    public function setError(?string $errorMessage = null): void {
        $this->hasError = $errorMessage !== null;
        $this->errorMessage = $errorMessage;
    }

    public function setUnreadCount(int $count): void { $this->unreadCount = max(0, $count); }
    public function setTotalItems(int $count): void { $this->totalItems = max(0, $count); }
    public function incrementUnreadCount(): void { $this->unreadCount++; }
    public function decrementUnreadCount(): void { $this->unreadCount = max(0, $this->unreadCount - 1); }

    // 下载规则 getter/setter
    public function getDownloadRules(): array { return $this->downloadRules; }
    public function getSavePath(): ?string { return $this->savePath; }
    public function setDownloadRules(array $rules): void { $this->downloadRules = $rules; }
    public function addDownloadRule(string $name, array $rule): void { $this->downloadRules[$name] = $rule; }
    public function removeDownloadRule(string $name): void { unset($this->downloadRules[$name]); }

    // 格式化方法
    public function getFormattedLastUpdate(): string
    {
        if ($this->lastUpdate === null) return '从未更新';
        return date('Y-m-d H:i:s', $this->lastUpdate);
    }

    public function getFormattedNextUpdate(): string
    {
        if ($this->nextUpdate === null) return '未计划';
        return date('Y-m-d H:i:s', $this->nextUpdate);
    }

    public function getUpdateIntervalFormatted(): string
    {
        if ($this->updateInterval === null) return '默认';
        return $this->formatDuration($this->updateInterval);
    }

    public function getTimeSinceLastUpdate(): ?string
    {
        if ($this->lastUpdate === null) return null;
        return $this->formatDuration(time() - $this->lastUpdate);
    }

    public function getTimeUntilNextUpdate(): ?string
    {
        if ($this->nextUpdate === null) return null;
        $until = $this->nextUpdate - time();
        return $until > 0 ? $this->formatDuration($until) : '已过期';
    }

    // 状态判断方法
    public function isDueForUpdate(): bool
    {
        if ($this->nextUpdate === null) return false;
        return time() >= $this->nextUpdate;
    }

    public function isRecentlyUpdated(int $minutes = 60): bool
    {
        if ($this->lastUpdate === null) return false;
        return (time() - $this->lastUpdate) < ($minutes * 60);
    }

    public function hasUnreadItems(): bool
    {
        return $this->unreadCount > 0;
    }

    public function getReadCount(): int
    {
        return max(0, $this->totalItems - $this->unreadCount);
    }

    public function getReadPercentage(): float
    {
        if ($this->totalItems === 0) return 100.0;
        return ($this->getReadCount() / $this->totalItems) * 100;
    }

    public function isHealthy(): bool
    {
        return $this->isActive() && !$this->hasError() && $this->isRecentlyUpdated(1440); // 24小时内有更新
    }

    public function hasDownloadRules(): bool
    {
        return !empty($this->downloadRules);
    }

    public function matchesDownloadRules(string $title, string $description): bool
    {
        if (!$this->autoDownloadEnabled || empty($this->downloadRules)) {
            return false;
        }

        $text = strtolower($title . ' ' . $description);
        foreach ($this->downloadRules as $rule) {
            if (isset($rule['mustContain'])) {
                $keywords = is_array($rule['mustContain']) ? $rule['mustContain'] : [$rule['mustContain']];
                foreach ($keywords as $keyword) {
                    if (str_contains($text, strtolower($keyword))) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    // 静态创建方法
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    public static function create(
        string $url,
        string $title,
        string $path = '',
        string $description = ''
    ): self {
        return new self([
            'url' => $url,
            'title' => $title,
            'path' => $path ?: self::generatePathFromTitle($title),
            'description' => $description,
            'isActive' => true,
            'autoDownloadEnabled' => false,
            'lastUpdate' => time(),
        ]);
    }

    private static function generatePathFromTitle(string $title): string
    {
        // 简单的路径生成：移除特殊字符，替换空格
        $path = preg_replace('/[^a-zA-Z0-9\s]/', '', $title);
        $path = preg_replace('/\s+/', '/', trim($path));
        return strtolower($path);
    }

    // 私有格式化方法
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
            'url' => $this->url,
            'title' => $this->title,
            'description' => $this->description,
            'link' => $this->link,
            'path' => $this->path,

            // 配置信息
            'autoDownloadEnabled' => $this->autoDownloadEnabled,
            'isActive' => $this->isActive,
            'updateInterval' => $this->updateInterval,
            'lastUpdate' => $this->lastUpdate,
            'nextUpdate' => $this->nextUpdate,

            // 状态信息
            'hasError' => $this->hasError,
            'errorMessage' => $this->errorMessage,
            'unreadCount' => $this->unreadCount,
            'totalItems' => $this->totalItems,
            'readCount' => $this->getReadCount(),
            'readPercentage' => $this->getReadPercentage(),

            // 下载规则
            'downloadRules' => $this->downloadRules,
            'savePath' => $this->savePath,

            // 格式化字段
            'formattedLastUpdate' => $this->getFormattedLastUpdate(),
            'formattedNextUpdate' => $this->getFormattedNextUpdate(),
            'updateIntervalFormatted' => $this->getUpdateIntervalFormatted(),
            'timeSinceLastUpdate' => $this->getTimeSinceLastUpdate(),
            'timeUntilNextUpdate' => $this->getTimeUntilNextUpdate(),

            // 状态判断
            'isDueForUpdate' => $this->isDueForUpdate(),
            'isRecentlyUpdated' => $this->isRecentlyUpdated(),
            'hasUnreadItems' => $this->hasUnreadItems(),
            'isHealthy' => $this->isHealthy(),
            'hasDownloadRules' => $this->hasDownloadRules(),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}