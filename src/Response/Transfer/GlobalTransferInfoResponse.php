<?php
declare(strict_types=1);

namespace PhpQbittorrent\Response\Transfer;

use PhpQbittorrent\Response\AbstractResponse;

/**
 * 全局传输信息响应对象
 */
class GlobalTransferInfoResponse extends AbstractResponse
{
    // 基本信息
    private int $dlInfoSpeed;
    private int $dlInfoData;
    private int $upInfoSpeed;
    private int $upInfoData;

    // 限制信息
    private int $dlRateLimit;
    private int $upRateLimit;

    // 连接信息
    private int $dhtNodes;
    private string $connectionStatus;

    // 扩展信息（仅在部分数据请求时返回）
    private bool $queueing;
    private bool $useAltSpeedLimits;
    private int $refreshInterval;

    /**
     * 创建成功的全局传输信息响应
     *
     * @param array<string, mixed> $data 响应数据
     * @param array<string, string> $headers 响应头
     * @param int $statusCode HTTP状态码
     * @param string $rawResponse 原始响应内容
     * @return static 全局传输信息响应实例
     */
    public static function success(
        array $data = [],
        array $headers = [],
        int $statusCode = 200,
        string $rawResponse = ''
    ): static {
        $instance = parent::success($data, $headers, $statusCode, $rawResponse);

        $instance->dlInfoSpeed = $data['dl_info_speed'] ?? 0;
        $instance->dlInfoData = $data['dl_info_data'] ?? 0;
        $instance->upInfoSpeed = $data['up_info_speed'] ?? 0;
        $instance->upInfoData = $data['up_info_data'] ?? 0;
        $instance->dlRateLimit = $data['dl_rate_limit'] ?? 0;
        $instance->upRateLimit = $data['up_rate_limit'] ?? 0;
        $instance->dhtNodes = $data['dht_nodes'] ?? 0;
        $instance->connectionStatus = $data['connection_status'] ?? 'disconnected';

        // 扩展信息（可选）
        $instance->queueing = $data['queueing'] ?? false;
        $instance->useAltSpeedLimits = $data['use_alt_speed_limits'] ?? false;
        $instance->refreshInterval = $data['refresh_interval'] ?? 5000;

        return $instance;
    }

