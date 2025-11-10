<?php
declare(strict_types=1);

namespace PhpQbittorrent\Enum;

/**
 * Torrent状态枚举
 *
 * 定义所有可能的Torrent状态，提供类型安全和状态判断方法
 */
enum TorrentState: string
{
    case ERROR = 'error';
    case MISSING_FILES = 'missingFiles';
    case UPLOADING = 'uploading';
    case PAUSED_UP = 'pausedUP';
    case QUEUED_UP = 'queuedUP';
    case STALLED_UP = 'stalledUP';
    case CHECKING_UP = 'checkingUP';
    case FORCED_UP = 'forcedUP';
    case ALLOCATING = 'allocating';
    case DOWNLOADING = 'downloading';
    case META_DL = 'metaDL';
    case PAUSED_DL = 'pausedDL';
    case QUEUED_DL = 'queuedDL';
    case STALLED_DL = 'stalledDL';
    case CHECKING_DL = 'checkingDL';
    case FORCED_DL = 'forcedDL';
    case CHECKING_RESUME_DATA = 'checkingResumeData';
    case MOVING = 'moving';
    case UNKNOWN = 'unknown';

    /**
     * 获取状态的显示名称
     *
     * @return string 显示名称
     */
    public function getDisplayName(): string
    {
        return match($this) {
            self::ERROR => '错误',
            self::MISSING_FILES => '文件缺失',
            self::UPLOADING => '上传中',
            self::PAUSED_UP => '已暂停（完成）',
            self::QUEUED_UP => '排队上传',
            self::STALLED_UP => '停滞上传',
            self::CHECKING_UP => '检查中（完成）',
            self::FORCED_UP => '强制上传',
            self::ALLOCATING => '分配空间',
            self::DOWNLOADING => '下载中',
            self::META_DL => '下载元数据',
            self::PAUSED_DL => '已暂停（下载）',
            self::QUEUED_DL => '排队下载',
            self::STALLED_DL => '停滞下载',
            self::CHECKING_DL => '检查中（下载）',
            self::FORCED_DL => '强制下载',
            self::CHECKING_RESUME_DATA => '检查恢复数据',
            self::MOVING => '移动中',
            self::UNKNOWN => '未知',
        };
    }

    /**
     * 检查是否为活动状态
     *
     * @return bool 是否为活动状态
     */
    public function isActive(): bool
    {
        return in_array($this, [
            self::DOWNLOADING,
            self::UPLOADING,
            self::STALLED_DL,
            self::STALLED_UP,
            self::FORCED_DL,
            self::FORCED_UP,
            self::META_DL,
            self::CHECKING_DL,
            self::CHECKING_UP,
            self::ALLOCATING,
            self::MOVING,
        ]);
    }

    /**
     * 检查是否已完成
     *
     * @return bool 是否已完成
     */
    public function isCompleted(): bool
    {
        return in_array($this, [
            self::UPLOADING,
            self::PAUSED_UP,
            self::QUEUED_UP,
            self::STALLED_UP,
            self::CHECKING_UP,
            self::FORCED_UP,
        ]);
    }

    /**
     * 检查是否正在下载
     *
     * @return bool 是否正在下载
     */
    public function isDownloading(): bool
    {
        return in_array($this, [
            self::DOWNLOADING,
            self::STALLED_DL,
            self::FORCED_DL,
            self::META_DL,
            self::ALLOCATING,
        ]);
    }

    /**
     * 检查是否正在上传
     *
     * @return bool 是否正在上传
     */
    public function isUploading(): bool
    {
        return in_array($this, [
            self::UPLOADING,
            self::STALLED_UP,
            self::FORCED_UP,
        ]);
    }

    /**
     * 检查是否已暂停
     *
     * @return bool 是否已暂停
     */
    public function isPaused(): bool
    {
        return in_array($this, [
            self::PAUSED_UP,
            self::PAUSED_DL,
        ]);
    }

    /**
     * 检查是否正在排队
     *
     * @return bool 是否正在排队
     */
    public function isQueued(): bool
    {
        return in_array($this, [
            self::QUEUED_UP,
            self::QUEUED_DL,
        ]);
    }

    /**
     * 检查是否正在检查
     *
     * @return bool 是否正在检查
     */
    public function isChecking(): bool
    {
        return in_array($this, [
            self::CHECKING_UP,
            self::CHECKING_DL,
            self::CHECKING_RESUME_DATA,
        ]);
    }

    /**
     * 检查是否为错误状态
     *
     * @return bool 是否为错误状态
     */
    public function isError(): bool
    {
        return $this === self::ERROR || $this === self::MISSING_FILES;
    }

    /**
     * 检查是否可以开始
     *
     * @return bool 是否可以开始
     */
    public function canStart(): bool
    {
        return in_array($this, [
            self::PAUSED_UP,
            self::PAUSED_DL,
            self::QUEUED_UP,
            self::QUEUED_DL,
            self::STALLED_UP,
            self::STALLED_DL,
            self::ERROR,
            self::MISSING_FILES,
        ]);
    }

    /**
     * 检查是否可以暂停
     *
     * @return bool 是否可以暂停
     */
    public function canPause(): bool
    {
        return $this->isActive();
    }

    /**
     * 获取状态对应的颜色代码
     *
     * @return string 颜色代码
     */
    public function getColor(): string
    {
        return match($this) {
            self::ERROR, self::MISSING_FILES => '#dc3545', // 红色
            self::DOWNLOADING, self::META_DL, self::ALLOCATING => '#007bff', // 蓝色
            self::UPLOADING => '#28a745', // 绿色
            self::PAUSED_UP, self::PAUSED_DL => '#ffc107', // 黄色
            self::QUEUED_UP, self::QUEUED_DL => '#6c757d', // 灰色
            self::STALLED_UP, self::STALLED_DL => '#fd7e14', // 橙色
            self::CHECKING_UP, self::CHECKING_DL, self::CHECKING_RESUME_DATA => '#17a2b8', // 青色
            self::FORCED_UP, self::FORCED_DL => '#e83e8c', // 粉色
            self::MOVING => '#6f42c1', // 紫色
            self::UNKNOWN => '#343a40', // 深灰色
        };
    }

    /**
     * 从字符串创建TorrentState枚举
     *
     * @param string $state 状态字符串
     * @return self TorrentState枚举
     */
    public static function fromString(string $state): self
    {
        try {
            return self::from($state);
        } catch (\ValueError $e) {
            return self::UNKNOWN;
        }
    }

    /**
     * 获取所有可能的值
     *
     * @return array<string> 所有可能的值
     */
    public static function getAllValues(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }

    /**
     * 获取活动状态列表
     *
     * @return array<self> 活动状态列表
     */
    public static function getActiveStates(): array
    {
        return array_filter(self::cases(), fn($state) => $state->isActive());
    }

    /**
     * 获取完成状态列表
     *
     * @return array<self> 完成状态列表
     */
    public static function getCompletedStates(): array
    {
        return array_filter(self::cases(), fn($state) => $state->isCompleted());
    }

    /**
     * 获取下载状态列表
     *
     * @return array<self> 下载状态列表
     */
    public static function getDownloadingStates(): array
    {
        return array_filter(self::cases(), fn($state) => $state->isDownloading());
    }
}