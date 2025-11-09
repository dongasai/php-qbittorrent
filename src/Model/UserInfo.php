<?php
declare(strict_types=1);

namespace Dongasai\qBittorrent\Model;

use JsonSerializable;

/**
 * 用户信息模型
 *
 * 封装用户的相关信息
 */
class UserInfo implements JsonSerializable
{
    /** @var string 用户名 */
    private string $username;

    /** @var string|null 显示名称 */
    private ?string $displayName;

    /** @var string|null 邮箱地址 */
    private ?string $email;

    /** @var string|null 用户角色 */
    private ?string $role;

    /** @var int|null 用户级别 */
    private ?int $level;

    /** @var int|null 最后登录时间（Unix时间戳） */
    private ?int $lastLoginTime;

    /** @var int|null 创建时间（Unix时间戳） */
    private ?int $createdTime;

    /** @var bool 是否启用 */
    private bool $enabled;

    /** @var bool 是否为管理员 */
    private bool $isAdmin;

    /** @var array<string> 用户权限 */
    private array $permissions;

    /** @var array<string, mixed> 用户偏好设置 */
    private array $preferences;

    /** @var array<string, mixed> 额外的用户数据 */
    private array $additionalData;

    /**
     * 构造函数
     *
     * @param string $username 用户名
     * @param string|null $displayName 显示名称
     * @param string|null $email 邮箱地址
     * @param string|null $role 用户角色
     * @param int|null $level 用户级别
     * @param int|null $lastLoginTime 最后登录时间
     * @param int|null $createdTime 创建时间
     * @param bool $enabled 是否启用
     * @param bool $isAdmin 是否为管理员
     * @param array<string> $permissions 用户权限
     * @param array<string, mixed> $preferences 用户偏好设置
     * @param array<string, mixed> $additionalData 额外数据
     */
    public function __construct(
        string $username,
        ?string $displayName = null,
        ?string $email = null,
        ?string $role = null,
        ?int $level = null,
        ?int $lastLoginTime = null,
        ?int $createdTime = null,
        bool $enabled = true,
        bool $isAdmin = false,
        array $permissions = [],
        array $preferences = [],
        array $additionalData = []
    ) {
        $this->username = $username;
        $this->displayName = $displayName;
        $this->email = $email;
        $this->role = $role;
        $this->level = $level;
        $this->lastLoginTime = $lastLoginTime;
        $this->createdTime = $createdTime;
        $this->enabled = $enabled;
        $this->isAdmin = $isAdmin;
        $this->permissions = $permissions;
        $this->preferences = $preferences;
        $this->additionalData = $additionalData;
    }

    /**
     * 从数组创建UserInfo实例
     *
     * @param array<string, mixed> $data 用户数据
     * @return self UserInfo实例
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['username'] ?? '',
            $data['displayName'] ?? null,
            $data['email'] ?? null,
            $data['role'] ?? null,
            $data['level'] ?? null,
            $data['lastLoginTime'] ?? null,
            $data['createdTime'] ?? null,
            $data['enabled'] ?? true,
            $data['isAdmin'] ?? false,
            $data['permissions'] ?? [],
            $data['preferences'] ?? [],
            $data['additionalData'] ?? []
        );
    }

    /**
     * 获取用户名
     *
     * @return string 用户名
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * 获取显示名称
     *
     * @return string|null 显示名称
     */
    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    /**
     * 获取用于显示的名称（优先使用显示名称）
     *
     * @return string 显示名称
     */
    public function getDisplayableName(): string
    {
        return $this->displayName ?: $this->username;
    }

    /**
     * 获取邮箱地址
     *
     * @return string|null 邮箱地址
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * 获取用户角色
     *
     * @return string|null 用户角色
     */
    public function getRole(): ?string
    {
        return $this->role;
    }

    /**
     * 获取用户级别
     *
     * @return int|null 用户级别
     */
    public function getLevel(): ?int
    {
        return $this->level;
    }

    /**
     * 获取最后登录时间
     *
     * @return int|null 最后登录时间（Unix时间戳）
     */
    public function getLastLoginTime(): ?int
    {
        return $this->lastLoginTime;
    }

    /**
     * 获取格式化的最后登录时间
     *
     * @return string|null 格式化的最后登录时间
     */
    public function getFormattedLastLoginTime(): ?string
    {
        return $this->lastLoginTime ? date('Y-m-d H:i:s', $this->lastLoginTime) : null;
    }

    /**
     * 获取创建时间
     *
     * @return int|null 创建时间（Unix时间戳）
     */
    public function getCreatedTime(): ?int
    {
        return $this->createdTime;
    }

    /**
     * 获取格式化的创建时间
     *
     * @return string|null 格式化的创建时间
     */
    public function getFormattedCreatedTime(): ?string
    {
        return $this->createdTime ? date('Y-m-d H:i:s', $this->createdTime) : null;
    }

    /**
     * 检查是否启用
     *
     * @return bool 是否启用
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * 检查是否为管理员
     *
     * @return bool 是否为管理员
     */
    public function isAdmin(): bool
    {
        return $this->isAdmin;
    }

    /**
     * 获取用户权限
     *
     * @return array<string> 用户权限
     */
    public function getPermissions(): array
    {
        return $this->permissions;
    }

