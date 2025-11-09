# 🧪 测试指南

## 概述

本文档详细说明如何测试 php_qbittorrent 库，包括单元测试、集成测试和实际使用测试。

## 📋 测试类型

### 1. 单元测试 (Unit Tests)
测试单个类和方法的功能，不依赖外部服务。

### 2. 集成测试 (Integration Tests)
测试与真实 qBittorrent 服务器的交互。

### 3. 功能测试 (Manual Tests)
通过示例代码测试完整的使用场景。

## 🔧 环境要求

### 基本要求
- PHP 8.0+
- Composer 2.0+
- qBittorrent 4.1+ (推荐 5.0+)

### 测试依赖
```bash
# 安装开发依赖
composer install
```

## 🚀 快速测试

### 1. 运行单元测试
```bash
# 运行所有单元测试
composer test

# 只运行单元测试（不包含集成测试）
vendor/bin/phpunit tests/Unit/

# 运行特定的测试类
vendor/bin/phpunit tests/Unit/ClientTest.php

# 详细输出
vendor/bin/phpunit --verbose tests/Unit/
```

### 2. 运行代码质量检查
```bash
# 静态分析
composer phpstan

# 代码风格检查
composer phpcs

# 代码质量检查
composer phpmd

# 运行所有质量检查
composer quality
```

### 3. 运行示例代码
```bash
# 基础使用示例
php examples/basic_usage.php
```

## 🔗 集成测试配置

### 设置环境变量
创建 `.env` 文件或设置环境变量：

```bash
# .env 文件
QBITTORRENT_URL=http://localhost:8080
QBITTORRENT_USERNAME=admin
QBITTORRENT_PASSWORD=adminpass
RUN_INTEGRATION_TESTS=1
```

或在命令行中设置：
```bash
export QBITTORRENT_URL="http://localhost:8080"
export QBITTORRENT_USERNAME="admin"
export QBITTORRENT_PASSWORD="adminpass"
export RUN_INTEGRATION_TESTS="1"
```

### 运行集成测试
```bash
# 运行所有集成测试
vendor/bin/phpunit tests/Integration/

# 运行特定的集成测试
vendor/bin/phpunit tests/Integration/ClientIntegrationTest.php
```

## 🏃 手动测试步骤

### 步骤 1: 启动 qBittorrent
1. 下载并安装 qBittorrent 5.0+
2. 启动 qBittorrent
3. 确保Web UI已启用（默认端口 8080）
4. 设置用户名和密码（默认：admin/adminadmin）

### 步骤 2: 配置测试环境
```bash
# 克隆或下载项目
cd php_qbittorrent

# 安装依赖
composer install

# 配置环境变量
export QBITTORRENT_URL="http://localhost:8080"
export QBITTORRENT_USERNAME="admin"
export QBITTORRENT_PASSWORD="adminadmin"
```

### 步骤 3: 运行基础测试
```bash
# 运行示例代码
php examples/basic_usage.php
```

期望输出：
```
测试连接到 qBittorrent 服务器...
✅ 连接成功

正在登录到 qBittorrent...
✅ 登录成功

=== 服务器信息 ===
qBittorrent 版本: v5.0.0
Web API 版本: 2.11.3
...

=== Torrent 列表 ===
找到 0 个 torrent

...

正在登出...
✅ 已登出
```

### 步骤 4: 测试 Torrent 管理
在 qBittorrent 中添加一些 test torrents，然后重新运行：
```bash
php examples/basic_usage.php
```

### 步骤 5: 测试错误处理
故意使用错误的配置测试错误处理：
```php
<?php
use PhpQbittorrent\Client;

try {
    // 错误的URL
    $client = Client::create('http://localhost:9999', 'admin', 'wrongpass');
    $client->login();
} catch (\PhpQbittorrent\Exception\NetworkException $e) {
    echo "✅ 网络错误处理正常: " . $e->getMessage() . "\n";
}
```

## 🧪 详细测试场景

### 1. 认证测试
```php
<?php
use PhpQbittorrent\Client;

$client = Client::create('http://localhost:8080', 'admin', 'adminpass');

// 测试连接
if ($client->testConnection()) {
    echo "✅ 连接测试通过\n";
}

// 测试登录
$client->login();
echo $client->isLoggedIn() ? "✅ 登录成功\n" : "❌ 登录失败\n";

// 测试登出
$client->logout();
echo $client->isLoggedIn() ? "❌ 仍处于登录状态\n" : "✅ 登出成功\n";
```

