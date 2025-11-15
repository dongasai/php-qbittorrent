<?php
declare(strict_types=1);

namespace PhpQbittorrent\Model;

use PhpQbittorrent\Contract\JsonSerializable;
use PhpQbittorrent\Exception\InvalidArgumentException;

/**
 * Torrent属性模型
 *
 * 表示qBittorrent中单个Torrent的详细属性信息
 */
class TorrentProperties implements JsonSerializable
{
    /** @var string 保存路径 */
    private string $savePath;

    /** @var int 创建时间（Unix时间戳） */
    private int $creationDate;

    /** @var int 分片大小（字节） */
    private int $pieceSize;

    /** @var string 注释 */
    private string $comment;

    /** @var int 总浪费数据（字节） */
    private int $totalWasted;

    /** @var int 总上传数据（字节） */
    private int $totalUploaded;

    /** @var int 总下载数据（字节） */
    private int $totalDownloaded;

    /** @var int 上传限制（字节/秒，-1表示无限制） */
    private int $upLimit;

    /** @var int 下载限制（字节/秒，-1表示无限制） */
    private int $dlLimit;

    /** @var int 运行时间（秒） */
    private int $timeElapsed;

    /** @var int 做种时间（秒） */
    private int $seedingTime;

    /** @var int 连接数 */
    private int $nbConnections;

    /** @var int 连接数限制 */
    private int $nbConnectionsLimit;

    /** @var float 分享比例 */
    private float $shareRatio;

    /** @var int 添加时间（Unix时间戳） */
    private int $additionDate;

    /** @var int 完成时间（Unix时间戳，-1表示未完成） */
    private int $completionDate;

    /** @var string 创建者 */
    private string $createdBy;

    /** @var int 平均下载速度（字节/秒） */
    private int $dlSpeedAvg;

    /** @var int 当前下载速度（字节/秒） */
    private int $dlSpeed;

    /** @var int 预计完成时间（秒） */
    private int $eta;

    /** @var int 最后看到完成时间（Unix时间戳） */
    private int $lastSeen;

    /** @var int 连接的Peers数量 */
    private int $peers;

    /** @var int 总Peers数量 */
    private int $peersTotal;

    /** @var int 已拥有的分片数 */
    private int $piecesHave;

    /** @var int 总分片数 */
    private int $piecesNum;

    /** @var int 下次announce时间（秒） */
    private int $reannounce;

    /** @var int 连接的种子数 */
    private int $seeds;

    /** @var int 总种子数 */
    private int $seedsTotal;

    /** @var int 总大小（字节） */
    private int $totalSize;

    /** @var int 平均上传速度（字节/秒） */
    private int $upSpeedAvg;

    /** @var int 当前上传速度（字节/秒） */
    private int $upSpeed;

    /** @var bool 是否为私有种子 */
    private bool $isPrivate;

