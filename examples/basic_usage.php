<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PhpQbittorrent\Config\ClientConfig;
use PhpQbittorrent\Transport\CurlTransport;

/**
 * 基础使用示例
 */

try {
    // 1. 创建配置
    $config = new ClientConfig(
        'http://localhost:8080',  // qBittorrent Web UI URL
        'admin',                   // 用户名
        'adminpass'                // 密码
    );

    // 可选：配置更多选项
    $config->setTimeout(30.0);
    $config->setVerifySSL(false);  // 如果使用自签名证书

    // 2. 验证配置
    if (!$config->validate()) {
        echo "配置验证失败:\n";
        print_r($config->getErrors());
        exit(1);
    }

    // 3. 创建传输层
    $transport = new CurlTransport();
    $transport->setBaseUrl($config->getUrl());
    $transport->setTimeout($config->getTimeout());
    $transport->setVerifySSL($config->isVerifySSL());

    // 4. 登录认证
    echo "正在登录到 qBittorrent...\n";
    $authResponse = $transport->request('POST', '/api/v2/auth/login', [
        'form_params' => [
            'username' => $config->getUsername(),
            'password' => $config->getPassword()
        ]
    ]);

    if (empty($authResponse)) {
        // 从响应头获取SID cookie
        // 注意：这里简化了cookie处理，实际实现中可能需要从响应头中提取
        echo "登录成功\n";
    } else {
        echo "登录失败\n";
        exit(1);
    }

    // 5. 设置认证cookie（这里需要从实际的响应中获取）
    $transport->setAuthentication('SID=your_session_id_here');

    // 6. 获取API版本信息
    echo "获取API版本信息...\n";
    $versionInfo = $transport->request('GET', '/api/v2/app/version');
    echo "qBittorrent版本: " . ($versionInfo[0] ?? 'Unknown') . "\n";

    // 7. 获取所有torrent列表
    echo "获取torrent列表...\n";
    $torrents = $transport->request('GET', '/api/v2/torrents/info');
    echo "找到 " . count($torrents) . " 个torrent\n";

    // 显示前5个torrent的基本信息
    $limit = min(5, count($torrents));
    for ($i = 0; $i < $limit; $i++) {
        $torrent = $torrents[$i];
        echo sprintf(
            "[%d] %s - %s (%.1f%%)\n",
            $i + 1,
            $torrent['name'] ?? 'Unknown',
            $torrent['state'] ?? 'Unknown',
            ($torrent['progress'] ?? 0) * 100
        );
    }

    // 8. 获取全局传输信息
    echo "\n获取全局传输信息...\n";
    $transferInfo = $transport->request('GET', '/api/v2/transfer/info');
    echo sprintf(
        "下载速度: %s/s, 上传速度: %s/s\n",
        formatBytes($transferInfo['dl_info_speed'] ?? 0),
        formatBytes($transferInfo['up_info_speed'] ?? 0)
    );

    // 9. 登出
    echo "正在登出...\n";
    $transport->request('POST', '/api/v2/auth/logout');
    echo "已登出\n";

} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
    echo "错误代码: " . $e->getCode() . "\n";

    if (method_exists($e, 'getErrorCode')) {
        echo "API错误代码: " . $e->getErrorCode() . "\n";
    }

    if (method_exists($e, 'getErrorDetails')) {
        echo "错误详情:\n";
        print_r($e->getErrorDetails());
    }

    exit(1);
}

/**
 * 格式化字节数
 */
function formatBytes(int $bytes, int $precision = 2): string
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));

    return round($bytes, $precision) . ' ' . $units[$pow];
}