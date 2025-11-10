<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PhpQbittorrent\UnifiedClient;
use PhpQbittorrent\Client;
use PhpQbittorrent\Config\ConfigurationManager;
use PhpQbittorrent\Factory\RequestFactory;
use PhpQbittorrent\Collection\TorrentCollection;
use PhpQbittorrent\Collection\SearchResultCollection;

/**
 * qBittorrent PHP API 使用示例
 *
 * 本文件展示了如何使用新的参数对象化 API进行各种操作
 */

echo "=== qBittorrent PHP API 使用示例 ===\n\n";

// 基础配置 - 请根据实际情况修改
$config = [
    'base_url' => 'http://localhost:8080',
    'username' => 'admin',
    'password' => 'adminadmin',
    'timeout' => 30,
    'verify_ssl' => false, // 如果使用HTTPS，请设置为true
    'debug' => true,
];

// ========================================
// 示例 1: 使用统一客户端（推荐方式）
// ========================================

echo "示例 1: 使用统一客户端\n";
echo "--------------------\n";

try {
    // 快速创建客户端
    $client = UnifiedClient::quick($config['base_url'], $config['username'], $config['password']);

    // 或从配置数组创建
    // $client = UnifiedClient::fromConfig($config);

    // 测试连接
    if ($client->testConnection()) {
        echo "✓ 连接测试成功\n";

        // 获取版本信息
        $version = $client->getVersion();
        echo "应用版本: {$version}\n";

        $webApiVersion = $client->getWebApiVersion();
        echo "Web API版本: {$webApiVersion}\n";

        // 获取传输信息
        $transferInfo = $client->getTransferInfo();
        echo "下载速度: " . formatBytes($transferInfo['dl_info_speed']) . "/s\n";
        echo "上传速度: " . formatBytes($transferInfo['up_info_speed']) . "/s\n";

        // 获取统计信息
        $stats = $client->getStatistics();
        echo "种子总数: {$stats['torrents']['total']}\n";
        echo "下载中: {$stats['torrents']['downloading']}\n";
        echo "做种中: {$stats['torrents']['seeding']}\n";
        echo "已完成: {$stats['torrents']['completed']}\n";

    } else {
        echo "✗ 连接测试失败\n";
    }

} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
}

echo "\n";

// ========================================
// 示例 2: 种子管理
// ========================================

echo "示例 2: 种子管理\n";
echo "--------------------\n";

try {
    $client = UnifiedClient::quick($config['base_url'], $config['username'], $config['password']);

    // 获取所有种子
    $torrents = $client->getTorrents();
    echo "种子总数: " . $torrents->count() . "\n";

    // 获取正在下载的种子
    $downloading = $torrents->getDownloading();
    echo "下载中的种子: " . $downloading->count() . "\n";

    // 获取正在做种的种子
    $seeding = $torrents->getSeeding();
    echo "做种中的种子: " . $seeding->count() . "\n";

    // 获取活跃的种子
    $active = $torrents->getActive();
    echo "活跃的种子: " . $active->count() . "\n";

    // 按大小排序获取最大的5个种子
    $largest = $torrents->sortBySize()->slice(0, 5);
    echo "最大的5个种子:\n";
    foreach ($largest as $torrent) {
        echo "- {$torrent->getName()} (" . formatBytes($torrent->getSize()) . ")\n";
    }

    // 按进度排序获取进度最低的种子
    $slowest = $torrents->sortByProgress()->slice(0, 3);
    echo "进度最低的3个种子:\n";
    foreach ($slowest as $torrent) {
        echo "- {$torrent->getName()} (" . round($torrent->getProgress() * 100, 2) . "%)\n";
    }

    // 获取有种子数的种子
    $withSeeders = $torrents->filter(function($torrent) {
        return $torrent->getNumSeeds() > 0;
    });
    echo "有种子数的种子: " . $withSeeders->count() . "\n";

} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
}

echo "\n";

// ========================================
// 示例 3: 添加和管理种子
// ========================================

echo "示例 3: 添加和管理种子\n";
echo "--------------------\n";

try {
    $client = UnifiedClient::quick($config['base_url'], $config['username'], $config['password']);

    // 添加种子URL（示例，请替换为实际的种子链接）
    // $torrentUrl = 'magnet:?xt=urn:btih:example';
    // if ($client->addTorrentFromUrl($torrentUrl, [
    //     'savepath' => '/downloads',
    //     'category' => 'test',
    //     'paused' => false
    // ])) {
    //     echo "✓ 种子添加成功\n";
    // } else {
    //     echo "✗ 种子添加失败\n";
    // }

    // 批量操作示例
    $allTorrents = $client->getTorrents();
    if ($allTorrents->count() > 0) {
        // 暂停所有活跃的种子
        $pausedCount = $client->pauseAllTorrents();
        echo "暂停了 {$pausedCount} 个种子\n";

        // 等待2秒
        sleep(2);

        // 恢复所有暂停的种子
        $resumedCount = $client->resumeAllTorrents();
        echo "恢复了 {$resumedCount} 个种子\n";

        // 清理完成的种子（不删除文件）
        $cleanedCount = $client->cleanCompletedTorrents(false);
        echo "清理了 {$cleanedCount} 个已完成的种子\n";
    }

    // 获取健康状态
    $health = $client->getHealthStatus();
    echo "系统健康状态: {$health['status']}\n";
    if (!empty($health['issues'])) {
        foreach ($health['issues'] as $issue) {
            echo "- {$issue}\n";
        }
    }

} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
}

echo "\n";

// ========================================
// 示例 4: 搜索功能
// ========================================

