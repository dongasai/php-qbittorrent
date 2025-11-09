<?php
declare(strict_types=1);

namespace Dongasai\qBittorrent\Enum;

/**
 * 连接状态枚举
 *
 * 定义qBittorrent的连接状态
 */
enum ConnectionState: string
{
    case CONNECTED = 'connected';
    case FIREWALLED = 'firewalled';
    case DISCONNECTED = 'disconnected';

    /**
     * 获取连接状态的显示名称
     *
     * @return string 显示名称
     */
    public function getDisplayName(): string
    {
        return match($this) {
            self::CONNECTED => '已连接',
            self::FIREWALLED => '被防火墙阻挡',
            self::DISCONNECTED => '未连接',
        };
    }

    /**
     * 获取连接状态的描述
     *
     * @return string 描述
     */
    public function getDescription(): string
    {
        return match($this) {
            self::CONNECTED => 'qBittorrent已成功连接到网络',
            self::FIREWALLED => 'qBittorrent连接被防火墙阻挡，可能影响下载速度',
            self::DISCONNECTED => 'qBittorrent未连接到网络',
        };
    }

    /**
     * 获取连接状态的颜色
     *
     * @return string 颜色代码
     */
    public function getColor(): string
    {
        return match($this) {
            self::CONNECTED => '#28a745',      // 绿色
            self::FIREWALLED => '#ffc107',    // 黄色
            self::DISCONNECTED => '#dc3545',  // 红色
        };
    }

    /**
     * 获取连接状态的图标
     *
     * @return string 图标
     */
    public function getIcon(): string
    {
        return match($this) {
            self::CONNECTED => '🟢',
            self::FIREWALLED => '🟡',
            self::DISCONNECTED => '🔴',
        };
    }

    /**
     * 检查是否为良好连接状态
     *
     * @return bool 是否为良好状态
     */
    public function isGood(): bool
    {
        return $this === self::CONNECTED;
    }

    /**
     * 检查是否有连接问题
     *
     * @return bool 是否有问题
     */
    public function hasProblem(): bool
    {
        return $this !== self::CONNECTED;
    }

    /**
     * 检查是否完全无法连接
     *
     * @return bool 是否无法连接
     */
    public function isDisconnected(): bool
    {
        return $this === self::DISCONNECTED;
    }

    /**
     * 从字符串创建连接状态枚举
     *
     * @param string $state 连接状态字符串
     * @return self 连接状态枚举
     */
    public static function fromString(string $state): self
    {
        try {
            return self::from($state);
        } catch (\ValueError $e) {
            return self::DISCONNECTED;
        }
    }

    /**
     * 获取所有连接状态
     *
     * @return array<self> 所有连接状态
     */
    public static function getAllStates(): array
    {
        return self::cases();
    }

    /**
     * 获取有问题的连接状态
     *
     * @return array<self> 有问题的连接状态
     */
    public static function getProblematicStates(): array
    {
        return array_filter(self::cases(), fn($state) => $state->hasProblem());
    }
}