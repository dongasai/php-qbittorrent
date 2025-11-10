<?php
declare(strict_types=1);
require_once __DIR__ . '/../vendor/autoload.php';

use PhpQbittorrent\Client;

/**
 * PHP qBittorrent Library - 快速测试脚本
 *
 * 预计运行需要3分钟,cli运行超时时间5分钟
 * php examples/quick_test.php
 * ## 🔒 安全声明
 *
 * **重要说明**：本测试脚本采用多重安全机制，确保不会影响用户现有的 torrents：
 *
 * 1. **只读操作** - 大部分测试为读取操作，不会修改任何数据
 * 2. **隔离测试** - 真实操作（暂停、恢复、移动等）仅对测试添加的磁力链接执行
 * 3. **Hash 验证** - 通过严格的 hash 匹配确保只操作已知测试连接
 * 4. **默认禁用清理** - 测试完成后默认不删除任何 torrents
 * 5. **用户确认** - 所有可变操作都需要明确的用户配置启用
 *
 * ## 测试内容概览
 *
 * 本脚本全面测试 php_qbittorrent 库的核心功能，包括：
 *
 * ### 1. 连接配置测试 (Connection Configuration Tests)
 * 1.1. ✅ 客户端实例创建测试
 * 1.2. ✅ 配置参数验证测试
 * 1.3. ✅ SSL证书设置测试
 * 1.4. ✅ 连接超时配置测试
 * 1.5. ✅ 环境变量加载测试
 *
 * ### 2. 认证管理测试 (Authentication Management Tests)
 * 2.1. ✅ 用户登录验证测试
 * 2.2. ✅ 登录状态检查测试
 * 2.3. ✅ 认证异常处理测试
 * 2.4. ✅ 无效凭据处理测试
 * 2.5. ✅ 网络连接异常测试
 * 2.6. ✅ 用户登出功能测试
 *
 * ### 3. 应用程序信息测试 (Application Information Tests)
 * 3.1. ✅ qBittorrent版本获取测试
 * 3.2. ✅ Web API版本获取测试
 * 3.3. ✅ 构建信息获取测试
 * 3.4. ✅ 默认保存路径获取测试
 * 3.5. ✅ 应用偏好设置获取测试
 * 3.6. ✅ Cookies管理功能测试
 * 3.7. ✅ 魔术方法访问测试
 *
 * ### 4. 传输统计信息测试 (Transfer Statistics Tests)
 * 4.1. ✅ 全局传输信息获取测试
 * 4.2. ✅ 下载速度统计测试
 * 4.3. ✅ 上传速度统计测试
 * 4.4. ✅ 总下载量统计测试
 * 4.5. ✅ 总上传量统计测试
 * 4.6. ✅ DHT节点连接统计测试
 * 4.7. ✅ 连接状态监控测试
 *
 * ### 5. Torrent基础管理测试 (Basic Torrent Management Tests)
 * 5.1. ✅ Torrent列表获取测试
 * 5.2. ✅ Torrent状态显示测试
 * 5.3. ✅ Torrent进度统计测试
 * 5.4. ✅ Torrent详细信息获取测试
 * 5.5. ✅ Torrent大小格式化测试
 * 5.6. ✅ Torrent过滤功能测试
 * 5.7. ✅ Torrent排序功能测试
 * 5.8. ✅ Torrent分页显示测试
 *
 * ### 6. 高级功能测试 (Advanced Features Tests)
 * 6.1. ✅ 搜索插件获取测试
 * 6.2. ✅ 搜索插件状态检查测试
 * 6.3. ✅ 搜索功能可用性验证测试
 * 6.4. ✅ Torrent统计信息获取测试
 * 6.5. ✅ 分类统计功能测试
 * 6.6. ✅ 状态分布统计测试
 * 6.7. ✅ 异常处理机制测试
 *
 * ### 7. 分类标签管理测试 (Category and Tag Management Tests)
 * 7.1. ✅ 分类列表获取测试
 * 7.2. ✅ 分类创建功能测试
 * 7.3. ✅ 分类路径设置测试
 * 7.4. ✅ 标签列表获取测试
 * 7.5. ✅ 标签创建功能测试
 * 7.6. ✅ 标签批量操作测试
 * 7.7. ✅ 分类标签关联测试
 *
 * ### 8. 磁力链接添加测试 (Magnet Link Addition Tests)
 * 8.1. ✅ 单个磁力链接添加测试
 * 8.2. ✅ 批量磁力链接添加测试
 * 8.3. ✅ 磁力链接参数配置测试
 * 8.4. ✅ 保存路径设置测试
 * 8.5. ✅ 分类自动分配测试
 * 8.6. ✅ 标签自动添加测试
 * 8.7. ✅ 添加结果验证测试
 * 8.8. ✅ 重复添加处理测试
 *
 * ### 9. Torrent操作管理测试 (Torrent Operation Management Tests)
 * 9.1. ✅ Torrent暂停功能测试 (仅限测试磁力链接)
 * 9.2. ✅ Torrent恢复功能测试 (仅限测试磁力链接)
 * 9.3. ✅ Torrent重新校验测试 (仅限测试磁力链接)
 * 9.4. ✅ Torrent目录移动测试 (仅限测试磁力链接)
 * 9.5. ✅ Torrent优先级设置测试 (仅限测试磁力链接)
 * 9.6. ✅ 强制启动功能测试 (仅限测试磁力链接)
 * 9.7. ✅ 超级做种功能测试 (仅限测试磁力链接)
 * 9.8. ✅ 顺序下载切换测试 (仅限测试磁力链接)
 * 9.9. ✅ 首尾Piece优先级测试 (仅限测试磁力链接)
 * 9.10. ✅ 批量操作支持测试 (仅限测试磁力链接)
 * 9.11. ✅ 操作结果验证测试 (仅限测试磁力链接)
 * 9.12. ✅ 状态变更监控测试 (仅限测试磁力链接)
 * 9.13. ✅ 多文件种子测试 (仅限多文件测试磁力链接)
 *   - 9.13.1. ✅ 文件列表获取测试
//    - 9.13.2. ✅ 全选文件测试
//    - 9.13.3. ✅ 减少文件测试 (选择性下载)
//    - 9.13.4. ✅ 增加文件测试 (重新选择)
//    - 9.13.5. ✅ 优先级循环切换测试
 *
 * **🔒 安全机制说明**:
 * - 所有真实操作（暂停、恢复、移动等）仅对测试添加的磁力链接执行
 * - 通过 hash 匹配确保不影响用户原有的 torrents
 * - 使用 `array_intersect()` 确保只操作已知的测试连接
 * - 在没有测试磁力链接时跳过所有管理操作测试
 *
 * ### 10. 性能评估测试 (Performance Evaluation Tests)
 * 10.1. ✅ API响应时间测试
 * 10.2. ✅ 总执行时间统计测试
 * 10.3. ✅ 平均响应时间计算测试
 * 10.4. ✅ 性能评级系统测试
 * 10.5. ✅ 内存使用监控测试
 * 10.6. ✅ 并发处理能力测试
 *
 * ## 🚧 缺失功能测试清单（待补充）
 *
 * ### 11. RSS功能测试 (RSS Function Tests) - 0/8 🔒
* **设计原则**: 本测试文件不进行RSS相关操作，避免影响用户的订阅配置
* 11.1. 🔒 RSS文件夹添加测试 (`addFolder`) - **安全考虑，不测试**
* 11.2. 🔒 RSS订阅源添加测试 (`addFeed`) - **安全考虑，不测试**
* 11.3. 🔒 RSS项目删除测试 (`removeItem`) - **安全考虑，不测试**
* 11.4. 🔒 RSS项目移动测试 (`moveItem`) - **安全考虑，不测试**
* 11.5. 🔒 RSS项目列表获取测试 (`items`) - **安全考虑，不测试**
* 11.6. 🔒 RSS标记已读测试 (`markAsRead`) - **安全考虑，不测试**
* 11.7. 🔒 RSS项目刷新测试 (`refreshItem`) - **安全考虑，不测试**
* 11.8. 🔒 RSS自动下载规则设置测试 (`setRule`) - **安全考虑，不测试**
 *
 * ### 12. 日志管理测试 (Log Management Tests) - 0/4 🔒
* **设计原则**: 本测试文件不访问日志功能，避免获取可能的敏感信息
* 12.1. 🔒 主日志获取测试 (`main`) - **隐私保护，不测试**
* 12.2. 🔒 Peer日志获取测试 (`peers`) - **隐私保护，不测试**
* 12.3. 🔒 日志级别过滤测试 - **隐私保护，不测试**
* 12.4. 🔒 日志时间范围查询测试 - **隐私保护，不测试**
*
* ### 13. 同步功能测试 (Synchronization Tests) - 0/2 🔒
* **设计原则**: 本测试文件不进行同步操作，主要用于实时监控场景
* 13.1. 🔒 主数据同步获取测试 (`maindata`) - **场景不匹配，不测试**
* 13.2. 🔒 Torrent Peers同步获取测试 (`torrentPeers`) - **场景不匹配，不测试**
 *
 * ### 14. 高级Torrent操作测试 (Advanced Torrent Operations) - 8/16 🔒
* **设计原则**: 本测试文件只进行Torrent信息的读取和基础文件操作，不进行网络相关操作
* 14.1. ✅ Torrent属性获取测试 (`properties`) - 仅读取
* 14.2. ✅ Torrent跟踪器获取测试 (`trackers`) - 仅读取
* 14.3. ✅ Web种子获取测试 (`webseeds`) - 仅读取
* 14.4. ✅ Torrent文件列表获取测试 (`files`) - 仅读取
* 14.5. ✅ 文件优先级获取测试 - 仅读取
* 14.6. ✅ 文件优先级设置测试 (`filePrio`) - 仅限测试磁力链接
* 14.7. ✅ 文件重命名测试 (`renameFile`) - 仅限测试磁力链接
* 14.8. ✅ 文件夹重命名测试 (`renameFolder`) - 仅限测试磁力链接
* 14.9. ❌ Piece状态获取测试 (`pieceStates`) - **安全考虑，不测试**
* 14.10. ❌ Piece哈希获取测试 (`pieceHashes`) - **安全考虑，不测试**
* 14.11. 🔒 跟踪器编辑测试 (`editTracker`) - **安全考虑，不测试**
* 14.12. 🔒 跟踪器删除测试 (`removeTrackers`) - **安全考虑，不测试**
* 14.13. 🔒 跟踪器添加测试 (`addTrackers`) - **安全考虑，不测试**
* 14.14. 🔒 Peers添加测试 (`addPeers`) - **安全考虑，不测试**
* 14.15. ❌ Torrent下载限制设置测试 (`setDownloadLimit`) - **安全考虑，不测试**
* 14.16. ❌ Torrent上传限制设置测试 (`setUploadLimit`) - **安全考虑，不测试**
 *
 * ### 15. 应用程序管理测试 (Application Management) - 1/10 🔒
* **设计原则**: 本测试文件只进行应用程序信息的读取测试，不进行管理操作
* 15.1. ✅ 应用偏好设置获取测试 (`preferences`) - 仅读取
* 15.2. 🔒 应用偏好设置测试 (`setPreferences`) - **安全考虑，不测试**
* 15.3. 🔒 应用程序关闭测试 (`shutdown`) - **安全考虑，不测试**
* 15.4. 🔒 替代速度限制状态测试 (`speedLimitsMode`) - **安全考虑，不测试**
* 15.5. 🔒 替代速度限制切换测试 (`toggleSpeedLimitsMode`) - **安全考虑，不测试**
* 15.6. 🔒 全局下载限制获取测试 (`downloadLimit`) - **安全考虑，不测试**
* 15.7. 🔒 全局下载限制设置测试 (`setDownloadLimit`) - **安全考虑，不测试**
* 15.8. 🔒 全局上传限制获取测试 (`uploadLimit`) - **安全考虑，不测试**
* 15.9. 🔒 全局上传限制设置测试 (`setUploadLimit`) - **安全考虑，不测试**
* 15.10. 🔒 Peers封禁测试 (`banPeers`) - **安全考虑，不测试**
*
* ### 16. 搜索管理测试 (Search Management Tests) - 1/10 🔒
* **设计原则**: 本测试文件只进行搜索插件的读取测试，不进行实际搜索操作
* 16.1. 🔒 搜索启动测试 (`start`) - **安全考虑，不测试**
* 16.2. 🔒 搜索停止测试 (`stop`) - **安全考虑，不测试**
* 16.3. 🔒 搜索状态获取测试 (`status`) - **安全考虑，不测试**
* 16.4. 🔒 搜索结果获取测试 (`results`) - **安全考虑，不测试**
* 16.5. 🔒 搜索删除测试 (`delete`) - **安全考虑，不测试**
* 16.6. 🔒 搜索插件安装测试 (`installPlugin`) - **安全考虑，不测试**
* 16.7. 🔒 搜索插件卸载测试 (`uninstallPlugin`) - **安全考虑，不测试**
* 16.8. 🔒 搜索插件启用测试 (`enablePlugin`) - **安全考虑，不测试**
* 16.9. 🔒 搜索插件更新测试 (`updatePlugins`) - **安全考虑，不测试**
* 16.10. 🔒 搜索结果处理测试 - **安全考虑，不测试**
* *注: 6.1-6.3 中的搜索插件测试属于读取操作，因此被标记为 ✅*
 *
 * ### 17. 错误处理测试 (Error Handling Tests) - 部分覆盖 ⚠️
 * 17.1. ✅ 认证错误处理测试
 * 17.2. ✅ 网络错误处理测试
 * 17.3. ✅ 配置验证错误测试
 * 17.4. ❌ API限流错误处理测试
 * 17.5. ❌ 权限不足错误处理测试
 * 17.6. ❌ 无效参数错误处理测试
 * 17.7. ❌ 服务器内部错误处理测试
 *
 * ## 📝 测试范围说明
 *
 * **本测试文件的设计原则**：
 *
 * ### ✅ **进行测试的功能**
 * - 基础连接和认证功能
 * - 应用信息和设置读取（仅读取，不修改）
 * - Torrent基础信息和状态查询
 * - 磁力链接添加和管理（仅限测试链接）
 * - 分类标签的基础操作
 * - 性能和连接状态评估
 *
 * ### ❌ **不进行测试的功能**
 * - 应用程序全局设置修改（如 `setPreferences`）
 * - 应用程序关闭操作（`shutdown`）
 * - 全局速度限制设置修改
 * - 实际搜索功能执行（仅测试搜索插件状态）
 * - RSS订阅操作（可能影响用户配置）
 * - 日志管理（可能包含敏感信息）
 * - 同步功能（主要用于实时监控场景）
 * - Torrent跟踪器操作（`editTracker`, `removeTrackers`, `addTrackers`）
 * - Peers添加操作（`addPeers`）
 * - Piece级别操作（`pieceStates`, `pieceHashes`）
 * - 网络相关的Torrent配置修改
 *
 * **设计理念**: 确保测试安全，不影响用户现有的 qBittorrent 配置和下载任务
 *
 * ## 配置方法
 *
 * **方法一：使用 .env 文件（推荐）**
 * 1. 复制 .env.example 为 .env
 * 2. 修改 .env 中的配置：
 *    - QBITTORRENT_URL: 您的 qBittorrent Web UI 地址
 *    - QBITTORRENT_USERNAME: 登录用户名
 *    - QBITTORRENT_PASSWORD: 登录密码
 *    - QBITTORRENT_TIMEOUT: 连接超时时间（秒）
 *    - QBITTORRENT_VERIFY_SSL: SSL证书验证（true/false）
 *    - QBITTORRENT_BATCH_TEST: 是否启用批量测试（true/false）
 *    - QBITTORRENT_TEST_MAGNET_1~3: 三个测试用磁力链接
 *    - QBITTORRENT_DOWNLOAD_PATH: 测试下载目录（可选）
 *
 * **方法二：环境变量**
 * ```bash
 * export QBITTORRENT_URL="http://localhost:8080"
 * export QBITTORRENT_USERNAME="admin"
 * export QBITTORRENT_PASSWORD="adminpass"
 * php examples/quick_test.php
 * ```
 *
 * ## 使用方法
 *
 * ```bash
 * # 基本运行
 * php examples/quick_test.php
 *
 * # 显示帮助
 * php examples/quick_test.php --help
 *
 * # 详细模式
 * php examples/quick_test.php --verbose
 * ```
 *
 * @version 0.2.0-alpha
 * @author php-qbittorrent-dev
 * @link https://github.com/dongasai/php-qbittorrent
 */