echo "示例 4: 搜索功能\n";
echo "--------------------\n";

try {
    $client = UnifiedClient::quick($config['base_url'], $config['username'], $config['password']);

    // 执行搜索（示例搜索词）
    $searchPattern = 'ubuntu';
    echo "搜索: {$searchPattern}\n";

    $searchResults = $client->search($searchPattern, [], 'all', 10); // 最多等待10秒

    echo "找到 " . $searchResults->count() . " 个搜索结果\n";

    if ($searchResults->count() > 0) {
        // 获取健康的搜索结果
        $healthyResults = $searchResults->getHealthy();
        echo "健康的结果: " . $healthyResults->count() . "\n";

        // 获取有种子数的结果
        $resultsWithSeeders = $searchResults->getWithSeeders();
        echo "有种子的结果: " . $resultsWithSeeders->count() . "\n";

        // 按评分排序获取前5个结果
        $topResults = $searchResults->sortByScore()->slice(0, 5);
        echo "最佳的5个搜索结果:\n";
        foreach ($topResults as $index => $result) {
            echo ($index + 1) . ". {$result->getFileName()}\n";
            echo "   大小: " . formatBytes($result->getFileSize()) . "\n";
            echo "   种子: {$result->getNbSeeders()}, 下载: {$result->getNbLeechers()}\n";
            echo "   站点: {$result->getSiteUrl()}\n";
            echo "   评分: {$result->getScore()}\n\n";
        }

        // 按大小分组
        $groupBySize = $searchResults->groupBySize();
        echo "按大小分组:\n";
        foreach ($groupBySize as $sizeRange => $results) {
            echo "- {$sizeRange}: " . $results->count() . " 个结果\n";
        }

        // 获取统计信息
        $stats = $searchResults->getStatistics();
        echo "搜索统计:\n";
        echo "- 总结果: {$stats['total_count']}\n";
        echo "- 有种子的: {$stats['with_seeders_count']}\n";
        echo "- 健康的: {$stats['healthy_count']}\n";
        echo "- 总大小: " . formatBytes($stats['total_size']) . "\n";
    }

} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
}

echo "\n";

// ========================================
// 示例 5: 使用高级客户端功能
// ========================================

echo "示例 5: 高级客户端功能\n";
echo "--------------------\n";

try {
    // 使用配置管理器
    $configManager = ConfigurationManager::fromArray($config);

    // 设置调试模式
    $configManager->setLogConfig(true, true, true);

    // 设置缓存
    $configManager->setCacheConfig(true, 300); // 5分钟缓存

    // 创建客户端
    $client = new UnifiedClient(null, $configManager);

    echo "配置信息:\n";
    echo "- 基础URL: " . $configManager->getBaseUrl() . "\n";
    echo "- 超时时间: " . $configManager->get('timeout') . " 秒\n";
    echo "- 调试模式: " . ($configManager->get('debug') ? '开启' : '关闭') . "\n";
    echo "- 缓存: " . ($configManager->get('cache_enabled') ? '开启' : '关闭') . "\n";

    // 使用请求工厂
    $versionRequest = RequestFactory::createGetVersionRequest();
    echo "创建的请求类型: " . get_class($versionRequest) . "\n";

    // 获取客户端信息
    $clientInfo = $client->getClientInfo();
    echo "传输层类型: " . $clientInfo['transport_class'] . "\n";
    echo "已初始化的API模块: " . implode(', ', array_keys(array_filter($clientInfo['api_instances']))) . "\n";

    // 保存配置到文件（示例）
    // $configManager->saveToJsonFile(__DIR__ . '/config.json');
    // echo "配置已保存到文件\n";

} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
}

echo "\n";

// ========================================
// 示例 6: 使用底层Client
// ========================================

echo "示例 6: 使用底层Client\n";
echo "--------------------\n";

try {
    $client = Client::create($config['base_url'], $config['username'], $config['password']);

    // 手动登录
    if ($client->login()) {
        echo "✓ 登录成功\n";

        // 使用应用API
        $versionResponse = $client->application()->getVersion(RequestFactory::createGetVersionRequest());
        echo "应用版本: " . $versionResponse->getVersion() . "\n";

        // 使用传输API
        $transferResponse = $client->transfer()->getGlobalTransferInfo(RequestFactory::createGetGlobalTransferInfoRequest());
        echo "下载速度: " . formatBytes($transferResponse->getDownloadSpeed()) . "/s\n";

        // 使用种子API
        $torrentsResponse = $client->torrents()->getTorrents(RequestFactory::createGetTorrentsRequest());
        echo "种子数量: " . $torrentsResponse->getTorrents()->count() . "\n";

        // 手动登出
        if ($client->logout()) {
            echo "✓ 登出成功\n";
        }
    }

} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
}

echo "\n";

// ========================================
// 辅助函数
// ========================================

/**
 * 格式化字节数
 */
function formatBytes(int $bytes): string
{
    if ($bytes == 0) return '0 B';

    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);

    $bytes /= (1 << (10 * $pow));

    return round($bytes, 2) . ' ' . $units[$pow];
}

echo "=== 示例结束 ===\n";
echo "\n提示:\n";
echo "- 请根据您的qBittorrent服务器配置修改示例中的连接信息\n";
echo "- 某些示例（如添加种子）需要实际可用的种子链接\n";
echo "- 建议在生产环境中启用SSL验证\n";
echo "- 可以通过设置环境变量 QBITTORRENT_BASE_URL, QBITTORRENT_USERNAME, QBITTORRENT_PASSWORD 来配置连接信息\n";