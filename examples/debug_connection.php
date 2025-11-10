<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PhpQbittorrent\Transport\CurlTransport;
use Nyholm\Psr7\Factory\Psr17Factory;

/**
 * 调试连接问题
 */

echo "=== qBittorrent 连接调试 ===\n\n";

$config = [
    'url' => $_ENV['QBITTORRENT_BASE_URL'] ?? 'http://192.168.4.105:8989',
    'username' => $_ENV['QBITTORRENT_USERNAME'] ?? 'admin',
    'password' => $_ENV['QBITTORRENT_PASSWORD'] ?? 'adminadmin',
];

echo "配置信息:\n";
echo "- URL: {$config['url']}\n";
echo "- 用户名: {$config['username']}\n";
echo "- 密码: {$config['password']}\n\n";

try {
    // 创建传输层
    $factory = new Psr17Factory();
    $transport = new CurlTransport($factory, $factory);
    $transport->setBaseUrl($config['url']);

    echo "✅ 传输层创建成功\n\n";

    // 测试基本连接
    echo "1. 测试基本连接...\n";
    try {
        $response = $transport->request('GET', '/api/v2/app/version');
        echo "✅ 基本连接成功\n";
        echo "响应: " . json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
    } catch (Exception $e) {
        echo "❌ 基本连接失败: " . $e->getMessage() . "\n";
        echo "错误类型: " . get_class($e) . "\n\n";
    }

    // 测试登录
    echo "2. 测试登录...\n";
    try {
        $response = $transport->request('POST', '/api/v2/auth/login', [
            'form_params' => [
                'username' => $config['username'],
                'password' => $config['password']
            ]
        ]);
        echo "✅ 登录请求成功\n";
        echo "响应: " . json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
    } catch (Exception $e) {
        echo "❌ 登录失败: " . $e->getMessage() . "\n";
        echo "错误类型: " . get_class($e) . "\n\n";
    }

    // 测试原始cURL请求 - 登录端点（应该不需要认证）
    echo "3. 测试原始cURL登录请求...\n";
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $config['url'] . '/api/v2/auth/login',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'username' => $config['username'],
            'password' => $config['password']
        ]),
        CURLOPT_USERAGENT => 'PHP qBittorrent Library Debug',
        CURLOPT_REFERER => $config['url'] . '/',
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: text/plain'
        ]
    ]);

    $response = curl_exec($curl);
    $error = curl_error($curl);
    $info = curl_getinfo($curl);
    curl_close($curl);

    if ($response === false) {
        echo "❌ 原始cURL登录请求失败: {$error}\n";
    } else {
        echo "✅ 原始cURL登录请求成功\n";
        echo "HTTP状态码: {$info['http_code']}\n";
        echo "完整响应:\n{$response}\n";

        // 提取Set-Cookie头
        if (preg_match('/Set-Cookie:\s*([^;\r\n]+)/i', $response, $matches)) {
            echo "找到Cookie: {$matches[1]}\n";
        }
    }

} catch (Exception $e) {
    echo "❌ 创建传输层失败: " . $e->getMessage() . "\n";
    echo "错误类型: " . get_class($e) . "\n";
}

echo "\n=== 调试完成 ===\n";