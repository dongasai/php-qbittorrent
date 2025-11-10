<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PhpQbittorrent\Transport\CurlTransport;
use Nyholm\Psr7\Factory\Psr17Factory;

/**
 * 调试异常问题
 */

echo "=== 异常调试测试 ===\n\n";

$config = [
    'url' => 'http://192.168.4.105:8989',
    'username' => 'admin',
    'password' => 'adminadmin',
];

try {
    $factory = new Psr17Factory();
    $transport = new CurlTransport($factory, $factory);
    $transport->setBaseUrl($config['url']);

    echo "测试登录请求...\n";
    $response = $transport->request('POST', '/api/v2/auth/login', [
        'form_params' => [
            'username' => $config['username'],
            'password' => $config['password']
        ]
    ]);

    echo "✅ 请求成功: " . json_encode($response, JSON_UNESCAPED_UNICODE) . "\n";

} catch (\PhpQbittorrent\Exception\AuthenticationException $e) {
    echo "❌ AuthenticationException:\n";
    echo "消息: " . $e->getMessage() . "\n";
    echo "错误代码: " . $e->getErrorCode() . "\n";
    echo "用户名: " . ($e->getUsername() ?? 'null') . "\n";
    echo "原因: " . ($e->getReason() ?? 'null') . "\n";
    echo "堆栈跟踪:\n" . $e->getTraceAsString() . "\n";

} catch (\PhpQbittorrent\Exception\NetworkException $e) {
    echo "❌ NetworkException:\n";
    echo "消息: " . $e->getMessage() . "\n";
    echo "错误代码: " . $e->getCode() . "\n";

} catch (\PhpQbittorrent\Exception\ClientException $e) {
    echo "❌ ClientException:\n";
    echo "消息: " . $e->getMessage() . "\n";
    echo "错误代码: " . $e->getCode() . "\n";

} catch (Exception $e) {
    echo "❌ 通用Exception:\n";
    echo "消息: " . $e->getMessage() . "\n";
    echo "错误类型: " . get_class($e) . "\n";
    echo "错误代码: " . $e->getCode() . "\n";
    echo "错误文件: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n=== 调试完成 ===\n";