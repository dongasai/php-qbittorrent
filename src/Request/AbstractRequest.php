<?php
declare(strict_types=1);

namespace Dongasai\qBittorrent\Request;

use Dongasai\qBittorrent\Contract\RequestInterface as RequestContract;
use Dongasai\qBittorrent\Validation\BasicValidationResult;
use Dongasai\qBittorrent\Contract\ValidationResult;

/**
 * 抽象请求基类
 *
 * 为所有请求对象提供通用实现和功能
 */
abstract class AbstractRequest implements RequestContract
{
    /** @var array<string, mixed> 请求数据 */
    protected array $data = [];

    /** @var array<string, string> 请求头 */
    protected array $headers = [];

    /** @var string 请求方法 */
    protected string $method = 'POST';

    /** @var string API端点 */
    protected string $endpoint;

    /** @var bool 是否需要认证 */
    protected bool $requiresAuth = true;

    /**
     * 构造函数
     *
     * @param array<string, mixed> $data 初始数据
     */
    protected function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * 获取请求的唯一标识
     *
     * @return string 请求唯一标识
     */
    public function getRequestId(): string
    {
        return md5(serialize([
            static::class,
            $this->endpoint,
            $this->method,
            $this->data
        ]));
    }

    /**
     * 获取请求方法类型
     *
     * @return string HTTP方法
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * 设置请求方法
     *
     * @param string $method HTTP方法
     * @return static 返回自身以支持链式调用
     */
    protected function setMethod(string $method): static
    {
        $this->method = strtoupper($method);
        return $this;
    }

    /**
     * 获取请求的API端点
     *
     * @return string API端点路径
     */
    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    /**
     * 设置API端点
     *
     * @param string $endpoint API端点路径
     * @return static 返回自身以支持链式调用
     */
    protected function setEndpoint(string $endpoint): static
    {
        $this->endpoint = $endpoint;
        return $this;
    }

    /**
     * 获取请求头
     *
     * @return array<string, string> 请求头数组
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * 设置请求头
     *
     * @param array<string, string> $headers 请求头
     * @return static 返回自身以支持链式调用
     */
    public function setHeaders(array $headers): static
    {
        $this->headers = $headers;
        return $this;
    }

    /**
     * 添加请求头
     *
     * @param string $name 头名称
     * @param string $value 头值
     * @return static 返回自身以支持链式调用
     */
    public function addHeader(string $name, string $value): static
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * 检查请求是否需要认证
     *
     * @return bool 是否需要认证
     */
    public function requiresAuthentication(): bool
    {
        return $this->requiresAuth;
    }

    /**
     * 设置是否需要认证
     *
     * @param bool $requiresAuth 是否需要认证
     * @return static 返回自身以支持链式调用
     */
    protected function setRequiresAuthentication(bool $requiresAuth): static
    {
        $this->requiresAuth = $requiresAuth;
        return $this;
    }

    /**
     * 转换为数组格式
     *
     * @return array<string, mixed> 请求数据数组
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * JSON序列化
     *
     * @return array<string, mixed> 序列化数据
     */
    public function jsonSerialize(): array
    {
        return [
            'endpoint' => $this->endpoint,
            'method' => $this->method,
            'data' => $this->toArray(),
            'headers' => $this->headers,
            'requiresAuth' => $this->requiresAuth,
        ];
    }

    /**
     * 验证请求参数
     *
     * 子类应该重写此方法以实现具体的验证逻辑
     *
     * @return ValidationResult 验证结果
     */
    public function validate(): ValidationResult
    {
        return BasicValidationResult::success();
    }

    /**
     * 获取请求数据值
     *
     * @param string $key 键名
     * @param mixed $default 默认值
     * @return mixed 数据值
     */
    protected function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * 设置请求数据值
     *
     * @param string $key 键名
     * @param mixed $value 值
     * @return static 返回自身以支持链式调用
     */
    protected function set(string $key, mixed $value): static
    {
        $this->data[$key] = $value;
        return $this;
    }

    /**
     * 检查请求数据是否存在指定键
     *
     * @param string $key 键名
     * @return bool 是否存在
     */
    protected function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * 移除请求数据指定键
     *
     * @param string $key 键名
     * @return static 返回自身以支持链式调用
     */
    protected function remove(string $key): static
    {
        unset($this->data[$key]);
        return $this;
    }

    /**
     * 清空所有请求数据
     *
     * @return static 返回自身以支持链式调用
     */
    protected function clear(): static
    {
        $this->data = [];
        return $this;
    }
}