    /**
     * 创建失败的全局传输信息响应
     *
     * @param array<string> $errors 错误信息
     * @param array<string, string> $headers 响应头
     * @param int $statusCode HTTP状态码
     * @param string $rawResponse 原始响应内容
     * @return self 全局传输信息响应实例
     */
    public static function failure(
        array $errors = [],
        array $headers = [],
        int $statusCode = 400,
        string $rawResponse = ''
    ): static {
        $instance = parent::failure($errors, $headers, $statusCode, $rawResponse);

        // 设置默认值
        $instance->dlInfoSpeed = 0;
        $instance->dlInfoData = 0;
        $instance->upInfoSpeed = 0;
        $instance->upInfoData = 0;
        $instance->dlRateLimit = 0;
        $instance->upRateLimit = 0;
        $instance->dhtNodes = 0;
        $instance->connectionStatus = 'disconnected';
        $instance->queueing = false;
        $instance->useAltSpeedLimits = false;
        $instance->refreshInterval = 5000;

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
            return self::success($responseData, $headers, $statusCode, $rawResponse);
        } else {
            return self::failure($errors, $headers, $statusCode, $rawResponse);
        }
    }

    /**
     * 从API响应创建响应对象
     *
     * @param array<string, mixed> $transferInfo 传输信息
     * @param array<string, string> $headers 响应头
     * @param int $statusCode HTTP状态码
     * @param string $rawResponse 原始响应内容
     * @return static 响应对象实例
     */
    public static function fromApiResponse(
        array $transferInfo,
        array $headers = [],
        int $statusCode = 200,
        string $rawResponse = ''
    ): static {
        return self::success($transferInfo, $headers, $statusCode, $rawResponse);
    }

    // 基本信息getter方法
    public function getDownloadSpeed(): int { return $this->dlInfoSpeed; }
    public function getDownloadedData(): int { return $this->dlInfoData; }
    public function getUploadSpeed(): int { return $this->upInfoSpeed; }
    public function getUploadedData(): int { return $this->upInfoData; }

    // 限制信息getter方法
    public function getDownloadRateLimit(): int { return $this->dlRateLimit; }
    public function getUploadRateLimit(): int { return $this->upRateLimit; }

    // 连接信息getter方法
    public function getDhtNodes(): int { return $this->dhtNodes; }
    public function getConnectionStatus(): string { return $this->connectionStatus; }

    // 扩展信息getter方法
    public function isQueueingEnabled(): bool { return $this->queueing; }
    public function isUsingAlternativeSpeedLimits(): bool { return $this->useAltSpeedLimits; }
    public function getRefreshInterval(): int { return $this->refreshInterval; }

    /**
     * 检查是否有下载活动
     *
     * @return bool 是否有下载活动
     */
    public function hasDownloadActivity(): bool
    {
        return $this->dlInfoSpeed > 0;
    }

    /**
     * 检查是否有上传活动
     *
     * @return bool 是否有上传活动
     */
    public function hasUploadActivity(): bool
    {
        return $this->upInfoSpeed > 0;
    }

    /**
     * 检查是否有任何活动
     *
     * @return bool 是否有任何活动
     */
    public function hasActivity(): bool
    {
        return $this->hasDownloadActivity() || $this->hasUploadActivity();
    }

    /**
     * 检查是否连接到网络
     *
     * @return bool 是否已连接
     */
    public function isConnected(): bool
    {
        return $this->connectionStatus === 'connected';
    }

    /**
     * 检查是否被防火墙阻挡
     *
     * @return bool 是否被防火墙阻挡
     */
    public function isFirewalled(): bool
    {
        return $this->connectionStatus === 'firewalled';
    }

    /**
     * 检查是否断开连接
     *
     * @return bool 是否断开连接
     */
    public function isDisconnected(): bool
    {
        return $this->connectionStatus === 'disconnected';
    }

    /**
     * 检查下载是否受限
     *
     * @return bool 下载是否受限
     */
    public function isDownloadLimited(): bool
    {
        return $this->dlRateLimit > 0;
    }

    /**
     * 检查上传是否受限
     *
     * @return bool 上传是否受限
     */
    public function isUploadLimited(): bool
    {
        return $this->upRateLimit > 0;
    }

    /**
     * 获取下载速度（格式化）
     *
     * @return string 格式化的下载速度
     */
    public function getFormattedDownloadSpeed(): string
    {
        return $this->formatBytes($this->dlInfoSpeed) . '/s';
    }

    /**
     * 获取上传速度（格式化）
     *
     * @return string 格式化的上传速度
     */
    public function getFormattedUploadSpeed(): string
    {
        return $this->formatBytes($this->upInfoSpeed) . '/s';
    }

    /**
     * 获取已下载数据（格式化）
     *
     * @return string 格式化的已下载数据
     */
    public function getFormattedDownloadedData(): string
    {
        return $this->formatBytes($this->dlInfoData);
    }

    /**
     * 获取已上传数据（格式化）
     *
     * @return string 格式化的已上传数据
     */
    public function getFormattedUploadedData(): string
    {
        return $this->formatBytes($this->upInfoData);
    }

    /**
     * 获取下载限制（格式化）
     *
     * @return string 格式化的下载限制
     */
    public function getFormattedDownloadRateLimit(): string
    {
        return $this->dlRateLimit > 0 ? $this->formatBytes($this->dlRateLimit) . '/s' : '无限制';
    }

    /**
     * 获取上传限制（格式化）
     *
     * @return string 格式化的上传限制
     */
    public function getFormattedUploadRateLimit(): string
    {
        return $this->upRateLimit > 0 ? $this->formatBytes($this->upRateLimit) . '/s' : '无限制';
    }

    /**
     * 获取传输比率
     *
     * @return float 传输比率
     */
    public function getTransferRatio(): float
    {
        if ($this->dlInfoData === 0) {
            return $this->upInfoData > 0 ? 999.9 : 0.0;
        }

        return round($this->upInfoData / $this->dlInfoData, 3);
    }

    /**
     * 获取会话持续时间（基于下载数据的估算）
     *
     * @return int 会话持续时间（秒）
     */
    public function getSessionDuration(): int
    {
        $totalSpeed = $this->dlInfoSpeed + $this->upInfoSpeed;
        if ($totalSpeed === 0) {
            return 0;
        }

        $totalData = $this->dlInfoData + $this->upInfoData;
        return (int) ($totalData / $totalSpeed);
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
     * 获取格式化的传输信息
     *
     * @return array<string, mixed> 格式化的传输信息
     */
    public function getFormattedInfo(): array
    {
        return [
            'download' => [
                'speed' => $this->getFormattedDownloadSpeed(),
                'total_data' => $this->getFormattedDownloadedData(),
                'limit' => $this->getFormattedDownloadRateLimit(),
                'is_limited' => $this->isDownloadLimited(),
                'has_activity' => $this->hasDownloadActivity(),
            ],
            'upload' => [
                'speed' => $this->getFormattedUploadSpeed(),
                'total_data' => $this->getFormattedUploadedData(),
                'limit' => $this->getFormattedUploadRateLimit(),
                'is_limited' => $this->isUploadLimited(),
                'has_activity' => $this->hasUploadActivity(),
            ],
            'connection' => [
                'status' => $this->connectionStatus,
                'is_connected' => $this->isConnected(),
                'is_firewalled' => $this->isFirewalled(),
                'is_disconnected' => $this->isDisconnected(),
                'dht_nodes' => $this->dhtNodes,
            ],
            'overall' => [
                'transfer_ratio' => $this->getTransferRatio(),
                'has_activity' => $this->hasActivity(),
                'session_duration' => $this->getSessionDuration(),
                'use_alt_speed_limits' => $this->useAltSpeedLimits,
                'queueing_enabled' => $this->queueing,
                'refresh_interval' => $this->refreshInterval,
            ],
        ];
    }

    /**
     * 转换为数组格式
     *
     * @return array<string, mixed> 响应数据数组
     */
    public function toArray(): array
    {
        $data = parent::toArray();

        $data['dl_info_speed'] = $this->dlInfoSpeed;
        $data['dl_info_data'] = $this->dlInfoData;
        $data['up_info_speed'] = $this->upInfoSpeed;
        $data['up_info_data'] = $this->upInfoData;
        $data['dl_rate_limit'] = $this->dlRateLimit;
        $data['up_rate_limit'] = $this->upRateLimit;
        $data['dht_nodes'] = $this->dhtNodes;
        $data['connection_status'] = $this->connectionStatus;
        $data['queueing'] = $this->queueing;
        $data['use_alt_speed_limits'] = $this->useAltSpeedLimits;
        $data['refresh_interval'] = $this->refreshInterval;
        $data['formatted_info'] = $this->getFormattedInfo();

        return $data;
    }

    /**
     * 获取响应的摘要信息
     *
     * @return array<string, mixed> 摘要信息
     */
    public function getSummary(): array
    {
        return [
            'success' => $this->isSuccess(),
            'download_speed' => $this->dlInfoSpeed,
            'upload_speed' => $this->upInfoSpeed,
            'downloaded_data' => $this->dlInfoData,
            'uploaded_data' => $this->upInfoData,
            'transfer_ratio' => $this->getTransferRatio(),
            'connection_status' => $this->connectionStatus,
            'is_connected' => $this->isConnected(),
            'has_activity' => $this->hasActivity(),
            'use_alt_speed_limits' => $this->useAltSpeedLimits,
            'status_code' => $this->getStatusCode(),
            'error_count' => count($this->getErrors()),
        ];
    }
}