<?php
declare(strict_types=1);
require_once __DIR__ . '/../vendor/autoload.php';

use PhpQbittorrent\Client;

/**
 * 磁力链接Hash检测和调试脚本
 * 用于诊断为什么测试脚本无法检测到新增的种子
 */

// 加载环境变量
function loadEnv(string $file): void
{
    if (!file_exists($file)) {
        echo "❌ 未找到 {$file} 文件\n";
        exit(1);
    }

    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) {
            continue;
        }

        if (str_contains($line, '=')) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // 移除引号
            if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                $value = substr($value, 1, -1);
            }

            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
        }
    }
}

/**
 * 从磁力链接提取hash
 */
function extractHashFromMagnet(string $magnet): ?string
{
    if (preg_match('/urn:btih:([a-fA-F0-9]{40})/i', $magnet, $matches)) {
        return strtolower($matches[1]);
    }
    return null;
}

/**
 * 获取测试磁力链接配置
 */
function getTestMagnets(): array
{
    $magnets = [];
    for ($i = 1; $i <= 4; $i++) {
        $magnetKey = "QBITTORRENT_TEST_MAGNET_{$i}";
        if (!empty($_ENV[$magnetKey])) {
            $magnets[] = [
                'magnet' => $_ENV[$magnetKey],
                'hash' => extractHashFromMagnet($_ENV[$magnetKey]),
                'key' => $magnetKey
            ];
        }
    }
    return $magnets;
}

// ============================================================================
// 主程序
// ============================================================================

echo "🔍 qBittorrent 磁力链接Hash检测调试工具\n";
echo "==========================================\n\n";

// 加载环境变量
loadEnv(__DIR__ . '/../.env');

// 获取配置
$config = [
    'url' => $_ENV['QBITTORRENT_URL'] ?? 'http://localhost:8080',
    'username' => $_ENV['QBITTORRENT_USERNAME'] ?? 'admin',
    'password' => $_ENV['QBITTORRENT_PASSWORD'] ?? 'adminpass',
    'timeout' => (float) ($_ENV['QBITTORRENT_TIMEOUT'] ?? 30.0),
    'verify_ssl' => filter_var($_ENV['QBITTORRENT_VERIFY_SSL'] ?? 'false', FILTER_VALIDATE_BOOLEAN),
];

echo "📋 连接配置:\n";
echo "   URL: {$config['url']}\n";
echo "   用户名: {$config['username']}\n";
echo "   SSL验证: " . ($config['verify_ssl'] ? '启用' : '禁用') . "\n\n";

// 获取测试磁力链接
$testMagnets = getTestMagnets();

echo "🧲 测试磁力链接配置:\n";
if (empty($testMagnets)) {
    echo "   ❌ 未配置测试磁力链接\n";
    exit(1);
}

foreach ($testMagnets as $index => $magnetInfo) {
    echo "   [" . ($index + 1) . "] {$magnetInfo['key']}\n";
    echo "       Hash: {$magnetInfo['hash']}\n";
    echo "       URL: " . substr($magnetInfo['magnet'], 0, 80) . "...\n\n";
}

