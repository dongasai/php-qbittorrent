<?php
declare(strict_types=1);

namespace PhpQbittorrent\Model;

use JsonSerializable;

/**
 * 会话信息模型
 *
 * 封装用户会话的相关信息
 */
class SessionInfo implements JsonSerializable
{
    /** @var string 会话ID */
    private string $sessionId;

    /** @var string 用户名 */
    private string $username;

    /** @var int 登录时间（Unix时间戳） */
    private int $loginTime;

    /** @var int|null 会话过期时间（Unix时间戳） */
    private ?int $expiresAt;

    /** @var string|null 客户端IP地址 */
    private ?string $clientIp;

    /** @var string|null User-Agent */
    private ?string $userAgent;

    /** @var string|null 登录方式 */
    private ?string $loginMethod;

    /** @var bool 是否为安全会话 */
    private bool $isSecure;

    /** @var array<string, mixed> 额外的会话数据 */
    private array $additionalData;

    /**
     * 构造函数
     *
     * @param string $sessionId 会话ID
     * @param string $username 用户名
     * @param int $loginTime 登录时间
     * @param int|null $expiresAt 会话过期时间
     * @param string|null $clientIp 客户端IP
     * @param string|null $userAgent User-Agent
     * @param string|null $loginMethod 登录方式
     * @param bool $isSecure 是否为安全会话
     * @param array<string, mixed> $additionalData 额外数据
     */
    public function __construct(
        string $sessionId,
        string $username,
        int $loginTime,
        ?int $expiresAt = null,
        ?string $clientIp = null,
        ?string $userAgent = null,
        ?string $loginMethod = null,
        bool $isSecure = false,
        array $additionalData = []
    ) {
        $this->sessionId = $sessionId;
        $this->username = $username;
        $this->loginTime = $loginTime;
        $this->expiresAt = $expiresAt;
        $this->clientIp = $clientIp;
        $this->userAgent = $userAgent;
        $this->loginMethod = $loginMethod;
        $this->isSecure = $isSecure;
        $this->additionalData = $additionalData;
    }

