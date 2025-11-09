<?php
declare(strict_types=1);

namespace PhpQbittorrent\API;

use PhpQbittorrent\Transport\TransportInterface;
use PhpQbittorrent\Exception\ClientException;

/**
 * 传输API类
 *
 * 处理qBittorrent传输相关的API操作，包括下载速度、上传速度、传输统计等
 */
final class TransferAPI
{
    private TransportInterface $transport;

    public function __construct(TransportInterface $transport)
    {
        $this->transport = $transport;
    }

    /**
     * 获取全局传输信息
     *
     * @return array 传输信息
     * @throws ClientException 获取失败
     */
    public function getTransferInfo(): array
    {
        return $this->transport->request('GET', '/api/v2/transfer/info');
    }

    /**
     * 获取下载速度统计
     *
     * @return array 下载速度信息
     * @throws ClientException 获取失败
     */
    public function getDownloadSpeedStats(): array
    {
        $transferInfo = $this->getTransferInfo();

        return [
            'dl_info_speed' => (int) ($transferInfo['dl_info_speed'] ?? 0),
            'dl_info_data' => (int) ($transferInfo['dl_info_data'] ?? 0),
            'dl_rate_limit' => (int) ($transferInfo['dl_rate_limit'] ?? 0),
        ];
    }

    /**
     * 获取上传速度统计
     *
     * @return array 上传速度信息
     * @throws ClientException 获取失败
     */
    public function getUploadSpeedStats(): array
    {
        $transferInfo = $this->getTransferInfo();

        return [
            'up_info_speed' => (int) ($transferInfo['up_info_speed'] ?? 0),
            'up_info_data' => (int) ($transferInfo['up_info_data'] ?? 0),
            'up_rate_limit' => (int) ($transferInfo['up_rate_limit'] ?? 0),
        ];
    }

    /**
     * 获取连接信息
     *
     * @return array 连接信息
     * @throws ClientException 获取失败
     */
    public function getConnectionInfo(): array
    {
        $transferInfo = $this->getTransferInfo();

        return [
            'connection_status' => $transferInfo['connection_status'] ?? 'disconnected',
            'dht_nodes' => (int) ($transferInfo['dht_nodes'] ?? 0),
            'dl_info_speed' => (int) ($transferInfo['dl_info_speed'] ?? 0),
            'up_info_speed' => (int) ($transferInfo['up_info_speed'] ?? 0),
        ];
    }

    /**
     * 设置全局下载速度限制
     *
     * @param int $limit 下载速度限制（字节/秒），0表示无限制
     * @return bool 设置是否成功
     * @throws ClientException 设置失败
     */
    public function setDownloadSpeedLimit(int $limit): bool
    {
        try {
            $this->transport->request('POST', '/api/v2/transfer/setDownloadLimit', [
                'form_params' => ['limit' => $limit]
            ]);
            return true;
        } catch (ClientException $e) {
            return false;
        }
    }

    /**
     * 设置全局上传速度限制
     *
     * @param int $limit 上传速度限制（字节/秒），0表示无限制
     * @return bool 设置是否成功
     * @throws ClientException 设置失败
     */
    public function setUploadSpeedLimit(int $limit): bool
    {
        try {
            $this->transport->request('POST', '/api/v2/transfer/setUploadLimit', [
                'form_params' => ['limit' => $limit]
            ]);
            return true;
        } catch (ClientException $e) {
            return false;
        }
    }

    /**
     * 设置同时的下载限制
     *
     * @param int $limit 同时下载数量限制
     * @return bool 设置是否成功
     * @throws ClientException 设置失败
     */
    public function setMaxDownloadingSlots(int $limit): bool
    {
        try {
            $this->transport->request('POST', '/api/v2/transfer/setDownloadLimit', [
                'form_params' => ['limit' => $limit]
            ]);
            return true;
        } catch (ClientException $e) {
            return false;
        }
    }

    /**
     * 设置同时的上传限制
     *
     * @param int $limit 同时上传数量限制
     * @return bool 设置是否成功
     * @throws ClientException 设置失败
     */
    public function setMaxUploadingSlots(int $limit): bool
    {
        try {
            $this->transport->request('POST', '/api/v2/transfer/setUploadLimit', [
                'form_params' => ['limit' => $limit]
            ]);
            return true;
        } catch (ClientException $e) {
            return false;
        }
    }

    /**
     * 获取当前的速度限制
     *
     * @return array 速度限制信息
     * @throws ClientException 获取失败
     */
    public function getCurrentSpeedLimits(): array
    {
        $transferInfo = $this->getTransferInfo();

        return [
            'dl_rate_limit' => (int) ($transferInfo['dl_rate_limit'] ?? 0),
            'up_rate_limit' => (int) ($transferInfo['up_rate_limit'] ?? 0),
        ];
    }

    /**
     * 限制上传速度
     *
     * @param int $limit 上传速度限制（字节/秒）
     * @return bool 设置是否成功
     */
    public function limitUploadSpeed(int $limit): bool
    {
        return $this->setUploadSpeedLimit($limit);
    }

    /**
     * 限制下载速度
     *
     * @param int $limit 下载速度限制（字节/秒）
     * @return bool 设置是否成功
     */
    public function limitDownloadSpeed(int $limit): bool
    {
        return $this->setDownloadSpeedLimit($limit);
    }

