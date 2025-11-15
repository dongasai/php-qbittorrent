# PHP qBittorrent API 文档

> 📚 **完整的 qBittorrent Web API PHP 客户端库文档**
> 🚀 **兼容 qBittorrent 5.x 版本**
> 📝 **详细的使用示例和最佳实践**

## 目录

1. [概述](#概述)
2. [qBittorrent原生API与实现对应关系](#qbittorrent原生api与实现对应关系)
3. [快速开始](#快速开始)
4. [客户端初始化](#客户端初始化)
5. [认证管理](#认证管理)
6. [应用管理API](#应用管理api)
7. [传输管理API](#传输管理api)
8. [种子管理API](#种子管理api)
9. [搜索API](#搜索api)
10. [RSS API](#rss-api)
11. [统一客户端](#统一客户端)
12. [错误处理](#错误处理)
13. [最佳实践](#最佳实践)

## 概述

PHP qBittorrent 是一个现代化的 qBittorrent Web API 客户端库，兼容 qBittorrent 5.x 版本。本库采用面向对象设计，提供类型安全的 API 接口，支持完整的 qBittorrent Web API 功能。

### 主要特性

- 🚀 **现代化PHP设计** - 支持PHP 8.0+，严格类型声明，返回类型约束
- 📦 **PSR标准兼容** - PSR-4自动加载，PSR-7 HTTP消息，PSR-12编码标准
- 🎯 **qBittorrent 5.x兼容** - 完全支持最新的qBittorrent Web API
- 🔒 **强大的异常处理** - 详细的错误信息，专门的异常类型
- 🏗️ **模块化架构** - 清晰的接口设计，易于扩展和维护
- ⚡ **高性能** - 连接复用，批量操作支持
- 🧪 **完整测试覆盖** - 单元测试和集成测试

### API模块结构

本库将 qBittorrent Web API 分为以下几个模块：

- **认证API** (`AuthAPI`) - 处理登录、登出等认证操作
- **应用API** (`ApplicationAPI`) - 应用程序信息和设置管理
- **传输API** (`TransferAPI`) - 全局传输信息和速度限制管理
- **种子API** (`TorrentAPI`) - 种子管理相关操作
- **搜索API** (`SearchAPI`) - 种子搜索功能
- **RSS API** (`RSSAPI`) - RSS订阅管理

## qBittorrent原生API与实现对应关系

本节详细展示了 qBittorrent 原生 Web API 与我们 PHP 实现之间的对应关系，帮助开发者更好地理解和使用本库。

### 认证API对应关系

| 原生API端点 | HTTP方法 | PHP实现类 | PHP方法 | 说明 |
|-------------|----------|-----------|---------|------|
| `/api/v2/auth/login` | POST | `Client` | `login()` | 用户登录认证 |
| `/api/v2/auth/logout` | POST | `Client` | `logout()` | 用户登出 |

**使用示例：**
```php
// 原生API调用
// POST /api/v2/auth/login
// 参数: username=admin&password=adminpass

// PHP实现
$client = new Client('http://localhost:8080', 'admin', 'adminpass');
$client->login();
```

### 应用管理API对应关系

| 原生API端点 | HTTP方法 | PHP实现类 | PHP方法 | 说明 |
|-------------|----------|-----------|---------|------|
| `/api/v2/app/version` | GET | `ApplicationAPI` | `getVersion()` | 获取应用版本 |
| `/api/v2/app/webapiVersion` | GET | `ApplicationAPI` | `getWebApiVersion()` | 获取Web API版本 |
| `/api/v2/app/buildInfo` | GET | `ApplicationAPI` | `getBuildInfo()` | 获取构建信息 |
| `/api/v2/app/preferences` | GET | `ApplicationAPI` | `getPreferences()` | 获取应用偏好设置 |

**使用示例：**
```php
// 原生API调用
// GET /api/v2/app/version
// 返回: "v4.6.0"

// PHP实现
$request = \PhpQbittorrent\Request\Application\GetVersionRequest::create();
$response = $client->application()->getVersion($request);
$version = $response->getVersion();
```

### 传输管理API对应关系

| 原生API端点 | HTTP方法 | PHP实现类 | PHP方法 | 说明 |
|-------------|----------|-----------|---------|------|
| `/api/v2/transfer/info` | GET | `TransferAPI` | `getGlobalTransferInfo()` | 获取全局传输信息 |
| `/api/v2/transfer/speedLimitsMode` | GET | `TransferAPI` | `getAlternativeSpeedLimitsState()` | 获取替代速度限制状态 |
| `/api/v2/transfer/toggleSpeedLimitsMode` | GET | `TransferAPI` | `toggleAlternativeSpeedLimits()` | 切换替代速度限制 |

**使用示例：**
```php
// 原生API调用
// GET /api/v2/transfer/info
// 返回: {"dl_info_speed": 1024, "up_info_speed": 512, ...}

// PHP实现
$request = \PhpQbittorrent\Request\Transfer\GetGlobalTransferInfoRequest::create();
$response = $client->transfer()->getGlobalTransferInfo($request);
$transferInfo = $response->getTransferInfo();
$downloadSpeed = $transferInfo->getDownloadSpeed();
```

### 种子管理API对应关系

| 原生API端点 | HTTP方法 | PHP实现类 | PHP方法 | 说明 |
|-------------|----------|-----------|---------|------|
| `/api/v2/torrents/info` | GET | `TorrentAPI` | `getTorrents()` | 获取种子列表 |
| `/api/v2/torrents/add` | POST | `TorrentAPI` | `addTorrents()` | 添加种子 |
| `/api/v2/torrents/pause` | POST | `TorrentAPI` | `pauseTorrents()` | 暂停种子 |
| `/api/v2/torrents/resume` | POST | `TorrentAPI` | `resumeTorrents()` | 恢复种子 |
| `/api/v2/torrents/delete` | POST | `TorrentAPI` | `deleteTorrents()` | 删除种子 |

**使用示例：**
```php
// 原生API调用
// GET /api/v2/torrents/info?filter=downloading
// 返回: [{"hash": "...", "name": "...", "size": 12345, ...}]

// PHP实现
$request = \PhpQbittorrent\Request\Torrent\GetTorrentsRequest::create()
    ->withFilter('downloading');
$response = $client->torrents()->getTorrents($request);
$torrents = $response->getTorrents();
```

### 搜索API对应关系

| 原生API端点 | HTTP方法 | PHP实现类 | PHP方法 | 说明 |
|-------------|----------|-----------|---------|------|
| `/api/v2/search/start` | POST | `SearchAPI` | `startSearch()` | 开始搜索 |
| `/api/v2/search/stop` | POST | `SearchAPI` | `stopSearch()` | 停止搜索 |
| `/api/v2/search/status` | GET | `SearchAPI` | `getSearchStatus()` | 获取搜索状态 |
| `/api/v2/search/results` | GET | `SearchAPI` | `getSearchResults()` | 获取搜索结果 |
| `/api/v2/search/delete` | POST | `SearchAPI` | `deleteSearch()` | 删除搜索 |

**使用示例：**
```php
// 原生API调用
// POST /api/v2/search/start
// 参数: pattern=ubuntu&plugins=all&category=all
// 返回: {"id": 123}

// PHP实现
$request = \PhpQbittorrent\Request\Search\StartSearchRequest::create()
    ->withPattern('ubuntu')
    ->withPlugins(['all'])
    ->withCategory('all');
$response = $client->search()->startSearch($request);
$searchId = $response->getSearchId();
```

### RSS API对应关系

| 原生API端点 | HTTP方法 | PHP实现类 | PHP方法 | 说明 |
|-------------|----------|-----------|---------|------|
| `/api/v2/rss/items` | GET | `RSSAPI` | `getAllItems()` | 获取所有RSS项目 |
| `/api/v2/rss/markAsRead` | POST | `RSSAPI` | `markAsRead()` | 标记为已读 |
| `/api/v2/rss/refreshItem` | POST | `RSSAPI` | `refreshItem()` | 刷新RSS项目 |

**使用示例：**
```php
// 原生API调用
// GET /api/v2/rss/items?withData=true
// 返回: [{"title": "...", "link": "...", "description": "...", ...}]

// PHP实现
$request = \PhpQbittorrent\Request\RSS\GetAllItemsRequest::create()
    ->withData(true);
$response = $client->rss()->getAllItems($request);
$rssItems = $response->getRssItems();
```

### 统一客户端简化对应关系

统一客户端 (`UnifiedClient`) 提供了更简化的API，隐藏了请求/响应对象的复杂性：

| 原生API端点 | 统一客户端方法 | 说明 |
|-------------|-------------|------|
| `/api/v2/app/version` | `getVersion()` | 直接返回版本字符串 |
| `/api/v2/transfer/info` | `getTransferInfo()` | 直接返回传输信息数组 |
| `/api/v2/torrents/info` | `getTorrents($options)` | 直接返回种子集合 |
| `/api/v2/torrents/add` | `addTorrent($options)` | 直接返回布尔值 |
| `/api/v2/search/start` | `search($pattern, ...)` | 同步搜索，直接返回结果 |

**对比示例：**
```php
// 标准客户端（需要请求/响应对象）
$request = \PhpQbittorrent\Request\Application\GetVersionRequest::create();
$response = $client->application()->getVersion($request);
if ($response->isSuccess()) {
    $version = $response->getVersion();
}

// 统一客户端（简化调用）
$version = $client->getVersion();
```

### 参数映射关系

#### 种子过滤参数

| 原生API参数 | PHP实现方法 | 说明 |
|-------------|-------------|------|
| `filter` | `withFilter($value)` | 过滤器：all, downloading, completed, paused, active, inactive, resumed, stalled, stalled_uploading, stalled_downloading |
| `category` | `withCategory($value)` | 分类名称 |
| `tag` | `withTag($value)` | 标签名称 |
| `sort` | `withSort($value)` | 排序字段 |
| `reverse` | `withReverse($bool)` | 是否逆序 |
| `limit` | `withLimit($int)` | 限制数量 |
| `offset` | `withOffset($int)` | 偏移量 |
| `hashes` | `withHashes($string)` | 种子哈希（多个用|分隔） |

#### 添加种子参数

| 原生API参数 | PHP实现方法 | 说明 |
|-------------|-------------|------|
| `urls` | `withUrls($string)` | 种子URL |
| `torrents` | `withTorrents($content)` | 种子文件内容 |
| `savepath` | `withSavePath($path)` | 保存路径 |
| `category` | `withCategory($name)` | 分类名称 |
| `paused` | `withPaused($bool)` | 是否暂停添加 |
| `skip_checking` | `withSkipChecking($bool)` | 跳过校验 |
| `root_folder` | `withRootFolder($string)` | 根文件夹 |

### 响应格式映射

#### 成功响应

| 原生API响应 | PHP响应对象 | 说明 |
|-------------|-------------|------|
| 字符串版本号 | `VersionResponse` | `getVersion()` 返回字符串 |
| JSON对象 | `BuildInfoResponse` | `getBuildInfo()` 返回数组 |
| JSON数组 | `TorrentListResponse` | `getTorrents()` 返回种子集合 |
| 空响应(200) | 通用响应对象 | `isSuccess()` 返回true |

#### 错误响应

| 原生API状态码 | PHP异常类型 | 说明 |
|-------------|-------------|------|
| 403 | `AuthenticationException` | 认证失败 |
| 404 | `NetworkException` | 端点不存在 |
| 415 | `ApiRuntimeException` | 不支持的媒体类型 |
| 500 | `NetworkException` | 服务器内部错误 |

### 实现优势

相比直接调用原生API，我们的PHP实现提供以下优势：

1. **类型安全** - 所有参数和响应都有明确的类型定义
2. **自动验证** - 请求参数会自动验证，减少错误
3. **统一错误处理** - 标准化的异常处理机制
4. **面向对象** - 更好的代码组织和可维护性
5. **自动会话管理** - 自动处理认证cookie
6. **响应封装** - 统一的响应对象格式
7. **批量操作支持** - 简化的批量操作方法
8. **IDE友好** - 完整的类型提示和文档注释

## 快速开始

### 安装

```bash
composer require dongasai/php-qbittorrent
```

### 基本使用

```php
<?php

require_once 'vendor/autoload.php';

use PhpQbittorrent\Client;

// 创建客户端实例
$client = new Client(
    'http://localhost:8080',
    'username',
    'password'
);

// 登录认证
if ($client->login()) {
    echo "登录成功！\n";
    
    // 获取应用版本
    $versionResponse = $client->application()->getVersion(
        \PhpQbittorrent\Request\Application\GetVersionRequest::create()
    );
    echo "qBittorrent 版本: " . $versionResponse->getVersion() . "\n";
    
    // 获取种子列表
    $torrentsResponse = $client->torrents()->getTorrents(
        \PhpQbittorrent\Request\Torrent\GetTorrentsRequest::create()
    );
    $torrents = $torrentsResponse->getTorrents();
    echo "当前种子数量: " . count($torrents) . "\n";
    
    // 登出
    $client->logout();
}
```

## 客户端初始化

### 基本客户端

```php
use PhpQbittorrent\Client;

// 基本初始化
$client = new Client(
    'http://localhost:8080',  // qBittorrent Web UI 地址
    'username',               // 用户名
    'password'                // 密码
);
```

### 自定义传输层

```php
use PhpQbittorrent\Client;
use PhpQbittorrent\Transport\CurlTransport;
use Nyholm\Psr7\Factory\Psr17Factory;

// 创建自定义传输层
$transport = new CurlTransport(
    new Psr17Factory(),  // 请求工厂
    new Psr17Factory()   // 响应工厂
);

// 使用自定义传输层创建客户端
$client = new Client(
    'http://localhost:8080',
    'username',
    'password',
    $transport
);
```

### 配置选项

客户端支持以下配置选项：

- `baseUrl` - qBittorrent 服务器地址
- `username` - 登录用户名
- `password` - 登录密码
- `transport` - 自定义传输层（可选）

## 认证管理

### 登录

```php
// 执行登录
$success = $client->login();

if ($success) {
    echo "认证成功\n";
} else {
    echo "认证失败\n";
}
```

### 检查认证状态

```php
// 检查是否已认证
if ($client->isAuthenticated()) {
    echo "用户已认证\n";
}

// 检查是否已登录（别名方法）
if ($client->isLoggedIn()) {
    echo "用户已登录\n";
}
```

### 强制认证

```php
// 如果未认证则自动认证
$success = $client->ensureAuthenticated();
```

### 登出

```php
// 执行登出
$success = $client->logout();

if ($success) {
    echo "登出成功\n";
}
```

## 应用管理API

应用管理API提供应用程序信息和设置管理功能。

### 获取应用版本

```php
use PhpQbittorrent\Request\Application\GetVersionRequest;

$request = GetVersionRequest::create();
$response = $client->application()->getVersion($request);

if ($response->isSuccess()) {
    echo "应用版本: " . $response->getVersion() . "\n";
} else {
    echo "获取版本失败: " . implode(', ', $response->getErrors()) . "\n";
}
```

### 获取Web API版本

```php
use PhpQbittorrent\Request\Application\GetWebApiVersionRequest;

$request = GetWebApiVersionRequest::create();
$response = $client->application()->getWebApiVersion($request);

if ($response->isSuccess()) {
    echo "Web API版本: " . $response->getVersion() . "\n";
}
```

### 获取构建信息

```php
use PhpQbittorrent\Request\Application\GetBuildInfoRequest;

$request = GetBuildInfoRequest::create();
$response = $client->application()->getBuildInfo($request);

if ($response->isSuccess()) {
    $buildInfo = $response->getBuildInfo();
    echo "构建信息: " . json_encode($buildInfo, JSON_PRETTY_PRINT) . "\n";
}
```

### 获取应用偏好设置

```php
use PhpQbittorrent\Request\Application\GetPreferencesRequest;

$request = GetPreferencesRequest::create();
$response = $client->application()->getPreferences($request);

if ($response->isSuccess()) {
    $preferences = $response->getData()['preferences'] ?? [];
    echo "偏好设置: " . json_encode($preferences, JSON_PRETTY_PRINT) . "\n";
}
```

## 传输管理API

传输管理API提供全局传输信息和速度限制管理功能。

### 获取全局传输信息

```php
use PhpQbittorrent\Request\Transfer\GetGlobalTransferInfoRequest;

$request = GetGlobalTransferInfoRequest::create();
$response = $client->transfer()->getGlobalTransferInfo($request);

if ($response->isSuccess()) {
    $transferInfo = $response->getTransferInfo();
    echo "下载速度: " . $transferInfo->getDownloadSpeed() . " 字节/秒\n";
    echo "上传速度: " . $transferInfo->getUploadSpeed() . " 字节/秒\n";
    echo "全局下载限制: " . $transferInfo->getDlLimit() . " 字节/秒\n";
    echo "全局上传限制: " . $transferInfo->getUpLimit() . " 字节/秒\n";
}
```

### 获取替代速度限制状态

```php
use PhpQbittorrent\Request\Transfer\GetAlternativeSpeedLimitsStateRequest;

$request = GetAlternativeSpeedLimitsStateRequest::create();
$response = $client->transfer()->getAlternativeSpeedLimitsState($request);

if ($response->isSuccess()) {
    $isAlternativeSpeedEnabled = $response->isAlternativeSpeedEnabled();
    echo "替代速度限制状态: " . ($isAlternativeSpeedEnabled ? '启用' : '禁用') . "\n";
}
```

### 切换替代速度限制

```php
use PhpQbittorrent\Request\Transfer\ToggleAlternativeSpeedLimitsRequest;

$request = ToggleAlternativeSpeedLimitsRequest::create();
$response = $client->transfer()->toggleAlternativeSpeedLimits($request);

if ($response->isSuccess()) {
    $isAlternativeSpeedEnabled = $response->isAlternativeSpeedEnabled();
    echo "切换后的状态: " . ($isAlternativeSpeedEnabled ? '启用' : '禁用') . "\n";
}
```

## 种子管理API

种子管理API提供种子相关的所有操作功能。

### 获取种子列表

```php
use PhpQbittorrent\Request\Torrent\GetTorrentsRequest;

// 基本请求
$request = GetTorrentsRequest::create();
$response = $client->torrents()->getTorrents($request);

if ($response->isSuccess()) {
    $torrents = $response->getTorrents();
    foreach ($torrents as $torrent) {
        echo "种子名称: " . $torrent->getName() . "\n";
        echo "种子哈希: " . $torrent->getHash() . "\n";
        echo "种子大小: " . $torrent->getSize() . " 字节\n";
        echo "进度: " . ($torrent->getProgress() * 100) . "%\n";
        echo "状态: " . $torrent->getState() . "\n";
        echo "------------------------\n";
    }
}

// 带过滤条件的请求
$request = GetTorrentsRequest::create()
    ->withFilter('downloading')  // 只获取下载中的种子
    ->withCategory('movies')     // 只获取指定分类的种子
    ->withSort('name')           // 按名称排序
    ->withReverse(true)          // 逆序
    ->withLimit(10)              // 限制返回数量
    ->withOffset(0);             // 偏移量

$response = $client->torrents()->getTorrents($request);
```

### 获取种子统计信息

```php
// 使用便捷方法获取统计信息
$stats = $client->torrents()->getTorrentStats();

echo "总种子数: " . $stats['total'] . "\n";
echo "下载中: " . $stats['downloading'] . "\n";
echo "做种中: " . $stats['seeding'] . "\n";
echo "已完成: " . $stats['completed'] . "\n";
echo "已暂停: " . $stats['paused'] . "\n";
echo "错误: " . $stats['error'] . "\n";
echo "非活动: " . $stats['inactive'] . "\n";
```

### 添加种子

```php
use PhpQbittorrent\Request\Torrent\AddTorrentRequest;

// 从URL添加种子
$request = AddTorrentRequest::create()
    ->withUrls('https://example.com/torrent.torrent')
    ->withSavePath('/downloads')
    ->withCategory('movies')
    ->withPaused(false);

$response = $client->torrents()->addTorrents($request);

if ($response->isSuccess()) {
    echo "种子添加成功\n";
} else {
    echo "种子添加失败\n";
}

// 从文件添加种子
$torrentContent = file_get_contents('/path/to/torrent.torrent');
$request = AddTorrentRequest::create()
    ->withTorrents($torrentContent)
    ->withFilename('example.torrent')
    ->withSavePath('/downloads')
    ->withCategory('movies');

$response = $client->torrents()->addTorrents($request);
```

### 暂停种子

```php
use PhpQbittorrent\Request\Torrent\PauseTorrentsRequest;

// 暂停单个种子
$request = PauseTorrentsRequest::create('torrent_hash_here');
$response = $client->torrents()->pauseTorrents($request);

// 暂停多个种子（用|分隔）
$hashes = 'hash1|hash2|hash3';
$request = PauseTorrentsRequest::create($hashes);
$response = $client->torrents()->pauseTorrents($request);

// 暂停所有种子
$request = PauseTorrentsRequest::create('all');
$response = $client->torrents()->pauseTorrents($request);
```

### 恢复种子

```php
use PhpQbittorrent\Request\Torrent\ResumeTorrentsRequest;

// 恢复单个种子
$request = ResumeTorrentsRequest::create('torrent_hash_here');
$response = $client->torrents()->resumeTorrents($request);

// 恢复多个种子
$hashes = 'hash1|hash2|hash3';
$request = ResumeTorrentsRequest::create($hashes);
$response = $client->torrents()->resumeTorrents($request);

// 恢复所有种子
$request = ResumeTorrentsRequest::create('all');
$response = $client->torrents()->resumeTorrents($request);
```

### 删除种子

```php
use PhpQbittorrent\Request\Torrent\DeleteTorrentsRequest;

// 删除种子但保留文件
$request = DeleteTorrentsRequest::create('torrent_hash_here', false);
$response = $client->torrents()->deleteTorrents($request);

// 删除种子并删除文件
$request = DeleteTorrentsRequest::create('torrent_hash_here', true);
$response = $client->torrents()->deleteTorrents($request);

// 删除多个种子
$hashes = 'hash1|hash2|hash3';
$request = DeleteTorrentsRequest::create($hashes, true);
$response = $client->torrents()->deleteTorrents($request);
```

## 搜索API

搜索API提供种子搜索功能。

### 开始搜索

```php
use PhpQbittorrent\Request\Search\StartSearchRequest;

$request = StartSearchRequest::create()
    ->withPattern('ubuntu')
    ->withPlugins(['all'])
    ->withCategory('all');

$response = $client->search()->startSearch($request);

if ($response->isSuccess()) {
    $searchId = $response->getSearchId();
    echo "搜索已开始，搜索ID: " . $searchId . "\n";
}
```

### 获取搜索状态

```php
use PhpQbittorrent\Request\Search\GetSearchStatusRequest;

$request = GetSearchStatusRequest::create();
$response = $client->search()->getSearchStatus($request);

if ($response->isSuccess()) {
    $searchJobs = $response->getSearchJobs();
    foreach ($searchJobs as $job) {
        echo "搜索ID: " . $job->getId() . "\n";
        echo "搜索状态: " . ($job->isRunning() ? '运行中' : '已完成') . "\n";
        echo "搜索进度: " . $job->getProgress() . "%\n";
    }
}
```

### 获取搜索结果

```php
use PhpQbittorrent\Request\Search\GetSearchResultsRequest;

$searchId = 123; // 从开始搜索响应中获取的ID
$request = GetSearchResultsRequest::create($searchId)
    ->withLimit(50)
    ->withOffset(0);

$response = $client->search()->getSearchResults($request);

if ($response->isSuccess()) {
    $results = $response->getSearchResults();
    foreach ($results as $result) {
        echo "文件名: " . $result->getFileName() . "\n";
        echo "文件大小: " . $result->getFileSize() . " 字节\n";
        echo "种子链接: " . $result->getFileUrl() . "\n";
        echo "种子哈希: " . $result->getTorrentHash() . "\n";
        echo "种子数量: " . $result->getNbSeeders() . "\n";
        echo "下载数量: " . $result->getNbLeechers() . "\n";
        echo "------------------------\n";
    }
}
```

### 停止搜索

```php
use PhpQbittorrent\Request\Search\StopSearchRequest;

$searchId = 123;
$request = StopSearchRequest::create($searchId);
$response = $client->search()->stopSearch($request);

if ($response->isSuccess()) {
    echo "搜索已停止\n";
}
```

### 删除搜索

```php
use PhpQbittorrent\Request\Search\DeleteSearchRequest;

$searchId = 123;
$request = DeleteSearchRequest::create($searchId);
$response = $client->search()->deleteSearch($request);

if ($response->isSuccess()) {
    echo "搜索已删除\n";
}
```

## RSS API

RSS API提供RSS订阅管理功能。

### 获取所有RSS项目

```php
use PhpQbittorrent\Request\RSS\GetAllItemsRequest;

$request = GetAllItemsRequest::create()
    ->withData(true); // 包含详细数据

$response = $client->rss()->getAllItems($request);

if ($response->isSuccess()) {
    $rssItems = $response->getRssItems();
    foreach ($rssItems as $item) {
        echo "标题: " . $item->getTitle() . "\n";
        echo "链接: " . $item->getLink() . "\n";
        echo "描述: " . $item->getDescription() . "\n";
        echo "发布时间: " . $item->getPubDate() . "\n";
        echo "是否已读: " . ($item->isRead() ? '是' : '否') . "\n";
        echo "------------------------\n";
    }
}
```

### 标记为已读

```php
use PhpQbittorrent\Request\RSS\MarkAsReadRequest;

$request = MarkAsReadRequest::create('item_path');
$response = $client->rss()->markAsRead($request);

if ($response->isSuccess()) {
    echo "已标记为已读\n";
}
```

### 刷新RSS项目

```php
use PhpQbittorrent\Request\RSS\RefreshItemRequest;

$request = RefreshItemRequest::create('item_path');
$response = $client->rss()->refreshItem($request);

if ($response->isSuccess()) {
    echo "RSS项目已刷新\n";
}
```

## 统一客户端

统一客户端 (`UnifiedClient`) 提供了更简化的API接口，整合了所有功能模块。

### 基本使用

```php
use PhpQbittorrent\UnifiedClient;

// 快速创建
$client = UnifiedClient::quick(
    'http://localhost:8080',
    'username',
    'password'
);

// 登录
if ($client->login()) {
    echo "登录成功\n";
    
    // 获取版本信息
    echo "版本: " . $client->getVersion() . "\n";
    echo "Web API版本: " . $client->getWebApiVersion() . "\n";
    
    // 获取传输信息
    $transferInfo = $client->getTransferInfo();
    echo "下载速度: " . $transferInfo['dl_info_speed'] . " 字节/秒\n";
    echo "上传速度: " . $transferInfo['up_info_speed'] . " 字节/秒\n";
    
    // 获取种子列表
    $torrents = $client->getTorrents();
    echo "种子数量: " . $torrents->count() . "\n";
    
    // 添加种子
    $success = $client->addTorrentFromUrl(
        'https://example.com/torrent.torrent',
        ['category' => 'movies', 'savepath' => '/downloads']
    );
    
    if ($success) {
        echo "种子添加成功\n";
    }
    
    // 搜索种子
    $results = $client->search('ubuntu', [], 'all', 30);
    echo "搜索结果数量: " . $results->count() . "\n";
}
```

### 从配置创建

```php
use PhpQbittorrent\UnifiedClient;

// 从数组配置创建
$config = [
    'base_url' => 'http://localhost:8080',
    'username' => 'username',
    'password' => 'password',
    'timeout' => 30,
    'verify_ssl' => false
];

$client = UnifiedClient::fromConfig($config);

// 从JSON文件创建
$client = UnifiedClient::fromJsonFile('/path/to/config.json');

// 从环境变量创建
$client = UnifiedClient::fromEnvironment('QBITTORRENT_');
```

### 批量操作

```php
// 批量暂停所有活动种子
$pausedCount = $client->pauseAllTorrents();
echo "暂停了 {$pausedCount} 个种子\n";

// 批量恢复所有种子
$resumedCount = $client->resumeAllTorrents();
echo "恢复了 {$resumedCount} 个种子\n";
```

## 错误处理

本库提供了详细的异常处理机制，包含多种专门的异常类型。

### 异常类型

- `AuthenticationException` - 认证相关错误
- `NetworkException` - 网络连接错误
- `ValidationException` - 请求验证错误
- `ApiRuntimeException` - API运行时错误
- `ClientException` - 客户端通用错误
- `Exception` - 基础异常类

### 错误处理示例

```php
use PhpQbittorrent\Client;
use PhpQbittorrent\Exception\AuthenticationException;
use PhpQbittorrent\Exception\NetworkException;
use PhpQbittorrent\Exception\ValidationException;

try {
    $client = new Client('http://localhost:8080', 'user', 'pass');
    
    if ($client->login()) {
        // 执行API操作
        $response = $client->application()->getVersion(
            \PhpQbittorrent\Request\Application\GetVersionRequest::create()
        );
        
        if ($response->isSuccess()) {
            echo "版本: " . $response->getVersion() . "\n";
        } else {
            echo "API错误: " . implode(', ', $response->getErrors()) . "\n";
        }
    }
    
} catch (AuthenticationException $e) {
    echo "认证失败: " . $e->getMessage() . "\n";
    echo "错误代码: " . $e->getErrorCode() . "\n";
    echo "用户名: " . $e->getUsername() . "\n";
    
} catch (NetworkException $e) {
    echo "网络错误: " . $e->getMessage() . "\n";
    echo "错误代码: " . $e->getErrorCode() . "\n";
    echo "URL: " . $e->getUrl() . "\n";
    echo "HTTP方法: " . $e->getHttpMethod() . "\n";
    
} catch (ValidationException $e) {
    echo "验证错误: " . $e->getMessage() . "\n";
    echo "验证错误详情: " . json_encode($e->getValidationErrors()) . "\n";
    
} catch (\Exception $e) {
    echo "未知错误: " . $e->getMessage() . "\n";
}
```

### 响应错误处理

```php
// 检查响应是否成功
$response = $client->application()->getVersion($request);

if (!$response->isSuccess()) {
    $errors = $response->getErrors();
    $statusCode = $response->getStatusCode();
    
    echo "请求失败，状态码: {$statusCode}\n";
    echo "错误信息: " . implode(', ', $errors) . "\n";
    
    // 获取原始响应内容进行调试
    $rawResponse = $response->getRawResponse();
    echo "原始响应: " . $rawResponse . "\n";
}
```

## 最佳实践

### 1. 连接管理

```php
// 使用单一客户端实例，避免重复创建
$client = new Client('http://localhost:8080', 'user', 'pass');

// 在应用启动时登录一次
if ($client->login()) {
    // 在整个应用生命周期中复用这个客户端
    // 库会自动管理会话状态
}
```

### 2. 错误重试机制

```php
function executeWithRetry(callable $operation, int $maxRetries = 3): mixed
{
    $attempts = 0;
    
    while ($attempts < $maxRetries) {
        try {
            return $operation();
        } catch (NetworkException $e) {
            $attempts++;
            if ($attempts >= $maxRetries) {
                throw $e;
            }
            
            // 指数退避
            $delay = min(2 ** $attempts, 10);
            sleep($delay);
        }
    }
    
    throw new \RuntimeException("操作失败，已达到最大重试次数");
}

// 使用示例
$version = executeWithRetry(function() use ($client) {
    $response = $client->application()->getVersion(
        \PhpQbittorrent\Request\Application\GetVersionRequest::create()
    );
    return $response->getVersion();
});
```

### 3. 批量操作优化

```php
// 批量操作时使用管道模式
$hashes = ['hash1', 'hash2', 'hash3'];

// 错误方式：多次请求
foreach ($hashes as $hash) {
    $client->torrents()->pauseTorrents(
        \PhpQbittorrent\Request\Torrent\PauseTorrentsRequest::create($hash)
    );
}

// 正确方式：单次请求
$hashesString = implode('|', $hashes);
$client->torrents()->pauseTorrents(
    \PhpQbittorrent\Request\Torrent\PauseTorrentsRequest::create($hashesString)
);
```

### 4. 内存管理

```php
// 处理大量种子时使用分页
$limit = 100;
$offset = 0;

do {
    $request = \PhpQbittorrent\Request\Torrent\GetTorrentsRequest::create()
        ->withLimit($limit)
        ->withOffset($offset);
    
    $response = $client->torrents()->getTorrents($request);
    $torrents = $response->getTorrents();
    
    // 处理当前页的种子
    foreach ($torrents as $torrent) {
        // 处理逻辑
    }
    
    $offset += $limit;
} while (count($torrents) === $limit);
```

### 5. 配置管理

```php
// 使用配置文件管理连接信息
// config.json
{
    "qbittorrent": {
        "base_url": "http://localhost:8080",
        "username": "admin",
        "password": "adminpass",
        "timeout": 30,
        "verify_ssl": false
    }
}

// 在应用中使用
$config = json_decode(file_get_contents('config.json'), true);
$qbConfig = $config['qbittorrent'];

$client = new Client(
    $qbConfig['base_url'],
    $qbConfig['username'],
    $qbConfig['password']
);
```

### 6. 日志记录

```php
// 为API调用添加日志
function logApiCall(string $method, string $endpoint, $response): void
{
    $logData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'method' => $method,
        'endpoint' => $endpoint,
        'success' => $response->isSuccess(),
        'status_code' => $response->getStatusCode()
    ];
    
    if (!$response->isSuccess()) {
        $logData['errors'] = $response->getErrors();
    }
    
    file_put_contents('api.log', json_encode($logData) . "\n", FILE_APPEND);
}

// 使用示例
$response = $client->application()->getVersion($request);
logApiCall('GET', '/version', $response);
```

---

## 📋 版本兼容性

| 项目 | 版本要求 |
|------|----------|
| **PHP版本** | 8.0+ |
| **qBittorrent版本** | 5.x |
| **扩展依赖** | curl, json |

## 📄 许可证

MIT License

## 🔗 更多信息

| 资源 | 链接 |
|------|------|
| **GitHub仓库** | [https://github.com/dongasai/php-qbittorrent](https://github.com/dongasai/php-qbittorrent) |
| **问题反馈** | [https://github.com/dongasai/php-qbittorrent/issues](https://github.com/dongasai/php-qbittorrent/issues) |
| **qBittorrent官方API文档** | [WebUI-API-(qBittorrent-5.0)](https://github.com/qbittorrent/qBittorrent/wiki/WebUI-API-(qBittorrent-5.0)) |

---

> 💡 **提示**: 如果在使用过程中遇到问题，请先查看 [错误处理](#错误处理) 章节，然后参考 [最佳实践](#最佳实践) 中的建议。