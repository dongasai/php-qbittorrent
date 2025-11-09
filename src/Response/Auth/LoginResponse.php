<?php
declare(strict_types=1);

namespace Dongasai\qBittorrent\Response\Auth;

use Dongasai\qBittorrent\Response\AbstractResponse;
use Dongasai\qBittorrent\Contract\ValidationResult;
use Dongasai\qBittorrent\Validation\BasicValidationResult;
use Dongasai\qBittorrent\Exception\ValidationException;

/**
 * 登录响应对象
 *
 * 封装登录请求的响应数据和状态信息
 */
class LoginResponse extends AbstractResponse
{
    /** @var string|null 会话ID */
    private ?string $sessionId = null;

    /** @var array<string, mixed> 用户信息 */
    private array $userInfo = [];

    /** @var bool 是否为首次登录 */
    private bool $isFirstLogin = false;

    /** @var int|null 会话过期时间（Unix时间戳） */
    private ?int $sessionExpiresAt = null;

    /** @var array<string, mixed> 额外的响应数据 */
    private array $additionalData = [];

    /**
     * 创建成功的登录响应
     *
     * @param string $sessionId 会话ID
     * @param array<string, string> $headers 响应头
     * @param int $statusCode HTTP状态码
     * @param string $rawResponse 原始响应内容
     * @param array<string, mixed> $userInfo 用户信息
     * @return self 登录响应实例
     */
    public static function success(
        string $sessionId,
        array $headers = [],
        int $statusCode = 200,
        string $rawResponse = '',
        array $userInfo = []
    ): self {
        $instance = parent::success([], $headers, $statusCode, $rawResponse);
        $instance->sessionId = $sessionId;
        $instance->userInfo = $userInfo;

        // 从响应头中提取会话信息
        $instance->extractSessionInfo($headers);

        return $instance;
    }

    /**
     * 创建失败的登录响应
     *
     * @param array<string> $errors 错误信息
     * @param array<string, string> $headers 响应头
     * @param int $statusCode HTTP状态码
     * @param string $rawResponse 原始响应内容
     * @return self 登录响应实例
     */
    public static function failure(
        array $errors = [],
        array $headers = [],
        int $statusCode = 403,
        string $rawResponse = ''
    ): self {
        $instance = parent::failure($errors, $headers, $statusCode, $rawResponse);

        // 根据状态码添加具体的错误信息
        if ($statusCode === 403) {
            $instance->addError('用户IP因登录失败次数过多而被禁止访问');
        } elseif ($statusCode === 401) {
            $instance->addError('用户名或密码错误');
        }

        return $instance;
    }

    /**
     * 从数组数据创建响应对象
     *
     * @param array<string, mixed> $data 响应数据
     * @return static 响应对象实例
     */
    public static function fromArray(array $data): static
    {
        $success = ($data['success'] ?? false);
        $headers = $data['headers'] ?? [];
        $statusCode = $data['statusCode'] ?? 200;
        $rawResponse = $data['rawResponse'] ?? '';
        $errors = $data['errors'] ?? [];
        $responseData = $data['data'] ?? [];

        if ($success) {
            $sessionId = $responseData['sessionId'] ?? null;
            $userInfo = $responseData['userInfo'] ?? [];

            $instance = self::success(
                $sessionId,
                $headers,
                $statusCode,
                $rawResponse,
                $userInfo
            );

            // 设置额外数据
            if (isset($responseData['isFirstLogin'])) {
                $instance->isFirstLogin = $responseData['isFirstLogin'];
            }
            if (isset($responseData['sessionExpiresAt'])) {
                $instance->sessionExpiresAt = $responseData['sessionExpiresAt'];
            }
            $instance->additionalData = $responseData['additionalData'] ?? [];

            return $instance;
        } else {
            return self::failure($errors, $headers, $statusCode, $rawResponse);
        }
    }

    /**
     * 获取会话ID
     *
     * @return string|null 会话ID
     */
    public function getSessionId(): ?string
    {
        return $this->sessionId;
    }

    /**
     * 获取用户信息
     *
     * @return array<string, mixed> 用户信息
     */
    public function getUserInfo(): array
    {
        return $this->userInfo;
    }

    /**
     * 检查是否已登录
     *
     * @return bool 是否已登录
     */
    public function isLoggedIn(): bool
    {
        return $this->isSuccess() && !empty($this->sessionId);
    }

    /**
     * 检查是否为首次登录
     *
     * @return bool 是否为首次登录
     */
    public function isFirstLogin(): bool
    {
        return $this->isFirstLogin;
    }