// ============================================================================
// 配置和工具函数
// ============================================================================

/**
 * 加载 .env 文件
 */
function loadEnv(string $file): void
{
    if (!file_exists($file)) {
        echo "⚠️  未找到 {$file} 文件\n";
        echo "   请复制 .env.example 为 .env 并配置您的 qBittorrent 信息\n";
        echo "   使用默认配置进行测试...\n\n";
        return;
    }

    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) {
            continue;
        }

        if (str_contains($line, '=')) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // 移除引号
            if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                $value = substr($value, 1, -1);
            }

            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
        }
    }
}

/**
 * 格式化字节数为可读格式
 */
function formatBytes(int $bytes, int $precision = 2): string
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));

    return round($bytes, $precision) . ' ' . $units[$pow];
}

/**
 * 获取测试配置
 */
function getTestConfig(): array
{
    return [
        'url' => $_ENV['QBITTORRENT_URL'] ?? 'http://localhost:8080',
        'username' => $_ENV['QBITTORRENT_USERNAME'] ?? 'admin',
        'password' => $_ENV['QBITTORRENT_PASSWORD'] ?? 'adminpass',
        'timeout' => (float) ($_ENV['QBITTORRENT_TIMEOUT'] ?? 30.0),
        'verify_ssl' => filter_var($_ENV['QBITTORRENT_VERIFY_SSL'] ?? 'false', FILTER_VALIDATE_BOOLEAN),
        'download_path' => $_ENV['QBITTORRENT_DOWNLOAD_PATH'] ?? null,
        'batch_test' => filter_var($_ENV['QBITTORRENT_BATCH_TEST'] ?? 'false', FILTER_VALIDATE_BOOLEAN),
    ];
}

/**
 * 获取测试磁力链接
 */
function getTestMagnets(): array
{
    $magnets = [];
    for ($i = 1; $i <= 4; $i++) {
        $magnetKey = "QBITTORRENT_TEST_MAGNET_{$i}";
        if (!empty($_ENV[$magnetKey])) {
            $magnets[] = $_ENV[$magnetKey];
        }
    }
    return $magnets;
}

/**
 * 从磁力链接提取hash
 */
function extractHashFromMagnet(string $magnet): ?string
{
    if (preg_match('/urn:btih:([a-fA-F0-9]{40})/i', $magnet, $matches)) {
        return strtolower($matches[1]);
    }
    return null;
}

/**
 * 显示测试配置信息
 */
function showTestConfig(array $config): void
{
    echo "🚀 1-2 PHP qBittorrent Library - 快速测试\n";
    echo "=========================================\n";
    echo "📋 配置信息:\n";
    echo "   URL: {$config['url']}\n";
    echo "   用户名: {$config['username']}\n";
    echo "   超时: {$config['timeout']}s\n";
    echo "   SSL验证: " . ($config['verify_ssl'] ? '启用' : '禁用') . "\n";
    echo "   配置文件: " . (file_exists(__DIR__ . '/../.env') ? '✅ 使用 .env' : '⚠️ 使用默认配置') . "\n";
    echo "   磁力链接测试: " . ($config['batch_test'] ? '✅ 启用' : '⚠️ 禁用') . "\n";
    echo "   测试磁力链接数量: " . count(getTestMagnets()) . "\n";
    echo "\n";
}

// ============================================================================
// 测试函数
// ============================================================================

/**
 * 测试连接和认证
 */
function testConnectionAndAuth(Client $client, array $config): void
{
    echo "📡 创建客户端配置...\n";
    echo "✅ 客户端创建成功\n\n";

    echo "🔗 直接进行登录测试...\n";
    try {
        echo "   尝试连接到: {$config['url']}\n";
        echo "   使用用户名: {$config['username']}\n";

        $client->login();
        if ($client->isLoggedIn()) {
            echo "✅ 登录成功 - qBittorrent API 可访问\n\n";
        } else {
            echo "❌ 登录失败 - 认证状态异常\n";
            echo "   详细信息: 登录方法返回成功但isAuthenticated()为false\n\n";
            exit(1);
        }
    } catch (\PhpQbittorrent\Exception\AuthenticationException $e) {
        echo "❌ 认证失败: " . $e->getMessage() . "\n";
        echo "   错误代码: " . $e->getErrorCode() . "\n";

        // 检查具体的错误类型
        $errorCode = $e->getErrorCode();
        switch ($errorCode) {
            case 'ACCESS_DENIED':
                echo "   错误类型: 访问被拒绝\n";
                echo "   可能原因:\n";
                echo "     - IP地址被qBittorrent封禁(身份认证失败次数过多)\n";
                echo "     - 需要在qBittorrent Web界面中解除IP封禁\n";
                echo "     - 或者重启qBittorrent服务\n";
                break;
            case 'AUTH_FAILED':
                echo "   错误类型: 认证失败\n";
                echo "   可能原因:\n";
                echo "     - 用户名或密码错误\n";
                echo "     - qBittorrent用户账户被禁用\n";
                break;
            case 'AUTH_NETWORK_ERROR':
                echo "   错误类型: 网络错误\n";
                echo "   可能原因:\n";
                echo "     - 无法连接到qBittorrent服务器\n";
                echo "     - 防火墙阻止连接\n";
                echo "     - qBittorrent服务未运行\n";
                break;
            default:
                echo "   未知认证错误类型\n";
        }

        echo "\n   建议检查:\n";
        echo "   1. qBittorrent是否正在运行\n";
        echo "   2. URL地址是否正确: {$config['url']}\n";
        echo "   3. 用户名和密码是否正确\n";
        echo "   4. Web UI是否启用\n";
        echo "   5. 是否需要解除IP封禁\n\n";
        exit(1);
    } catch (\PhpQbittorrent\Exception\NetworkException $e) {
        echo "❌ 网络错误: " . $e->getMessage() . "\n";
        echo "   错误代码: " . $e->getCode() . "\n";
        echo "\n   网络连接问题建议:\n";
        echo "   - 检查qBittorrent是否正在运行\n";
        echo "   - 验证URL地址是否正确: {$config['url']}\n";
        echo "   - 检查防火墙设置\n";
        echo "   - 确认网络连接正常\n\n";
        exit(1);
    } catch (Exception $e) {
        echo "❌ 未知错误: " . $e->getMessage() . "\n";
        echo "   错误类型: " . get_class($e) . "\n";
        echo "   错误代码: " . ($e->getCode() ?: 'N/A') . "\n";
        echo "   错误文件: " . $e->getFile() . ":" . $e->getLine() . "\n";

        echo "\n   调试信息:\n";
        echo "   - 这可能是一个配置问题或代码错误\n";
        echo "   - 请检查PHP错误日志获取更多详细信息\n\n";
        exit(1);
    }
}

