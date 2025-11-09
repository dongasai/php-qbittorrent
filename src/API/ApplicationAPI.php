<?php
declare(strict_types=1);

namespace PhpQbittorrent\API;

use PhpQbittorrent\Transport\TransportInterface;
use PhpQbittorrent\Exception\ClientException;

/**
 * 应用程序API类
 *
 * 处理qBittorrent应用程序相关的API操作，包括版本信息、构建信息、偏好设置等
 */
final class ApplicationAPI
{
    private TransportInterface $transport;

    public function __construct(TransportInterface $transport)
    {
        $this->transport = $transport;
    }

    /**
     * 获取qBittorrent应用程序版本
     *
     * @return string 应用程序版本号
     * @throws ClientException 获取失败
     */
    public function getVersion(): string
    {
        $response = $this->transport->request('GET', '/api/v2/app/version');
        return $response['version'] ?? $response[0] ?? 'unknown';
    }

    /**
     * 获取Web API版本
     *
     * @return string Web API版本号
     * @throws ClientException 获取失败
     */
    public function getWebApiVersion(): string
    {
        $response = $this->transport->request('GET', '/api/v2/app/webapiVersion');
        return $response['version'] ?? $response[0] ?? 'unknown';
    }

    /**
     * 获取qBittorrent构建信息
     *
     * @return array 构建信息
     * @throws ClientException 获取失败
     */
    public function getBuildInfo(): array
    {
        return $this->transport->request('GET', '/api/v2/app/buildInfo');
    }

    /**
     * 获取应用程序偏好设置
     *
     * @param string|null $specificSetting 指定的偏好设置名称，null表示获取所有设置
     * @return array 偏好设置
     * @throws ClientException 获取失败
     */
    public function getPreferences(?string $specificSetting = null): array
    {
        if ($specificSetting !== null) {
            $response = $this->transport->request('GET', '/api/v2/app/preferences', [
                'query' => ['pref' => $specificSetting]
            ]);
            return [$specificSetting => $response[$specificSetting] ?? null];
        }

        return $this->transport->request('GET', '/api/v2/app/preferences');
    }

    /**
     * 设置应用程序偏好设置
     *
     * @param array $preferences 偏好设置数组
     * @return bool 设置是否成功
     * @throws ClientException 设置失败
     */
    public function setPreferences(array $preferences): bool
    {
        $this->transport->request('POST', '/api/v2/app/setPreferences', [
            'json' => [
                'json' => json_encode($preferences, JSON_UNESCAPED_UNICODE)
            ]
        ]);

        return true;
    }

    /**
     * 获取默认保存路径
     *
     * @return string 默认保存路径
     * @throws ClientException 获取失败
     */
    public function getDefaultSavePath(): string
    {
        $preferences = $this->getPreferences();
        return $preferences['save_path'] ?? '';
    }

    /**
     * 获取监听端口
     *
     * @return int 监听端口
     * @throws ClientException 获取失败
     */
    public function getListeningPort(): int
    {
        $preferences = $this->getPreferences();
        return (int) ($preferences['listen_port'] ?? 0);
    }

    /**
     * 获取全局下载和上传速度限制
     *
     * @return array 速度限制信息
     * @throws ClientException 获取失败
     */
    public function getGlobalSpeedLimits(): array
    {
        $preferences = $this->getPreferences();

        return [
            'dl_limit' => (int) ($preferences['dl_limit'] ?? 0),
            'up_limit' => (int) ($preferences['up_limit'] ?? 0),
            'dl_limit_alt' => (int) ($preferences['dl_limit_alt'] ?? 0),
            'up_limit_alt' => (int) ($preferences['up_limit_alt'] ?? 0),
            'alt_speed_enabled' => (bool) ($preferences['alt_speed_enabled'] ?? false),
        ];
    }

    /**
     * 获取DHT配置信息
     *
     * @return array DHT配置
     * @throws ClientException 获取失败
     */
    public function getDHTConfiguration(): array
    {
        $preferences = $this->getPreferences();

        return [
            'dht' => (bool) ($preferences['dht'] ?? false),
            'dht_port' => (int) ($preferences['dht_port'] ?? 6881),
            'dont_enable_dht_when_private' => (bool) ($preferences['dont_enable_dht_when_private'] ?? true),
        ];
    }

    /**
     * 获取P2P配置信息
     *
     * @return array P2P配置
     * @throws ClientException 获取失败
     */
    public function getP2PConfiguration(): array
    {
        $preferences = $this->getPreferences();

        return [
            'pex' => (bool) ($preferences['pex'] ?? true),
            'lsd' => (bool) ($preferences['lsd'] ?? true),
            'max_connec' => (int) ($preferences['max_connec'] ?? 500),
            'max_connec_per_torrent' => (int) ($preferences['max_connec_per_torrent'] ?? 100),
            'max_uploads' => (int) ($preferences['max_uploads'] ?? 100),
            'max_uploads_per_torrent' => (int) ($preferences['max_uploads_per_torrent'] ?? 100),
        ];
    }