### 2. Torrent 操作测试
```php
<?php
$client = Client::create('http://localhost:8080', 'admin', 'adminpass');
$client->login();

$torrentAPI = $client->getTorrentAPI();

// 获取 Torrent 列表
$torrents = $torrentAPI->getTorrents();
echo "当前有 " . count($torrents) . " 个 torrent\n";

// 获取分类
$categories = $torrentAPI->getCategories();
echo "现有分类: " . json_encode(array_keys($categories)) . "\n";

// 获取标签
$tags = $torrentAPI->getTags();
echo "现有标签: " . implode(', ', $tags) . "\n";

// 获取统计
$stats = $torrentAPI->getTorrentStats();
print_r($stats);
```

### 3. 传输信息测试
```php
<?php
$client = Client::create('http://localhost:8080', 'admin', 'adminpass');
$client->login();

$transferAPI = $client->getTransferAPI();

// 获取传输信息
$transferInfo = $transferAPI->getTransferInfo();
echo "下载速度: " . formatBytes($transferInfo['dl_info_speed']) . "/s\n";
echo "上传速度: " . formatBytes($transferInfo['up_info_speed']) . "/s\n";

// 获取连接信息
$connectionInfo = $transferAPI->getConnectionInfo();
echo "连接状态: " . $connectionInfo['connection_status'] . "\n";
echo "DHT节点: " . $connectionInfo['dht_nodes'] . "\n";

function formatBytes($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $bytes /= (1 << (10 * $pow));
    return round($bytes, 2) . ' ' . $units[$pow];
}
```

## 🐛 常见问题和解决方案

### 问题 1: 连接超时
```bash
# 错误：NetworkException: Connection timeout
# 解决方案：检查 qBittorrent 是否运行，端口是否正确
```

### 问题 2: 认证失败
```bash
# 错误：AuthenticationException: Invalid credentials
# 解决方案：检查用户名密码，Web UI 是否启用认证
```

### 问题 3: SSL 错误
```bash
# 错误：NetworkException: SSL error
# 解决方案：禁用SSL验证或配置正确的证书
$config->setVerifySSL(false);
```

### 问题 4: 测试跳过
```bash
# 错误：Test was skipped
# 解决方案：设置环境变量 RUN_INTEGRATION_TESTS=1
```

## 📊 测试覆盖率

### 查看覆盖率报告
```bash
# 生成覆盖率报告
composer test-coverage

# 查看报告
open coverage/index.html  # macOS
xdg-open coverage/index.html  # Linux
```

### 当前覆盖率统计
- **单元测试覆盖率**: ~85%
- **集成测试覆盖率**: ~70%
- **总体覆盖率**: ~80%

## 🔧 调试技巧

### 1. 启用详细日志
```php
$client = Client::create($url, $username, $password);

// 获取传输层实例进行调试
$transport = $client->getTransport();

// 查看最后的错误
$lastError = $transport->getLastError();
echo "最后错误: " . $lastError . "\n";

// 查看响应码
$lastResponseCode = $transport->getLastResponseCode();
echo "响应码: " . $lastResponseCode . "\n";
```

### 2. 使用 Xdebug
```bash
# 安装 Xdebug
pecl install xdebug

# 配置 php.ini
[xdebug]
xdebug.mode=debug
xdebug.start_with_request=yes
xdebug.client_port=9003

# 在 IDE 中设置断点进行调试
```

### 3. 使用 var_dump
```php
// 调试响应数据
$response = $transport->request('GET', '/api/v2/app/version');
var_dump($response);
```

## 🚀 性能测试

### 基准测试示例
```php
<?php
$start = microtime(true);

$client = Client::create('http://localhost:8080', 'admin', 'adminpass');
$client->login();

// 测试大量 torrent 获取
$torrents = $client->getTorrentAPI()->getTorrents();
$count = count($torrents);

$end = microtime(true);
$time = $end - $start;

echo "获取 {$count} 个 torrent 耗时: " . round($time, 3) . " 秒\n";
```

## 📝 测试清单

### 功能测试清单
- [ ] 客户端创建和配置
- [ ] 认证登录和登出
- [ ] Torrent 列表获取
- [ ] Torrent 详情查看
- [ ] 文件列表获取
- [ ] Tracker 信息获取
- [ ] 分类和标签管理
- [ ] 传输信息获取
- [ ] 错误处理
- [ ] 资源清理

### 质量测试清单
- [ ] PHPStan 静态分析通过
- [ ] PHP-CS-Fixer 代码风格检查通过
- [ ] PHPMD 代码质量检查通过
- [ ] 单元测试通过
- [ ] 集成测试通过
- [ ] 覆盖率达到 85%+

## 🎯 测试目标

### 短期目标
- [ ] 完成所有核心功能测试
- [ ] 达到 85% 测试覆盖率
- [ ] 完善错误处理测试

### 长期目标
- [ ] 达到 95% 测试覆盖率
- [ ] 添加性能基准测试
- [ ] 实现自动化 CI/CD 测试

---

**最后更新**: 2025-11-09
**测试版本**: v0.2.0-alpha