    /**
     * 检查是否有指定权限
     *
     * @param string $permission 权限名称
     * @return bool 是否有权限
     */
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions) || $this->isAdmin;
    }

    /**
     * 添加权限
     *
     * @param string $permission 权限名称
     * @return self 返回自身以支持链式调用
     */
    public function addPermission(string $permission): self
    {
        if (!in_array($permission, $this->permissions)) {
            $this->permissions[] = $permission;
        }
        return $this;
    }

    /**
     * 移除权限
     *
     * @param string $permission 权限名称
     * @return self 返回自身以支持链式调用
     */
    public function removePermission(string $permission): self
    {
        $key = array_search($permission, $this->permissions);
        if ($key !== false) {
            unset($this->permissions[$key]);
            $this->permissions = array_values($this->permissions);
        }
        return $this;
    }

    /**
     * 获取用户偏好设置
     *
     * @param string|null $key 设置键名，为null时返回所有设置
     * @return mixed 偏好设置
     */
    public function getPreferences(?string $key = null): mixed
    {
        if ($key === null) {
            return $this->preferences;
        }

        return $this->preferences[$key] ?? null;
    }

    /**
     * 设置用户偏好
     *
     * @param string $key 设置键名
     * @param mixed $value 设置值
     * @return self 返回自身以支持链式调用
     */
    public function setPreference(string $key, mixed $value): self
    {
        $this->preferences[$key] = $value;
        return $this;
    }

    /**
     * 获取额外数据
     *
     * @param string|null $key 数据键名，为null时返回所有额外数据
     * @return mixed 额外数据
     */
    public function getAdditionalData(?string $key = null): mixed
    {
        if ($key === null) {
            return $this->additionalData;
        }

        return $this->additionalData[$key] ?? null;
    }

    /**
     * 设置额外数据
     *
     * @param string $key 数据键名
     * @param mixed $value 数据值
     * @return self 返回自身以支持链式调用
     */
    public function setAdditionalData(string $key, mixed $value): self
    {
        $this->additionalData[$key] = $value;
        return $this;
    }

    /**
     * 获取用户完整名称
     *
     * @return string 完整名称
     */
    public function getFullName(): string
    {
        return $this->displayName ?: $this->username;
    }

    /**
     * 获取用户头衔
     *
     * @return string 用户头衔
     */
    public function getTitle(): string
    {
        if ($this->isAdmin) {
            return '管理员';
        }

        if ($this->role) {
            return $this->role;
        }

        if ($this->level) {
            return "用户级别 {$this->level}";
        }

        return '用户';
    }

    /**
     * 检查用户是否活跃
     *
     * @param int $inactiveThreshold 非活跃阈值（天）
     * @return bool 是否活跃
     */
    public function isActive(int $inactiveThreshold = 30): bool
    {
        if ($this->lastLoginTime === null) {
            return false;
        }

        $inactiveTime = time() - $this->lastLoginTime;
        return $inactiveTime < ($inactiveThreshold * 86400);
    }

    /**
     * 获取账户年龄（天）
     *
     * @return int|null 账户年龄（天）
     */
    public function getAccountAge(): ?int
    {
        if ($this->createdTime === null) {
            return null;
        }

        return floor((time() - $this->createdTime) / 86400);
    }

    /**
     * 获取用户状态描述
     *
     * @return string 状态描述
     */
    public function getStatusDescription(): string
    {
        if (!$this->enabled) {
            return '已禁用';
        }

        if ($this->isAdmin) {
            return '管理员';
        }

        if ($this->isActive()) {
            return '活跃';
        }

        return '不活跃';
    }

    /**
     * 获取用户的摘要信息
     *
     * @return array<string, mixed> 摘要信息
     */
    public function getSummary(): array
    {
        return [
            'username' => $this->username,
            'display_name' => $this->getDisplayableName(),
            'title' => $this->getTitle(),
            'email' => $this->email,
            'role' => $this->role,
            'level' => $this->level,
            'status' => $this->getStatusDescription(),
            'is_admin' => $this->isAdmin,
            'is_enabled' => $this->enabled,
            'is_active' => $this->isActive(),
            'last_login' => $this->getFormattedLastLoginTime(),
            'created_time' => $this->getFormattedCreatedTime(),
            'account_age' => $this->getAccountAge(),
            'permissions_count' => count($this->permissions),
        ];
    }

    /**
     * 转换为数组格式
     *
     * @return array<string, mixed> 用户数据数组
     */
    public function toArray(): array
    {
        return [
            'username' => $this->username,
            'displayName' => $this->displayName,
            'email' => $this->email,
            'role' => $this->role,
            'level' => $this->level,
            'lastLoginTime' => $this->lastLoginTime,
            'createdTime' => $this->createdTime,
            'enabled' => $this->enabled,
            'isAdmin' => $this->isAdmin,
            'permissions' => $this->permissions,
            'preferences' => $this->preferences,
            'fullName' => $this->getFullName(),
            'title' => $this->getTitle(),
            'isActive' => $this->isActive(),
            'accountAge' => $this->getAccountAge(),
            'statusDescription' => $this->getStatusDescription(),
            'additionalData' => $this->additionalData,
        ];
    }

    /**
     * JSON序列化
     *
     * @return array<string, mixed> 序列化数据
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}