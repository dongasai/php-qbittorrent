<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PhpQbittorrent\UnifiedClient;

/**
 * qBittorrent Sync API使用示例
 *
 * 演示如何使用同步API进行实时监控和数据获取
 */

echo "=== qBittorrent Sync API 使用示例 ===\n\n";

// 从环境变量读取配置
$baseUrl = $_ENV['QBITTORRENT_URL'] ?? 'http://localhost:8080';
$username = $_ENV['QBITTORRENT_USERNAME'] ?? 'admin';
$password = $_ENV['QBITTORRENT_PASSWORD'] ?? 'adminadmin';

try {
    // 创建统一客户端
    $client = new UnifiedClient($baseUrl, $username, $password);

    echo "正在连接到 qBittorrent 服务器: $baseUrl\n";

    // 登录
    if (!$client->login()) {
        echo "❌ 登录失败，请检查用户名和密码\n";
        exit(1);
    }

    echo "✅ 登录成功！\n\n";

    // 1. 获取主要数据同步 - 完整更新
    echo "📊 1. 获取主要数据同步（完整更新）:\n";
    $mainData = $client->getMainData(0);

    echo "   - 响应ID: {$mainData['rid']}\n";
    echo "   - 是否完整更新: " . ($mainData['full_update'] ? '是' : '否') . "\n";
    echo "   - Torrents数量: " . count($mainData['torrents']) . "\n";
    echo "   - 分类数量: " . count($mainData['categories']) . "\n";
    echo "   - 标签数量: " . count($mainData['tags']) . "\n";

    if (!empty($mainData['server_state'])) {
        $serverState = $mainData['server_state'];
        echo "   - 服务器状态:\n";
        echo "     * 下载速度: " . formatBytes($serverState['dl_info_speed'] ?? 0) . "/s\n";
        echo "     * 上传速度: " . formatBytes($serverState['up_info_speed'] ?? 0) . "/s\n";
    }

    echo "\n";

    // 2. 增量更新
    echo "📈 2. 增量更新测试（使用上次rid）:\n";
    $incrementalData = $client->getMainData($mainData['rid']);

    echo "   - 响应ID: {$incrementalData['rid']}\n";
    echo "   - 是否完整更新: " . ($incrementalData['full_update'] ? '是' : '否') . "\n";
    echo "   - Torrents数量: " . count($incrementalData['torrents']) . "\n";
    echo "   - 已删除Torrents: " . count($incrementalData['torrents_removed']) . "\n";

    echo "\n";

    // 3. 获取实时统计信息
    echo "📈 3. 获取实时统计信息:\n";
    $stats = $client->getRealtimeStats();

    echo "   - 时间戳: " . date('Y-m-d H:i:s', $stats['timestamp']) . "\n";
    echo "   - 总Torrents: {$stats['total_torrents']}\n";
    echo "   - 活跃Torrents: {$stats['active_torrents']}\n";
    echo "   - 下载中: {$stats['downloading_torrents']}\n";
    echo "   - 做种中: {$stats['seeding_torrents']}\n";
    echo "   - 已暂停: {$stats['paused_torrents']}\n";
    echo "   - 总大小: " . formatBytes($stats['total_size']) . "\n";
    echo "   - 总下载速度: " . formatBytes($stats['total_download_speed']) . "/s\n";
    echo "   - 总上传速度: " . formatBytes($stats['total_upload_speed']) . "/s\n";

    echo "\n";

    // 4. 获取Torrent Peers数据（如果有torrent）
    if (!empty($mainData['torrents'])) {
        $firstHash = get_first_array_key($mainData['torrents']);

        echo "🌍 4. 获取第一个Torrent的Peers数据:\n";
        echo "   - Torrent Hash: $firstHash\n";

        try {
            $peersData = $client->getTorrentPeers($firstHash, 0);

            echo "   - Peers数量: {$peersData['peers_count']}\n";
            echo "   - 总下载速度: " . formatBytes($peersData['total_download_speed']) . "/s\n";
            echo "   - 总上传速度: " . formatBytes($peersData['total_upload_speed']) . "/s\n";

            if (!empty($peersData['peers'])) {
                echo "   - 前3个Peers:\n";
                $count = 0;
                foreach ($peersData['peers'] as $peer) {
                    if ($count >= 3) break;

                    $country = $peer['country'] ?? 'Unknown';
                    $client = $peer['client'] ?? 'Unknown';
                    $progress = round(($peer['progress'] ?? 0) * 100, 1);

                    echo "     * $country - $client - {$progress}%\n";
                    $count++;
                }
            }
        } catch (\Exception $e) {
            echo "   ❌ 获取Peers数据失败: " . $e->getMessage() . "\n";
        }
    }

    echo "\n";

    // 5. 简单监控演示（非阻塞，只演示几轮）
    echo "⏰ 5. 简单监控演示（3轮，每2秒一次）:\n";
    $monitorCount = 0;
    $callback = function ($data) use (&$monitorCount) {
        $monitorCount++;
        $torrentCount = count($data['torrents']);
        echo "   监控轮次 #$monitorCount - Torrents: $torrentCount";

        if ($data['full_update']) {
            echo " (完整更新)";
        } else {
            $changes = count($data['torrents_removed']) + count($data['categories_removed']) + count($data['tags_removed']);
            if ($changes > 0) {
                echo " (部分更新，{$changes}个变化)";
            } else {
                echo " (无变化)";
            }
        }
        echo "\n";

        // 3轮后停止
        if ($monitorCount >= 3) {
            throw new \RuntimeException('Monitor completed');
        }
    };

    try {
        $client->monitorChanges(2, $callback);
    } catch (\RuntimeException $e) {
        if ($e->getMessage() === 'Monitor completed') {
            echo "   ✅ 监控演示完成\n";
        } else {
            echo "   ❌ 监控过程中发生错误: " . $e->getMessage() . "\n";
        }
    }

    echo "\n";

    // 登出
    echo "🚪 正在登出...\n";
    if ($client->logout()) {
        echo "✅ 登出成功！\n";
    } else {
        echo "❌ 登出失败\n";
    }

} catch (\Exception $e) {
    echo "❌ 发生错误: " . $e->getMessage() . "\n";
    echo "文件: " . $e->getFile() . "\n";
    echo "行号: " . $e->getLine() . "\n";
    exit(1);
}

/**
 * 格式化字节数为人类可读格式
 */
function formatBytes(int $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);

    $bytes /= pow(1024, $pow);

    return round($bytes, 2) . ' ' . $units[$pow];
}

/**
 * 获取数组的第一个键名
 */
function get_first_array_key(array $array): ?string
{
    foreach ($array as $key => $value) {
        return $key;
    }
    return null;
}

echo "\n=== 示例完成 ===\n";