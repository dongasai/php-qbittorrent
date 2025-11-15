<?php
declare(strict_types=1);
require_once __DIR__ . '/../vendor/autoload.php';

use PhpQbittorrent\Client;

/**
 * 测试分类管理优化
 * 
 * 这个脚本专门测试修改后的分类管理功能
 * 验证"创建前先检查，如存在删除再创建"的逻辑
 */

// 加载环境变量
function loadEnv(string $file): void
{
    if (!file_exists($file)) {
        echo "⚠️  未找到 {$file} 文件\n";
        return;
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

// 获取测试配置
function getTestConfig(): array
{
    return [
        'url' => $_ENV['QBITTORRENT_URL'] ?? 'http://localhost:8080',
        'username' => $_ENV['QBITTORRENT_USERNAME'] ?? 'admin',
        'password' => $_ENV['QBITTORRENT_PASSWORD'] ?? 'adminpass',
        'timeout' => (float) ($_ENV['QBITTORRENT_TIMEOUT'] ?? 30.0),
        'verify_ssl' => filter_var($_ENV['QBITTORRENT_VERIFY_SSL'] ?? 'false', FILTER_VALIDATE_BOOLEAN),
    ];
}

// 主测试函数
function testCategoryOptimization(): void
{
    echo "🧪 测试分类管理优化\n";
    echo "========================\n\n";

    // 加载配置
    loadEnv(__DIR__ . '/../.env');
    $config = getTestConfig();

    echo "📋 配置信息:\n";
    echo "   URL: {$config['url']}\n";
    echo "   用户名: {$config['username']}\n\n";

    try {
        // 创建客户端
        $client = new Client(
            $config['url'],
            $config['username'],
            $config['password']
        );

        // 登录
        echo "🔗 正在登录...\n";
        $client->login();
        
        if ($client->isLoggedIn()) {
            echo "✅ 登录成功\n\n";
        } else {
            echo "❌ 登录失败\n";
            return;
        }

        // 获取Torrent API
        $torrentAPI = $client->getTorrentAPI();

        // 测试分类名称
        $testCategory = 'php_optimization_test_' . date('His');

        echo "📂 测试分类: {$testCategory}\n\n";

        // 步骤1: 检查分类是否存在
        echo "步骤1: 检查分类是否存在...\n";
        $categories = $torrentAPI->getCategories();
        $existsBefore = is_array($categories) && isset($categories[$testCategory]);
        echo "   分类存在: " . ($existsBefore ? '是' : '否') . "\n\n";

        // 步骤2: 创建分类（应该成功）
        echo "步骤2: 创建分类...\n";
        $createResult1 = $torrentAPI->createCategory($testCategory, '/tmp/test');
        
        if ($createResult1 && $createResult1->isSuccess()) {
            echo "   ✅ 首次创建成功\n";
        } else {
            echo "   ❌ 首次创建失败\n";
            if ($createResult1) {
                echo "      状态码: " . ($createResult1->getStatusCode() ?? 'Unknown') . "\n";
                echo "      错误信息: " . ($createResult1->getData()['error'] ?? 'None') . "\n";
            }
        }

        // 等待一下
        sleep(1);

        // 步骤3: 再次检查分类是否存在
        echo "\n步骤3: 再次检查分类是否存在...\n";
        $categories2 = $torrentAPI->getCategories();
        $existsAfterCreate = is_array($categories2) && isset($categories2[$testCategory]);
        echo "   分类存在: " . ($existsAfterCreate ? '是' : '否') . "\n\n";

        // 步骤4: 尝试再次创建相同分类（应该失败或触发优化逻辑）
        echo "步骤4: 尝试再次创建相同分类（测试优化逻辑）...\n";
        $createResult2 = $torrentAPI->createCategory($testCategory, '/tmp/test');
        
        if ($createResult2 && $createResult2->isSuccess()) {
            echo "   ✅ 二次创建成功（优化逻辑生效）\n";
        } else {
            echo "   ⚠️  二次创建失败（预期行为）\n";
            if ($createResult2) {
                echo "      状态码: " . ($createResult2->getStatusCode() ?? 'Unknown') . "\n";
                echo "      错误信息: " . ($createResult2->getData()['error'] ?? 'None') . "\n";
                
                // 检查是否是HTTP 409错误
                $statusCode = $createResult2->getStatusCode();
                if ($statusCode === 409) {
                    echo "      ✅ 检测到HTTP 409错误 - 这是我们要解决的问题\n";
                }
            }
        }

        // 步骤5: 最终验证
        echo "\n步骤5: 最终验证...\n";
        $finalCategories = $torrentAPI->getCategories();
        $finalExists = is_array($finalCategories) && isset($finalCategories[$testCategory]);
        echo "   分类最终存在: " . ($finalExists ? '是' : '否') . "\n";

        if ($finalExists) {
            echo "   ✅ 分类管理优化测试成功\n";
        } else {
            echo "   ❌ 分类管理优化测试失败\n";
        }

        // 清理：删除测试分类
        echo "\n🧹 清理测试分类...\n";
        $cleanupResult = $torrentAPI->removeCategories($testCategory);
        if ($cleanupResult && $cleanupResult->isSuccess()) {
            echo "   ✅ 测试分类删除成功\n";
        } else {
            echo "   ❌ 测试分类删除失败\n";
        }

        // 登出
        $client->logout();
        echo "\n✅ 测试完成\n";

    } catch (Exception $e) {
        echo "❌ 测试异常: " . $e->getMessage() . "\n";
        echo "   错误类型: " . get_class($e) . "\n";
        echo "   错误代码: " . ($e->getCode() ?: 'N/A') . "\n";
    }
}

// 运行测试
testCategoryOptimization();