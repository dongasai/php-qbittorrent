<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PhpQbittorrent\Client;

// 手动加载环境变量
function loadEnv($file) {
    if (!file_exists($file)) {
        return;
    }
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) {
            continue;
        }
        if (strpos($line, '=') === false) {
            continue;
        }
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

// 加载.env文件
loadEnv(__DIR__ . '/../.env');

// 读取配置
$url = getenv('QBITTORRENT_URL') ?: 'http://localhost:8080';
$username = getenv('QBITTORRENT_USERNAME') ?: 'admin';
$password = getenv('QBITTORRENT_PASSWORD') ?: 'adminadmin';

echo "环境变量调试:\n";
echo "QBITTORRENT_URL = " . getenv('QBITTORRENT_URL') . "\n";
echo "QBITTORRENT_USERNAME = " . getenv('QBITTORRENT_USERNAME') . "\n";
echo "QBITTORRENT_PASSWORD = " . (getenv('QBITTORRENT_PASSWORD') ? '***已设置***' : '未设置') . "\n\n";

echo "🔧 调试Torrent API测试\n====================\n";
echo "URL: {$url}\n";
echo "用户名: {$username}\n\n";

try {
    // 创建客户端
    echo "🏗️  创建客户端...\n";
    $client = new Client($url, $username, $password);
    echo "✅ 客户端创建成功\n\n";

    // 登录测试
    echo "🔑 尝试登录...\n";
    $loginSuccess = $client->login();
    echo ($loginSuccess ? "✅ 登录成功" : "❌ 登录失败") . "\n\n";

    if ($loginSuccess) {
        // 测试传输信息（这个在quick_test中是成功的）
        echo "📊 测试传输信息API...\n";
        try {
            $transferRequest = \PhpQbittorrent\Request\Transfer\GetGlobalTransferInfoRequest::create();
            $transferResponse = $client->transfer()->getGlobalTransferInfo($transferRequest);
            if ($transferResponse->isSuccess()) {
                $transferInfo = $transferResponse->toArray();
                echo "✅ 传输信息获取成功，DHT节点: " . ($transferInfo['dht_nodes'] ?? 'N/A') . "\n\n";
            } else {
                echo "❌ 传输信息响应失败\n\n";
            }
        } catch (Exception $e) {
            echo "❌ 传输信息获取失败: " . $e->getMessage() . "\n\n";
        }

        // 测试torrent列表
        echo "📂 测试torrent列表API...\n";
        try {
            // 使用正确的请求对象
            echo "方法1: 使用GetTorrentsRequest调用torrents()->getTorrents()\n";
            $torrentRequest = \PhpQbittorrent\Request\Torrent\GetTorrentsRequest::create();
            $torrentResponse = $client->torrents()->getTorrents($torrentRequest);
            if ($torrentResponse->isSuccess()) {
                $torrents = $torrentResponse->getTorrents();
                echo "✅ Torrent列表获取成功，数量: " . $torrents->count() . "\n\n";

                // 显示前3个torrent的基本信息
                for ($i = 0; $i < min(3, $torrents->count()); $i++) {
                    $torrent = $torrents->get($i);
                    echo "Torrent " . ($i+1) . ": " . $torrent->getName() .
                         " (状态: " . $torrent->getState()->value .
                         " 进度: " . round($torrent->getProgress() * 100, 2) . "%)\n";
                }
            } else {
                echo "❌ Torrent列表响应失败\n\n";
            }

        } catch (Exception $e) {
            echo "❌ Torrent列表获取失败: " . $e->getMessage() . "\n";
            echo "错误类型: " . get_class($e) . "\n\n";
        }

        // 测试应用偏好设置
        echo "\n🔧 测试应用偏好设置API...\n";
        try {
            $preferencesRequest = \PhpQbittorrent\Request\Application\GetPreferencesRequest::create();
            $preferencesResponse = $client->application()->getPreferences($preferencesRequest);
            if ($preferencesResponse->isSuccess()) {
                echo "✅ 偏好设置获取成功\n";
            } else {
                echo "❌ 偏好设置响应失败\n";
            }
        } catch (Exception $e) {
            echo "❌ 偏好设置获取失败: " . $e->getMessage() . "\n";
        }

        // 测试默认保存路径
        echo "\n📁 测试默认保存路径API...\n";
        try {
            $savePathRequest = \PhpQbittorrent\Request\Application\GetDefaultSavePathRequest::create();
            $savePathResponse = $client->application()->getDefaultSavePath($savePathRequest);
            if ($savePathResponse->isSuccess()) {
                $savePath = $savePathResponse->getSavePath();
                echo "✅ 默认保存路径: " . ($savePath ?? 'N/A') . "\n";
            } else {
                echo "❌ 默认保存路径响应失败\n";
            }
        } catch (Exception $e) {
            echo "❌ 默认保存路径获取失败: " . $e->getMessage() . "\n";
        }
    }

} catch (Exception $e) {
    echo "❌ 测试失败: " . $e->getMessage() . "\n";
    echo "错误类型: " . get_class($e) . "\n";
    echo "堆栈跟踪:\n" . $e->getTraceAsString() . "\n";
}

echo "\n🔍 调试完成\n";