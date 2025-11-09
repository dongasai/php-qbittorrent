<?php
declare(strict_types=1);
require_once __DIR__ . '/../vendor/autoload.php';

use PhpQbittorrent\Client;

/**
 * 快速验证修复效果
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

echo "🔧 快速验证修复效果\n";
echo "===================\n\n";

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

try {
    $client = Client::fromArray($config);
    $client->login();

    if (!$client->isLoggedIn()) {
        echo "❌ 登录失败\n";
        exit(1);
    }

    $torrentAPI = $client->getTorrentAPI();

    // 测试磁力链接检测修复
    echo "🧲 测试磁力链接检测修复:\n";

    $testMagnets = [];
    for ($i = 1; $i <= 4; $i++) {
        $magnetKey = "QBITTORRENT_TEST_MAGNET_{$i}";
        if (!empty($_ENV[$magnetKey])) {
            $testMagnets[] = $_ENV[$magnetKey];
        }
    }

    $extractHashFromMagnet = function(string $magnet): ?string {
        if (preg_match('/urn:btih:([a-fA-F0-9]{40})/i', $magnet, $matches)) {
            return strtolower($matches[1]);
        }
        return null;
    };

    $expectedHashes = [];
    foreach ($testMagnets as $magnet) {
        $hash = $extractHashFromMagnet($magnet);
        if ($hash) {
            $expectedHashes[] = $hash;
        }
    }

    echo "   期望的测试种子: " . count($expectedHashes) . " 个\n";

    $torrents = $torrentAPI->getTorrents();
    $foundTestHashes = [];

    foreach ($torrents as $torrent) {
        $hash = strtolower($torrent['hash'] ?? '');
        if (in_array($hash, $expectedHashes)) {
            $foundTestHashes[] = $hash;
            echo "   ✅ 找到: {$hash} - " . ($torrent['name'] ?? 'Unknown') . "\n";
        }
    }

    echo "   找到的测试种子: " . count($foundTestHashes) . " 个\n\n";

    // 测试API方法修复
    echo "🔧 测试API方法修复:\n";

    if (!empty($foundTestHashes)) {
        $testHash = $foundTestHashes[0];
        echo "   测试种子: {$testHash}\n";

        try {
            // 测试分类设置
            $testCategory = 'test_quick_fix';
            echo "   🔧 测试 setTorrentCategory...\n";
            $result = $torrentAPI->setTorrentCategory([$testHash], $testCategory);
            echo "     " . ($result ? "✅ 成功" : "❌ 失败") . "\n";

            // 测试标签添加
            $testTags = ['test-tag-1', 'test-tag-2'];
            echo "   🏷️  测试 addTorrentTags...\n";
            $result = $torrentAPI->addTorrentTags([$testHash], $testTags);
            echo "     " . ($result ? "✅ 成功" : "❌ 失败") . "\n";

            // 验证结果
            sleep(1);
            $updatedTorrents = $torrentAPI->getTorrents();
            foreach ($updatedTorrents as $torrent) {
                if (strtolower($torrent['hash'] ?? '') === $testHash) {
                    echo "   📊 验证结果:\n";
                    echo "     分类: " . ($torrent['category'] ?? 'none') . "\n";
                    echo "     标签: " . ($torrent['tags'] ?? 'none') . "\n";
                    break;
                }
            }

        } catch (Exception $e) {
            echo "   ❌ API调用错误: " . $e->getMessage() . "\n";
        }
    }

    $client->logout();
    echo "\n✅ 快速验证完成\n";

} catch (Exception $e) {
    echo "❌ 错误: " . $e->getMessage() . "\n";
    exit(1);
}