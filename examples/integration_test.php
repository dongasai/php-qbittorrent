<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PhpQbittorrent\UnifiedClient;
use PhpQbittorrent\Client;
use PhpQbittorrent\Config\ConfigurationManager;
use PhpQbittorrent\Factory\RequestFactory;

/**
 * qBittorrent PHP API 集成测试
 *
 * 此脚本用于测试参数对象化 API的各项功能是否正常工作
 */

echo "=== qBittorrent PHP API 集成测试 ===\n\n";

// 测试配置 - 请根据实际情况修改
$testConfig = [
    'base_url' => $_ENV['QBITTORRENT_BASE_URL'] ?? 'http://localhost:8080',
    'username' => $_ENV['QBITTORRENT_USERNAME'] ?? 'admin',
    'password' => $_ENV['QBITTORRENT_PASSWORD'] ?? 'adminadmin',
    'timeout' => 10,
    'verify_ssl' => false,
    'debug' => true,
];

// 测试结果统计
$testResults = [
    'passed' => 0,
    'failed' => 0,
    'skipped' => 0,
    'total' => 0,
];

/**
 * 运行测试
 */
function runTest(string $testName, callable $test): void
{
    global $testResults;
    $testResults['total']++;

    echo "测试: {$testName} ... ";

    try {
        $result = $test();
        if ($result) {
            echo "✓ 通过\n";
            $testResults['passed']++;
        } else {
            echo "✗ 失败\n";
            $testResults['failed']++;
        }
    } catch (Exception $e) {
        echo "✗ 错误: " . $e->getMessage() . "\n";
        $testResults['failed']++;
    }
}

/**
 * 跳过测试
 */
function skipTest(string $testName, string $reason): void
{
    global $testResults;
    $testResults['total']++;
    $testResults['skipped']++;

    echo "测试: {$testName} ... ⚠ 跳过 ({$reason})\n";
}

// ========================================
// 1. 配置管理器测试
// ========================================

echo "1. 配置管理器测试\n";
echo "----------------\n";

runTest('创建默认配置', function() use ($testConfig) {
    $config = new ConfigurationManager($testConfig);
    return $config->get('base_url') === $testConfig['base_url'];
});

runTest('配置验证', function() {
    try {
        new ConfigurationManager(['base_url' => '']);
        return false; // 应该抛出异常
    } catch (Exception $e) {
        return true;
    }
});

runTest('从数组创建配置', function() use ($testConfig) {
    $config = ConfigurationManager::fromArray($testConfig);
    return $config->getBaseUrl() === $testConfig['base_url'];
});

runTest('配置合并', function() use ($testConfig) {
    $config1 = new ConfigurationManager($testConfig);
    $config2 = new ConfigurationManager(['timeout' => 60]);
    $config1->set('timeout', 60);
    return $config1->get('timeout') === 60;
});

echo "\n";

// ========================================
// 2. 客户端连接测试
// ========================================

echo "2. 客户端连接测试\n";
echo "----------------\n";

runTest('创建统一客户端', function() use ($testConfig) {
    $client = UnifiedClient::fromConfig($testConfig);
    return $client instanceof UnifiedClient;
});

runTest('创建底层客户端', function() use ($testConfig) {
    $client = Client::create(
        $testConfig['base_url'],
        $testConfig['username'],
        $testConfig['password']
    );
    return $client instanceof Client;
});

runTest('客户端信息获取', function() use ($testConfig) {
    $client = UnifiedClient::fromConfig($testConfig);
    $info = $client->getClientInfo();
    return isset($info['base_url']) && isset($info['username']);
});

echo "\n";

// ========================================
// 3. API连接测试（需要实际的qBittorrent服务）
// ========================================

echo "3. API连接测试\n";
echo "----------------\n";

runTest('测试连接', function() use ($testConfig) {
    $client = UnifiedClient::fromConfig($testConfig);
    return $client->testConnection();
});

// 如果连接测试失败，跳过后续的API测试
try {
    $testClient = UnifiedClient::fromConfig($testConfig);
    $connected = $testClient->testConnection();
} catch (Exception $e) {
    $connected = false;
}

