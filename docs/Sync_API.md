# qBittorrent Sync API 使用指南

## 概述

本库现在支持完整的qBittorrent Sync API，提供实时数据同步和监控功能。通过增量更新机制，可以高效地跟踪qBittorrent的所有状态变化。

## 主要功能

### 1. 主要数据同步

获取qBittorrent的主要数据，包括：
- Torrents列表和状态
- 分类信息变更
- 标签变更
- 服务器状态信息
- 支持增量更新，避免重复传输

### 2. Torrent Peers数据

获取特定torrent的所有连接peers信息：
- Peer连接详情（IP、端口、国家等）
- 客户端信息
- 传输速度和进度
- 支持按国家、客户端分组统计

### 3. 实时监控

提供强大的实时监控功能：
- 基于rid的增量更新机制
- 可配置监控间隔
- 变化回调支持
- 自动错误恢复和日志记录

### 4. 统计分析

内置丰富的统计分析功能：
- Torrent状态分布统计
- 总上传/下载速度
- 数据大小统计
- 活跃torrent统计

## 使用方法

### 基础用法

```php
use PhpQbittorrent\UnifiedClient;

// 创建客户端
$client = new UnifiedClient(
    'http://localhost:8080',
    'admin',
    'adminadmin'
);

// 登录
$client->login();
```

### 获取主要数据

```php
// 完整更新
$mainData = $client->getMainData();

// 增量更新
$mainData = $client->getMainData($lastRid);

// 检查是否有完整更新
if ($mainData['full_update']) {
    echo "收到完整更新\n";
} else {
    echo "收到增量更新，rid: {$mainData['rid']}\n";
}

// 访问各种数据
$torrents = $mainData['torrents'];
$removedTorrents = $mainData['torrents_removed'];
$categories = $mainData['categories'];
$tags = $mainData['tags'];
$serverState = $mainData['server_state'];
```

### 获取Peers数据

```php
$peersData = $client->getTorrentPeers('torrent_hash');

echo "Peers数量: {$peersData['peers_count']}\n";
echo "总下载速度: " . formatBytes($peersData['total_download_speed']) . "/s\n";
echo "总上传速度: " . formatBytes($peersData['total_upload_speed']) . "/s\n";

// 按国家分组
$byCountry = $peersData['grouped_by_country'] ?? [];
foreach ($byCountry as $country => $peers) {
    echo "国家: $country, Peers: " . count($peers) . "\n";
}
```

### 实时监控

```php
// 基本监控
$client->monitorChanges(5, function ($data) {
    $timestamp = date('Y-m-d H:i:s');
    echo "[$timestamp] 数据更新 - rid: {$data['rid']}\n";

    if ($data['full_update']) {
        echo "完整更新，包含" . count($data['torrents']) . "个torrent\n";
    }
});

// 高级监控 - 自定义间隔
$client->monitorChanges(2, function ($data) {
    // 处理实时数据
    $this->handleRealtimeUpdate($data);
});

// 短间隔监控 - 用于高频监控
$client->monitorChanges(1, function ($data) {
    // 高频数据处理
});
```

### 统计信息

```php
$stats = $client->getRealtimeStats();

echo "=== qBittorrent 统计 ===\n";
echo "时间: " . date('Y-m-d H:i:s', $stats['timestamp']) . "\n";
echo "总Torrents: {$stats['total_torrents']}\n";
echo "下载中: {$stats['downloading_torrents']}\n";
echo "做种中: {$stats['seeding_torrents']}\n";
echo "已暂停: {$stats['paused_torrents']}\n";
echo "总大小: " . formatBytes($stats['total_size']) . "\n";
echo "下载速度: " . formatBytes($stats['total_download_speed']) . "/s\n";
echo "上传速度: " . formatBytes($stats['total_upload_speed']) . "/s\n";
```

## 高级用法

### 自定义监控逻辑

```php
class TorrentMonitor
{
    private UnifiedClient $client;
    private array $lastState = [];

    public function __construct(UnifiedClient $client)
    {
        $this->client = $client;
    }

    public function start(): void
    {
        $this->client->monitorChanges(3, [$this, 'processUpdate']);
    }

    private function processUpdate(array $data): void
    {
        $currentState = $this->extractState($data);
        $changes = $this->compareStates($this->lastState, $currentState);

        if (!empty($changes)) {
            $this->handleChanges($changes);
        }

        $this->lastState = $currentState;
    }

    private function extractState(array $data): array
    {
        return [
            'torrents' => array_keys($data['torrents'] ?? []),
            'total_count' => count($data['torrents'] ?? []),
            'downloading_count' => count(array_filter($data['torrents'] ?? [], fn($t) => $t['state'] === 'downloading'))
        ];
    }

    private function compareStates(array $old, array $new): array
    {
        // 状态比较逻辑
        return [];
    }

    private function handleChanges(array $changes): void
    {
        foreach ($changes as $change) {
            echo "检测到变化: " . $change['type'] . "\n";
        }
    }
}

$monitor = new TorrentMonitor($client);
$monitor->start();
```

### 错误处理

```php
try {
    $mainData = $client->getMainData();
} catch (\PhpQbittorrent\Exception\NetworkException $e) {
    echo "网络错误: " . $e->getMessage() . "\n";
} catch (\PhpQbittorrent\Exception\AuthenticationException $e) {
    echo "认证错误: " . $e->getMessage() . "\n";
    // 需要重新登录
    $client->login();
} catch (\Exception $e) {
    echo "未知错误: " . $e->getMessage() . "\n";
}
```

## 最佳实践

### 1. 性能优化

- 合理设置监控间隔，避免过于频繁的请求
- 使用增量更新，减少数据传输量
- 缓存不常变化的数据

### 2. 错误处理

- 实现自动重连机制
- 处理网络超时情况
- 记录详细错误日志

### 3. 内存管理

- 长时间运行的监控脚本要注意内存使用
- 定期清理不需要的数据
- 使用流式处理大数据集

### 4. 监控策略

- 根据需要选择合适的监控间隔
- 重要变化时发送通知
- 定期保存监控状态

## 配置建议

```bash
# 环境变量
export QBITTORRENT_URL="http://localhost:8080"
export QBITTORRENT_USERNAME="admin"
export QBITTORRENT_PASSWORD="adminadmin"
```

```ini
# 配置文件
[sync]
monitor_interval = 5
retry_attempts = 3
timeout = 30
```

## 故障排除

### 常见问题

1. **连接失败**: 检查URL、认证信息
2. **数据不同步**: 确认rid参数正确传递
3. **监控中断**: 检查网络连接和错误处理
4. **性能问题**: 调整监控间隔和数据量

### 调试技巧

- 启用详细日志记录
- 使用小测试数据集验证
- 检查qBittorrent版本兼容性
- 监控网络带宽使用

## 版本兼容性

- 完全兼容 qBittorrent 5.x Web API
- 支持 qBittorrent 4.1+ 的基本功能
- 自动处理API版本差异

## 更多信息

- [API文档](qbit_wiki/WebUI-API-(qBittorrent-5.0).md)
- [完整示例](examples/sync_example.php)
- [单元测试](tests/Sync/)
- [架构文档](DEV.md)