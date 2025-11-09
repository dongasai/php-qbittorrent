<?php
declare(strict_types=1);

namespace Dongasai\qBittorrent\Response\Auth;

use Dongasai\qBittorrent\Response\AbstractResponse;
use Dongasai\qBittorrent\Contract\ValidationResult;
use Dongasai\qBittorrent\Validation\BasicValidationResult;

/**
 * 登出响应对象
 *
 * 封装登出请求的响应数据和状态信息
 */
class LogoutResponse extends AbstractResponse
{
    /** @var bool 是否成功清理会话 */
    private bool $sessionCleared = false;

    /** @var bool 是否清理所有会话 */
    private bool $allSessionsCleared = false;

    /** @var int|null 清理的会话数量 */
    private ?int $clearedSessionsCount = null;

    /** @var string|null 登出时间 */
    private ?string $logoutTime = null;

    /** @var array<string, mixed> 额外的响应数据 */
    private array $additionalData = [];

    /**
     * 创建成功的登出响应
     *
     * @param array<string, string> $headers 响应头
     * @param int $statusCode HTTP状态码
     * @param string $rawResponse 原始响应内容
     * @param bool $sessionCleared 是否成功清理会话
     * @param bool $allSessionsCleared 是否清理所有会话
     * @return self 登出响应实例
     */
    public static function success(
        array $headers = [],
        int $statusCode = 200,
        string $rawResponse = '',
        bool $sessionCleared = true,
        bool $allSessionsCleared = false
    ): self {
        $instance = parent::success([], $headers, $statusCode, $rawResponse);
        $instance->sessionCleared = $sessionCleared;
        $instance->allSessionsCleared = $allSessionsCleared;
        $instance->logoutTime = date('Y-m-d H:i:s');

        return $instance;
    }

    /**
     * 创建失败的登出响应
     *
     * @param array<string> $errors 错误信息
     * @param array<string, string> $headers 响应头
     * @param int $statusCode HTTP状态码
     * @param string $rawResponse 原始响应内容
     * @return self 登出响应实例
     */
    public static function failure(
        array $errors = [],
        array $headers = [],
        int $statusCode = 400,
        string $rawResponse = ''
    ): self {
        return parent::failure($errors, $headers, $statusCode, $rawResponse);
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
            $sessionCleared = $responseData['sessionCleared'] ?? true;
            $allSessionsCleared = $responseData['allSessionsCleared'] ?? false;

            $instance = self::success(
                $headers,
                $statusCode,
                $rawResponse,
                $sessionCleared,
                $allSessionsCleared
            );

            // 设置额外数据
            if (isset($responseData['clearedSessionsCount'])) {
                $instance->clearedSessionsCount = $responseData['clearedSessionsCount'];
            }
            if (isset($responseData['logoutTime'])) {
                $instance->logoutTime = $responseData['logoutTime'];
            }
            $instance->additionalData = $responseData['additionalData'] ?? [];

            return $instance;
        } else {
            return self::failure($errors, $headers, $statusCode, $rawResponse);
        }
    }

    /**
     * 检查会话是否已清理
     *
     * @return bool 是否已清理
     */
    public function isSessionCleared(): bool
    {
        return $this->sessionCleared;
    }

    /**
     * 检查是否清理了所有会话
     *
     * @return bool 是否清理了所有会话
     */
    public function areAllSessionsCleared(): bool
    {
        return $this->allSessionsCleared;
    }

    /**
     * 获取清理的会话数量
     *
     * @return int|null 清理的会话数量
     */
    public function getClearedSessionsCount(): ?int
    {
        return $this->clearedSessionsCount;
    }

    /**
     * 获取登出时间
     *
     * @return string|null 登出时间
     */
    public function getLogoutTime(): ?string
    {
        return $this->logoutTime;
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
     * 验证响应数据
     *
     * @return ValidationResult 验证结果
     */
    public function validate(): ValidationResult
    {
        $result = BasicValidationResult::success();

        if ($this->isSuccess()) {
            // 验证登出时间的格式
            if ($this->logoutTime !== null) {
                $logoutTimestamp = strtotime($this->logoutTime);
                if ($logoutTimestamp === false) {
                    $result->addWarning('登出时间格式异常');
                } elseif ($logoutTimestamp > time()) {
                    $result->addWarning('登出时间在未来，可能异常');
                }
            }

            // 验证会话数量
            if ($this->clearedSessionsCount !== null) {
                if ($this->clearedSessionsCount < 0) {
                    $result->addError('清理的会话数量不能为负数');
                } elseif (!$this->allSessionsCleared && $this->clearedSessionsCount > 1) {
                    $result->addWarning('清理了多个会话但未标记为清理所有会话');
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

        $data['sessionCleared'] = $this->sessionCleared;
        $data['allSessionsCleared'] = $this->allSessionsCleared;
        $data['clearedSessionsCount'] = $this->clearedSessionsCount;
        $data['logoutTime'] = $this->logoutTime;
        $data['additionalData'] = $this->additionalData;

        return $data;
    }

    /**
     * 获取登出响应的摘要信息
     *
     * @return array<string, mixed> 摘要信息
     */
    public function getSummary(): array
    {
        return [
            'success' => $this->isSuccess(),
            'session_cleared' => $this->sessionCleared,
            'all_sessions_cleared' => $this->allSessionsCleared,
            'cleared_sessions_count' => $this->clearedSessionsCount,
            'logout_time' => $this->logoutTime,
            'status_code' => $this->getStatusCode(),
            'error_count' => count($this->getErrors()),
        ];
    }

    /**
     * 检查登出是否完全成功
     *
     * @return bool 是否完全成功
     */
    public function isFullySuccessful(): bool
    {
        return $this->isSuccess() && $this->sessionCleared;
    }

    /**
     * 获取登出状态描述
     *
     * @return string 状态描述
     */
    public function getStatusDescription(): string
    {
        if (!$this->isSuccess()) {
            return '登出失败';
        }

        if (!$this->sessionCleared) {
            return '登出成功但会话清理失败';
        }

        if ($this->allSessionsCleared) {
            return '登出成功，已清理所有会话';
        }

        return '登出成功，已清理当前会话';
    }

    /**
     * 获取清理操作的详细信息
     *
     * @return array<string, mixed> 清理操作详情
     */
    public function getClearedSessionDetails(): array
    {
        return [
            'current_session_cleared' => $this->sessionCleared,
            'all_sessions_cleared' => $this->allSessionsCleared,
            'cleared_count' => $this->clearedSessionsCount,
            'logout_time' => $this->logoutTime,
            'success' => $this->isFullySuccessful(),
        ];
    }
}