    /**
     * 暂停所有下载
     *
     * @return bool 操作是否成功
     */
    public function pauseAllDownloads(): bool
    {
        try {
            $this->transport->request('POST', '/api/v2/torrents/pause', [
                'form_params' => ['hashes' => 'all']
            ]);
            return true;
        } catch (ClientException $e) {
            return false;
        }
    }

    /**
     * 恢复所有下载
     *
     * @return bool 操作是否成功
     */
    public function resumeAllDownloads(): bool
    {
        try {
            $this->transport->request('POST', '/api/v2/torrents/resume', [
                'form_params' => ['hashes' => 'all']
            ]);
            return true;
        } catch (ClientException $e) {
            return false;
        }
    }

    /**
     * 切换替代速度限制（替代速度限制通常用于节能模式）
     *
     * @param bool $enabled 是否启用替代速度限制
     * @return bool 操作是否成功
     */
    public function toggleAlternativeSpeedLimits(bool $enabled): bool
    {
        try {
            $endpoint = $enabled ? '/api/v2/transfer/speedLimitsMode' : '/api/v2/transfer/speedLimitsMode';
            $this->transport->request('POST', $endpoint);
            return true;
        } catch (ClientException $e) {
            return false;
        }
    }

    /**
     * 获取替代速度限制状态
     *
     * @return bool 替代速度限制是否启用
     */
    public function isAlternativeSpeedLimitsEnabled(): bool
    {
        try {
            $response = $this->transport->request('GET', '/api/v2/transfer/speedLimitsMode');
            return (bool) ($response[0] ?? false);
        } catch (ClientException $e) {
            return false;
        }
    }

    /**
     * 设置连接限制
     *
     * @param int $maxConnections 最大连接数
     * @param int $maxConnectionsPerTorrent 每个torrent的最大连接数
     * @return bool 设置是否成功
     */
    public function setConnectionLimits(int $maxConnections, int $maxConnectionsPerTorrent): bool
    {
        try {
            // 通过设置preferences来调整连接限制
            $this->transport->request('POST', '/api/v2/app/setPreferences', [
                'json' => [
                    'json' => json_encode([
                        'max_connec' => $maxConnections,
                        'max_connec_per_torrent' => $maxConnectionsPerTorrent
                    ], JSON_UNESCAPED_UNICODE)
                ]
            ]);
            return true;
        } catch (ClientException $e) {
            return false;
        }
    }

    /**
     * 获取实时传输统计（可能需要轮询）
     *
     * @return array 实时传输统计
     */
    public function getRealtimeStats(): array
    {
        $transferInfo = $this->getTransferInfo();

        return [
            'timestamp' => time(),
            'download_speed' => (int) ($transferInfo['dl_info_speed'] ?? 0),
            'upload_speed' => (int) ($transferInfo['up_info_speed'] ?? 0),
            'total_downloaded' => (int) ($transferInfo['dl_info_data'] ?? 0),
            'total_uploaded' => (int) ($transferInfo['up_info_data'] ?? 0),
            'dht_nodes' => (int) ($transferInfo['dht_nodes'] ?? 0),
            'connection_status' => $transferInfo['connection_status'] ?? 'disconnected',
        ];
    }

    /**
     * 获取传输历史摘要
     *
     * @return array 传输历史摘要
     */
    public function getTransferSummary(): array
    {
        $stats = $this->getRealtimeStats();

        return [
            'current_download_speed' => $this->formatBytes($stats['download_speed']) . '/s',
            'current_upload_speed' => $this->formatBytes($stats['upload_speed']) . '/s',
            'total_downloaded' => $this->formatBytes($stats['total_downloaded']),
            'total_uploaded' => $this->formatBytes($stats['total_uploaded']),
            'active_connections' => $stats['dht_nodes'],
            'connection_status' => $stats['connection_status'],
            'last_updated' => date('Y-m-d H:i:s', $stats['timestamp']),
        ];
    }

    /**
     * 检查传输系统是否健康
     *
     * @return array 健康状态信息
     */
    public function getHealthStatus(): array
    {
        try {
            $transferInfo = $this->getTransferInfo();

            $status = [
                'healthy' => true,
                'issues' => [],
                'connection_status' => $transferInfo['connection_status'] ?? 'disconnected',
                'dht_nodes' => (int) ($transferInfo['dht_nodes'] ?? 0),
            ];

            // 检查连接状态
            if ($status['connection_status'] !== 'connected') {
                $status['healthy'] = false;
                $status['issues'][] = '连接状态异常：' . $status['connection_status'];
            }

            // 检查DHT节点
            if ($status['dht_nodes'] === 0) {
                $status['issues'][] = 'DHT节点数为0，可能影响发现能力';
            }

            return $status;

        } catch (ClientException $e) {
            return [
                'healthy' => false,
                'issues' => ['无法获取传输信息：' . $e->getMessage()],
                'connection_status' => 'error',
                'dht_nodes' => 0,
            ];
        }
    }

    /**
     * 格式化字节数为可读格式
     *
     * @param int $bytes 字节数
     * @param int $precision 精度
     * @return string 格式化后的字符串
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}