    /**
     * 从API响应数据创建TorrentProperties实例
     *
     * @param array<string, mixed> $data API响应数据
     * @return self TorrentProperties实例
     * @throws InvalidArgumentException 如果数据格式无效
     */
    public static function fromApiData(array $data): self
    {
        $instance = new self();

        // 必需字段
        if (!isset($data['save_path'])) {
            throw new InvalidArgumentException('save_path is required');
        }
        if (!isset($data['piece_size'])) {
            throw new InvalidArgumentException('piece_size is required');
        }
        if (!isset($data['total_size'])) {
            throw new InvalidArgumentException('total_size is required');
        }

        // 设置属性，提供默认值
        $instance->savePath = (string) ($data['save_path'] ?? '');
        $instance->creationDate = isset($data['creation_date']) ? (int) $data['creation_date'] : 0;
        $instance->pieceSize = (int) $data['piece_size'];
        $instance->comment = (string) ($data['comment'] ?? '');
        $instance->totalWasted = isset($data['total_wasted']) ? (int) $data['total_wasted'] : 0;
        $instance->totalUploaded = isset($data['total_uploaded']) ? (int) $data['total_uploaded'] : 0;
        $instance->totalDownloaded = isset($data['total_downloaded']) ? (int) $data['total_downloaded'] : 0;
        $instance->upLimit = isset($data['up_limit']) ? (int) $data['up_limit'] : -1;
        $instance->dlLimit = isset($data['dl_limit']) ? (int) $data['dl_limit'] : -1;
        $instance->timeElapsed = isset($data['time_elapsed']) ? (int) $data['time_elapsed'] : 0;
        $instance->seedingTime = isset($data['seeding_time']) ? (int) $data['seeding_time'] : 0;
        $instance->nbConnections = isset($data['nb_connections']) ? (int) $data['nb_connections'] : 0;
        $instance->nbConnectionsLimit = isset($data['nb_connections_limit']) ? (int) $data['nb_connections_limit'] : 0;
        $instance->shareRatio = isset($data['share_ratio']) ? (float) $data['share_ratio'] : 0.0;
        $instance->additionDate = isset($data['addition_date']) ? (int) $data['addition_date'] : 0;
        $instance->completionDate = isset($data['completion_date']) ? (int) $data['completion_date'] : -1;
        $instance->createdBy = (string) ($data['created_by'] ?? '');
        $instance->dlSpeedAvg = isset($data['dl_speed_avg']) ? (int) $data['dl_speed_avg'] : 0;
        $instance->dlSpeed = isset($data['dl_speed']) ? (int) $data['dl_speed'] : 0;
        $instance->eta = isset($data['eta']) ? (int) $data['eta'] : 8640000; // 默认值
        $instance->lastSeen = isset($data['last_seen']) ? (int) $data['last_seen'] : 0;
        $instance->peers = isset($data['peers']) ? (int) $data['peers'] : 0;
        $instance->peersTotal = isset($data['peers_total']) ? (int) $data['peers_total'] : 0;
        $instance->piecesHave = isset($data['pieces_have']) ? (int) $data['pieces_have'] : 0;
        $instance->piecesNum = isset($data['pieces_num']) ? (int) $data['pieces_num'] : 0;
        $instance->reannounce = isset($data['reannounce']) ? (int) $data['reannounce'] : 0;
        $instance->seeds = isset($data['seeds']) ? (int) $data['seeds'] : 0;
        $instance->seedsTotal = isset($data['seeds_total']) ? (int) $data['seeds_total'] : 0;
        $instance->totalSize = (int) $data['total_size'];
        $instance->upSpeedAvg = isset($data['up_speed_avg']) ? (int) $data['up_speed_avg'] : 0;
        $instance->upSpeed = isset($data['up_speed']) ? (int) $data['up_speed'] : 0;
        $instance->isPrivate = isset($data['isPrivate']) ? (bool) $data['isPrivate'] : false;

        return $instance;
    }

    /**
     * 私有构造函数
     */
    private function __construct()
    {
    }

    // Getter方法

    public function getSavePath(): string
    {
        return $this->savePath;
    }

    public function getCreationDate(): int
    {
        return $this->creationDate;
    }

    public function getPieceSize(): int
    {
        return $this->pieceSize;
    }

    public function getComment(): string
    {
        return $this->comment;
    }

    public function getTotalWasted(): int
    {
        return $this->totalWasted;
    }

    public function getTotalUploaded(): int
    {
        return $this->totalUploaded;
    }

    public function getTotalDownloaded(): int
    {
        return $this->totalDownloaded;
    }

    public function getUpLimit(): int
    {
        return $this->upLimit;
    }

    public function getDlLimit(): int
    {
        return $this->dlLimit;
    }

    public function getTimeElapsed(): int
    {
        return $this->timeElapsed;
    }

    public function getSeedingTime(): int
    {
        return $this->seedingTime;
    }

    public function getNbConnections(): int
    {
        return $this->nbConnections;
    }

    public function getNbConnectionsLimit(): int
    {
        return $this->nbConnectionsLimit;
    }

