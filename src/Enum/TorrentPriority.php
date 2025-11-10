<?php
declare(strict_types=1);

namespace PhpQbittorrent\Enum;

/**
 * Torrent优先级枚举
 *
 * 定义Torrent优先级设置
 */
enum TorrentPriority: int
{
    case MINIMAL = 0;     // 最低优先级
    case LOW = 1;         // 低优先级
    case NORMAL = 2;      // 普通优先级
    case HIGH = 3;        // 高优先级
    case MAXIMAL = 4;     // 最高优先级
    case NOT_SET = -1;    // 未设置（禁用队列时）

    /**
     * 获取优先级的显示名称
     *
     * @return string 显示名称
     */
    public function getDisplayName(): string
    {
        return match($this) {
            self::MINIMAL => '最低',
            self::LOW => '低',
            self::NORMAL => '普通',
            self::HIGH => '高',
            self::MAXIMAL => '最高',
            self::NOT_SET => '未设置',
        };
    }

    /**
     * 获取优先级的图标
     *
     * @return string 图标
     */
    public function getIcon(): string
    {
        return match($this) {
            self::MINIMAL => '↓↓',
            self::LOW => '↓',
            self::NORMAL => '→',
            self::HIGH => '↑',
            self::MAXIMAL => '↑↑',
            self::NOT_SET => '—',
        };
    }

    /**
     * 检查是否为有效优先级
     *
     * @param int $priority 优先级值
     * @return bool 是否有效
     */
    public static function isValid(int $priority): bool
    {
        $validValues = array_map(fn($case) => $case->value, self::cases());
        return in_array($priority, $validValues);
    }

    /**
     * 从整数值创建优先级枚举
     *
     * @param int $priority 优先级值
     * @return self 优先级枚举
     */
    public static function fromInt(int $priority): self
    {
        return match($priority) {
            0 => self::MINIMAL,
            1 => self::LOW,
            2 => self::NORMAL,
            3 => self::HIGH,
            4 => self::MAXIMAL,
            -1 => self::NOT_SET,
            default => self::NORMAL,
        };
    }
}