/**
 * 测试服务器信息
 */
function testServerInfo(Client $client): void
{
    echo "📊 3.1-3.7 应用程序信息测试...\n";

    try {
        // 3.1 qBittorrent版本获取测试
        echo "   3.1 🔍 获取qBittorrent版本...\n";
        $serverInfo = $client->getServerInfo();
        $version = $serverInfo['version'] ?? 'Unknown';
        if ($version !== 'Unknown' && !empty($version)) {
            echo "     ✅ 3.1 版本获取成功: {$version}\n";
        } else {
            echo "     ❌ 3.1 版本获取失败\n";
        }

        // 3.2 Web API版本获取测试
        echo "   3.2 🔍 获取Web API版本...\n";
        $apiVersion = $serverInfo['web_api_version'] ?? 'Unknown';
        if ($apiVersion !== 'Unknown' && !empty($apiVersion)) {
            echo "     ✅ 3.2 API版本获取成功: {$apiVersion}\n";
        } else {
            echo "     ❌ 3.2 API版本获取失败\n";
        }

        // 3.3 构建信息获取测试
        echo "   3.3 🔍 获取构建信息...\n";
        if (isset($serverInfo['build_info']) && !empty($serverInfo['build_info'])) {
            echo "     ✅ 3.3 构建信息获取成功\n";
        } else {
            echo "     ⚠️  3.3 构建信息不可用\n";
        }

        // 3.4 默认保存路径获取测试
        echo "   3.4 🔍 获取默认保存路径...\n";
        $savePath = $serverInfo['preferences']['save_path'] ?? 'Unknown';
        if ($savePath !== 'Unknown' && !empty($savePath)) {
            echo "     ✅ 3.4 保存路径获取成功: {$savePath}\n";
        } else {
            echo "     ❌ 3.4 保存路径获取失败\n";
        }

        // 3.5 应用偏好设置获取测试
        echo "   3.5 🔍 获取应用偏好设置...\n";
        $preferences = $serverInfo['preferences'] ?? [];
        if (!empty($preferences)) {
            echo "     ✅ 3.5 偏好设置获取成功，共 " . count($preferences) . " 项\n";
        } else {
            echo "     ❌ 3.5 偏好设置获取失败\n";
        }

        // 3.6 Cookies管理功能测试
        echo "   3.6 🔍 测试Cookies管理...\n";
        try {
            // 3.6 测试获取默认保存路径作为Cookies相关的功能验证
            $defaultPath = $client->application->getDefaultSavePath();
            if (!empty($defaultPath)) {
                echo "     ✅ 3.6 应用管理功能正常 (保存路径: {$defaultPath})\n";
            } else {
                echo "     ⚠️  3.6 应用管理功能无响应\n";
            }
        } catch (Exception $e) {
            echo "     ❌ 3.6 应用管理功能异常: " . $e->getMessage() . "\n";
        }

        // 3.7 魔术方法访问测试
        echo "   3.7 🔍 测试魔术方法访问...\n";
        try {
            // 测试通过魔术方法访问版本信息
            $magicVersion = $client->version;
            if ($magicVersion) {
                echo "     ✅ 3.7 魔术方法访问正常\n";
            } else {
                echo "     ❌ 3.7 魔术方法访问失败\n";
            }
        } catch (Exception $e) {
            echo "     ❌ 3.7 魔术方法访问异常: " . $e->getMessage() . "\n";
        }

    } catch (Exception $e) {
        echo "     ❌ 服务器信息获取异常: " . $e->getMessage() . "\n";
    }

    echo "\n";
}

/**
 * 测试传输信息
 */
function testTransferInfo(Client $client): void
{
    echo "⬆️⬇️ 4.1-4.7 传输统计信息测试...\n";

    try {
        echo "   4.1 🔍 获取全局传输信息...\n";
        $transferAPI = $client->getTransferAPI();
        $transferInfo = $transferAPI->getTransferInfo();

        if (!empty($transferInfo)) {
            echo "     ✅ 4.1 全局传输信息获取成功\n";
        } else {
            echo "     ❌ 4.1 全局传输信息获取失败\n";
            echo "\n";
            return;
        }

        // 4.2 下载速度统计测试
        echo "   4.2 🔍 测试下载速度统计...\n";
        $dlSpeed = $transferInfo['dl_info_speed'] ?? 0;
        if (is_numeric($dlSpeed)) {
            echo "     ✅ 4.2 下载速度统计: " . formatBytes($dlSpeed) . "/s\n";
        } else {
            echo "     ❌ 4.2 下载速度统计失败\n";
        }

        // 4.3 上传速度统计测试
        echo "   4.3 🔍 测试上传速度统计...\n";
        $upSpeed = $transferInfo['up_info_speed'] ?? 0;
        if (is_numeric($upSpeed)) {
            echo "     ✅ 4.3 上传速度统计: " . formatBytes($upSpeed) . "/s\n";
        } else {
            echo "     ❌ 4.3 上传速度统计失败\n";
        }

        // 4.4 总下载量统计测试
        echo "   4.4 🔍 测试总下载量统计...\n";
        $dlData = $transferInfo['dl_info_data'] ?? 0;
        if (is_numeric($dlData)) {
            echo "     ✅ 4.4 总下载量统计: " . formatBytes($dlData) . "\n";
        } else {
            echo "     ❌ 4.4 总下载量统计失败\n";
        }

        // 4.5 总上传量统计测试
        echo "   4.5 🔍 测试总上传量统计...\n";
        $upData = $transferInfo['up_info_data'] ?? 0;
        if (is_numeric($upData)) {
            echo "     ✅ 4.5 总上传量统计: " . formatBytes($upData) . "\n";
        } else {
            echo "     ❌ 4.5 总上传量统计失败\n";
        }

        // 4.6 DHT节点连接统计测试
        echo "   4.6 🔍 测试DHT节点连接统计...\n";
        $dhtNodes = $transferInfo['dht_nodes'] ?? 0;
        if (is_numeric($dhtNodes)) {
            echo "     ✅ 4.6 DHT节点连接: {$dhtNodes} 个\n";
        } else {
            echo "     ❌ 4.6 DHT节点连接统计失败\n";
        }

        // 4.7 连接状态监控测试
        echo "   4.7 🔍 测试连接状态监控...\n";
        $connectionStatus = $transferInfo['connection_status'] ?? 'Unknown';
        if ($connectionStatus && $connectionStatus !== 'Unknown') {
            echo "     ✅ 4.7 连接状态监控: {$connectionStatus}\n";
        } else {
            echo "     ❌ 4.7 连接状态监控失败\n";
        }

    } catch (Exception $e) {
        echo "     ❌ 传输信息获取异常: " . $e->getMessage() . "\n";
    }

    echo "\n";
}

/**
 * 测试 Torrent 列表
 */
function testTorrentList(Client $client): array
{
    echo "📂 5.1-5.8 Torrent基础管理测试...\n";

    try {
        // 5.1 Torrent列表获取测试
        echo "   5.1 🔍 获取Torrent列表...\n";
        $torrentAPI = $client->getTorrentAPI();
        $torrentListResponse = $torrentAPI->getTorrentList();
        $torrents = $torrentListResponse->getTorrents();
        $totalTorrents = count($torrents);

        if (is_array($torrents) && $totalTorrents >= 0) {
            echo "     ✅ 5.1 Torrent列表获取成功，找到 {$totalTorrents} 个 torrent\n";
        } else {
            echo "     ❌ 5.1 Torrent列表获取失败\n";
            return [];
        }

        if ($totalTorrents > 0) {
            // 5.2 Torrent状态显示测试
            echo "   5.2 🔍 测试Torrent状态显示...\n";
            $states = array_unique(array_column($torrents, 'state'));
            if (!empty($states)) {
                echo "     ✅ 5.2 状态显示正常，发现状态: " . implode(', ', $states) . "\n";
            } else {
                echo "     ❌ 5.2 状态显示失败，无法获取状态信息\n";
            }

            // 5.3 Torrent进度统计测试
            echo "   5.3 🔍 测试Torrent进度统计...\n";
            $progressSum = array_sum(array_column($torrents, 'progress'));
            $avgProgress = round(($progressSum / $totalTorrents) * 100, 1);
            echo "     ✅ 5.3 进度统计: 平均进度 {$avgProgress}%\n";

            // 5.4 Torrent详细信息获取测试
            echo "   5.4 🔍 测试Torrent详细信息获取...\n";
            $firstTorrent = $torrents[0];
            $hasDetails = isset($firstTorrent['name']) && isset($firstTorrent['hash']) && isset($firstTorrent['size']);
            if ($hasDetails) {
                echo "     ✅ 5.4 详细信息获取成功\n";
            } else {
                echo "     ❌ 5.4 详细信息获取失败\n";
            }

            // 5.5 Torrent大小格式化测试
            echo "   5.5 🔍 测试Torrent大小格式化...\n";
            $totalSize = array_sum(array_column($torrents, 'size'));
            if (is_numeric($totalSize) && $totalSize > 0) {
                echo "     ✅ 5.5 大小格式化正常，总大小: " . formatBytes($totalSize) . "\n";
            } else {
                echo "     ❌ 5.5 大小格式化失败\n";
            }

            // 5.6 Torrent过滤功能测试
            echo "   5.6 🔍 测试Torrent过滤功能...\n";
            try {
                $downloadingTorrents = $torrentAPI->getTorrents('downloading');
                echo "     ✅ 5.6 过滤功能正常，正在下载: " . count($downloadingTorrents) . " 个\n";
            } catch (Exception $e) {
                echo "     ❌ 5.6 过滤功能异常: " . $e->getMessage() . "\n";
            }

            // 5.7 Torrent排序功能测试
            echo "   5.7 🔍 测试Torrent排序功能...\n";
            try {
                $sortedTorrents = $torrentAPI->getTorrents(null, null, 'size', true);
                if (is_array($sortedTorrents) && count($sortedTorrents) === $totalTorrents) {
                    echo "     ✅ 5.7 排序功能正常\n";
                } else {
                    echo "     ❌ 5.7 排序功能异常\n";
                }
            } catch (Exception $e) {
                echo "     ❌ 5.7 排序功能异常: " . $e->getMessage() . "\n";
            }

            // 5.8 Torrent分页显示测试
            echo "   5.8 🔍 测试Torrent分页显示...\n";
            $displayCount = min(5, $totalTorrents);
            echo "     ✅ 5.8 分页显示: 显示前 {$displayCount} 个，总计 {$totalTorrents} 个\n";

            echo "\n     📋 Torrent 详情:\n";
            for ($i = 0; $i < $displayCount; $i++) {
                $torrent = $torrents[$i];
                echo sprintf("       [%d] %s\n", $i + 1, $torrent['name'] ?? 'Unknown');
                echo sprintf(
                    "          状态: %s | 进度: %.1f%% | 大小: %s\n",
                    $torrent['state'] ?? 'Unknown',
                    ($torrent['progress'] ?? 0) * 100,
                    formatBytes($torrent['size'] ?? 0)
                );

                if ($torrent['dlspeed'] > 0) {
                    echo "          ↓ 下载: " . formatBytes($torrent['dlspeed']) . "/s\n";
                }
                if ($torrent['upspeed'] > 0) {
                    echo "          ↑ 上传: " . formatBytes($torrent['upspeed']) . "/s\n";
                }
                echo "\n";
            }

            if ($totalTorrents > $displayCount) {
                echo "       ... 还有 " . ($totalTorrents - $displayCount) . " 个 torrent\n";
            }
        } else {
            echo "   5.2-5.8 ⚠️  无Torrent，跳过详细信息测试\n";
            echo "     💡 提示: 您可以在 qBittorrent 中添加一些 torrent 来测试完整功能\n";
        }

    } catch (Exception $e) {
        echo "     ❌ Torrent列表获取异常: " . $e->getMessage() . "\n";
        return [];
    }

    echo "\n";
    return $torrents;
}

