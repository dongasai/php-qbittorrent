# 🧲 磁力链接测试指南

## 概述

本指南详细说明如何使用 php_qbittorrent 库中的磁力链接测试功能。该功能可以批量添加磁力链接到 qBittorrent 并验证添加结果。

## 🔧 配置要求

### 1. 启用批量测试

在您的 `.env` 文件中设置：

```bash
QBITTORRENT_BATCH_TEST=true
```

### 2. 配置测试磁力链接

您可以配置最多3个测试用磁力链接：

```bash
# 测试用磁力链接
QBITTORRENT_TEST_MAGNET_1=magnet:?xt=urn:btih:40a5dc71f18c91acbc62b4be8c13a2a4bd026d5
QBITTORRENT_TEST_MAGNET_2=magnet:?xt=urn:btih:40a5dc71f18c91acbc62b4be8c13a2a4bd026d6
QBITTORRENT_TEST_MAGNET_3=magnet:?xt=urn:btih:6b33a9a8f18c91acbc62b4be8c13a2a4bd026d7
```

### 3. 可选配置

```bash
# 自定义下载目录（可选）
QBITTORRENT_DOWNLOAD_PATH=/downloads/test
```

## 🚀 使用方法

### 方法一：运行完整测试脚本

```bash
# 运行包含磁力链接测试的完整测试脚本
php examples/quick_test.php
```

### 方法二：单独测试磁力链接功能

```bash
# 只测试磁力链接功能
php examples/magnet_test.php
```

## 📋 测试流程

### 1. 准备阶段
- 检查环境变量配置
- 验证 qBittorrent 连接
- 获取当前 torrent 数量

### 2. 添加阶段
- 逐个添加配置的磁力链接
- 显示添加结果和错误信息
- 记录新添加的 torrent hash

### 3. 验证阶段
- 等待 3 秒让 qBittorrent 处理
- 获取并显示新添加的 torrent 信息
- 显示状态、进度、大小等信息

### 4. 管理操作测试阶段
- **暂停和恢复测试** - 测试torrent的暂停和恢复功能
- **重新校验测试** - 测试torrent数据完整性校验（随机执行）
- **移动目录测试** - 测试将torrent移动到指定目录（需要配置QBITTORRENT_DOWNLOAD_PATH）
- **优先级设置** - 测试调整torrent下载优先级
- **标签管理** - 测试添加和验证torrent标签
- **分类管理** - 测试创建分类并添加torrent到分类
- **状态验证** - 验证所有操作后的最终状态

### 5. 清理阶段
- 默认不清理测试 torrents
- 可选择启用自动清理（修改代码中的 `$cleanupEnabled` 变量）

## 🎯 测试输出示例

```
🧲 磁力链接测试功能...
   启用磁力链接测试: 3 个测试链接
   测试前torrent数量: 0
   添加磁力链接 1...
     ✅ 磁力链接添加成功
     📝 新增torrent: Example Torrent 1
   添加磁力链接 2...
     ✅ 磁力链接添加成功
     📝 新增torrent: Example Torrent 2
   添加磁力链接 3...
     ✅ 磁力链接添加成功
     📝 新增torrent: Example Torrent 3
   成功添加 3 个磁力链接
   等待torrent信息更新...
   验证添加的torrent:
     ✅ 找到torrent: Example Torrent 1
        状态: downloading | 进度: 0.0%
        大小: 1.2 GB
        下载: 0 B/s
     ✅ 找到torrent: Example Torrent 2
        状态: metaDL | 进度: 15.5%
        大小: 856 MB
     ✅ 找到torrent: Example Torrent 3
        状态: stalled | 进度: 100.0%
        大小: 2.1 GB

   🔧 Torrent管理操作测试:
     测试torrent: Example Torrent 1
     初始状态: downloading
     ⏸️  测试暂停...
        ✅ 暂停成功
        当前状态: pausedDL
     ▶️  测试恢复...
        ✅ 恢复成功
        当前状态: downloading
     🎯 测试设置下载优先级...
        ✅ 优先级设置成功
     🏷️  测试添加标签...
        ✅ 标签添加成功: php-qbittorrent-test-2025-11-09
        ✅ 标签验证成功
     📂 测试添加到分类...
        ✅ 添加到分类成功: php-qbittorrent-test
        ✅ 分类验证成功
     📊 最终状态检查:
        状态: downloading
        进度: 2.3%
        分类: php-qbittorrent-test
        标签: php-qbittorrent-test-2025-11-09
```

## ⚙️ 自定义配置

### 修改清理行为

在 `quick_test.php` 中找到以下行：

```php
$cleanupEnabled = false; // 默认不清理，用户可以改为true
```

改为 `true` 以启用自动清理：

```php
$cleanupEnabled = true; // 启用自动清理
```

### 自定义磁力链接

您可以使用任何有效的磁力链接替换默认的测试链接。建议使用：

- 公共领域的测试 torrents
- 小型文件以减少测试时间
- 不同状态的 torrents（下载中、已完成、元数据下载中）

## 🔍 故障排除

### 问题 1: 磁力链接添加失败

**原因**:
- 磁力链接格式错误
- qBittorrent 无法解析磁力链接
- 网络连接问题

**解决方案**:
- 验证磁力链接格式正确
- 检查网络连接
- 查看 qBittorrent 日志

### 问题 2: 找不到新添加的 torrent

**原因**:
- qBittorrent 还在处理元数据
- torrent 已存在
- 清理功能过早删除

**解决方案**:
- 增加等待时间
- 检查 torrent 是否已存在
- 禁用自动清理

### 问题 3: 测试被跳过

**原因**:
- `QBITTORRENT_BATCH_TEST` 设置为 `false`
- 未配置测试磁力链接

**解决方案**:
```bash
# 确保设置了环境变量
export QBITTORRENT_BATCH_TEST=true
export QBITTORRENT_TEST_MAGNET_1="your:magnet:link:here"
```

## 🛡️ 安全注意事项

### 1. 只操作测试磁力链接
- 测试脚本只会操作通过本次测试添加的磁力链接
- 不会影响端点中原有的其他种子
- 管理操作仅针对 `QBITTORRENT_TEST_MAGNET_*` 环境变量添加的torrents

### 2. 使用测试 torrents
- 避免使用受版权保护的内容
- 建议使用公共领域或测试专用的 torrents
- 考虑文件大小对测试时间的影响

### 3. 网络安全
- 确保在安全的网络环境中测试
- 注意防火墙和代理设置
- 监控网络流量

### 4. 存储空间
- 测试 torrents 会占用磁盘空间
- 定期清理测试数据
- 监控可用存储空间

## 📊 性能考虑

### 测试时间
- 每个磁力链接添加大约需要 1-2 秒
- 元数据下载可能需要额外时间
- 验证阶段等待 3 秒

### 资源使用
- CPU: 轻微影响，主要用于验证
- 内存: 基本无影响
- 磁盘: 取决于下载的 torrents 大小
- 网络: 可能占用较多带宽

## 🎉 成功标准

磁力链接测试成功的标准：
- [ ] 成功添加配置的磁力链接
- [ ] 能够获取新添加的 torrent 信息
- [ ] 正确显示 torrent 状态和进度
- [ ] 错误处理工作正常
- [ ] 清理功能（如果启用）正常工作

---

**更新时间**: 2025-11-09
**测试版本**: v0.2.0-alpha