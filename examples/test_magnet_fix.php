<?php
declare(strict_types=1);
require_once __DIR__ . '/../vendor/autoload.php';

use PhpQbittorrent\Client;

/**
 * 测试修复后的磁力链接检测功能
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
 * 获取测试磁力链接
 */
function getTestMagnets(): array
{
    $magnets = [];
    for ($i = 1; $i <= 4; $i++) {
        $magnetKey = "QBITTORRENT_TEST_MAGNET_{$i}";
        if (!empty($_ENV[$magnetKey])) {
            $magnets[] = $_ENV[$magnetKey];
        }
    }
    return $magnets;
}

// ============================================================================
// 主程序
// ============================================================================

echo "🔧 测试修复后的磁力链接检测功能\n";
echo "==================================\n\n";

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

echo "📋 连接配置: {$config['url']}\n\n";

try {
    // 创建客户端
    $client = Client::fromArray($config);
    $client->login();

    if (!$client->isLoggedIn()) {
        echo "❌ 登录失败\n";
        exit(1);
    }
    echo "✅ 登录成功\n\n";

    $torrentAPI = $client->getTorrentAPI();

    // 获取测试磁力链接
    $testMagnets = getTestMagnets();
    echo "🧲 配置的测试磁力链接: " . count($testMagnets) . " 个\n";

    $expectedHashes = [];
    foreach ($testMagnets as $index => $magnet) {
        $hash = extractHashFromMagnet($magnet);
        if ($hash) {
            $expectedHashes[] = $hash;
            echo "   [" . ($index + 1) . "] {$hash}\n";
            echo "       URL: " . substr($magnet, 0, 60) . "...\n\n";
        }
    }

    // 查找所有种子
    echo "🔍 在qBittorrent中查找测试种子...\n";
    $torrents = $torrentAPI->getTorrents();

    $foundTestHashes = [];
    $metaDLCount = 0;

    foreach ($torrents as $torrent) {
        $hash = strtolower($torrent['hash'] ?? '');
        if (in_array($hash, $expectedHashes)) {
            $foundTestHashes[] = $hash;
            echo "   ✅ 找到测试种子:\n";
            echo "       Hash: {$hash}\n";
            echo "       名称: " . ($torrent['name'] ?? 'Unknown') . "\n";
            echo "       状态: " . ($torrent['state'] ?? 'Unknown') . "\n";
            echo "       进度: " . round(($torrent['progress'] ?? 0) * 100, 1) . "%\n";
            echo "       大小: " . formatBytes($torrent['size'] ?? 0) . "\n\n";

            if ($torrent['state'] === 'metaDL') {
                $metaDLCount++;
            }
        }
    }

    echo "📊 检测结果:\n";
    echo "   期望的测试种子: " . count($expectedHashes) . " 个\n";
    echo "   找到的测试种子: " . count($foundTestHashes) . " 个\n";
    echo "   正在下载元数据: {$metaDLCount} 个\n\n";

    if (!empty($foundTestHashes)) {
        echo "🎉 修复成功！测试脚本现在能够识别测试种子了！\n";
        echo "💡 这些hash将可以用于分类标签管理测试:\n";
        foreach ($foundTestHashes as $hash) {
            echo "   - {$hash}\n";
        }
        echo "\n✅ 可以重新运行 quick_test.php 来完成完整的测试流程\n";
    } else {
        echo "❌ 仍然没有找到测试种子\n";
        echo "💡 请检查:\n";
        echo "   1. .env 文件中的磁力链接是否正确\n";
        echo "   2. qBittorrent是否正在运行\n";
        echo "   3. 磁力链接是否有效\n";
    }

    $client->logout();
    echo "\n✅ 测试完成\n";

} catch (Exception $e) {
    echo "❌ 错误: " . $e->getMessage() . "\n";
    echo "   错误类型: " . get_class($e) . "\n";
    exit(1);
}

function formatBytes(int $bytes, int $precision = 2): string
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));

    return round($bytes, $precision) . ' ' . $units[$pow];
}