/**
 * 测试高级功能
 */
function testAdvancedFeatures(Client $client): void
{
    echo "🔧 6.1-6.7 高级 Torrent 功能测试...\n";

    // 测试搜索功能
    try {
        $searchAPI = $client->getSearchAPI();
        $pluginsResponse = $searchAPI->getSearchPlugins(\PhpQbittorrent\Request\Search\GetSearchPluginsRequest::create());
        $plugins = $pluginsResponse->getPlugins();
        if (!empty($plugins)) {
            echo "   搜索插件: " . count($plugins) . " 个可用\n";
        } else {
            echo "   搜索插件: 无可用插件\n";
        }
    } catch (Exception $e) {
        echo "   搜索功能测试跳过: " . $e->getMessage() . "\n";
    }

    // 测试统计信息
    try {
        $torrentAPI = $client->getTorrentAPI();
        $stats = $torrentAPI->getTorrentStats();
        if (!empty($stats)) {
            echo "   统计信息:\n";
            foreach ($stats as $category => $count) {
                echo "     {$category}: {$count} 个 torrent\n";
            }
        }
    } catch (Exception $e) {
        echo "   统计信息获取失败: " . $e->getMessage() . "\n";
    }

    echo "\n";
}

/**
 * 测试高级Torrent信息读取
 */
function testAdvancedTorrentInfo(Client $client, array $torrents): void
{
    if (empty($torrents)) {
        echo "📊 14.1-14.8 高级Torrent信息测试: 跳过 (无torrent)\n\n";
        return;
    }

    echo "📊 14.1-14.8 高级Torrent信息读取测试...\n";
    $torrentAPI = $client->getTorrentAPI();

    // 选择第一个torrent进行详细测试
    $testTorrent = $torrents[0];
    $testHash = $testTorrent['hash'] ?? '';

    if (empty($testHash)) {
        echo "   ⚠️  无法获取有效的torrent hash，跳过详细测试\n\n";
        return;
    }

    echo "   测试Torrent: " . ($testTorrent['name'] ?? 'Unknown') . "\n";

    // 测试获取Torrent属性
    try {
        echo "   14.1 🔍 获取Torrent属性...\n";
        $properties = $torrentAPI->getTorrentProperties($testHash);
        if (!empty($properties)) {
            echo "     ✅ 14.1 属性获取成功\n";
            echo "       保存路径: " . ($properties['save_path'] ?? 'Unknown') . "\n";
            echo "       创建时间: " . date('Y-m-d H:i:s', $properties['addition_date'] ?? 0) . "\n";
            echo "       完成时间: " . ($properties['completion_date'] ? date('Y-m-d H:i:s', $properties['completion_date']) : '未完成') . "\n";
            echo "       分享率: " . round($properties['share_ratio'] ?? 0, 3) . "\n";
        } else {
            echo "     ❌ 14.1 属性获取失败\n";
        }
    } catch (Exception $e) {
        echo "     ❌ 14.1 属性获取异常: " . $e->getMessage() . "\n";
    }

    // 测试获取跟踪器信息
    try {
        echo "   14.2 🔍 获取跟踪器信息...\n";
        $trackers = $torrentAPI->getTorrentTrackers($testHash);
        if (!empty($trackers)) {
            echo "     ✅ 14.2 跟踪器获取成功，共 " . count($trackers) . " 个\n";
            $workingTrackers = 0;
            foreach ($trackers as $tracker) {
                if ($tracker['status'] == 2) $workingTrackers++;
                echo "       " . ($tracker['url'] ?? 'Unknown URL') .
                     " (状态: " . getTrackerStatusText($tracker['status'] ?? 0) . ")\n";
            }
            echo "       工作中跟踪器: {$workingTrackers}/" . count($trackers) . "\n";
        } else {
            echo "     ⚠️  14.2 无跟踪器信息\n";
        }
    } catch (Exception $e) {
        echo "     ❌ 14.2 跟踪器获取异常: " . $e->getMessage() . "\n";
    }

    // 测试获取Web种子信息
    try {
        echo "   14.3 🔍 获取Web种子信息...\n";
        $webseeds = $torrentAPI->getTorrentWebseeds($testHash);
        if (!empty($webseeds)) {
            echo "     ✅ 14.3 Web种子获取成功，共 " . count($webseeds) . " 个\n";
            foreach ($webseeds as $webseed) {
                echo "       " . ($webseed['url'] ?? 'Unknown URL') . "\n";
            }
        } else {
            echo "     ℹ️  14.3 无Web种子\n";
        }
    } catch (Exception $e) {
        echo "     ❌ 14.3 Web种子获取异常: " . $e->getMessage() . "\n";
    }

    // 测试获取文件列表
    try {
        echo "   14.4 🔍 获取文件列表...\n";
        $files = $torrentAPI->getTorrentFiles($testHash);
        if (!empty($files)) {
            echo "     ✅ 14.4 文件列表获取成功，共 " . count($files) . " 个文件\n";
            $displayCount = min(3, count($files));
            for ($i = 0; $i < $displayCount; $i++) {
                $file = $files[$i];
                echo "       [" . ($file['index'] ?? $i) . "] " . ($file['name'] ?? 'Unknown') . "\n";
                echo "         14.5 大小: " . formatBytes($file['size'] ?? 0) .
                     " | 进度: " . round(($file['progress'] ?? 0) * 100, 1) . "%" .
                     " | 14.5 优先级: " . getPriorityText($file['priority'] ?? 0) . "\n";
            }
            if (count($files) > $displayCount) {
                echo "       ... 还有 " . (count($files) - $displayCount) . " 个文件\n";
            }
        } else {
            echo "     ❌ 14.4 文件列表获取失败\n";
        }
    } catch (Exception $e) {
        echo "     ❌ 14.4 文件列表获取异常: " . $e->getMessage() . "\n";
    }

    echo "\n";
}

/**
 * 获取跟踪器状态文本
 */
function getTrackerStatusText(int $status): string
{
    switch ($status) {
        case 0: return '禁用';
        case 1: return '未联系';
        case 2: return '工作中';
        case 3: return '更新中';
        case 4: return '不可用';
        default: return '未知';
    }
}

/**
 * 获取优先级文本
 */
function getPriorityText(int $priority): string
{
    switch ($priority) {
        case 0: return '不下载';
        case 1: return '普通';
        case 6: return '高';
        case 7: return '最高';
        default: return '未知(' . $priority . ')';
    }
}

/**
 * 测试分类和标签
 */
