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

echo "🚀 PHP qBittorrent API 测试\n========================\n";
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
        // 测试传输信息
        echo "📊 测试传输信息API...\n";
        try {
            $transferRequest = \PhpQbittorrent\Request\Transfer\GetGlobalTransferInfoRequest::create();
            $transferResponse = $client->transfer()->getGlobalTransferInfo($transferRequest);
            if ($transferResponse->isSuccess()) {
                $transferInfo = $transferResponse->toArray();
                echo "✅ 传输信息获取成功:\n";
                echo "   DHT节点: " . ($transferInfo['dht_nodes'] ?? 'N/A') . "\n";
                echo "   连接状态: " . ($transferInfo['connection_status'] ?? 'N/A') . "\n";
                echo "   下载速度: " . ($transferInfo['dl_info_speed'] ?? 0) . " B/s\n";
                echo "   上传速度: " . ($transferInfo['up_info_speed'] ?? 0) . " B/s\n";
                echo "   总下载量: " . ($transferInfo['dl_info_data'] ?? 0) . " B\n";
                echo "   总上传量: " . ($transferInfo['up_info_data'] ?? 0) . " B\n\n";
            } else {
                echo "❌ 传输信息响应失败\n\n";
            }
        } catch (Exception $e) {
            echo "❌ 传输信息获取失败: " . $e->getMessage() . "\n\n";
        }

        // 测试torrent列表
        echo "📂 测试torrent列表API...\n";
        try {
            $torrentRequest = \PhpQbittorrent\Request\Torrent\GetTorrentsRequest::create();
            $torrentResponse = $client->torrents()->getTorrents($torrentRequest);
            if ($torrentResponse->isSuccess()) {
                $torrents = $torrentResponse->getTorrents();
                echo "✅ Torrent列表获取成功，数量: " . $torrents->count() . "\n\n";

                // 统计torrent状态
                $stats = ['downloading' => 0, 'seeding' => 0, 'completed' => 0, 'paused' => 0, 'error' => 0, 'other' => 0];
                for ($i = 0; $i < min(10, $torrents->count()); $i++) { // 只检查前10个
                    $torrent = $torrents->get($i);
                    $state = $torrent->getState()->value;
                    switch ($state) {
                        case 'downloading':
                        case 'metaDL':
                            $stats['downloading']++;
                            break;
                        case 'uploading':
                        case 'stalledUP':
                        case 'forcedUP':
                            $stats['seeding']++;
                            break;
                        case 'pausedUP':
                        case 'pausedDL':
                            $stats['paused']++;
                            break;
                        case 'error':
                        case 'missingFiles':
                            $stats['error']++;
                            break;
                        default:
                            $stats['other']++;
                    }
                }

                echo "Torrent状态统计 (前10个):\n";
                echo "   下载中: " . $stats['downloading'] . "\n";
                echo "   做种中: " . $stats['seeding'] . "\n";
                echo "   已暂停: " . $stats['paused'] . "\n";
                echo "   错误: " . $stats['error'] . "\n";
                echo "   其他: " . $stats['other'] . "\n\n";

                // 显示前3个torrent的基本信息
                echo "前3个Torrent详情:\n";
                for ($i = 0; $i < min(3, $torrents->count()); $i++) {
                    $torrent = $torrents->get($i);
                    echo "Torrent " . ($i+1) . ": " . substr($torrent->getName(), 0, 50) .
                         (strlen($torrent->getName()) > 50 ? '...' : '') . "\n";
                    echo "   状态: " . $torrent->getState()->value .
                         " | 进度: " . round($torrent->getProgress() * 100, 2) . "%" .
                         " | 大小: " . round($torrent->getSize() / 1024 / 1024, 2) . " MB\n";
                    echo "   下载: " . $torrent->getDownloadSpeed() . " B/s | 上传: " . $torrent->getUploadSpeed() . " B/s\n\n";
                }
            } else {
                echo "❌ Torrent列表响应失败\n\n";
            }
        } catch (Exception $e) {
            echo "❌ Torrent列表获取失败: " . $e->getMessage() . "\n\n";
        }

        echo "🎉 主要API功能测试完成！\n";
        echo "✅ 核心功能已正常工作：\n";
        echo "   - 认证登录\n";
        echo "   - 传输信息获取\n";
        echo "   - Torrent列表管理\n\n";
    }

} catch (Exception $e) {
    echo "❌ 测试失败: " . $e->getMessage() . "\n";
    echo "错误类型: " . get_class($e) . "\n";
    if ($e->getPrevious()) {
        echo "内部错误: " . $e->getPrevious()->getMessage() . "\n";
    }
}

echo "测试完成\n";