<?php
declare(strict_types=1);

namespace Dongasai\qBittorrent\Request\Auth;

use Dongasai\qBittorrent\Request\AbstractRequest;
use Dongasai\qBittorrent\Contract\ValidationResult;
use Dongasai\qBittorrent\Validation\BasicValidationResult;
use Dongasai\qBittorrent\Exception\ValidationException;

/**
 * 登录请求对象
 *
 * 用于封装用户登录请求的所有参数和验证逻辑
 */
class LoginRequest extends AbstractRequest
{
    /** @var string 用户名 */
    private string $username;

    /** @var string 密码 */
    private string $password;

    /** @var string|null 来源URL */
    private ?string $origin = null;

    /** @var int 最大密码长度 */
    private const MAX_PASSWORD_LENGTH = 255;

    /** @var int 最大用户名长度 */
    private const MAX_USERNAME_LENGTH = 255;

    /**
     * 私有构造函数
     *
     * @param string $username 用户名
     * @param string $password 密码
     */
    private function __construct(string $username, string $password)
    {
        $this->username = $username;
        $this->password = $password;

        parent::__construct([
            'username' => $username,
            'password' => $password
        ]);

        $this->setEndpoint('/auth/login')
             ->setMethod('POST')
             ->setRequiresAuthentication(false);
    }

    /**
     * 创建Builder实例
     *
     * @return LoginRequestBuilder Builder实例
     */
    public static function builder(): LoginRequestBuilder
    {
        return new LoginRequestBuilder();
    }

    /**
     * 直接创建登录请求实例
     *
     * @param string $username 用户名
     * @param string $password 密码
     * @return self 登录请求实例
     * @throws ValidationException 如果参数无效
     */
    public static function create(string $username, string $password): self
    {
        $request = new self($username, $password);
        $validation = $request->validate();

        if (!$validation->isValid()) {
            throw ValidationException::fromValidationResult(
                $validation,
                'Login request validation failed'
            );
        }

        return $request;
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
     * 获取密码
     *
     * @return string 密码
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * 获取来源URL
     *
     * @return string|null 来源URL
     */
    public function getOrigin(): ?string
    {
        return $this->origin;
    }

    /**
     * 设置来源URL
     *
     * @param string $origin 来源URL
     * @return self 返回自身以支持链式调用
     */
    public function setOrigin(string $origin): self
    {
        $this->origin = $origin;
        $this->set('origin', $origin);
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

        // 验证用户名
        if (empty(trim($this->username))) {
            $result->addError('用户名不能为空');
        } elseif (strlen($this->username) > self::MAX_USERNAME_LENGTH) {
            $result->addError('用户名长度不能超过 ' . self::MAX_USERNAME_LENGTH . ' 个字符');
        } elseif (!mb_check_encoding($this->username, 'UTF-8')) {
            $result->addError('用户名包含无效的字符编码');
        }

        // 验证密码
        if (empty(trim($this->password))) {
            $result->addError('密码不能为空');
        } elseif (strlen($this->password) > self::MAX_PASSWORD_LENGTH) {
            $result->addError('密码长度不能超过 ' . self::MAX_PASSWORD_LENGTH . ' 个字符');
        } elseif (!mb_check_encoding($this->password, 'UTF-8')) {
            $result->addError('密码包含无效的字符编码');
        }

        // 验证来源URL（如果提供）
        if ($this->origin !== null) {
            if (empty(trim($this->origin))) {
                $result->addError('来源URL不能为空字符串');
            } elseif (!filter_var($this->origin, FILTER_VALIDATE_URL)) {
                $result->addError('来源URL格式无效');
            }
        }

        // 安全性检查
        if (preg_match('/[<>"\']/', $this->username)) {
            $result->addWarning('用户名包含可能不安全的字符');
        }

        return $result;
    }

    /**
     * 获取请求头
     *
     * @return array<string, string> 请求头数组
     */
    public function getHeaders(): array
    {
        $headers = parent::getHeaders();

        // 添加Referer或Origin头（如果提供）
        if ($this->origin !== null) {
            $headers['Origin'] = $this->origin;
            $headers['Referer'] = $this->origin;
        } else {
            // 默认添加一个合理的Referer
            $headers['Referer'] = '/';
        }

        return $headers;
    }

    /**
     * 转换为数组格式（用于HTTP请求）
     *
     * @return array<string, mixed> 请求数据数组
     */
    public function toArray(): array
    {
        return [
            'username' => $this->username,
            'password' => $this->password,
        ];
    }

    /**
     * 获取登录请求的摘要信息
     *
     * @return array<string, mixed> 摘要信息
     */
    public function getSummary(): array
    {
        return [
            'username' => $this->username,
            'password_length' => strlen($this->password),
            'origin' => $this->origin,
            'endpoint' => $this->getEndpoint(),
            'method' => $this->getMethod(),
            'requires_auth' => $this->requiresAuthentication(),
        ];
    }
}

/**
 * 登录请求构建器
 *
 * 使用Builder模式创建LoginRequest实例
 */
class LoginRequestBuilder
{
    private ?string $username = null;
    private ?string $password = null;
    private ?string $origin = null;

    /**
     * 设置用户名
     *
     * @param string $username 用户名
     * @return self 返回自身以支持链式调用
     */
    public function username(string $username): self
    {
        $this->username = $username;
        return $this;
    }

    /**
     * 设置密码
     *
     * @param string $password 密码
     * @return self 返回自身以支持链式调用
     */
    public function password(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    /**
     * 设置来源URL
     *
     * @param string $origin 来源URL
     * @return self 返回自身以支持链式调用
     */
    public function origin(string $origin): self
    {
        $this->origin = $origin;
        return $this;
    }

    /**
     * 构建LoginRequest实例
     *
     * @return LoginRequest 登录请求实例
     * @throws ValidationException 如果参数无效
     */
    public function build(): LoginRequest
    {
        if ($this->username === null) {
            throw ValidationException::missingParameter('username');
        }

        if ($this->password === null) {
            throw ValidationException::missingParameter('password');
        }

        $request = new LoginRequest($this->username, $this->password);

        if ($this->origin !== null) {
            $request->setOrigin($this->origin);
        }

        $validation = $request->validate();
        if (!$validation->isValid()) {
            throw ValidationException::fromValidationResult(
                $validation,
                'Login request validation failed'
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
        $result = \Dongasai\qBittorrent\Validation\BasicValidationResult::success();

        if ($this->username === null) {
            $result->addError('用户名是必需的');
        }

        if ($this->password === null) {
            $result->addError('密码是必需的');
        }

        return $result;
    }
}