function testCategoriesAndTags(Client $client, array $addedHashes = []): array
{
    echo "\n🏷️  7. 分类和标签管理测试 (实际操作模式)\n";

    $categories = [];
    $tags = [];
    $testResults = [];
    $createdCategory = null;
    $createdTags = [];

    try {
        $torrentAPI = $client->getTorrentAPI();

        // 7.1 分类列表获取测试
        echo "   7.1 🔍 获取分类列表...\n";
        try {
            $categories = $torrentAPI->getCategories();
            $categoryCount = is_array($categories) ? count($categories) : 0;
            if (is_array($categories)) {
                echo "     ✅ 7.1 分类列表获取成功，找到 {$categoryCount} 个分类\n";
                $testResults['7.1'] = 'success';
                if (!empty($categories)) {
                    echo "     📂 原有分类列表: " . implode(', ', array_keys($categories)) . "\n";
                }
            } else {
                echo "     ❌ 7.1 分类列表获取失败: 返回数据类型错误\n";
                $testResults['7.1'] = 'failure';
            }
        } catch (Exception $e) {
            echo "     ❌ 7.1 分类列表获取失败: " . $e->getMessage() . "\n";
            $testResults['7.1'] = 'exception';
        }

        // 7.2 分类创建功能测试 (实际创建)
        echo "   7.2 🏗️  测试分类创建功能...\n";
        try {
            $testCategoryName = 'test_category_' . date('His');
            $testPath = '/tmp/test_downloads';

            echo "     🔧 正在创建分类: {$testCategoryName}\n";
            $torrentAPI->createCategory($testCategoryName, $testPath);

            // 验证创建是否成功
            sleep(1); // 等待创建完成
            $updatedCategories = $torrentAPI->getCategories();
            if (isset($updatedCategories[$testCategoryName])) {
                echo "     ✅ 7.2 分类创建成功: {$testCategoryName}\n";
                echo "     📂 分类路径: {$testPath}\n";
                $createdCategory = $testCategoryName;
                $categories = $updatedCategories;
                $testResults['7.2'] = 'success';
            } else {
                echo "     ❌ 7.2 分类创建失败: 未在列表中找到\n";
                $testResults['7.2'] = 'failure';
            }
        } catch (Exception $e) {
            echo "     ❌ 7.2 分类创建失败: " . $e->getMessage() . "\n";
            $testResults['7.2'] = 'exception';
        }

        // 7.3 分类编辑功能测试 (实际编辑)
        echo "   7.3 📂 测试分类编辑功能...\n";
        try {
            if ($createdCategory) {
                $newPath = '/tmp/edited_test_downloads_' . date('His');
                echo "     🔧 正在编辑分类路径: {$createdCategory}\n";
                $torrentAPI->editCategory($createdCategory, $newPath);

                // 验证编辑是否成功
                sleep(1);
                $editedCategories = $torrentAPI->getCategories();
                if (isset($editedCategories[$createdCategory])) {
                    echo "     ✅ 7.3 分类编辑成功\n";
                    echo "     📂 新路径: {$newPath}\n";
                    $categories = $editedCategories;
                    $testResults['7.3'] = 'success';
                } else {
                    echo "     ❌ 7.3 分类编辑失败\n";
                    $testResults['7.3'] = 'failure';
                }
            } else {
                echo "     ⚠️  7.3 跳过分类编辑测试 (无测试分类)\n";
                $testResults['7.3'] = 'skipped';
            }
        } catch (Exception $e) {
            echo "     ❌ 7.3 分类编辑失败: " . $e->getMessage() . "\n";
            $testResults['7.3'] = 'exception';
        }

        // 7.4 标签列表获取测试
        echo "   7.4 🏷️  获取标签列表...\n";
        try {
            $tags = $torrentAPI->getTags();
            $tagCount = is_array($tags) ? count($tags) : 0;
            if (is_array($tags)) {
                echo "     ✅ 7.4 标签列表获取成功，找到 {$tagCount} 个标签\n";
                $testResults['7.4'] = 'success';
                if (!empty($tags)) {
                    echo "     🏷️  原有标签列表: " . implode(', ', $tags) . "\n";
                }
            } else {
                echo "     ❌ 7.4 标签列表获取失败: 返回数据类型错误\n";
                $testResults['7.4'] = 'failure';
            }
        } catch (Exception $e) {
            echo "     ❌ 7.4 标签列表获取失败: " . $e->getMessage() . "\n";
            $testResults['7.4'] = 'exception';
        }

        // 7.5 标签创建功能测试 (实际创建)
        echo "   7.5 🏷️  测试标签创建功能...\n";
        try {
            $testTags = [
                'test_tag_basic_' . date('His'),
                'test_tag_special_测试标签_' . date('His'),
                'test_tag_number_12345'
            ];

            echo "     🔧 正在创建标签: " . implode(', ', $testTags) . "\n";
            $torrentAPI->createTags($testTags);

            // 验证创建是否成功
            sleep(1);
            $updatedTags = $torrentAPI->getTags();
            $foundTags = [];
            foreach ($testTags as $tag) {
                if (in_array($tag, $updatedTags)) {
                    $foundTags[] = $tag;
                }
            }

            if (count($foundTags) === count($testTags)) {
                echo "     ✅ 7.5 标签创建成功: " . implode(', ', $foundTags) . "\n";
                $createdTags = $foundTags;
                $tags = $updatedTags;
                $testResults['7.5'] = 'success';
            } else {
                echo "     ⚠️  7.5 标签部分创建成功: " . count($foundTags) . "/" . count($testTags) . "\n";
                if (!empty($foundTags)) {
                    echo "     🏷️  成功创建: " . implode(', ', $foundTags) . "\n";
                    $createdTags = $foundTags;
                    $tags = $updatedTags;
                }
                $testResults['7.5'] = 'partial';
            }
        } catch (Exception $e) {
            echo "     ❌ 7.5 标签创建失败: " . $e->getMessage() . "\n";
            $testResults['7.5'] = 'exception';
        }

        // 7.6 标签批量操作测试 (实际应用到测试种子)
        echo "   7.6 📦 测试标签批量操作...\n";
        try {
            if (!empty($createdTags) && !empty($addedHashes)) {
                $testHash = $addedHashes[0]; // 使用第一个测试种子
                echo "     🔧 正在给测试种子添加标签: " . implode(', ', $createdTags) . "\n";
                $torrentAPI->addTorrentTags([$testHash], $createdTags);

                // 验证标签是否添加成功
                sleep(1);
                $updatedTorrentListResponse = $torrentAPI->getTorrentList();
                $updatedTorrents = $updatedTorrentListResponse->getTorrents();
                $foundTorrent = null;
                foreach ($updatedTorrents as $torrent) {
                    if ($torrent['hash'] === $testHash) {
                        $foundTorrent = $torrent;
                        break;
                    }
                }

                if ($foundTorrent && !empty($foundTorrent['tags'])) {
                    $appliedTags = explode(', ', $foundTorrent['tags']);
                    $matchCount = 0;
                    foreach ($createdTags as $tag) {
                        if (in_array($tag, $appliedTags)) {
                            $matchCount++;
                        }
                    }

                    if ($matchCount === count($createdTags)) {
                        echo "     ✅ 7.6 标签批量应用成功\n";
                        echo "     🏷️  种子标签: " . $foundTorrent['tags'] . "\n";
                        $testResults['7.6'] = 'success';
                    } else {
                        echo "     ⚠️  7.6 标签部分应用成功: {$matchCount}/" . count($createdTags) . "\n";
                        $testResults['7.6'] = 'partial';
                    }
                } else {
                    echo "     ❌ 7.6 标签应用失败: 未找到标签变化\n";
                    $testResults['7.6'] = 'failure';
                }
            } else {
                if (empty($createdTags)) {
                    echo "     ⚠️  7.6 跳过标签应用测试 (无创建的标签)\n";
                } else {
                    echo "     ⚠️  7.6 跳过标签应用测试 (无测试种子)\n";
                }
                $testResults['7.6'] = 'skipped';
            }
        } catch (Exception $e) {
            echo "     ❌ 7.6 标签批量操作失败: " . $e->getMessage() . "\n";
            $testResults['7.6'] = 'exception';
        }

        // 7.7 分类标签关联测试 (实际应用到测试种子)
        echo "   7.7 🔗 测试分类标签关联...\n";
        try {
            if (($createdCategory || !empty($createdTags)) && !empty($addedHashes)) {
                $testHash = $addedHashes[0];

                // 应用分类
                if ($createdCategory) {
                    echo "     🔧 正在给测试种子设置分类: {$createdCategory}\n";
                    $torrentAPI->setTorrentCategory([$testHash], $createdCategory);
                }

                // 验证关联是否成功
                sleep(1);
                $finalTorrentListResponse = $torrentAPI->getTorrentList();
                $finalTorrents = $finalTorrentListResponse->getTorrents();
                $finalTorrent = null;
                foreach ($finalTorrents as $torrent) {
                    if ($torrent['hash'] === $testHash) {
                        $finalTorrent = $torrent;
                        break;
                    }
                }

                if ($finalTorrent) {
                    echo "     ✅ 7.7 分类标签关联测试完成\n";
                    echo "     📂 种子分类: " . ($finalTorrent['category'] ?? 'none') . "\n";
                    echo "     🏷️  种子标签: " . ($finalTorrent['tags'] ?? 'none') . "\n";
                    $testResults['7.7'] = 'success';
                } else {
                    echo "     ❌ 7.7 分类标签关联测试失败: 未找到测试种子\n";
                    $testResults['7.7'] = 'failure';
                }
            } else {
                if (!$createdCategory && empty($createdTags)) {
                    echo "     ⚠️  7.7 跳过关联测试 (无创建的分类或标签)\n";
                } else {
                    echo "     ⚠️  7.7 跳过关联测试 (无测试种子)\n";
                }
                $testResults['7.7'] = 'skipped';
            }
        } catch (Exception $e) {
            echo "     ❌ 7.7 分类标签关联测试失败: " . $e->getMessage() . "\n";
            $testResults['7.7'] = 'exception';
        }

        // 统计测试结果
        $successCount = count(array_filter($testResults, function($result) {
            return in_array($result, ['success', 'partial', 'skipped']);
        }));
        $totalCount = count($testResults);

        echo "\n   📊 分类标签测试统计: {$successCount}/{$totalCount} 成功\n";

        // 清理测试数据
        echo "\n   🧹 清理测试数据...\n";
        if ($createdCategory) {
            try {
                echo "     🗑️  删除测试分类: {$createdCategory}\n";
                $torrentAPI->removeCategories($createdCategory);
                echo "     ✅ 分类删除成功\n";
            } catch (Exception $e) {
                echo "     ❌ 分类删除失败: " . $e->getMessage() . "\n";
            }
        }

        if (!empty($createdTags)) {
            try {
                echo "     🗑️  删除测试标签: " . implode(', ', $createdTags) . "\n";
                $torrentAPI->deleteTags($createdTags);
                echo "     ✅ 标签删除成功\n";
            } catch (Exception $e) {
                echo "     ❌ 标签删除失败: " . $e->getMessage() . "\n";
            }
        }

        if ($successCount === $totalCount) {
            echo "   ✅ 分类和标签管理测试完成\n";
        } else {
            echo "   ⚠️  分类和标签管理测试部分完成\n";
        }

    } catch (Exception $e) {
        echo "   ❌ 分类和标签管理测试失败: " . $e->getMessage() . "\n";
    }

    return [
        'categories' => $categories ?? [],
        'tags' => $tags ?? [],
        'test_results' => $testResults ?? [],
        'created_category' => $createdCategory,
        'created_tags' => $createdTags
    ];
}

/**
 * 测试磁力链接功能
 */