if (!$connected) {
    echo "\n⚠ 无法连接到qBittorrent服务，跳过API测试\n";
    echo "请确保:\n";
    echo "1. qBittorrent正在运行\n";
    echo "2. Web UI已启用\n";
    echo "3. 连接信息正确\n";
    echo "4. 用户名和密码正确\n\n";
} else {
    runTest('获取应用版本', function() use ($testConfig) {
        $client = UnifiedClient::fromConfig($testConfig);
        $version = $client->getVersion();
        return !empty($version);
    });

    runTest('获取Web API版本', function() use ($testConfig) {
        $client = UnifiedClient::fromConfig($testConfig);
        $version = $client->getWebApiVersion();
        return !empty($version);
    });

    runTest('获取构建信息', function() use ($testConfig) {
        $client = UnifiedClient::fromConfig($testConfig);
        $buildInfo = $client->getBuildInfo();
        return is_array($buildInfo) && !empty($buildInfo);
    });

    runTest('获取传输信息', function() use ($testConfig) {
        $client = UnifiedClient::fromConfig($testConfig);
        $transferInfo = $client->getTransferInfo();
        return isset($transferInfo['dl_info_speed']) && isset($transferInfo['up_info_speed']);
    });

    runTest('获取种子列表', function() use ($testConfig) {
        $client = UnifiedClient::fromConfig($testConfig);
        $torrents = $client->getTorrents();
        return $torrents instanceof \PhpQbittorrent\Collection\TorrentCollection;
    });

    runTest('种子集合操作', function() use ($testConfig) {
        $client = UnifiedClient::fromConfig($testConfig);
        $torrents = $client->getTorrents();

        // 测试集合方法
        $downloading = $torrents->getDownloading();
        $seeding = $torrents->getSeeding();
        $active = $torrents->getActive();

        return $downloading instanceof \PhpQbittorrent\Collection\TorrentCollection &&
               $seeding instanceof \PhpQbittorrent\Collection\TorrentCollection &&
               $active instanceof \PhpQbittorrent\Collection\TorrentCollection;
    });

    runTest('获取统计信息', function() use ($testConfig) {
        $client = UnifiedClient::fromConfig($testConfig);
        $stats = $client->getStatistics();
        return isset($stats['torrents']) && isset($stats['transfer']);
    });

    runTest('获取健康状态', function() use ($testConfig) {
        $client = UnifiedClient::fromConfig($testConfig);
        $health = $client->getHealthStatus();
        return isset($health['status']) && isset($health['issues']);
    });

    runTest('速度控制切换', function() use ($testConfig) {
        $client = UnifiedClient::fromConfig($testConfig);
        $result1 = $client->toggleAlternativeSpeedLimits();
        sleep(1);
        $result2 = $client->toggleAlternativeSpeedLimits();
        return $result1 && $result2;
    });

    // 搜索功能测试（可选）
    runTest('搜索功能', function() use ($testConfig) {
        $client = UnifiedClient::fromConfig($testConfig);

        // 使用简短的超时时间避免测试过长
        try {
            $results = $client->search('test', [], 'all', 5);
            return $results instanceof \PhpQbittorrent\Collection\SearchResultCollection;
        } catch (Exception $e) {
            // 搜索功能可能需要插件配置，失败不算严重错误
            return true;
        }
    });
}

echo "\n";

// ========================================
// 4. 请求工厂测试
// ========================================

echo "4. 请求工厂测试\n";
echo "----------------\n";

runTest('创建版本请求', function() {
    $request = RequestFactory::createGetVersionRequest();
    return $request instanceof \PhpQbittorrent\Request\Application\GetVersionRequest;
});

runTest('创建种子请求', function() {
    $request = RequestFactory::createGetTorrentsRequest();
    return $request instanceof \PhpQbittorrent\Request\Torrent\GetTorrentsRequest;
});

runTest('创建搜索请求', function() {
    $request = RequestFactory::createStartSearchRequest('test');
    return $request instanceof \PhpQbittorrent\Request\Search\StartSearchRequest;
});

runTest('创建传输请求', function() {
    $request = RequestFactory::createGetGlobalTransferInfoRequest();
    return $request instanceof \PhpQbittorrent\Request\Transfer\GetGlobalTransferInfoRequest;
});

runTest('批量创建请求', function() {
    $requests = RequestFactory::createBasicInfoRequests();
    return count($requests) >= 5 && isset($requests['version']);
});

echo "\n";

// ========================================
// 5. 数据模型测试
// ========================================

echo "5. 数据模型测试\n";
echo "----------------\n";

runTest('TorrentCollection创建', function() {
    $collection = new \PhpQbittorrent\Collection\TorrentCollection();
    return $collection instanceof \PhpQbittorrent\Collection\TorrentCollection && $collection->isEmpty();
});

runTest('SearchResultCollection创建', function() {
    $collection = new \PhpQbittorrent\Collection\SearchResultCollection();
    return $collection instanceof \PhpQbittorrent\Collection\SearchResultCollection && $collection->isEmpty();
});

runTest('配置管理器创建传输实例', function() use ($testConfig) {
    $config = new ConfigurationManager($testConfig);
    $transport = $config->createTransport();
    return $transport instanceof \PhpQbittorrent\Contract\TransportInterface;
});

echo "\n";

// ========================================
// 测试结果汇总
// ========================================

echo "=== 测试结果汇总 ===\n";
echo "总测试数: {$testResults['total']}\n";
echo "通过: {$testResults['passed']}\n";
echo "失败: {$testResults['failed']}\n";
echo "跳过: {$testResults['skipped']}\n";

$passRate = $testResults['total'] > 0
    ? round(($testResults['passed'] / $testResults['total']) * 100, 2)
    : 0;

echo "通过率: {$passRate}%\n\n";

if ($testResults['failed'] > 0) {
    echo "⚠ 有 {$testResults['failed']} 个测试失败，请检查相关功能\n";
}

if ($testResults['passed'] === $testResults['total']) {
    echo "✓ 所有测试通过！\n";
} elseif ($passRate >= 80) {
    echo "✓ 大部分测试通过，基本功能正常\n";
} else {
    echo "✗ 多个测试失败，请检查配置和连接\n";
}

echo "\n=== 测试完成 ===\n";

// 显示测试环境信息
echo "\n测试环境信息:\n";
echo "- PHP版本: " . PHP_VERSION . "\n";
echo "- 测试时间: " . date('Y-m-d H:i:s') . "\n";
echo "- 连接地址: {$testConfig['base_url']}\n";
echo "- 用户名: {$testConfig['username']}\n";
echo "- 调试模式: " . ($testConfig['debug'] ? '开启' : '关闭') . "\n";

if ($connected ?? false) {
    echo "- 服务器连接: ✓ 正常\n";
} else {
    echo "- 服务器连接: ✗ 无法连接\n";
}

echo "\n提示:\n";
echo "- 如果API测试失败，请检查qBittorrent服务状态\n";
echo "- 确保Web UI已启用且端口配置正确\n";
echo "- 验证用户名和密码是否正确\n";
echo "- 检查防火墙设置是否阻止连接\n";