    /**
     * 获取代理配置
     *
     * @return array 代理配置
     * @throws ClientException 获取失败
     */
    public function getProxyConfiguration(): array
    {
        $preferences = $this->getPreferences();

        return [
            'proxy_type' => (int) ($preferences['proxy_type'] ?? 0),
            'proxy_ip' => $preferences['proxy_ip'] ?? '',
            'proxy_port' => (int) ($preferences['proxy_port'] ?? 8080),
            'proxy_peer_connections' => (bool) ($preferences['proxy_peer_connections'] ?? false),
            'proxy_auth_enabled' => (bool) ($preferences['proxy_auth_enabled'] ?? false),
            'proxy_username' => $preferences['proxy_username'] ?? '',
            'proxy_password' => $preferences['proxy_password'] ?? '',
            'proxy_hostname_lookup' => (bool) ($preferences['proxy_hostname_lookup'] ?? false),
        ];
    }

    /**
     * 获取磁盘缓存配置
     *
     * @return array 磁盘缓存配置
     * @throws ClientException 获取失败
     */
    public function getDiskCacheConfiguration(): array
    {
        $preferences = $this->getPreferences();

        return [
            'disk_cache' => (int) ($preferences['disk_cache'] ?? 64),
            'disk_cache_ttl' => (int) ($preferences['disk_cache_ttl'] ?? 60),
            'os_cache' => (bool) ($preferences['os_cache'] ?? true),
            'max_inactive_cache_time' => (int) ($preferences['max_inactive_cache_time'] ?? 30),
        ];
    }

    /**
     * 获取Web UI配置
     *
     * @return array Web UI配置
     * @throws ClientException 获取失败
     */
    public function getWebUIConfiguration(): array
    {
        $preferences = $this->getPreferences();

        return [
            'web_ui_domain_list' => $preferences['web_ui_domain_list'] ?? '*',
            'web_ui_address' => $preferences['web_ui_address'] ?? '*',
            'web_ui_port' => (int) ($preferences['web_ui_port'] ?? 8080),
            'web_ui_upnp' => (bool) ($preferences['web_ui_upnp'] ?? true),
            'web_ui_username' => $preferences['web_ui_username'] ?? '',
            'web_ui_password' => $preferences['web_ui_password'] ?? '',
            'web_ui_csrf_protection_enabled' => (bool) ($preferences['web_ui_csrf_protection_enabled'] ?? true),
            'web_ui_clickjacking_protection_enabled' => (bool) ($preferences['web_ui_clickjacking_protection_enabled'] ?? true),
            'web_ui_secure_cookie_enabled' => (bool) ($preferences['web_ui_secure_cookie_enabled'] ?? true),
            'web_ui_max_auth_fail_count' => (int) ($preferences['web_ui_max_auth_fail_count'] ?? 5),
            'web_ui_ban_duration' => (int) ($preferences['web_ui_ban_duration'] ?? 3600),
            'web_ui_session_timeout' => (int) ($preferences['web_ui_session_timeout'] ?? 3600),
        ];
    }

    /**
     * 获取高级设置配置
     *
     * @return array 高级设置配置
     * @throws ClientException 获取失败
     */
    public function getAdvancedSettings(): array
    {
        $preferences = $this->getPreferences();

        return [
            'libtorrent_mode' => (int) ($preferences['libtorrent_mode'] ?? 0),
            'add_trackers_enabled' => (bool) ($preferences['add_trackers_enabled'] ?? true),
            'add_trackers' => $preferences['add_trackers'] ?? '',
            'alternative_webui_enabled' => (bool) ($preferences['alternative_webui_enabled'] ?? false),
            'alternative_webui_path' => $preferences['alternative_webui_path'] ?? '',
            'current_network_interface' => $preferences['current_network_interface'] ?? '',
            'save_path_changed' => (bool) ($preferences['save_path_changed'] ?? false),
            'save_path_history' => $preferences['save_path_history'] ?? [],
            'banned_IPs' => $preferences['banned_IPs'] ?? [],
        ];
    }

    /**
     * 关闭qBittorrent应用程序
     *
     * @param bool $force 是否强制关闭
     * @return bool 关闭是否成功
     * @throws ClientException 关闭失败
     */
    public function shutdown(bool $force = false): bool
    {
        try {
            $this->transport->request('POST', '/api/v2/app/shutdown', [
                'form_params' => ['force' => $force ? 'true' : 'false']
            ]);
            return true;
        } catch (ClientException $e) {
            return false;
        }
    }

    /**
     * 设置首选的文件协议
     *
     * @param string $protocol 协议名称 (如: 'http', 'https', 'ftp')
     * @return bool 设置是否成功
     * @throws ClientException 设置失败
     */
    public function setFileProtocol(string $protocol): bool
    {
        return $this->setPreferences(['file_protocol' => $protocol]);
    }

    /**
     * 获取应用程序的完整状态摘要
     *
     * @return array 应用程序状态摘要
     * @throws ClientException 获取失败
     */
    public function getApplicationStatus(): array
    {
        return [
            'version' => $this->getVersion(),
            'webapi_version' => $this->getWebApiVersion(),
            'build_info' => $this->getBuildInfo(),
            'default_save_path' => $this->getDefaultSavePath(),
            'listening_port' => $this->getListeningPort(),
            'speed_limits' => $this->getGlobalSpeedLimits(),
        ];
    }
}