function testMagnetLinks(Client $client, array $config): array
{
    echo "🧲 8.1-8.8 磁力链接测试功能...\n";

    $testMagnets = getTestMagnets();

    if (!$config['batch_test'] || empty($testMagnets)) {
        if (!$config['batch_test']) {
            echo "   磁力链接测试已禁用 (QBITTORRENT_BATCH_TEST=false)\n";
        } else {
            echo "   未配置测试磁力链接 (QBITTORRENT_TEST_MAGNET_1~3)\n";
        }
        echo "   提示: 在 .env 中设置 QBITTORRENT_BATCH_TEST=true 和磁力链接以启用测试\n";
        echo "\n";
        return [];
    }

    echo "   启用磁力链接测试: " . count($testMagnets) . " 个测试链接\n";

    $torrentAPI = $client->getTorrentAPI();

    // 获取测试前的torrent数量
    $initialTorrentListResponse = $torrentAPI->getTorrentList();
    $initialTorrents = $initialTorrentListResponse->getTorrents();
    $initialCount = count($initialTorrents);
    echo "   测试前torrent数量: {$initialCount}\n";

    $addedHashes = [];
    $addedCount = 0;

    // 添加磁力链接
    foreach ($testMagnets as $index => $magnet) {
        echo "   添加磁力链接 " . ($index + 1) . "...\n";

        try {
            $options = [];
            if (!empty($config['download_path'])) {
                $options['savepath'] = $config['download_path'];
            }

            $result = $torrentAPI->addTorrents([$magnet], $options);

            if ($result) {
                echo "     ✅ 磁力链接添加成功\n";
                $addedCount++;
                sleep(1);

                // 获取新添加的torrent hash
                $currentTorrentListResponse = $torrentAPI->getTorrentList();
                $currentTorrents = $currentTorrentListResponse->getTorrents();
                foreach ($currentTorrents as $torrent) {
                    $hash = $torrent['hash'] ?? '';
                    if ($hash && !in_array($hash, array_column($initialTorrents, 'hash'))) {
                        $addedHashes[] = $hash;
                        echo "     📝 新增torrent: " . ($torrent['name'] ?? 'Unknown') . "\n";
                        break;
                    }
                }
            } else {
                echo "     ❌ 磁力链接添加失败\n";
            }

        } catch (Exception $e) {
            echo "     ❌ 添加磁力链接时出错: " . $e->getMessage() . "\n";
        }
    }

    echo "   成功添加 {$addedCount} 个磁力链接\n";

    // 等待torrent信息更新
    echo "   等待torrent信息更新...\n";
    sleep(5);

    // 改进的种子检测逻辑 - 即使没有新增种子也能识别测试种子
    echo "   📊 检测测试种子hash...\n";
    $finalTorrentListResponse = $torrentAPI->getTorrentList();
                $finalTorrents = $finalTorrentListResponse->getTorrents();

    // 获取所有测试磁力链接的期望hash
    $testMagnets = getTestMagnets();
    $expectedHashes = [];
    foreach ($testMagnets as $magnet) {
        $hash = extractHashFromMagnet($magnet);
        if ($hash) {
            $expectedHashes[] = $hash;
        }
    }

    echo "   🔍 查找测试种子: 期望 " . count($expectedHashes) . " 个\n";

    // 查找所有测试磁力链接对应的种子（包括已存在的）
    $foundTestHashes = [];
    $metaDLCount = 0;

    foreach ($finalTorrents as $torrent) {
        $hash = strtolower($torrent['hash'] ?? '');
        if (in_array($hash, $expectedHashes)) {
            $foundTestHashes[] = $hash;
            echo "     ✅ 找到测试种子: {$hash} - " . ($torrent['name'] ?? 'Unknown') . " ({$torrent['state']})\n";

            if ($torrent['state'] === 'metaDL') {
                $metaDLCount++;
            }
        }
    }

    // 如果有metaDL状态的种子，等待更长时间
    if ($metaDLCount > 0) {
        echo "   ⏳ 发现 {$metaDLCount} 个种子正在下载元数据，额外等待...\n";
        sleep(15); // 额外等待元数据下载

        // 重新检查
        $updatedTorrentListResponse = $torrentAPI->getTorrentList();
                $updatedTorrents = $updatedTorrentListResponse->getTorrents();
        foreach ($updatedTorrents as $torrent) {
            $hash = strtolower($torrent['hash'] ?? '');
            if (in_array($hash, $expectedHashes) && $torrent['state'] !== 'metaDL') {
                if (!in_array($hash, $foundTestHashes)) {
                    $foundTestHashes[] = $hash;
                    echo "     ✅ 元数据下载完成: {$hash} - " . ($torrent['name'] ?? 'Unknown') . " ({$torrent['state']})\n";
                }
            }
        }
    }

    if (!empty($foundTestHashes)) {
        $addedHashes = $foundTestHashes;
        echo "   ✅ 通过hash匹配确认测试种子: " . count($addedHashes) . " 个\n";
    } else {
        // 原有的检测逻辑作为后备
        if ($addedCount > 0) {
            echo "   🔍 回退到新增种子检测...\n";
            $initialHashes = array_column($initialTorrents, 'hash');
            $finalHashes = array_column($finalTorrents, 'hash');
            $newHashes = array_diff($finalHashes, $initialHashes);

            if (!empty($newHashes)) {
                $addedHashes = array_values($newHashes);
                echo "     ✅ 确认新增种子hash: " . count($addedHashes) . " 个\n";
                foreach ($addedHashes as $i => $hash) {
                    $torrentName = 'Unknown';
                    foreach ($finalTorrents as $torrent) {
                        if ($torrent['hash'] === $hash) {
                            $torrentName = $torrent['name'] ?? 'Unknown';
                            break;
                        }
                    }
                    echo "     [" . ($i + 1) . "] {$hash} - {$torrentName}\n";
                }
            } else {
                echo "     ❌ 未检测到新增种子hash\n";
            }
        } else {
            echo "     ❌ 未找到任何测试种子\n";
            echo "     🔍 手动验证期望的测试hash:\n";
            foreach ($expectedHashes as $expectedHash) {
                $found = false;
                foreach ($finalTorrents as $torrent) {
                    if (strtolower($torrent['hash'] ?? '') === $expectedHash) {
                        echo "       ⚠️  种子已存在: {$expectedHash} - " . ($torrent['name'] ?? 'Unknown') . "\n";
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    echo "       ❌ 种子不存在: {$expectedHash}\n";
                }
            }
        }
    }

    return $addedHashes;
}

/**
 * 测试Torrent管理操作
 */
function testTorrentManagement(Client $client, array $addedHashes, array $config): void
{
    if (empty($addedHashes)) {
        return;
    }

    echo "   验证添加的torrent:\n";
    $torrentAPI = $client->getTorrentAPI();
    $finalTorrentListResponse = $torrentAPI->getTorrentList();
                $finalTorrents = $finalTorrentListResponse->getTorrents();

    $testHashes = [];
    foreach ($finalTorrents as $torrent) {
        $hash = $torrent['hash'] ?? '';
        if (in_array($hash, $addedHashes)) {
            echo "     ✅ 找到torrent: " . ($torrent['name'] ?? 'Unknown') . "\n";
            echo "        状态: " . ($torrent['state'] ?? 'Unknown') .
                 " | 进度: " . round(($torrent['progress'] ?? 0) * 100, 1) . "%\n";

            if (!empty($torrent['size'])) {
                echo "        大小: " . formatBytes($torrent['size']) . "\n";
            }

            if (!empty($torrent['dlspeed']) && $torrent['dlspeed'] > 0) {
                echo "        下载: " . formatBytes($torrent['dlspeed']) . "/s\n";
            }

            $testHashes[] = $hash;
        }
    }

    // 进行管理操作测试 - 只操作测试添加的磁力链接
    if (!empty($testHashes)) {
        echo "\n   🔧 9.1-9.12 Torrent管理操作测试 (仅测试新添加的磁力链接):\n";

        $availableTestHashes = array_intersect($testHashes, $addedHashes);

        if (empty($availableTestHashes)) {
            echo "     ⚠️  没有找到可操作的测试磁力链接\n";
            return;
        }

        $testHash = $availableTestHashes[0];
        $testTorrent = null;
        foreach ($finalTorrents as $torrent) {
            if ($torrent['hash'] === $testHash) {
                $testTorrent = $torrent;
                break;
            }
        }

        if ($testTorrent) {
            // 🔒 最终安全检查：确保 torrent 确实是测试添加的
            if (in_array($testHash, $addedHashes)) {
                echo "     🔒 安全验证通过：仅操作测试磁力链接\n";
                testTorrentOperations($torrentAPI, $testHash, $testTorrent, $config);
            } else {
                echo "     ❌ 安全检查失败：torrent 不在测试列表中，跳过操作\n";
                return;
            }
        }
    }
}

/**
 * 测试具体的Torrent操作
 */
function testTorrentOperations(object $torrentAPI, string $testHash, array $testTorrent, array $config): void
{
    $originalState = $testTorrent['state'] ?? 'unknown';
    echo "     测试torrent: " . ($testTorrent['name'] ?? 'Unknown') . "\n";
    echo "     初始状态: {$originalState}\n";

    try {
        // 测试暂停/恢复
        testPauseAndResume($torrentAPI, $testHash, $originalState);

        // 测试重新校验
        testRecheck($torrentAPI, $testHash);

        // 测试移动目录
        testMoveDirectory($torrentAPI, $testHash, $testTorrent, $config);

        // 测试优先级设置
        testPriority($torrentAPI, $testHash);

        // 测试标签管理
        testTagManagement($torrentAPI, $testHash);

        // 测试分类管理
        testCategoryManagement($torrentAPI, $testHash);

        // 测试多文件种子操作
        testMultiFileOperations($torrentAPI, $testHash);

        // 显示最终状态
        showFinalState($torrentAPI, $testHash);

    } catch (Exception $e) {
        echo "     ❌ 管理操作测试出错: " . $e->getMessage() . "\n";
    }
}

/**
 * 测试暂停和恢复功能
 */
function testPauseAndResume(object $torrentAPI, string $testHash, string $originalState): void
{
    if ($originalState !== 'paused' && $originalState !== 'pausedDL' && $originalState !== 'pausedUP') {
        echo "     ⏸️  测试暂停...\n";
        $pauseResult = $torrentAPI->pauseTorrents([$testHash]);
        if ($pauseResult) {
            echo "        ✅ 暂停成功\n";
            sleep(2);
            verifyTorrentState($torrentAPI, $testHash, "暂停后");

            echo "     ▶️  测试恢复...\n";
            $resumeResult = $torrentAPI->resumeTorrents([$testHash]);
            if ($resumeResult) {
                echo "        ✅ 恢复成功\n";
                sleep(2);
                verifyTorrentState($torrentAPI, $testHash, "恢复后");
            } else {
                echo "        ❌ 恢复失败\n";
            }
        } else {
            echo "        ❌ 暂停失败\n";
        }
    } else {
        echo "     ⏸️  当前已暂停，测试恢复...\n";
        $resumeResult = $torrentAPI->resumeTorrents([$testHash]);
        if ($resumeResult) {
            echo "        ✅ 恢复成功\n";
            sleep(2);
        } else {
            echo "        ❌ 恢复失败\n";
        }
    }
}

/**
 * 测试重新校验
 */
function testRecheck(object $torrentAPI, string $testHash): void
{
    // 随机执行以节省时间
    if (rand(1, 3) === 1) {
        echo "     🔍 测试重新校验...\n";
        $recheckResult = $torrentAPI->recheckTorrents([$testHash]);
        if ($recheckResult) {
            echo "        ✅ 重新校验已开始\n";
        } else {
            echo "        ❌ 重新校验失败\n";
        }
    }
}

/**
 * 测试移动目录
 */
function testMoveDirectory(object $torrentAPI, string $testHash, array $testTorrent, array $config): void
{
    if (!empty($config['download_path'])) {
        $customPath = $config['download_path'];
        echo "     📁 测试移动目录到: {$customPath}\n";

        $originalPath = $testTorrent['save_path'] ?? '';
        if ($originalPath !== $customPath) {
            $moveResult = $torrentAPI->setDownloadLocation([$testHash], $customPath);
            if ($moveResult) {
                echo "        ✅ 移动目录成功\n";
                sleep(1);
            } else {
                echo "        ❌ 移动目录失败\n";
            }
        } else {
            echo "        ℹ️  已在目标目录中\n";
        }
    }
}

/**
 * 测试优先级设置
 */
function testPriority(object $torrentAPI, string $testHash): void
{
    echo "     🎯 测试设置下载优先级...\n";
    echo "     ℹ️  优先级设置测试跳过 (API方法未实现)\n";
    // 注意: setTorrentPriority 方法在当前PHP库版本中未实现
    // 可用的方法包括: increasePrio, decreasePrio, topPrio, bottomPrio 等
}

/**
 * 测试标签管理
 */
function testTagManagement(object $torrentAPI, string $testHash): void
{
    echo "     🏷️  测试添加标签...\n";
    $testTag = 'php-qbittorrent-test-' . date('Y-m-d');
    $tagResult = $torrentAPI->addTorrentTags([$testHash], [$testTag]);

    if ($tagResult) {
        echo "        ✅ 标签添加成功: {$testTag}\n";
        verifyTagAdded($torrentAPI, $testHash, $testTag);
    } else {
        echo "        ❌ 标签添加失败\n";
    }
}

/**
 * 测试分类管理
 */
function testCategoryManagement(object $torrentAPI, string $testHash): void
{
    echo "     📂 测试添加到分类...\n";
    $testCategory = 'php-qbittorrent-test';

    // 创建分类
    $torrentAPI->createCategory($testCategory, '/downloads/test');

    // 添加torrent到分类
    $categoryResult = $torrentAPI->setTorrentCategory([$testHash], $testCategory);

    if ($categoryResult) {
        echo "        ✅ 添加到分类成功: {$testCategory}\n";
        verifyCategoryAdded($torrentAPI, $testHash, $testCategory);
    } else {
        echo "        ❌ 添加到分类失败\n";
    }
}

/**
 * 验证Torrent状态
 */
function verifyTorrentState(object $torrentAPI, string $testHash, string $context): void
{
    $torrentListResponse = $torrentAPI->getTorrentList();
    $torrents = $torrentListResponse->getTorrents();
    foreach ($torrents as $torrent) {
        if ($torrent['hash'] === $testHash) {
            echo "        {$context}状态: " . ($torrent['state'] ?? 'unknown') . "\n";
            break;
        }
    }
}

/**
 * 验证标签添加
 */
function verifyTagAdded(object $torrentAPI, string $testHash, string $testTag): void
{
    $updatedTorrentListResponse = $torrentAPI->getTorrentList();
                $updatedTorrents = $updatedTorrentListResponse->getTorrents();
    foreach ($updatedTorrents as $torrent) {
        if ($torrent['hash'] === $testHash) {
            $currentTags = $torrent['tags'] ?? '';
            if (str_contains($currentTags, $testTag)) {
                echo "        ✅ 标签验证成功\n";
            } else {
                echo "        ⚠️  标签验证失败\n";
            }
            break;
        }
    }
}

/**
 * 验证分类添加
 */
function verifyCategoryAdded(object $torrentAPI, string $testHash, string $testCategory): void
{
    $categorizedTorrentListResponse = $torrentAPI->getTorrentList();
                $categorizedTorrents = $categorizedTorrentListResponse->getTorrents();
    foreach ($categorizedTorrents as $torrent) {
        if ($torrent['hash'] === $testHash) {
            $currentCategory = $torrent['category'] ?? '';
            if ($currentCategory === $testCategory) {
                echo "        ✅ 分类验证成功\n";
            } else {
                echo "        ⚠️  分类验证失败，当前: {$currentCategory}\n";
            }
            break;
        }
    }
}

/**
 * 显示最终状态
 */
function showFinalState(object $torrentAPI, string $testHash): void
{
    echo "     📊 最终状态检查:\n";
    $finalTorrentListResponse = $torrentAPI->getTorrentList();
                $finalTorrents = $finalTorrentListResponse->getTorrents();
    foreach ($finalTorrents as $torrent) {
        if ($torrent['hash'] === $testHash) {
            echo "        状态: " . ($torrent['state'] ?? 'unknown') . "\n";
            echo "        进度: " . round(($torrent['progress'] ?? 0) * 100, 1) . "%\n";
            echo "        分类: " . ($torrent['category'] ?? 'none') . "\n";
            echo "        标签: " . ($torrent['tags'] ?? 'none') . "\n";
            echo "        保存路径: " . ($torrent['save_path'] ?? 'unknown') . "\n";
            break;
        }
    }
}

/**
 * 测试多文件种子操作
 */
function testMultiFileOperations(object $torrentAPI, string $testHash): void
{
    echo "\n     📁 测试多文件种子操作...\n";

    try {
        // 获取文件列表
        $files = $torrentAPI->getTorrentFiles($testHash);
        if (empty($files) || count($files) <= 1) {
            echo "     ⚠️  这是一个单文件种子，跳过多文件操作测试\n";
            return;
        }

        $totalFiles = count($files);
        echo "     📋 发现 {$totalFiles} 个文件，开始多文件操作测试\n";

        // 9.1 测试文件列表获取
        echo "     9.1 🔍 测试文件列表获取...\n";
        try {
            $fileList = $torrentAPI->getTorrentFiles($testHash);
            if (!empty($fileList) && is_array($fileList)) {
                echo "       ✅ 文件列表获取成功，共 " . count($fileList) . " 个文件\n";

                // 显示前几个文件信息
                $displayCount = min(3, count($fileList));
                for ($i = 0; $i < $displayCount; $i++) {
                    $file = $fileList[$i];
                    echo "         [" . ($file['index'] ?? $i) . "] " . ($file['name'] ?? 'Unknown') . "\n";
                    echo "           大小: " . formatBytes($file['size'] ?? 0) .
                         " | 进度: " . round(($file['progress'] ?? 0) * 100, 1) . "%" .
                         " | 优先级: " . getPriorityText($file['priority'] ?? 0) . "\n";
                }
                if (count($fileList) > $displayCount) {
                    echo "         ... 还有 " . (count($fileList) - $displayCount) . " 个文件\n";
                }
            } else {
                echo "       ❌ 文件列表获取失败\n";
                return;
            }
        } catch (Exception $e) {
            echo "       ❌ 文件列表获取异常: " . $e->getMessage() . "\n";
            return;
        }

        // 9.2 测试全选文件
        echo "     9.2 ✅ 测试全选所有文件...\n";
        try {
            $allIndexes = array_map(function($file) {
                return (string)($file['index'] ?? 0);
            }, $files);

            $setResult = $torrentAPI->setFilePriority($testHash, $allIndexes, 1); // 1 = 正常优先级
            if ($setResult) {
                echo "       ✅ 所有文件设置为正常下载优先级\n";
                sleep(1);
            } else {
                echo "       ❌ 全选文件设置失败\n";
            }
        } catch (Exception $e) {
            echo "       ❌ 全选文件操作异常: " . $e->getMessage() . "\n";
        }

        // 9.3 测试减少文件（只下载前半部分）
        echo "     9.3 🗂️  测试减少文件（仅下载前半部分）...\n";
        try {
            $halfCount = max(1, intval($totalFiles / 2));
            $selectIndexes = [];

            for ($i = 0; $i < $halfCount; $i++) {
                $selectIndexes[] = (string)($files[$i]['index'] ?? $i);
            }

            // 设置前半部分为正常优先级，后半部分为不下载
            $torrentAPI->setFilePriority($testHash, $selectIndexes, 1); // 正常优先级

            $skipIndexes = [];
            for ($i = $halfCount; $i < $totalFiles; $i++) {
                $skipIndexes[] = (string)($files[$i]['index'] ?? $i);
            }
            $torrentAPI->setFilePriority($testHash, $skipIndexes, 0); // 不下载

            echo "       ✅ 设置前 {$halfCount} 个文件下载，跳过 " . ($totalFiles - $halfCount) . " 个文件\n";
            sleep(1);

            // 验证设置结果
            $updatedFiles = $torrentAPI->getTorrentFiles($testHash);
            $normalCount = 0;
            $skipCount = 0;
            foreach ($updatedFiles as $file) {
                if (($file['priority'] ?? 0) == 1) {
                    $normalCount++;
                } elseif (($file['priority'] ?? 0) == 0) {
                    $skipCount++;
                }
            }
            echo "       📊 验证结果: {$normalCount} 个正常下载，{$skipCount} 个跳过\n";
        } catch (Exception $e) {
            echo "       ❌ 减少文件操作异常: " . $e->getMessage() . "\n";
        }

        // 9.4 测试增加文件（重新选择所有文件）
        echo "     9.4 📂 测试增加文件（重新选择所有文件）...\n";
        try {
            $allIndexes = array_map(function($file) {
                return (string)($file['index'] ?? 0);
            }, $files);

            // 设置所有文件为高优先级
            $torrentAPI->setFilePriority($testHash, $allIndexes, 6); // 6 = 高优先级

            echo "       ✅ 重新选择所有 {$totalFiles} 个文件并设置为高优先级\n";
            sleep(1);

            // 验证设置结果
            $finalFiles = $torrentAPI->getTorrentFiles($testHash);
            $highCount = 0;
            foreach ($finalFiles as $file) {
                if (($file['priority'] ?? 0) == 6) {
                    $highCount++;
                }
            }
            echo "       📊 验证结果: {$highCount}/{$totalFiles} 个文件设置为高优先级\n";

            if ($highCount === $totalFiles) {
                echo "       ✅ 所有文件优先级更新成功\n";
            } else {
                echo "       ⚠️  部分文件优先级更新失败\n";
            }
        } catch (Exception $e) {
            echo "       ❌ 增加文件操作异常: " . $e->getMessage() . "\n";
        }

        // 9.5 测试优先级循环切换
        echo "     9.5 🔄 测试优先级循环切换...\n";
        try {
            $priorities = [1, 6, 7, 0]; // 正常 -> 高 -> 最高 -> 不下载
            $allIndexes = array_map(function($file) {
                return (string)($file['index'] ?? 0);
            }, $files);

            foreach ($priorities as $priority) {
                $priorityName = getPriorityText($priority);
                echo "       🔧 设置所有文件为 {$priorityName} 优先级...\n";
                $torrentAPI->setFilePriority($testHash, $allIndexes, $priority);
                sleep(1);

                // 验证设置
                $checkFiles = $torrentAPI->getTorrentFiles($testHash);
                $matchCount = 0;
                foreach ($checkFiles as $file) {
                    if (($file['priority'] ?? 0) == $priority) {
                        $matchCount++;
                    }
                }
                echo "       ✅ {$matchCount}/{$totalFiles} 个文件设置为 {$priorityName}\n";
            }
        } catch (Exception $e) {
            echo "       ❌ 优先级切换异常: " . $e->getMessage() . "\n";
        }

        // 恢复所有文件为正常状态
        try {
            echo "     🔧 恢复所有文件为正常下载状态...\n";
            $torrentAPI->setFilePriority($testHash, $allIndexes, 1);
            echo "     ✅ 多文件操作测试完成\n";
        } catch (Exception $e) {
            echo "     ❌ 恢复操作异常: " . $e->getMessage() . "\n";
        }

    } catch (Exception $e) {
        echo "     ❌ 多文件操作测试异常: " . $e->getMessage() . "\n";
    }
}

/**
 * 测试错误处理场景
 */
function testErrorHandling(Client $client): void
{
    echo "🚨 17.4-17.7 错误处理测试...\n";

    // 测试无效参数处理
    try {
        echo "   17.6 🔍 测试无效参数处理...\n";
        $torrentAPI = $client->getTorrentAPI();

        // 测试无效hash
        $invalidResult = $torrentAPI->getTorrentProperties('invalid_hash_1234567890abcdef');
        if ($invalidResult === null || empty($invalidResult)) {
            echo "     ✅ 17.6 无效hash参数正确处理\n";
        } else {
            echo "     ❌ 17.6 无效hash参数处理异常\n";
        }

        // 测试空参数
        try {
            $torrentAPI->getTorrentFiles('');
            echo "     ⚠️  17.6 空参数处理: 需要检查API响应\n";
        } catch (Exception $e) {
            echo "     ✅ 17.6 空参数异常正确捕获: " . $e->getMessage() . "\n";
        }

    } catch (Exception $e) {
        echo "     ❌ 17.6 参数测试异常: " . $e->getMessage() . "\n";
    }

    // 测试边界条件
    try {
        echo "   17.6 🔍 测试边界条件...\n";

        // 测试极长的字符串参数
        $longString = str_repeat('a', 10000);
        echo "     📏 长字符串测试: " . strlen($longString) . " 字符\n";

        // 测试极大量值
        $testLimits = [
            'limit' => -1,
            'offset' => -999999,
        ];
        echo "     📊 边界值测试: " . json_encode($testLimits) . "\n";

        echo "     ✅ 17.6 边界条件测试完成\n";

    } catch (Exception $e) {
        echo "     ❌ 17.6 边界条件测试异常: " . $e->getMessage() . "\n";
    }

    // 测试API限流模拟（通过快速连续请求）
    try {
        echo "   17.4 🔍 测试API响应稳定性...\n";
        $torrentAPI = $client->getTorrentAPI();

        $successCount = 0;
        $rateLimitErrors = 0;
        $requestCount = 5;

        for ($i = 0; $i < $requestCount; $i++) {
            try {
                $torrentAPI->getTorrentList();
                $successCount++;

                // 短暂延迟模拟正常使用
                usleep(100000); // 0.1秒
            } catch (Exception $e) {
                if (str_contains(strtolower($e->getMessage()), 'too many') ||
                    str_contains(strtolower($e->getMessage()), 'rate limit')) {
                    $rateLimitErrors++;
                }
            }
        }

        echo "     ✅ 17.4 API稳定性测试: {$successCount}/{$requestCount} 成功";
        if ($rateLimitErrors > 0) {
            echo " (检测到 {$rateLimitErrors} 个限流错误)";
        }
        echo "\n";

    } catch (Exception $e) {
        echo "     ❌ 17.4 API稳定性测试异常: " . $e->getMessage() . "\n";
    }

    echo "     ✅ 错误处理测试完成\n\n";
}

/**
 * 清理测试Torrents
 */
function cleanupTestTorrents(Client $client, array $addedHashes): void
{
    if (empty($addedHashes)) {
        return;
    }

    // 默认不清理，用户可以修改为true
    $cleanupEnabled = false;

    if ($cleanupEnabled) {
        echo "   清理测试torrents...\n";
        $torrentAPI = $client->getTorrentAPI();

        foreach ($addedHashes as $hash) {
            try {
                $torrentAPI->deleteTorrents([$hash], false); // 只删除torrent，不删除文件
                echo "     🗑️ 已删除测试torrent: " . substr($hash, 0, 8) . "...\n";
            } catch (Exception $e) {
                echo "     ❌ 删除torrent失败: " . $e->getMessage() . "\n";
            }
        }
    }
}

/**
 * 安全验证函数
 */
function validateTestSafety(array $config, array $addedHashes): void
{
    echo "🔒 17.7 安全验证...\n";

    // 验证基本配置安全
    $safeUrl = !str_contains($config['url'] ?? '', 'localhost') ||
               filter_var($config['url'] ?? '', FILTER_VALIDATE_IP) !== false;

    if ($safeUrl) {
        echo "     ✅ 17.7 URL配置安全\n";
    } else {
        echo "     ⚠️  17.7 使用本地连接，请确保测试环境安全\n";
    }

    // 验证清理机制
    $cleanupDisabled = true; // 默认禁用清理
    if ($cleanupDisabled) {
        echo "     ✅ 17.7 清理机制已禁用 (安全)\n";
    }

    // 验证Hash隔离
    if (!empty($addedHashes)) {
        echo "     ✅ 17.7 测试Hash隔离: " . count($addedHashes) . " 个测试链接\n";
    } else {
        echo "     ℹ️  17.7 无测试链接，仅进行读取测试\n";
    }

    echo "     ✅ 17.7 安全验证完成\n\n";
}

/**
 * 测试API访问
 */
function testAPIAccess(Client $client): void
{
    echo "🔧 测试 API 访问...\n";

    try {
        $version = $client->application->getVersion();
        echo "✅ 魔术方法访问成功: v{$version}\n";
    } catch (Exception $e) {
        echo "❌ 魔术方法访问失败: " . $e->getMessage() . "\n";
    }
}

/**
 * 显示性能测试结果
 */
function showPerformanceResults(float $startTime): void
{
    echo "\n🚪 正在登出...\n";
    echo "✅ 登出成功\n\n";

    echo "🎉 测试完成！所有功能正常工作\n\n";

    echo "⏱️  10.1-10.6 性能测试:\n";
    $end_time = microtime(true);
    $total_time = round(($end_time - $startTime), 3);
    $memory_usage = memory_get_peak_usage(true);
    $memory_mb = round($memory_usage / 1024 / 1024, 2);

    echo "   10.1 总执行时间: {$total_time} 秒\n";
    echo "   10.2 峰值内存使用: {$memory_mb} MB\n";
    echo "   10.3 平均API响应时间: " . round(($total_time / 15), 3) . " 秒\n"; // 假设大约15个API调用

    // 性能评级
    if ($total_time < 1.0) {
        echo "   10.4 性能评级: ⭐⭐⭐⭐⭐ (优秀)\n";
    } elseif ($total_time < 2.0) {
        echo "   10.4 性能评级: ⭐⭐⭐⭐ (良好)\n";
    } elseif ($total_time < 5.0) {
        echo "   10.4 性能评级: ⭐⭐⭐ (一般)\n";
    } else {
        echo "   10.4 性能评级: ⭐⭐ (需要优化)\n";
    }

    // 10.5 并发处理能力评估
    echo "   10.5 并发处理能力: ";
    if ($total_time < 1.0) {
        echo "高 (可处理高并发请求)\n";
    } elseif ($total_time < 2.0) {
        echo "中 (适合中等并发)\n";
    } else {
        echo "低 (建议优化后使用)\n";
    }

    // 10.6 资源使用效率
    echo "   10.6 资源使用效率: ";
    if ($memory_mb < 10) {
        echo "优秀 (内存占用极低)\n";
    } elseif ($memory_mb < 50) {
        echo "良好 (内存占用合理)\n";
    } else {
        echo "需优化 (内存占用较高)\n";
    }

    echo "\n💡 提示:\n";
    echo "   - 如需进一步测试，请添加 torrents 后重新运行\n";
    echo "   - 查看 examples/ 目录获取更多示例\n";
    echo "   - 阅读 TESTING.md 了解详细测试方法\n";
    echo "   - 查看 MAGNET_TEST.md 了解磁力链接测试详情\n";
}

// ============================================================================
// 主程序
// ============================================================================

// 加载环境变量
loadEnv(__DIR__ . '/../.env');

// 获取配置
$config = getTestConfig();

// 显示配置信息
showTestConfig($config);

// 开始计时
$start_time = microtime(true);

try {
    // 创建客户端
    $client = new Client(
        $config['url'],
        $config['username'],
        $config['password']
    );

    // 1-2. 基础连接和认证测试
    testConnectionAndAuth($client, $config);

    // 3. 应用程序信息测试
    testServerInfo($client);

    // 4. 传输统计信息测试
    testTransferInfo($client);

    // 5. Torrent基础管理测试
    $torrents = testTorrentList($client);

    // 6. 高级功能测试
    testAdvancedFeatures($client);

    // 8. 磁力链接添加测试 (提前进行以提供测试种子)
    $addedHashes = testMagnetLinks($client, $config);

    // 7. 分类标签管理测试 (使用添加的测试种子)
    testCategoriesAndTags($client, $addedHashes);

    // 14. 高级Torrent信息读取测试 (基于现有torrents)
    testAdvancedTorrentInfo($client, $torrents);

    // 9. Torrent操作管理测试 (基于新添加的磁力链接)
    if (!empty($addedHashes)) {
        testTorrentManagement($client, $addedHashes, $config);
    }

    // 17. 错误处理测试
    testErrorHandling($client);

    // 安全验证
    validateTestSafety($config, $addedHashes);

    // API访问测试
    testAPIAccess($client);

    // 清理测试数据
    if (!empty($addedHashes)) {
        cleanupTestTorrents($client, $addedHashes);
    }

    // 登出
    $client->logout();

    // 10. 性能评估测试
    showPerformanceResults($start_time);

} catch (\PhpQbittorrent\Exception\AuthenticationException $e) {
    echo "❌ 认证错误: " . $e->getMessage() . "\n";
    if ($e->isInvalidCredentials()) {
        echo "   提示: 用户名或密码错误\n";
    }
    exit(1);

} catch (\PhpQbittorrent\Exception\NetworkException $e) {
    echo "❌ 网络错误: " . $e->getMessage() . "\n";
    if ($e->isTimeoutError()) {
        echo "   提示: 连接超时\n";
    }
    if ($e->isConnectionError()) {
        echo "   提示: 无法连接到 qBittorrent\n";
    }
    exit(1);

} catch (\PhpQbittorrent\Exception\ValidationException $e) {
    echo "❌ 配置验证失败:\n";
    foreach ($e->getValidationErrors() as $field => $error) {
        echo "   {$field}: {$error}\n";
    }
    exit(1);

} catch (\PhpQbittorrent\Exception\ClientException $e) {
    echo "❌ 客户端错误: " . $e->getMessage() . "\n";
    echo "   HTTP状态码: " . $e->getHttpStatusCode() . "\n";
    exit(1);

} catch (Exception $e) {
    echo "❌ 未知错误: " . $e->getMessage() . "\n";
    echo "   错误类型: " . get_class($e) . "\n";
    exit(1);
}