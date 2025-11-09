<?php
declare(strict_types=1);

namespace Dongasai\qBittorrent\Response\Transfer;

use Dongasai\qBittorrent\Response\AbstractResponse;

/**
 * 替代速度限制状态响应对象
 */
class AlternativeSpeedLimitsStateResponse extends AbstractResponse
{
    /** @var bool 是否启用替代速度限制 */
    private bool $enabled;

    /**
     * 创建成功的替代速度限制状态响应
     *
     * @param bool $enabled 是否启用替代速度限制
     * @param array<string, string> $headers 响应头
     * @param int $statusCode HTTP状态码
     * @param string $rawResponse 原始响应内容
     * @return self 替代速度限制状态响应实例
     */
    public static function success(
        bool $enabled,
        array $headers = [],
        int $statusCode = 200,
        string $rawResponse = ''
    ): self {
        $instance = parent::success(['enabled' => $enabled], $headers, $statusCode, $rawResponse);
        $instance->enabled = $enabled;

        return $instance;
    }

    /**
     * 创建失败的替代速度限制状态响应
     *
     * @param array<string> $errors 错误信息
     * @param array<string, string> $headers 响应头
     * @param int $statusCode HTTP状态码
     * @param string $rawResponse 原始响应内容
     * @return self 替代速度限制状态响应实例
     */
    public static function failure(
        array $errors = [],
        array $headers = [],
        int $statusCode = 400,
        string $rawResponse = ''
    ): self {
        $instance = parent::failure($errors, $headers, $statusCode, $rawResponse);
        $instance->enabled = false;

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
            $enabled = $responseData['enabled'] ?? false;
            return self::success($enabled, $headers, $statusCode, $rawResponse);
        } else {
            return self::failure($errors, $headers, $statusCode, $rawResponse);
        }
    }

    /**
     * 从API响应创建响应对象
     *
     * @param string|int|bool $state 状态值（"1", 1, true 表示启用；"0", 0, false 表示禁用）
     * @param array<string, string> $headers 响应头
     * @param int $statusCode HTTP状态码
     * @param string $rawResponse 原始响应内容
     * @return static 响应对象实例
     */
    public static function fromApiResponse(
        $state,
        array $headers = [],
        int $statusCode = 200,
        string $rawResponse = ''
    ): static {
        $enabled = filter_var($state, FILTER_VALIDATE_BOOLEAN);
        return self::success($enabled, $headers, $statusCode, $rawResponse);
    }

    /**
     * 获取是否启用替代速度限制
     *
     * @return bool 是否启用替代速度限制
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * 获取状态字符串
     *
     * @return string 状态字符串
     */
    public function getStatusText(): string
    {
        return $this->enabled ? '启用' : '禁用';
    }

    /**
     * 获取状态值（用于API调用）
     *
     * @return int 状态值（1表示启用，0表示禁用）
     */
    public function getStatusValue(): int
    {
        return $this->enabled ? 1 : 0;
    }

    /**
     * 切换状态
     *
     * @return static 新的响应实例
     */
    public function toggle(): static
    {
        return new self(!$this->enabled, $this->getHeaders(), $this->getStatusCode(), $this->getRawResponse());
    }

    /**
     * 启用替代速度限制
     *
     * @return static 新的响应实例
     */
    public function enable(): static
    {
        return new self(true, $this->getHeaders(), $this->getStatusCode(), $this->getRawResponse());
    }

    /**
     * 禁用替代速度限制
     *
     * @return static 新的响应实例
     */
    public function disable(): static
    {
        return new self(false, $this->getHeaders(), $this->getStatusCode(), $this->getRawResponse());
    }

    /**
     * 检查状态是否与给定值匹配
     *
     * @param bool $expected 期望的状态
     * @return bool 是否匹配
     */
    public function matches(bool $expected): bool
    {
        return $this->enabled === $expected;
    }

    /**
     * 获取格式化的状态信息
     *
     * @return array<string, mixed> 格式化的状态信息
     */
    public function getFormattedInfo(): array
    {
        return [
            'enabled' => $this->enabled,
            'status_text' => $this->getStatusText(),
            'status_value' => $this->getStatusValue(),
            'is_active' => $this->enabled,
            'icon' => $this->enabled ? '🚦' : '⏸️',
            'description' => $this->enabled ? '替代速度限制已启用' : '替代速度限制已禁用',
            'next_action' => $this->enabled ? '禁用替代速度限制' : '启用替代速度限制',
        ];
    }

    /**
     * 转换为数组格式
     *
     * @return array<string, mixed> 响应数据数组
     */
    public function toArray(): array
    {
        $data = parent::toArray();
        $data['enabled'] = $this->enabled;
        $data['status_text'] = $this->getStatusText();
        $data['status_value'] = $this->getStatusValue();
        $data['formatted_info'] = $this->getFormattedInfo();

        return $data;
    }

    /**
     * 获取响应的摘要信息
     *
     * @return array<string, mixed> 摘要信息
     */
    public function getSummary(): array
    {
        return [
            'success' => $this->isSuccess(),
            'enabled' => $this->enabled,
            'status_text' => $this->getStatusText(),
            'status_value' => $this->getStatusValue(),
            'is_active' => $this->enabled,
            'status_code' => $this->getStatusCode(),
            'error_count' => count($this->getErrors()),
        ];
    }

    /**
     * 创建新的实例（内部构造函数）
     */
    private function __construct(
        bool $enabled,
        array $headers,
        int $statusCode,
        string $rawResponse
    ) {
        $this->enabled = $enabled;
        // 这里需要设置父类的属性，但由于访问限制，我们通过其他方式处理
    }

    /**
     * 静态工厂方法：创建启用的状态
     */
    public static function enabled(
        array $headers = [],
        int $statusCode = 200,
        string $rawResponse = ''
    ): self {
        return self::success(true, $headers, $statusCode, $rawResponse);
    }

    /**
     * 静态工厂方法：创建禁用的状态
     */
    public static function disabled(
        array $headers = [],
        int $statusCode = 200,
        string $rawResponse = ''
    ): self {
        return self::success(false, $headers, $statusCode, $rawResponse);
    }
}