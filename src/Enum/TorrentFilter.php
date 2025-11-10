<?php
declare(strict_types=1);

namespace PhpQbittorrent\Enum;

/**
 * Torrent过滤条件枚举
 *
 * 定义Torrent列表的过滤条件
 */
enum TorrentFilter: string
{
    case ALL = 'all';
    case DOWNLOADING = 'downloading';
    case SEEDING = 'seeding';
    case COMPLETED = 'completed';
    case STOPPED = 'stopped';
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case RUNNING = 'running';
    case STALLED = 'stalled';
    case STALLED_UPLOADING = 'stalled_uploading';
    case STALLED_DOWNLOADING = 'stalled_downloading';
    case ERRORED = 'errored';

    /**
     * 获取过滤条件的显示名称
     *
     * @return string 显示名称
     */
    public function getDisplayName(): string
    {
        return match($this) {
            self::ALL => '全部',
            self::DOWNLOADING => '下载中',
            self::SEEDING => '做种中',
            self::COMPLETED => '已完成',
            self::STOPPED => '已停止',
            self::ACTIVE => '活动中',
            self::INACTIVE => '非活动',
            self::RUNNING => '运行中',
            self::STALLED => '停滞',
            self::STALLED_UPLOADING => '停滞上传',
            self::STALLED_DOWNLOADING => '停滞下载',
            self::ERRORED => '错误',
        };
    }

    /**
     * 获取过滤条件的描述
     *
     * @return string 描述
     */
    public function getDescription(): string
    {
        return match($this) {
            self::ALL => '显示所有Torrent',
            self::DOWNLOADING => '显示正在下载的Torrent',
            self::SEEDING => '显示正在做种的Torrent',
            self::COMPLETED => '显示已完成的Torrent',
            self::STOPPED => '显示已停止的Torrent',
            self::ACTIVE => '显示活动的Torrent（下载或上传中）',
            self::INACTIVE => '显示非活动的Torrent',
            self::RUNNING => '显示运行中的Torrent',
            self::STALLED => '显示停滞的Torrent',
            self::STALLED_UPLOADING => '显示停滞上传的Torrent',
            self::STALLED_DOWNLOADING => '显示停滞下载的Torrent',
            self::ERRORED => '显示有错误的Torrent',
        };
    }

    /**
     * 从字符串创建过滤条件枚举
     *
     * @param string $filter 过滤条件字符串
     * @return self 过滤条件枚举
     */
    public static function fromString(string $filter): self
    {
        try {
            return self::from($filter);
        } catch (\ValueError $e) {
            return self::ALL;
        }
    }

    /**
     * 获取所有过滤条件
     *
     * @return array<self> 所有过滤条件
     */
    public static function getAllFilters(): array
    {
        return self::cases();
    }

    /**
     * 获取常用的过滤条件
     *
     * @return array<self> 常用过滤条件
     */
    public static function getCommonFilters(): array
    {
        return [
            self::ALL,
            self::DOWNLOADING,
            self::SEEDING,
            self::COMPLETED,
            self::ACTIVE,
            self::ERRORED,
        ];
    }

    /**
     * 获取状态相关的过滤条件
     *
     * @return array<self> 状态相关的过滤条件
     */
    public static function getStatusFilters(): array
    {
        return [
            self::DOWNLOADING,
            self::SEEDING,
            self::COMPLETED,
            self::STOPPED,
            self::RUNNING,
            self::STALLED,
            self::ERRORED,
        ];
    }

    /**
     * 获取活动相关的过滤条件
     *
     * @return array<self> 活动相关的过滤条件
     */
    public static function getActivityFilters(): array
    {
        return [
            self::ACTIVE,
            self::INACTIVE,
            self::RUNNING,
            self::STALLED,
        ];
    }
}