<?php
declare(strict_types=1);

namespace PhpQbittorrent\Model;

use JsonSerializable;

/**
 * 搜索任务模型
 *
 * 封装搜索任务的完整信息
 */
class SearchJob implements JsonSerializable
{
    // 基本信息
    private int $id;
    private string $pattern;
    private string $status;
    private int $total;

    // 配置信息
    private array $plugins;
    private string $category;
    private ?int $startTime;
    private ?int $endTime;

    // 状态信息
    private bool $isRunning;
    private bool $isCompleted;
    private bool $hasError;
    private ?string $errorMessage;

    /**
     * 构造函数
     */
    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? 0;
        $this->pattern = $data['pattern'] ?? '';
        $this->status = $data['status'] ?? 'unknown';
        $this->total = $data['total'] ?? 0;

        $this->plugins = $data['plugins'] ?? [];
        $this->category = $data['category'] ?? 'all';
        $this->startTime = $data['startTime'] ?? null;
        $this->endTime = $data['endTime'] ?? null;

        $this->isRunning = $this->status === 'Running';
        $this->isCompleted = $this->status === 'Stopped';
        $this->hasError = !in_array($this->status, ['Running', 'Stopped']);
        $this->errorMessage = $data['errorMessage'] ?? null;
    }

    // 基本信息 getter/setter
    public function getId(): int { return $this->id; }
    public function getPattern(): string { return $this->pattern; }
    public function getStatus(): string { return $this->status; }
    public function getTotal(): int { return $this->total; }

    // 配置信息 getter/setter
    public function getPlugins(): array { return $this->plugins; }
    public function getCategory(): string { return $this->category; }
    public function getStartTime(): ?int { return $this->startTime; }
    public function getEndTime(): ?int { return $this->endTime; }

    // 状态信息 getter/setter
    public function isRunning(): bool { return $this->isRunning; }
    public function isCompleted(): bool { return $this->isCompleted; }
    public function hasError(): bool { return $this->hasError; }
    public function getErrorMessage(): ?string { return $this->errorMessage; }

    // 状态更新方法
    public function setStatus(string $status): void
    {
        $this->status = $status;
        $this->isRunning = $status === 'Running';
        $this->isCompleted = $status === 'Stopped';
        $this->hasError = !in_array($status, ['Running', 'Stopped']);
    }

    public function setTotal(int $total): void
    {
        $this->total = max(0, $total);
    }

    public function setStartTime(int $timestamp): void
    {
        $this->startTime = $timestamp;
    }

    public function setEndTime(?int $timestamp): void
    {
        $this->endTime = $timestamp;
    }

    public function setError(?string $errorMessage = null): void
    {
        $this->hasError = $errorMessage !== null;
        $this->errorMessage = $errorMessage;
        if ($errorMessage !== null) {
            $this->status = 'Error';
            $this->isRunning = false;
            $this->isCompleted = false;
        }
    }

    public function markAsRunning(): void
    {
        $this->status = 'Running';
        $this->isRunning = true;
        $this->isCompleted = false;
        $this->hasError = false;
        $this->errorMessage = null;
        if ($this->startTime === null) {
            $this->startTime = time();
        }
    }

    public function markAsCompleted(): void
    {
        $this->status = 'Stopped';
        $this->isRunning = false;
        $this->isCompleted = true;
        $this->hasError = false;
        $this->errorMessage = null;
        $this->endTime = time();
    }

    public function markAsError(string $errorMessage): void
    {
        $this->setError($errorMessage);
        $this->endTime = time();
    }

    // 格式化方法
    public function getFormattedStartTime(): string
    {
        if ($this->startTime === null) return '未开始';
        return date('Y-m-d H:i:s', $this->startTime);
    }

    public function getFormattedEndTime(): string
    {
        if ($this->endTime === null) return '未结束';
        return date('Y-m-d H:i:s', $this->endTime);
    }

    public function getFormattedDuration(): string
    {
        if ($this->startTime === null) return '未知';

        $endTime = $this->endTime ?? time();
        $duration = $endTime - $this->startTime;

        return $this->formatDuration($duration);
    }

    public function getFormattedStatus(): string
    {
        return match($this->status) {
            'Running' => '运行中',
            'Stopped' => '已完成',
            'Error' => '错误',
            default => '未知'
        };
    }

    public function getProgressPercentage(): float
    {
        if ($this->status === 'Running' && $this->total > 0) {
            // 简单的进度估算：基于时间
            $elapsed = time() - $this->startTime;
            $estimatedTotal = 300; // 估计5分钟
            return min(($elapsed / $estimatedTotal) * 100, 99);
        }

        if ($this->isCompleted()) {
            return 100.0;
        }

        return 0.0;
    }

    // 状态判断方法
    public function isActive(): bool
    {
        return $this->isRunning() && !$this->hasError();
    }

    public function canStop(): bool
    {
        return $this->isRunning();
    }

    public function canDelete(): bool
    {
        return $this->isCompleted() || $this->hasError();
    }

    public function isRecent(int $minutes = 10): bool
    {
        $time = $this->endTime ?? $this->startTime;
        if ($time === null) return false;
        return (time() - $time) < ($minutes * 60);
    }

    public function isLongRunning(int $minutes = 30): bool
    {
        if ($this->startTime === null) return false;

        $endTime = $this->endTime ?? time();
        return ($endTime - $this->startTime) > ($minutes * 60);
    }

    public function hasResults(): bool
    {
        return $this->total > 0;
    }

    public function usesPlugin(string $plugin): bool
    {
        return in_array($plugin, $this->plugins) || in_array('all', $this->plugins) || in_array('enabled', $this->plugins);
    }

    public function isAllPlugins(): bool
    {
        return in_array('all', $this->plugins);
    }

    public function isEnabledPlugins(): bool
    {
        return in_array('enabled', $this->plugins);
    }

    // 静态创建方法
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    public static function create(
        int $id,
        string $pattern,
        array $plugins = [],
        string $category = 'all'
    ): self {
        return new self([
            'id' => $id,
            'pattern' => $pattern,
            'plugins' => $plugins,
            'category' => $category,
            'status' => 'Running',
            'startTime' => time(),
            'total' => 0,
        ]);
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
            'id' => $this->id,
            'pattern' => $this->pattern,
            'status' => $this->status,
            'total' => $this->total,

            // 配置信息
            'plugins' => $this->plugins,
            'category' => $this->category,
            'startTime' => $this->startTime,
            'endTime' => $this->endTime,

            // 状态信息
            'isRunning' => $this->isRunning,
            'isCompleted' => $this->isCompleted,
            'hasError' => $this->hasError,
            'errorMessage' => $this->errorMessage,

            // 格式化字段
            'formattedStartTime' => $this->getFormattedStartTime(),
            'formattedEndTime' => $this->getFormattedEndTime(),
            'formattedDuration' => $this->getFormattedDuration(),
            'formattedStatus' => $this->getFormattedStatus(),
            'progressPercentage' => $this->getProgressPercentage(),

            // 状态判断
            'isActive' => $this->isActive(),
            'canStop' => $this->canStop(),
            'canDelete' => $this->canDelete(),
            'isRecent' => $this->isRecent(),
            'isLongRunning' => $this->isLongRunning(),
            'hasResults' => $this->hasResults(),
            'usesAllPlugins' => $this->isAllPlugins(),
            'usesEnabledPlugins' => $this->isEnabledPlugins(),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}