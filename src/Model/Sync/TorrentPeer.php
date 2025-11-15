<?php
declare(strict_types=1);

namespace PhpQbittorrent\Model\Sync;

/**
 * TorrentPeer - Torrent Peer连接信息模型
 *
 * 表示连接到特定torrent的peer信息
 *
 * @package PhpQbittorrent\Model\Sync
 */
class TorrentPeer
{
    private string $ip;
    private int $port;
    private string $country;
    private string $countryCode;
    private ?string $client;
    private float $progress;
    private int $downloadSpeed;
    private int $uploadSpeed;
    private int $downloaded;
    private int $uploaded;
    private ?int $connectionType;
    private ?int $flags;
    private ?string $relevant;

    /**
     * 构造函数
     *
     * @param string $IP Peer的IP地址
     * @param int $port Peer的端口号
     * @param string $country 国家名称
     * @param string $countryCode 国家代码
     * @param string|null $client 客户端软件
     * @param float $progress 下载进度（0-1）
     * @param int $downloadSpeed 下载速度（bytes/s）
     * @param int $uploadSpeed 上传速度（bytes/s）
     * @param int $downloaded 已下载量（bytes）
     * @param int $uploaded 已上传量（bytes）
     * @param int|null $connectionType 连接类型
     * @param int|null $flags 标志位
     * @param string|null $relevant 相关信息
     */
    public function __construct(
        string $IP,
        int $port,
        string $country,
        string $countryCode,
        ?string $client = null,
        float $progress = 0.0,
        int $downloadSpeed = 0,
        int $uploadSpeed = 0,
        int $downloaded = 0,
        int $uploaded = 0,
        ?int $connectionType = null,
        ?int $flags = null,
        ?string $relevant = null
    ) {
        $this->ip = $IP;
        $this->port = $port;
        $this->country = $country;
        $this->countryCode = $countryCode;
        $this->client = $client;
        $this->progress = $progress;
        $this->downloadSpeed = $downloadSpeed;
        $this->uploadSpeed = $uploadSpeed;
        $this->downloaded = $downloaded;
        $this->uploaded = $uploaded;
        $this->connectionType = $connectionType;
        $this->flags = $flags;
        $this->relevant = $relevant;
    }

    /**
     * 获取IP地址
     */
    public function getIP(): string
    {
        return $this->ip;
    }

    /**
     * 获取端口号
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * 获取国家名称
     */
    public function getCountry(): string
    {
        return $this->country;
    }

    /**
     * 获取国家代码
     */
    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    /**
     * 获取客户端软件
     */
    public function getClient(): ?string
    {
        return $this->client;
    }

    /**
     * 获取下载进度（0-1）
     */
    public function getProgress(): float
    {
        return $this->progress;
    }

    /**
     * 获取下载速度（bytes/s）
     */
    public function getDownloadSpeed(): int
    {
        return $this->downloadSpeed;
    }

    /**
     * 获取上传速度（bytes/s）
     */
    public function getUploadSpeed(): int
    {
        return $this->uploadSpeed;
    }

    /**
     * 获取已下载量（bytes）
     */
    public function getDownloaded(): int
    {
        return $this->downloaded;
    }

    /**
     * 获取已上传量（bytes）
     */
    public function getUploaded(): int
    {
        return $this->uploaded;
    }

    /**
     * 获取连接类型
     */
    public function getConnectionType(): ?int
    {
        return $this->connectionType;
    }

    /**
     * 获取标志位
     */
    public function getFlags(): ?int
    {
        return $this->flags;
    }

    /**
     * 获取相关信息
     */
    public function getRelevant(): ?string
    {
        return $this->relevant;
    }

    /**
     * 获取完整地址（IP:Port）
     */
    public function getAddress(): string
    {
        return $this->ip . ':' . $this->port;
    }

    /**
     * 转换为数组
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'ip' => $this->ip,
            'port' => $this->port,
            'country' => $this->country,
            'country_code' => $this->countryCode,
            'client' => $this->client,
            'progress' => $this->progress,
            'dl_speed' => $this->downloadSpeed,
            'up_speed' => $this->uploadSpeed,
            'downloaded' => $this->downloaded,
            'uploaded' => $this->uploaded,
            'connection_type' => $this->connectionType,
            'flags' => $this->flags,
            'relevant' => $this->relevant,
        ];
    }

    /**
     * 从数组创建TorrentPeer对象
     *
     * @param array $data 原始数据数组
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['ip'] ?? '',
            $data['port'] ?? 0,
            $data['country'] ?? '',
            $data['country_code'] ?? '',
            $data['client'] ?? null,
            $data['progress'] ?? 0.0,
            $data['dl_speed'] ?? 0,
            $data['up_speed'] ?? 0,
            $data['downloaded'] ?? 0,
            $data['uploaded'] ?? 0,
            $data['connection_type'] ?? null,
            $data['flags'] ?? null,
            $data['relevant'] ?? null
        );
    }
}