try {
    // 创建客户端
    $client = Client::fromArray($config);

    echo "🔗 正在登录...\n";
    $client->login();

    if (!$client->isLoggedIn()) {
        echo "❌ 登录失败\n";
        exit(1);
    }
    echo "✅ 登录成功\n\n";

    $torrentAPI = $client->getTorrentAPI();

    // 获取当前所有种子
    echo "📊 获取当前种子列表...\n";
    $currentTorrents = $torrentAPI->getTorrents();
    $currentHashes = array_map(function($torrent) {
        return strtolower($torrent['hash'] ?? '');
    }, $currentTorrents);

    echo "   当前种子数量: " . count($currentTorrents) . "\n";
    echo "   当前Hash列表:\n";
    foreach ($currentTorrents as $torrent) {
        $hash = strtolower($torrent['hash'] ?? '');
        $name = $torrent['name'] ?? 'Unknown';
        $state = $torrent['state'] ?? 'Unknown';
        $progress = round(($torrent['progress'] ?? 0) * 100, 1);
        echo "     {$hash} - {$name} ({$state}, {$progress}%)\n";
    }
    echo "\n";

    // 检查测试磁力链接对应的种子是否已存在
    echo "🔍 检查测试磁力链接对应的种子...\n";
    $foundMagnets = [];
    $missingMagnets = [];

    foreach ($testMagnets as $magnetInfo) {
        $expectedHash = strtolower($magnetInfo['hash']);
        $isFound = false;

        foreach ($currentTorrents as $torrent) {
            $actualHash = strtolower($torrent['hash'] ?? '');
            if ($actualHash === $expectedHash) {
                $foundMagnets[] = [
                    'magnet_info' => $magnetInfo,
                    'torrent' => $torrent
                ];
                $isFound = true;
                break;
            }
        }

        if (!$isFound) {
            $missingMagnets[] = $magnetInfo;
        }
    }

    echo "   ✅ 已存在的测试种子: " . count($foundMagnets) . " 个\n";
    foreach ($foundMagnets as $found) {
        $torrent = $found['torrent'];
        echo "     {$torrent['hash']} - {$torrent['name']} ({$torrent['state']})\n";
    }

    echo "   ❌ 缺失的测试种子: " . count($missingMagnets) . " 个\n";
    foreach ($missingMagnets as $missing) {
        echo "     {$missing['hash']} - {$missing['key']}\n";
    }

    echo "\n";

    // 如果有缺失的种子，尝试添加
    if (!empty($missingMagnets)) {
        echo "🔧 尝试添加缺失的磁力链接...\n";

        $beforeCount = count($currentTorrents);
        $successCount = 0;

        foreach ($missingMagnets as $magnetInfo) {
            echo "   正在添加: {$magnetInfo['key']}...\n";

            try {
                $result = $torrentAPI->addTorrents([$magnetInfo['magnet']]);

                if ($result) {
                    echo "     ✅ 添加请求成功\n";
                    $successCount++;
                } else {
                    echo "     ❌ 添加请求失败\n";
                }
            } catch (Exception $e) {
                echo "     ❌ 添加异常: " . $e->getMessage() . "\n";
            }
        }

        // 等待一下让qBittorrent处理
        echo "   ⏳ 等待qBittorrent处理新种子...\n";
        sleep(10);

        // 重新检查种子列表
        echo "   🔄 重新检查种子列表...\n";
        $newTorrents = $torrentAPI->getTorrents();
        $newHashes = array_map(function($torrent) {
            return strtolower($torrent['hash'] ?? '');
        }, $newTorrents);

        $addedHashes = array_diff($newHashes, $currentHashes);

        echo "   📊 添加前后对比:\n";
        echo "     添加前种子数: {$beforeCount}\n";
        echo "     添加后种子数: " . count($newTorrents) . "\n";
        echo "     成功请求: {$successCount} 个\n";
        echo "     实际新增: " . count($addedHashes) . " 个\n";

        if (!empty($addedHashes)) {
            echo "     ✅ 新增Hash列表:\n";
            foreach ($addedHashes as $hash) {
                foreach ($newTorrents as $torrent) {
                    if (strtolower($torrent['hash'] ?? '') === $hash) {
                        echo "       {$hash} - {$torrent['name']} ({$torrent['state']})\n";
                        break;
                    }
                }
            }
        } else {
            echo "     ❌ 未检测到新增种子\n";

            // 详细分析原因
            echo "   🔍 详细分析:\n";

            // 检查是否有重复的种子
            $duplicateCount = 0;
            foreach ($missingMagnets as $magnetInfo) {
                $expectedHash = strtolower($magnetInfo['hash']);
                foreach ($newTorrents as $torrent) {
                    $actualHash = strtolower($torrent['hash'] ?? '');
                    if ($actualHash === $expectedHash) {
                        $duplicateCount++;
                        echo "     种子可能已存在: {$actualHash} - {$torrent['name']}\n";
                    }
                }
            }

            if ($duplicateCount === 0) {
                echo "     可能原因:\n";
                echo "       1. qBittorrent还在处理磁力链接元数据下载\n";
                echo "       2. 磁力链接无效或种子不存在\n";
                echo "       3. qBittorrent配置问题（如保存路径）\n";
                echo "       4. 网络连接问题\n";
            }
        }
    }

    echo "\n🎯 建议:\n";
    echo "1. 如果种子处于'metaDL'状态，请等待元数据下载完成\n";
    echo "2. 检查qBittorrent的日志查看具体错误信息\n";
    echo "3. 确保保存路径存在且有写入权限\n";
    echo "4. 尝试手动添加磁力链接验证其有效性\n";
    echo "5. 检查网络连接和防火墙设置\n";

    $client->logout();
    echo "\n✅ 调试完成\n";

} catch (Exception $e) {
    echo "❌ 错误: " . $e->getMessage() . "\n";
    echo "   错误类型: " . get_class($e) . "\n";
    exit(1);
}