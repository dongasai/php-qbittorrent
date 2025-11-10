<?php
declare(strict_types=1);

namespace PhpQbittorrent\Contract;

/**
 * 验证结果接口
 *
 * 统一的验证结果接口，用于请求/响应参数验证
 */
interface ValidationResult
{
    /**
     * 检查验证是否通过
     *
     * @return bool 是否通过验证
     */
    public function isValid(): bool;

    /**
     * 获取错误信息
     *
     * @return array<string> 错误信息数组
     */
    public function getErrors(): array;

    /**
     * 获取第一个错误信息
     *
     * @return string|null 第一个错误信息，如果没有错误返回null
     */
    public function getFirstError(): ?string;

    /**
     * 获取警告信息
     *
     * @return array<string> 警告信息数组
     */
    public function getWarnings(): array;

    /**
     * 获取第一个警告信息
     *
     * @return string|null 第一个警告信息，如果没有警告返回null
     */
    public function getFirstWarning(): ?string;

    /**
     * 添加错误信息
     *
     * @param string $error 错误信息
     * @return static 返回自身以支持链式调用
     */
    public function addError(string $error): static;

    /**
     * 添加警告信息
     *
     * @param string $warning 警告信息
     * @return static 返回自身以支持链式调用
     */
    public function addWarning(string $warning): static;

    /**
     * 合并另一个验证结果
     *
     * @param ValidationResult $other 另一个验证结果
     * @return static 返回自身以支持链式调用
     */
    public function merge(ValidationResult $other): static;

    /**
     * 转换为数组格式
     *
     * @return array<string, mixed> 验证结果数组
     */
    public function toArray(): array;
}