<?php
declare(strict_types=1);
require_once __DIR__ . '/../vendor/autoload.php';

use PhpQbittorrent\Client;

/**
 * 测试修复后的API方法调用
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

echo "🔧 测试修复后的API方法调用\n";
echo "===========================\n\n";

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

    // 获取测试种子hash
    $testMagnets = getTestMagnets();
    $expectedHashes = [];
    foreach ($testMagnets as $magnet) {
        $hash = extractHashFromMagnet($magnet);
        if ($hash) {
            $expectedHashes[] = $hash;
        }
    }

    $torrents = $torrentAPI->getTorrents();
    $testHash = null;

    foreach ($torrents as $torrent) {
        $hash = strtolower($torrent['hash'] ?? '');
        if (in_array($hash, $expectedHashes)) {
            $testHash = $hash;
            echo "✅ 找到测试种子: " . substr($hash, 0, 16) . "...\n";
            echo "   名称: " . ($torrent['name'] ?? 'Unknown') . "\n";
            echo "   状态: " . ($torrent['state'] ?? 'Unknown') . "\n\n";
            break;
        }
    }

    if (!$testHash) {
        echo "❌ 未找到测试种子\n";
        exit(1);
    }

    echo "🧪 测试关键API方法:\n";
    echo "==================\n";

    // 测试1: 暂停/恢复
    echo "1. 测试暂停/恢复功能...\n";
    try {
        $pauseResult = $torrentAPI->pauseTorrents([$testHash]);
        echo "   暂停: " . ($pauseResult ? "✅ 成功" : "❌ 失败") . "\n";

        sleep(1);

        $resumeResult = $torrentAPI->resumeTorrents([$testHash]);
        echo "   恢复: " . ($resumeResult ? "✅ 成功" : "❌ 失败") . "\n";
    } catch (Exception $e) {
        echo "   ❌ 暂停/恢复错误: " . $e->getMessage() . "\n";
    }

    // 测试2: 重新校验
    echo "\n2. 测试重新校验功能...\n";
    try {
        $recheckResult = $torrentAPI->recheckTorrents([$testHash]);
        echo "   重新校验: " . ($recheckResult ? "✅ 成功" : "❌ 失败") . "\n";
    } catch (Exception $e) {
        echo "   ❌ 重新校验错误: " . $e->getMessage() . "\n";
    }

    // 测试3: 分类设置
    echo "\n3. 测试分类设置...\n";
    try {
        $testCategory = 'test_api_methods';

        // 首先创建分类
        $createResult = $torrentAPI->createCategory($testCategory, '/tmp/test');
        echo "   创建分类: " . ($createResult ? "✅ 成功" : "❌ 失败") . "\n";

        if ($createResult) {
            // 设置分类
            $categoryResult = $torrentAPI->setTorrentCategory([$testHash], $testCategory);
            echo "   设置分类: " . ($categoryResult ? "✅ 成功" : "❌ 失败") . "\n";

            // 清理分类
            $torrentAPI->removeCategories($testCategory);
            echo "   清理分类: ✅ 完成\n";
        }
    } catch (Exception $e) {
        echo "   ❌ 分类设置错误: " . $e->getMessage() . "\n";
    }

    // 测试4: 标签管理
    echo "\n4. 测试标签管理...\n";
    try {
        $testTags = ['test_tag_api', 'php_qbittorrent'];

        // 添加标签
        $addResult = $torrentAPI->addTorrentTags([$testHash], $testTags);
        echo "   添加标签: " . ($addResult ? "✅ 成功" : "❌ 失败") . "\n";

        if ($addResult) {
            // 验证标签
            sleep(1);
            $updatedTorrents = $torrentAPI->getTorrents();
            foreach ($updatedTorrents as $torrent) {
                if (strtolower($torrent['hash'] ?? '') === $testHash) {
                    echo "   当前标签: " . ($torrent['tags'] ?? 'none') . "\n";
                    break;
                }
            }

            // 清理标签
            $removeResult = $torrentAPI->removeTorrentTags([$testHash], $testTags);
            echo "   清理标签: " . ($removeResult ? "✅ 成功" : "❌ 失败") . "\n";
        }
    } catch (Exception $e) {
        echo "   ❌ 标签管理错误: " . $e->getMessage() . "\n";
    }

    // 测试5: 移动目录
    echo "\n5. 测试移动目录...\n";
    try {
        $testLocation = '/tmp/test_move_location';
        $moveResult = $torrentAPI->setDownloadLocation([$testHash], $testLocation);
        echo "   移动目录: " . ($moveResult ? "✅ 成功" : "❌ 失败") . "\n";

        if ($moveResult) {
            // 移回原目录
            sleep(2);
            $originalLocation = '/Downloads/temp/20250925'; // 默认位置
            $moveBackResult = $torrentAPI->setDownloadLocation([$testHash], $originalLocation);
            echo "   恢复目录: " . ($moveBackResult ? "✅ 成功" : "❌ 失败") . "\n";
        }
    } catch (Exception $e) {
        echo "   ❌ 移动目录错误: " . $e->getMessage() . "\n";
    }

    echo "\n🎉 API方法测试完成！\n";

    $client->logout();

} catch (Exception $e) {
    echo "❌ 测试过程中出错: " . $e->getMessage() . "\n";
    echo "   错误类型: " . get_class($e) . "\n";
    exit(1);
}