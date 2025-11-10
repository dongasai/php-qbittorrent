<?php
declare(strict_types=1);

namespace PhpQbittorrent\Validation;

use PhpQbittorrent\Contract\ValidationResult as ValidationResultContract;

/**
 * 基础验证结果实现
 *
 * 提供验证结果的标准实现
 */
class BasicValidationResult implements ValidationResultContract
{
    /** @var array<string> 错误信息 */
    private array $errors = [];

    /** @var array<string> 警告信息 */
    private array $warnings = [];

    /**
     * 创建验证通过的结果
     *
     * @return static 验证通过的结果
     */
    public static function success(): static
    {
        return new static();
    }

    /**
     * 创建验证失败的结果
     *
     * @param array<string> $errors 错误信息
     * @param array<string> $warnings 警告信息
     * @return static 验证失败的结果
     */
    public static function failure(array $errors = [], array $warnings = []): static
    {
        $result = new static();
        $result->errors = $errors;
        $result->warnings = $warnings;
        return $result;
    }

    public function isValid(): bool
    {
        return empty($this->errors);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getFirstError(): ?string
    {
        return $this->errors[0] ?? null;
    }

    public function getWarnings(): array
    {
        return $this->warnings;
    }

    public function getFirstWarning(): ?string
    {
        return $this->warnings[0] ?? null;
    }

    public function addError(string $error): static
    {
        $this->errors[] = $error;
        return $this;
    }

    public function addWarning(string $warning): static
    {
        $this->warnings[] = $warning;
        return $this;
    }

    public function merge(ValidationResultContract $other): static
    {
        $this->errors = array_merge($this->errors, $other->getErrors());
        $this->warnings = array_merge($this->warnings, $other->getWarnings());
        return $this;
    }

    public function toArray(): array
    {
        return [
            'valid' => $this->isValid(),
            'errors' => $this->errors,
            'warnings' => $this->warnings,
        ];
    }
}