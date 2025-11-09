<?php
declare(strict_types=1);

namespace PhpQbittorrent\Exception;

use Throwable;

/**
 * 认证异常
 *
 * 处理qBittorrent认证相关错误
 */
class AuthenticationException extends ClientException
{
    private ?string $username = null;
    private ?string $reason = null;

    /**
     * @param string $message 错误消息
     * @param string $errorCode 错误代码
     * @param array $errorDetails 错误详情
     * @param string|null $username 用户名
     * @param string|null $reason 失败原因
     * @param Throwable|null $previous 前一个异常
     */
    public function __construct(
        string $message,
        string $errorCode = 'AUTH_FAILED',
        array $errorDetails = [],
        ?string $username = null,
        ?string $reason = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $errorCode, $errorDetails, 401, $previous);

        $this->username = $username;
        $this->reason = $reason;

        // 添加认证相关错误详情
        if ($username !== null) {
            $this->addErrorDetail('username', $username);
        }
        if ($reason !== null) {
            $this->addErrorDetail('reason', $reason);
        }
    }

    public function isAuthenticationError(): bool
    {
        return true;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function isInvalidCredentials(): bool
    {
        return $this->getErrorCode() === 'INVALID_CREDENTIALS' ||
               str_contains(strtolower($this->getMessage()), 'invalid') ||
               str_contains(strtolower($this->getMessage()), 'wrong');
    }

    public function isSessionExpired(): bool
    {
        return $this->getErrorCode() === 'SESSION_EXPIRED' ||
               str_contains(strtolower($this->getMessage()), 'expired') ||
               str_contains(strtolower($this->getMessage()), 'session');
    }

    public function isAccessDenied(): bool
    {
        return $this->getErrorCode() === 'ACCESS_DENIED' ||
               str_contains(strtolower($this->getMessage()), 'access denied') ||
               str_contains(strtolower($this->getMessage()), 'forbidden');
    }

    /**
     * 创建无效凭据异常
     */
    public static function invalidCredentials(?string $username = null): self
    {
        return new self(
            '认证失败: 用户名或密码无效',
            'INVALID_CREDENTIALS',
            [],
            $username,
            '提供的用户名和密码组合不正确'
        );
    }

    /**
     * 创建会话过期异常
     */
    public static function sessionExpired(?string $username = null): self
    {
        return new self(
            '认证失败: 会话已过期',
            'SESSION_EXPIRED',
            [],
            $username,
            '认证会话已过期，需要重新登录'
        );
    }

    /**
     * 创建访问被拒绝异常
     */
    public static function accessDenied(string $resource = ''): self
    {
        $message = '访问被拒绝';
        if (!empty($resource)) {
            $message .= ": {$resource}";
        }

        return new self(
            $message,
            'ACCESS_DENIED',
            ['resource' => $resource],
            null,
            '没有访问该资源的权限'
        );
    }

    /**
     * 创建认证令牌缺失异常
     */
    public static function tokenMissing(): self
    {
        return new self(
            '认证失败: 缺少认证令牌',
            'TOKEN_MISSING',
            [],
            null,
            '请求中缺少必要的认证信息'
        );
    }

    /**
     * 创建认证令牌无效异常
     */
    public static function tokenInvalid(string $token): self
    {
        return new self(
            '认证失败: 认证令牌无效',
            'TOKEN_INVALID',
            ['token_length' => strlen($token)],
            null,
            '提供的认证令牌无效或已损坏'
        );
    }
}