    /**
     * 从数组创建SessionInfo实例
     *
     * @param array<string, mixed> $data 会话数据
     * @return self SessionInfo实例
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['sessionId'] ?? '',
            $data['username'] ?? '',
            $data['loginTime'] ?? time(),
            $data['expiresAt'] ?? null,
            $data['clientIp'] ?? null,
            $data['userAgent'] ?? null,
            $data['loginMethod'] ?? null,
            $data['isSecure'] ?? false,
            $data['additionalData'] ?? []
        );
    }

    /**
     * 获取会话ID
     *
     * @return string 会话ID
     */
    public function getSessionId(): string
    {
        return $this->sessionId;
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
     * 获取登录时间
     *
     * @return int 登录时间（Unix时间戳）
     */
    public function getLoginTime(): int
    {
        return $this->loginTime;
    }

    /**
     * 获取格式化的登录时间
     *
     * @return string 格式化的登录时间
     */
    public function getFormattedLoginTime(): string
    {
        return date('Y-m-d H:i:s', $this->loginTime);
    }

    /**
     * 获取会话过期时间
     *
     * @return int|null 会话过期时间（Unix时间戳）
     */
    public function getExpiresAt(): ?int
    {
        return $this->expiresAt;
    }

    /**
     * 获取格式化的过期时间
     *
     * @return string|null 格式化的过期时间
     */
    public function getFormattedExpiresAt(): ?string
    {
        return $this->expiresAt ? date('Y-m-d H:i:s', $this->expiresAt) : null;
    }

    /**
     * 获取客户端IP地址
     *
     * @return string|null 客户端IP地址
     */
    public function getClientIp(): ?string
    {
        return $this->clientIp;
    }

    /**
     * 获取User-Agent
     *
     * @return string|null User-Agent
     */
    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    /**
     * 获取登录方式
     *
     * @return string|null 登录方式
     */
    public function getLoginMethod(): ?string
    {
        return $this->loginMethod;
    }

    /**
     * 检查是否为安全会话
     *
     * @return bool 是否为安全会话
     */
    public function isSecure(): bool
    {
        return $this->isSecure;
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
     * 检查会话是否过期
     *
     * @return bool 是否过期
     */
    public function isExpired(): bool
    {
        if ($this->expiresAt === null) {
            return false; // 无过期时间，认为不会过期
        }

        return time() > $this->expiresAt;
    }

    /**
     * 获取剩余会话时间（秒）
     *
     * @return int|null 剩余时间（秒），如果无过期时间返回null
     */
    public function getRemainingTime(): ?int
    {
        if ($this->expiresAt === null) {
            return null;
        }

        $remaining = $this->expiresAt - time();
        return max(0, $remaining);
    }

    /**
     * 获取会话持续时间（秒）
     *
     * @return int 持续时间（秒）
     */
    public function getDuration(): int
    {
        return time() - $this->loginTime;
    }

    /**
     * 获取格式化的会话持续时间
     *
     * @return string 格式化的持续时间
     */
    public function getFormattedDuration(): string
    {
        $duration = $this->getDuration();
        $hours = floor($duration / 3600);
        $minutes = floor(($duration % 3600) / 60);
        $seconds = $duration % 60;

        if ($hours > 0) {
            return sprintf('%d小时%d分%d秒', $hours, $minutes, $seconds);
        } elseif ($minutes > 0) {
            return sprintf('%d分%d秒', $minutes, $seconds);
        } else {
            return sprintf('%d秒', $seconds);
        }
    }

    /**
     * 更新会话过期时间
     *
     * @param int|null $expiresAt 新的过期时间
     * @return self 返回自身以支持链式调用
     */
    public function updateExpiresAt(?int $expiresAt): self
    {
        $this->expiresAt = $expiresAt;
        return $this;
    }

    /**
     * 延长会话时间
     *
     * @param int $seconds 延长的秒数
     * @return self 返回自身以支持链式调用
     */
    public function extend(int $seconds): self
    {
        if ($this->expiresAt !== null) {
            $this->expiresAt += $seconds;
        }
        return $this;
    }

    /**
     * 获取会话状态描述
     *
     * @return string 状态描述
     */
    public function getStatusDescription(): string
    {
        if ($this->isExpired()) {
            return '已过期';
        }

        $remaining = $this->getRemainingTime();
        if ($remaining === null) {
            return '活跃';
        }

        if ($remaining < 300) { // 5分钟内
            return '即将过期';
        }

        return '活跃';
    }

    /**
     * 获取会话的摘要信息
     *
     * @return array<string, mixed> 摘要信息
     */
    public function getSummary(): array
    {
        return [
            'session_id' => substr($this->sessionId, 0, 8) . '***',
            'username' => $this->username,
            'login_time' => $this->getFormattedLoginTime(),
            'duration' => $this->getFormattedDuration(),
            'expires_at' => $this->getFormattedExpiresAt(),
            'remaining_time' => $this->getRemainingTime(),
            'status' => $this->getStatusDescription(),
            'is_secure' => $this->isSecure,
            'client_ip' => $this->clientIp,
            'login_method' => $this->loginMethod,
        ];
    }

    /**
     * 转换为数组格式
     *
     * @return array<string, mixed> 会话数据数组
     */
    public function toArray(): array
    {
        return [
            'sessionId' => $this->sessionId,
            'username' => $this->username,
            'loginTime' => $this->loginTime,
            'expiresAt' => $this->expiresAt,
            'clientIp' => $this->clientIp,
            'userAgent' => $this->userAgent,
            'loginMethod' => $this->loginMethod,
            'isSecure' => $this->isSecure,
            'isExpired' => $this->isExpired(),
            'remainingTime' => $this->getRemainingTime(),
            'duration' => $this->getDuration(),
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