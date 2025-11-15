<?php
declare(strict_types=1);

namespace PhpQbittorrent\Request\Auth;

use PhpQbittorrent\Request\AbstractRequest;
use PhpQbittorrent\Contract\ValidationResult;
use PhpQbittorrent\Validation\BasicValidationResult;
use PhpQbittorrent\Exception\ValidationException;

/**
 * 登出请求对象
 *
 * 用于封装用户登出请求的参数和验证逻辑
 */
class LogoutRequest extends AbstractRequest
{
    /** @var string|null 会话ID */
    private ?string $sessionId = null;

    /** @var bool 是否清理所有会话 */
    private bool $clearAllSessions = false;

    /**
     * 私有构造函数
     */
    private function __construct()
    {
        parent::__construct([]);

        $this->setEndpoint('/logout')
             ->setMethod('POST')
             ->setRequiresAuthentication(true);
    }

    /**
     * 创建Builder实例
     *
     * @return LogoutRequestBuilder Builder实例
     */
    public static function builder(): LogoutRequestBuilder
    {
        return new LogoutRequestBuilder();
    }

    /**
     * 直接创建登出请求实例
     *
     * @return self 登出请求实例
     */
    public static function create(): self
    {
        return new self();
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
     * 设置会话ID
     *
     * @param string $sessionId 会话ID
     * @return self 返回自身以支持链式调用
     */
    public function setSessionId(string $sessionId): self
    {
        $this->sessionId = $sessionId;
        return $this;
    }

    /**
     * 是否清理所有会话
     *
     * @return bool 是否清理所有会话
     */
    public function shouldClearAllSessions(): bool
    {
        return $this->clearAllSessions;
    }

    /**
     * 设置是否清理所有会话
     *
     * @param bool $clearAllSessions 是否清理所有会话
     * @return self 返回自身以支持链式调用
     */
    public function setClearAllSessions(bool $clearAllSessions): self
    {
        $this->clearAllSessions = $clearAllSessions;
        return $this;
    }

    /**
     * 验证请求参数
     *
     * @return ValidationResult 验证结果
     */
    public function validate(): ValidationResult
    {
        $result = BasicValidationResult::success();

        // 登出请求通常不需要特殊验证
        // 但可以添加一些业务逻辑验证

        if ($this->sessionId !== null) {
            if (empty(trim($this->sessionId))) {
                $result->addError('会话ID不能为空字符串');
            }
        }

        return $result;
    }

    /**
     * 转换为数组格式（用于HTTP请求）
     *
     * @return array<string, mixed> 请求数据数组
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->clearAllSessions) {
            $data['clearAll'] = 'true';
        }

        return $data;
    }

    /**
     * 获取请求头
     *
     * @return array<string, string> 请求头数组
     */
    public function getHeaders(): array
    {
        $headers = parent::getHeaders();

        // 添加默认的Referer头
        $headers['Referer'] = '/';

        return $headers;
    }

    /**
     * 获取登出请求的摘要信息
     *
     * @return array<string, mixed> 摘要信息
     */
    public function getSummary(): array
    {
        return [
            'session_id' => $this->sessionId ? '***' : null,
            'clear_all_sessions' => $this->clearAllSessions,
            'endpoint' => $this->getEndpoint(),
            'method' => $this->getMethod(),
            'requires_auth' => $this->requiresAuthentication(),
        ];
    }
}

/**
 * 登出请求构建器
 *
 * 使用Builder模式创建LogoutRequest实例
 */
class LogoutRequestBuilder
{
    private ?string $sessionId = null;
    private bool $clearAllSessions = false;

    /**
     * 设置会话ID
     *
     * @param string $sessionId 会话ID
     * @return self 返回自身以支持链式调用
     */
    public function sessionId(string $sessionId): self
    {
        $this->sessionId = $sessionId;
        return $this;
    }

    /**
     * 设置是否清理所有会话
     *
     * @param bool $clearAllSessions 是否清理所有会话
     * @return self 返回自身以支持链式调用
     */
    public function clearAllSessions(bool $clearAllSessions = true): self
    {
        $this->clearAllSessions = $clearAllSessions;
        return $this;
    }

    /**
     * 构建LogoutRequest实例
     *
     * @return LogoutRequest 登出请求实例
     * @throws ValidationException 如果参数无效
     */
    public function build(): LogoutRequest
    {
        $request = new LogoutRequest();

        if ($this->sessionId !== null) {
            $request->setSessionId($this->sessionId);
        }

        if ($this->clearAllSessions) {
            $request->setClearAllSessions(true);
        }

        $validation = $request->validate();
        if (!$validation->isValid()) {
            throw ValidationException::fromValidationResult(
                $validation,
                'Logout request validation failed'
            );
        }

        return $request;
    }

    /**
     * 验证当前配置
     *
     * @return ValidationResult 验证结果
     */
    public function validate(): ValidationResult
    {
        return \PhpQbittorrent\Validation\BasicValidationResult::success();
    }
}