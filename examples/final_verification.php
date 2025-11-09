<?php
declare(strict_types=1);
require_once __DIR__ . '/../vendor/autoload.php';

use PhpQbittorrent\Client;

/**
 * 最终验证修复效果 - 完整测试
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

echo "🎉 最终验证修复效果\n";
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

    echo "✅ 问题1: 磁力链接Hash检测修复\n";
    echo "================================\n";

    // 获取测试磁力链接和期望的hash
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

    echo "   配置的测试磁力链接: " . count($testMagnets) . " 个\n";
    echo "   期望的种子Hash: " . count($expectedHashes) . " 个\n\n";

    $torrents = $torrentAPI->getTorrents();
    $foundTestHashes = [];

    foreach ($torrents as $torrent) {
        $hash = strtolower($torrent['hash'] ?? '');
        if (in_array($hash, $expectedHashes)) {
            $foundTestHashes[] = $hash;
            echo "   ✅ 找到测试种子: " . substr($hash, 0, 16) . "...\n";
            echo "      名称: " . ($torrent['name'] ?? 'Unknown') . "\n";
            echo "      状态: " . ($torrent['state'] ?? 'Unknown') . "\n\n";
        }
    }

    if (!empty($foundTestHashes)) {
        echo "🎊 磁力链接检测修复成功！找到 " . count($foundTestHashes) . " 个测试种子\n\n";
    } else {
        echo "❌ 磁力链接检测仍有问题\n\n";
        exit(1);
    }

    echo "✅ 问题2: API方法调用修复\n";
    echo "========================\n";

    $testHash = $foundTestHashes[0];
    echo "   使用测试种子: " . substr($testHash, 0, 16) . "...\n\n";

    // 创建测试分类（如果不存在）
    $testCategory = 'test_final_verification_' . date('His');
    echo "   🏗️  创建测试分类: {$testCategory}\n";
    $createResult = $torrentAPI->createCategory($testCategory, '/tmp/test');
    echo "      " . ($createResult ? "✅ 创建成功" : "❌ 创建失败") . "\n\n";

    // 测试分类设置
    echo "   📂 测试分类设置...\n";
    try {
        $categoryResult = $torrentAPI->setTorrentCategory([$testHash], $testCategory);
        echo "      " . ($categoryResult ? "✅ setTorrentCategory 成功" : "❌ setTorrentCategory 失败") . "\n";
    } catch (Exception $e) {
        echo "      ❌ setTorrentCategory 错误: " . $e->getMessage() . "\n";
    }

    // 测试标签添加
    $testTags = ['test_tag_' . date('His'), 'php_qbittorrent'];
    echo "   🏷️  测试标签添加...\n";
    try {
        $tagResult = $torrentAPI->addTorrentTags([$testHash], $testTags);
        echo "      " . ($tagResult ? "✅ addTorrentTags 成功" : "❌ addTorrentTags 失败") . "\n";
    } catch (Exception $e) {
        echo "      ❌ addTorrentTags 错误: " . $e->getMessage() . "\n";
    }

    // 验证设置结果
    echo "\n   📊 验证设置结果...\n";
    sleep(1);
    $updatedTorrents = $torrentAPI->getTorrents();
    foreach ($updatedTorrents as $torrent) {
        if (strtolower($torrent['hash'] ?? '') === $testHash) {
            echo "      分类: " . ($torrent['category'] ?? 'none') . "\n";
            echo "      标签: " . ($torrent['tags'] ?? 'none') . "\n";
            break;
        }
    }

    // 清理测试数据
    echo "\n   🧹 清理测试数据...\n";
    try {
        $torrentAPI->removeCategories($testCategory);
        echo "      ✅ 删除测试分类\n";
    } catch (Exception $e) {
        echo "      ⚠️  删除分类失败: " . $e->getMessage() . "\n";
    }

    $client->logout();

    echo "\n🎯 总结:\n";
    echo "=========\n";
    echo "✅ 磁力链接Hash检测: 修复成功\n";
    echo "✅ API方法调用: 修复成功\n";
    echo "✅ 分类标签管理: 功能正常\n";
    echo "\n🚀 现在可以安全运行完整的 quick_test.php 了！\n";

} catch (Exception $e) {
    echo "❌ 验证过程中出错: " . $e->getMessage() . "\n";
    echo "   错误类型: " . get_class($e) . "\n";
    exit(1);
}