    public function getShareRatio(): float
    {
        return $this->shareRatio;
    }

    public function getAdditionDate(): int
    {
        return $this->additionDate;
    }

    public function getCompletionDate(): int
    {
        return $this->completionDate;
    }

    public function getCreatedBy(): string
    {
        return $this->createdBy;
    }

    public function getDlSpeedAvg(): int
    {
        return $this->dlSpeedAvg;
    }

    public function getDlSpeed(): int
    {
        return $this->dlSpeed;
    }

    public function getEta(): int
    {
        return $this->eta;
    }

    public function getLastSeen(): int
    {
        return $this->lastSeen;
    }

    public function getPeers(): int
    {
        return $this->peers;
    }

    public function getPeersTotal(): int
    {
        return $this->peersTotal;
    }

    public function getPiecesHave(): int
    {
        return $this->piecesHave;
    }

    public function getPiecesNum(): int
    {
        return $this->piecesNum;
    }

    public function getReannounce(): int
    {
        return $this->reannounce;
    }

    public function getSeeds(): int
    {
        return $this->seeds;
    }

    public function getSeedsTotal(): int
    {
        return $this->seedsTotal;
    }

    public function getTotalSize(): int
    {
        return $this->totalSize;
    }

    public function getUpSpeedAvg(): int
    {
        return $this->upSpeedAvg;
    }

    public function getUpSpeed(): int
    {
        return $this->upSpeed;
    }

    public function isPrivate(): bool
    {
        return $this->isPrivate;
    }

    /**
     * 检查是否已完成
     *
     * @return bool 是否已完成
     */
    public function isCompleted(): bool
    {
        return $this->completionDate > 0;
    }

    /**
     * 获取格式化的文件大小
     *
     * @return string 格式化的文件大小
     */
    public function getFormattedSize(): string
    {
        return $this->formatBytes($this->totalSize);
    }

    /**
     * 获取格式化的上传速度
     *
     * @return string 格式化的上传速度
     */
    public function getFormattedUpSpeed(): string
    {
        return $this->formatBytes($this->upSpeed) . '/s';
    }

    /**
     * 获取格式化的下载速度
     *
     * @return string 格式化的下载速度
     */
    public function getFormattedDlSpeed(): string
    {
        return $this->formatBytes($this->dlSpeed) . '/s';
    }

    /**
     * 转换为数组
     *
     * @return array<string, mixed> 属性数组
     */
    public function toArray(): array
    {
        return [
            'save_path' => $this->savePath,
            'creation_date' => $this->creationDate,
            'piece_size' => $this->pieceSize,
            'comment' => $this->comment,
            'total_wasted' => $this->totalWasted,
            'total_uploaded' => $this->totalUploaded,
            'total_downloaded' => $this->totalDownloaded,
            'up_limit' => $this->upLimit,
            'dl_limit' => $this->dlLimit,
            'time_elapsed' => $this->timeElapsed,
            'seeding_time' => $this->seedingTime,
            'nb_connections' => $this->nbConnections,
            'nb_connections_limit' => $this->nbConnectionsLimit,
            'share_ratio' => $this->shareRatio,
            'addition_date' => $this->additionDate,
            'completion_date' => $this->completionDate,
            'created_by' => $this->createdBy,
            'dl_speed_avg' => $this->dlSpeedAvg,
            'dl_speed' => $this->dlSpeed,
            'eta' => $this->eta,
            'last_seen' => $this->lastSeen,
            'peers' => $this->peers,
            'peers_total' => $this->peersTotal,
            'pieces_have' => $this->piecesHave,
            'pieces_num' => $this->piecesNum,
            'reannounce' => $this->reannounce,
            'seeds' => $this->seeds,
            'seeds_total' => $this->seedsTotal,
            'total_size' => $this->totalSize,
            'up_speed_avg' => $this->upSpeedAvg,
            'up_speed' => $this->upSpeed,
            'isPrivate' => $this->isPrivate,
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