<?php
declare(strict_types=1);

namespace PhpQbittorrent\Response;

use PhpQbittorrent\Contract\ResponseInterface as ResponseContract;
use PhpQbittorrent\Validation\BasicValidationResult;
use PhpQbittorrent\Contract\ValidationResult;

/**
 * 抽象响应基类
 *
 * 为所有响应对象提供通用实现和功能
 */
abstract class AbstractResponse implements ResponseContract
{
    /** @var bool 是否成功 */
    protected bool $success;

    /** @var array<string> 错误信息 */
    protected array $errors = [];

    /** @var array<string, string> 响应头 */
    protected array $headers = [];

    /** @var int HTTP状态码 */
    protected int $statusCode = 200;

    /** @var string 原始响应内容 */
    protected string $rawResponse = '';

    /** @var mixed 响应数据 */
    protected mixed $data = null;

    /**
     * 私有构造函数，使用工厂方法创建实例
     *
     * @param bool $success 是否成功
     */
    private function __construct(bool $success)
    {
        $this->success = $success;
    }

    /**
     * 从数组数据创建成功响应对象
     *
     * @param array<string, mixed> $data 响应数据
     * @param array<string, string> $headers 响应头
     * @param int $statusCode 状态码
     * @param string $rawResponse 原始响应内容
     * @return static 响应对象实例
     */
    protected static function success(
        array $data = [],
        array $headers = [],
        int $statusCode = 200,
        string $rawResponse = ''
    ): static {
        $instance = new static(true);
        $instance->data = $data;
        $instance->headers = $headers;
        $instance->statusCode = $statusCode;
        $instance->rawResponse = $rawResponse;

        return $instance;
    }

    /**
     * 从数组数据创建失败响应对象
     *
     * @param array<string> $errors 错误信息
     * @param array<string, string> $headers 响应头
     * @param int $statusCode 状态码
     * @param string $rawResponse 原始响应内容
     * @return static 响应对象实例
     */
    protected static function failure(
        array $errors = [],
        array $headers = [],
        int $statusCode = 400,
        string $rawResponse = ''
    ): static {
        $instance = new static(false);
        $instance->errors = $errors;
        $instance->headers = $headers;
        $instance->statusCode = $statusCode;
        $instance->rawResponse = $rawResponse;

        return $instance;
    }

    /**
     * 从数组数据创建响应对象
     *
     * 子类应该重写此方法以实现具体的创建逻辑
     *
     * @param array<string, mixed> $data 响应数据
     * @return static 响应对象实例
     */
    public static function fromArray(array $data): static
    {
        $success = ($data['success'] ?? true);
        $headers = $data['headers'] ?? [];
        $statusCode = $data['statusCode'] ?? 200;
        $rawResponse = $data['rawResponse'] ?? '';
        $errors = $data['errors'] ?? [];
        $responseData = $data['data'] ?? null;

        if ($success) {
            return static::success($responseData, $headers, $statusCode, $rawResponse);
        } else {
            return static::failure($errors, $headers, $statusCode, $rawResponse);
        }
    }

    /**
     * 检查响应是否成功
     *
     * @return bool 是否成功
     */
    public function isSuccess(): bool
    {
        return $this->success && empty($this->errors);
    }

    /**
     * 获取错误信息
     *
     * @return array<string> 错误信息数组
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * 获取第一个错误信息
     *
     * @return string|null 第一个错误信息，如果没有错误返回null
     */
    public function getFirstError(): ?string
    {
        return $this->errors[0] ?? null;
    }

    /**
     * 获取响应数据
     *
     * @return mixed 响应数据
     */
    public function getData(): mixed
    {
        return $this->data;
    }

    /**
     * 获取HTTP状态码
     *
     * @return int HTTP状态码
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * 获取响应头
     *
     * @return array<string, string> 响应头数组
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * 获取指定头信息
     *
     * @param string $name 头名称
     * @return string|null 头值，如果不存在返回null
     */
    public function getHeader(string $name): ?string
    {
        return $this->headers[$name] ?? null;
    }

    /**
     * 获取原始响应内容
     *
     * @return string 原始响应内容
     */
    public function getRawResponse(): string
    {
        return $this->rawResponse;
    }

    /**
     * 转换为数组格式
     *
     * @return array<string, mixed> 响应数据数组
     */
    public function toArray(): array
    {
        return [
            'success' => $this->isSuccess(),
            'errors' => $this->errors,
            'data' => $this->data,
            'statusCode' => $this->statusCode,
            'headers' => $this->headers,
            'rawResponse' => $this->rawResponse,
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

    /**
     * 设置响应数据
     *
     * @param mixed $data 响应数据
     * @return static 返回自身以支持链式调用
     */
    protected function setData(mixed $data): static
    {
        $this->data = $data;
        return $this;
    }

    /**
     * 添加错误信息
     *
     * @param string $error 错误信息
     * @return static 返回自身以支持链式调用
     */
    protected function addError(string $error): static
    {
        $this->errors[] = $error;
        $this->success = false;
        return $this;
    }

    /**
     * 设置HTTP状态码
     *
     * @param int $statusCode HTTP状态码
     * @return static 返回自身以支持链式调用
     */
    protected function setStatusCode(int $statusCode): static
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    /**
     * 设置响应头
     *
     * @param array<string, string> $headers 响应头
     * @return static 返回自身以支持链式调用
     */
    protected function setHeaders(array $headers): static
    {
        $this->headers = $headers;
        return $this;
    }

    /**
     * 设置原始响应内容
     *
     * @param string $rawResponse 原始响应内容
     * @return static 返回自身以支持链式调用
     */
    protected function setRawResponse(string $rawResponse): static
    {
        $this->rawResponse = $rawResponse;
        return $this;
    }

    /**
     * 验证响应数据
     *
     * 子类可以重写此方法以实现具体的验证逻辑
     *
     * @return ValidationResult 验证结果
     */
    public function validate(): ValidationResult
    {
        return BasicValidationResult::success();
    }
}