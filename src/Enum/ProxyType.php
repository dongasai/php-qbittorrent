<?php
declare(strict_types=1);

namespace Dongasai\qBittorrent\Enum;

/**
 * 代理类型枚举
 *
 * 定义不同的代理类型配置
 */
enum ProxyType: int
{
    case DISABLED = -1;           // 禁用代理
    case HTTP_WITHOUT_AUTH = 1;   // HTTP代理（无认证）
    case SOCKS5_WITHOUT_AUTH = 2; // SOCKS5代理（无认证）
    case HTTP_WITH_AUTH = 3;      // HTTP代理（带认证）
    case SOCKS5_WITH_AUTH = 4;    // SOCKS5代理（带认证）
    case SOCKS4_WITHOUT_AUTH = 5; // SOCKS4代理（无认证）

    /**
     * 获取代理类型的显示名称
     *
     * @return string 显示名称
     */
    public function getDisplayName(): string
    {
        return match($this) {
            self::DISABLED => '禁用代理',
            self::HTTP_WITHOUT_AUTH => 'HTTP代理',
            self::SOCKS5_WITHOUT_AUTH => 'SOCKS5代理',
            self::HTTP_WITH_AUTH => 'HTTP代理（认证）',
            self::SOCKS5_WITH_AUTH => 'SOCKS5代理（认证）',
            self::SOCKS4_WITHOUT_AUTH => 'SOCKS4代理',
        };
    }

    /**
     * 获取代理类型的协议名称
     *
     * @return string 协议名称
     */
    public function getProtocol(): string
    {
        return match($this) {
            self::HTTP_WITHOUT_AUTH, self::HTTP_WITH_AUTH => 'http',
            self::SOCKS5_WITHOUT_AUTH, self::SOCKS5_WITH_AUTH => 'socks5',
            self::SOCKS4_WITHOUT_AUTH => 'socks4',
            self::DISABLED => 'none',
        };
    }

    /**
     * 检查是否需要认证
     *
     * @return bool 是否需要认证
     */
    public function requiresAuthentication(): bool
    {
        return in_array($this, [
            self::HTTP_WITH_AUTH,
            self::SOCKS5_WITH_AUTH,
        ]);
    }

    /**
     * 检查是否为HTTP代理
     *
     * @return bool 是否为HTTP代理
     */
    public function isHttp(): bool
    {
        return in_array($this, [
            self::HTTP_WITHOUT_AUTH,
            self::HTTP_WITH_AUTH,
        ]);
    }

    /**
     * 检查是否为SOCKS代理
     *
     * @return bool 是否为SOCKS代理
     */
    public function isSocks(): bool
    {
        return in_array($this, [
            self::SOCKS5_WITHOUT_AUTH,
            self::SOCKS5_WITH_AUTH,
            self::SOCKS4_WITHOUT_AUTH,
        ]);
    }

    /**
     * 检查是否已启用代理
     *
     * @return bool 是否已启用代理
     */
    public function isEnabled(): bool
    {
        return $this !== self::DISABLED;
    }

    /**
     * 从整数值创建代理类型枚举
     *
     * @param int $type 代理类型值
     * @return self 代理类型枚举
     */
    public static function fromInt(int $type): self
    {
        return match($type) {
            -1 => self::DISABLED,
            1 => self::HTTP_WITHOUT_AUTH,
            2 => self::SOCKS5_WITHOUT_AUTH,
            3 => self::HTTP_WITH_AUTH,
            4 => self::SOCKS5_WITH_AUTH,
            5 => self::SOCKS4_WITHOUT_AUTH,
            default => self::DISABLED,
        };
    }

    /**
     * 从协议名称创建代理类型枚举
     *
     * @param string $protocol 协议名称
     * @param bool $requiresAuth 是否需要认证
     * @return self 代理类型枚举
     */
    public static function fromProtocol(string $protocol, bool $requiresAuth = false): self
    {
        return match(strtolower($protocol)) {
            'http' => $requiresAuth ? self::HTTP_WITH_AUTH : self::HTTP_WITHOUT_AUTH,
            'socks5' => $requiresAuth ? self::SOCKS5_WITH_AUTH : self::SOCKS5_WITHOUT_AUTH,
            'socks4' => self::SOCKS4_WITHOUT_AUTH,
            default => self::DISABLED,
        };
    }

    /**
     * 获取所有启用的代理类型
     *
     * @return array<self> 启用的代理类型列表
     */
    public static function getEnabledTypes(): array
    {
        return array_filter(self::cases(), fn($type) => $type->isEnabled());
    }

    /**
     * 获取需要认证的代理类型
     *
     * @return array<self> 需要认证的代理类型列表
     */
    public static function getAuthenticatedTypes(): array
    {
        return array_filter(self::cases(), fn($type) => $type->requiresAuthentication());
    }
}