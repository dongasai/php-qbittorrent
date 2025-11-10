<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * 详细连接调试脚本
 * 分析登录后API调用失败的原因
 */

echo "=== 详细连接调试 ===\n\n";

// 加载环境变量
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_contains($line, '=')) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// 配置 - 使用与 quick_test.php 相同的逻辑
$config = [
    'base_url' => $_ENV['QBITTORRENT_URL'] ?? 'http://192.168.4.105:8989',
    'username' => $_ENV['QBITTORRENT_USERNAME'] ?? 'admin',
    'password' => $_ENV['QBITTORRENT_PASSWORD'] ?? 'adminpass',
];

echo "配置信息:\n";
echo "- URL: {$config['base_url']}\n";
echo "- 用户名: {$config['username']}\n";
echo "- 密码: " . str_repeat('*', strlen($config['password'])) . "\n\n";

try {
    // 创建客户端
    $client = new \PhpQbittorrent\Client(
        $config['base_url'],
        $config['username'],
        $config['password']
    );

    echo "1. 客户端创建成功\n\n";

    // 登录
    echo "2. 尝试登录...\n";
    $loginSuccess = $client->login();
    if ($loginSuccess) {
        echo "   ✅ 登录成功\n";
        echo "   认证状态: " . ($client->isLoggedIn() ? '已认证' : '未认证') . "\n\n";
    } else {
        echo "   ❌ 登录失败\n\n";
        exit(1);
    }

    // 测试不同的 API 端点
    echo "3. 测试 API 端点...\n";

    $testEndpoints = [
        '/api/v2/app/version',
        '/api/v2/app/webapiVersion',
        '/api/v2/transfer/info',
        '/api/v2/torrents/info',
    ];

    $transport = $client->getTransport();

    foreach ($testEndpoints as $endpoint) {
        echo "   测试端点: {$endpoint}\n";

        try {
            $response = $transport->get($endpoint);
            $statusCode = $response->getStatusCode();
            $body = substr($response->getBody(), 0, 200); // 只显示前200个字符

            echo "     状态码: {$statusCode}\n";
            echo "     响应: {$body}\n";

            if ($statusCode === 200) {
                echo "     ✅ 端点正常\n";
            } else {
                echo "     ❌ 端点异常\n";
            }
        } catch (Exception $e) {
            echo "     ❌ 请求失败: " . $e->getMessage() . "\n";
        }
        echo "\n";
    }

    // 使用 Client 方法测试
    echo "4. 使用 Client 方法测试...\n";

    try {
        echo "   测试 application()->getVersion()...\n";
        $versionResponse = $client->application()->getVersion(\PhpQbittorrent\Request\Application\GetVersionRequest::create());
        echo "     响应类型: " . get_class($versionResponse) . "\n";
        echo "     成功状态: " . ($versionResponse->isSuccess() ? '成功' : '失败') . "\n";
        if ($versionResponse->isSuccess()) {
            echo "     版本: " . $versionResponse->getVersion() . "\n";
        }
        echo "     ✅ Application API 正常\n\n";
    } catch (Exception $e) {
        echo "     ❌ Application API 失败: " . $e->getMessage() . "\n\n";
    }

    try {
        echo "   测试 transfer()->getGlobalTransferInfo()...\n";
        $transferResponse = $client->transfer()->getGlobalTransferInfo(\PhpQbittorrent\Request\Transfer\GetGlobalTransferInfoRequest::create());
        echo "     响应类型: " . get_class($transferResponse) . "\n";
        echo "     成功状态: " . ($transferResponse->isSuccess() ? '成功' : '失败') . "\n";
        echo "     ✅ Transfer API 正常\n\n";
    } catch (Exception $e) {
        echo "     ❌ Transfer API 失败: " . $e->getMessage() . "\n\n";
    }

    // 检查传输层状态
    echo "5. 传输层状态检查...\n";
    echo "   传输类型: " . get_class($transport) . "\n";
    echo "   基础URL: " . $transport->getBaseUrl() . "\n";
    echo "   超时设置: " . (method_exists($transport, 'getTimeout') ? '30s' : '未知') . "\n\n";

    // 检查 Cookie
    echo "6. 认证状态检查...\n";
    if (method_exists($transport, 'getCookies')) {
        $cookies = $transport->getCookies();
        echo "   Cookies: " . (empty($cookies) ? '无' : '有设置') . "\n";
    } else {
        echo "   无法检查 Cookie 状态\n";
    }
    echo "   客户端认证状态: " . ($client->isLoggedIn() ? '已认证' : '未认证') . "\n\n";

} catch (Exception $e) {
    echo "❌ 调试过程中发生错误: " . $e->getMessage() . "\n";
    echo "错误类型: " . get_class($e) . "\n";
    echo "错误代码: " . ($e->getCode() ?: '无') . "\n";
}

echo "\n=== 调试完成 ===\n";