    /**
     * 获取会话过期时间
     *
     * @return int|null 会话过期时间（Unix时间戳）
     */
    public function getSessionExpiresAt(): ?int
    {
        return $this->sessionExpiresAt;
    }

    /**
     * 检查会话是否已过期
     *
     * @return bool 是否已过期
     */
    public function isSessionExpired(): bool
    {
        if ($this->sessionExpiresAt === null) {
            return false; // 未设置过期时间，认为不会过期
        }

        return time() > $this->sessionExpiresAt;
    }

    /**
     * 获取剩余会话时间（秒）
     *
     * @return int|null 剩余时间（秒），如果无过期时间返回null
     */
    public function getRemainingSessionTime(): ?int
    {
        if ($this->sessionExpiresAt === null) {
            return null;
        }

        $remaining = $this->sessionExpiresAt - time();
        return max(0, $remaining);
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
     * 从响应头中提取会话信息
     *
     * @param array<string, string> $headers 响应头
     * @return void
     */
    private function extractSessionInfo(array $headers): void
    {
        // 从Set-Cookie头中提取SID
        if (isset($headers['Set-Cookie'])) {
            $this->sessionId = $this->extractSessionId($headers['Set-Cookie']);
        }

        // 从其他响应头中提取会话过期时间
        if (isset($headers['X-Session-Expires'])) {
            $expiresAt = strtotime($headers['X-Session-Expires']);
            if ($expiresAt !== false) {
                $this->sessionExpiresAt = $expiresAt;
            }
        }
    }

    /**
     * 从Cookie头中提取会话ID
     *
     * @param string $cookieHeader Cookie头内容
     * @return string|null 会话ID
     */
    private function extractSessionId(string $cookieHeader): ?string
    {
        // 匹配SID=后面的内容，直到分号或字符串结束
        if (preg_match('/SID=([^;]+)/', $cookieHeader, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    /**
     * 验证响应数据
     *
     * @return ValidationResult 验证结果
     */
    public function validate(): ValidationResult
    {
        $result = BasicValidationResult::success();

        if ($this->isLoggedIn()) {
            // 验证会话ID格式
            if (!empty($this->sessionId) && !preg_match('/^[a-zA-Z0-9\-_]+$/', $this->sessionId)) {
                $result->addWarning('会话ID格式可能异常');
            }

            // 验证会话过期时间
            if ($this->sessionExpiresAt !== null) {
                if ($this->sessionExpiresAt <= time()) {
                    $result->addWarning('会话已过期');
                } elseif ($this->sessionExpiresAt > time() + 86400 * 30) {
                    $result->addWarning('会话过期时间超过30天，可能异常');
                }
            }
        }

        return $result;
    }

    /**
     * 转换为数组格式
     *
     * @return array<string, mixed> 响应数据数组
     */
    public function toArray(): array
    {
        $data = parent::toArray();

        $data['sessionId'] = $this->sessionId;
        $data['userInfo'] = $this->userInfo;
        $data['isLoggedIn'] = $this->isLoggedIn();
        $data['isFirstLogin'] = $this->isFirstLogin;
        $data['sessionExpiresAt'] = $this->sessionExpiresAt;
        $data['isSessionExpired'] = $this->isSessionExpired();
        $data['remainingSessionTime'] = $this->getRemainingSessionTime();
        $data['additionalData'] = $this->additionalData;

        return $data;
    }

    /**
     * 获取登录响应的摘要信息
     *
     * @return array<string, mixed> 摘要信息
     */
    public function getSummary(): array
    {
        return [
            'success' => $this->isSuccess(),
            'logged_in' => $this->isLoggedIn(),
            'session_id' => $this->sessionId ? substr($this->sessionId, 0, 8) . '***' : null,
            'has_user_info' => !empty($this->userInfo),
            'is_first_login' => $this->isFirstLogin,
            'session_expires_at' => $this->sessionExpiresAt,
            'remaining_time' => $this->getRemainingSessionTime(),
            'status_code' => $this->getStatusCode(),
            'error_count' => count($this->getErrors()),
        ];
    }

    /**
     * 获取用于认证的Cookie字符串
     *
     * @return string Cookie字符串
     */
    public function getAuthCookie(): string
    {
        if (empty($this->sessionId)) {
            return '';
        }

        return "SID={$this->sessionId}";
    }

    /**
     * 创建用于HTTP请求的认证头
     *
     * @return array<string, string> 认证头
     */
    public function getAuthHeaders(): array
    {
        $headers = [];

        if (!empty($this->sessionId)) {
            $headers['Cookie'] = $this->getAuthCookie();
        }

        return $headers;
    }
}