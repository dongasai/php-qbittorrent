<?php
declare(strict_types=1);

namespace PhpQbittorrent\Model;

use PhpQbittorrent\Contract\JsonSerializable;
use PhpQbittorrent\Exception\InvalidArgumentException;

/**
 * Torrent Tracker模型
 *
 * 表示qBittorrent中单个Tracker的信息
 */
class TorrentTracker implements JsonSerializable
{
    /** @var string Tracker URL */
    private string $url;

    /** @var int Tracker状态 */
    private int $status;

    /** @var int 优先级层级 */
    private int $tier;

    /** @var int 报告的Peers数量 */
    private int $numPeers;

    /** @var int 报告的Seeds数量 */
    private int $numSeeds;

    /** @var int 报告的Leeches数量 */
    private int $numLeeches;

    /** @var int 报告的完成下载数 */
    private int $numDownloaded;

    /** @var string Tracker消息 */
    private string $msg;

    // Tracker状态常量
    public const STATUS_DISABLED = 0;    // 禁用
    public const STATUS_NOT_CONTACTED = 1;  // 未联系
    public const STATUS_WORKING = 2;       // 工作中
    public const STATUS_UPDATING = 3;    // 更新中
    public const STATUS_NOT_WORKING = 4;   // 不工作

    /**
     * 从API响应数据创建TorrentTracker实例
     *
     * @param array<string, mixed> $data API响应数据
     * @return self TorrentTracker实例
     * @throws InvalidArgumentException 如果数据格式无效
     */
    public static function fromApiData(array $data): self
    {
        $instance = new self();

        // 必需字段
        if (!isset($data['url'])) {
            throw new InvalidArgumentException('url is required');
        }
        if (!isset($data['status'])) {
            throw new InvalidArgumentException('status is required');
        }

        // 设置属性，提供默认值
        $instance->url = (string) $data['url'];
        $instance->status = (int) ($data['status'] ?? 0);
        $instance->tier = isset($data['tier']) ? (int) $data['tier'] : 0;
        $instance->numPeers = isset($data['num_peers']) ? (int) $data['num_peers'] : 0;
        $instance->numSeeds = isset($data['num_seeds']) ? (int) $data['num_seeds'] : 0;
        $instance->numLeeches = isset($data['num_leeches']) ? (int) $data['num_leeches'] : 0;
        $instance->numDownloaded = isset($data['num_downloaded']) ? (int) $data['num_downloaded'] : 0;
        $instance->msg = (string) ($data['msg'] ?? '');

        return $instance;
    }

    /**
     * 私有构造函数
     */
    private function __construct()
    {
    }

    // Getter方法

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getTier(): int
    {
        return $this->tier;
    }

    public function getNumPeers(): int
    {
        return $this->numPeers;
    }

    public function getNumSeeds(): int
    {
        return $this->numSeeds;
    }

    public function getNumLeeches(): int
    {
        return $this->numLeeches;
    }

    public function getNumDownloaded(): int
    {
        return $this->numDownloaded;
    }

    public function getMsg(): string
    {
        return $this->msg;
    }

    // 状态检查方法

    /**
     * 是否禁用
     */
    public function isDisabled(): bool
    {
        return $this->status === self::STATUS_DISABLED;
    }

    /**
     * 是否未联系
     */
    public function isNotContacted(): bool
    {
        return $this->status === self::STATUS_NOT_CONTACTED;
    }

    /**
     * 是否工作中
     */
    public function isWorking(): bool
    {
        return $this->status === self::STATUS_WORKING;
    }

    /**
     * 是否更新中
     */
    public function isUpdating(): bool
    {
        return $this->status === self::STATUS_UPDATING;
    }

    /**
     * 是否不工作
     */
    public function isNotWorking(): bool
    {
        return $this->status === self::STATUS_NOT_WORKING;
    }

    /**
     * 是否活跃（工作或更新中）
     */
    public function isActive(): bool
    {
        return $this->isWorking() || $this->isUpdating();
    }

    /**
     * 获取状态描述
     *
     * @return string 状态描述
     */
    public function getStatusDescription(): string
    {
        return match ($this->status) {
            self::STATUS_DISABLED => '禁用',
            self::STATUS_NOT_CONTACTED => '未联系',
            self::STATUS_WORKING => '工作中',
            self::STATUS_UPDATING => '更新中',
            self::STATUS_NOT_WORKING => '不工作',
            default => '未知状态',
        };
    }

    /**
     * 获取主机名
     *
     * @return string 主机名
     */
    public function getHostname(): string
    {
        $parsedUrl = parse_url($this->url);
        return $parsedUrl['host'] ?? $this->url;
    }

    /**
     * 转换为数组
     *
     * @return array<string, mixed> 属性数组
     */
    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'status' => $this->status,
            'status_description' => $this->getStatusDescription(),
            'tier' => $this->tier,
            'num_peers' => $this->numPeers,
            'num_seeds' => $this->numSeeds,
            'num_leeches' => $this->numLeeches,
            'num_downloaded' => $this->numDownloaded,
            'msg' => $this->msg,
            'hostname' => $this->getHostname(),
            'is_active' => $this->isActive(),
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
     * 获取格式化的统计信息
     *
     * @return array<string, mixed> 格式化的统计信息
     */
    public function getFormattedStats(): array
    {
        return [
            'status' => $this->getStatusDescription(),
            'tier' => $this->tier,
            'peers_seeds_leeches' => "{$this->numPeers}/{$this->numSeeds}/{$this->numLeeches}",
            'total_download_count' => $this->numDownloaded,
            'has_message' => !empty(trim($this->msg)),
            'message' => $this->msg ?: '无消息',
        ];
    }
}