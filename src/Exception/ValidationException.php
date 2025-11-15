<?php
declare(strict_types=1);

namespace PhpQbittorrent\Exception;

use Throwable;

/**
 * 验证异常
 *
 * 处理参数验证和业务逻辑验证错误
 */
class ValidationException extends ClientException
{
    private array $validationErrors = [];
    private ?string $field = null;

    /**
     * @param string $message 错误消息
     * @param string $errorCode 错误代码
     * @param array $validationErrors 验证错误详情
     * @param string|null $field 验证失败的字段
     * @param array $errorDetails 其他错误详情
     * @param Throwable|null $previous 前一个异常
     */
    public function __construct(
        string $message,
        string $errorCode = 'VALIDATION_ERROR',
        array $validationErrors = [],
        ?string $field = null,
        array $errorDetails = [],
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $errorCode, $errorDetails, null, $previous);

        $this->validationErrors = $validationErrors;
        $this->field = $field;

        // 添加验证相关错误详情
        if (!empty($validationErrors)) {
            $this->addErrorDetail('validation_errors', $validationErrors);
        }
        if ($field !== null) {
            $this->addErrorDetail('field', $field);
        }
    }

    public function isValidationFailure(): bool
    {
        return true;
    }

    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    public function getField(): ?string
    {
        return $this->field;
    }

    public function hasField(string $field): bool
    {
        return $this->field === $field || isset($this->validationErrors[$field]);
    }

    public function getErrorForField(string $field): ?string
    {
        return $this->validationErrors[$field] ?? null;
    }

    /**
     * 创建配置验证异常
     */
    public static function invalidConfig(array $errors): self
    {
        return new self(
            '配置验证失败',
            'INVALID_CONFIG',
            $errors,
            null,
            ['config_errors' => $errors]
        );
    }

    /**
     * 创建参数验证异常
     */
    public static function invalidParameter(
        string $parameter,
        string $reason,
        mixed $value = null
    ): self {
        $message = "参数 '{$parameter}' 无效: {$reason}";
        $errors = [$parameter => $reason];
        $details = ['parameter' => $parameter];

        if ($value !== null) {
            $details['value'] = $value;
        }

        return new self(
            $message,
            'INVALID_PARAMETER',
            $errors,
            $parameter,
            $details
        );
    }

    /**
     * 创建必需参数缺失异常
     */
    public static function missingParameter(string $parameter): self
    {
        return new self(
            "缺少必需参数: {$parameter}",
            'MISSING_PARAMETER',
            [$parameter => '该参数是必需的'],
            $parameter,
            ['required_parameter' => $parameter]
        );
    }

    /**
     * 从验证结果创建异常
     */
    public static function fromValidationResult(
        \PhpQbittorrent\Contract\ValidationResult $validationResult,
        string $message = 'Validation failed'
    ): self {
        return new self(
            $message,
            'VALIDATION_ERROR',
            $validationResult->getErrors(),
            null,
            ['validation_errors' => $validationResult->getErrors()]
        );
    }

    /**
     * 创建参数格式无效异常
     */
    public static function invalidFormat(
        string $parameter,
        string $expectedFormat,
        mixed $actualValue = null
    ): self {
        $message = "参数 '{$parameter}' 格式无效，期望: {$expectedFormat}";

        $details = [
            'parameter' => $parameter,
            'expected_format' => $expectedFormat
        ];

        if ($actualValue !== null) {
            $details['actual_value'] = $actualValue;
        }

        return new self(
            $message,
            'INVALID_FORMAT',
            [$parameter => "格式应为: {$expectedFormat}"],
            $parameter,
            $details
        );
    }

    /**
     * 创建值超出范围异常
     */
    public static function outOfRange(
        string $parameter,
        mixed $value,
        mixed $min = null,
        mixed $max = null
    ): self {
        $message = "参数 '{$parameter}' 值超出范围";
        $details = [
            'parameter' => $parameter,
            'value' => $value
        ];

        if ($min !== null) {
            $details['min'] = $min;
            $message .= "，最小值: {$min}";
        }

        if ($max !== null) {
            $details['max'] = $max;
            $message .= "，最大值: {$max}";
        }

        $errors = [$parameter => $message];

        return new self(
            $message,
            'OUT_OF_RANGE',
            $errors,
            $parameter,
            $details
        );
    }

    /**
     * 创建枚举值无效异常
     */
    public static function invalidEnumValue(
        string $parameter,
        mixed $value,
        array $allowedValues
    ): self {
        $message = "参数 '{$parameter}' 的值无效，允许的值: " . implode(', ', $allowedValues);

        return new self(
            $message,
            'INVALID_ENUM_VALUE',
            [$parameter => $message],
            $parameter,
            [
                'parameter' => $parameter,
                'value' => $value,
                'allowed_values' => $allowedValues
            ]
        );
    }
}