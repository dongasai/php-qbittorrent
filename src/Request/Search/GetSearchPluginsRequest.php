<?php
declare(strict_types=1);

namespace PhpQbittorrent\Request\Search;

use PhpQbittorrent\Request\AbstractRequest;
use PhpQbittorrent\Contract\ValidationResult;
use PhpQbittorrent\Validation\BasicValidationResult;

/**
 * 获取搜索插件请求
 */
class GetSearchPluginsRequest extends AbstractRequest
{
    /**
     * 创建获取搜索插件请求
     *
     * @return self
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * 获取请求端点
     *
     * @return string
     */
    public function getEndpoint(): string
    {
        return '/plugins';
    }

    /**
     * 获取请求方法
     *
     * @return string
     */
    public function getMethod(): string
    {
        return 'GET';
    }

    /**
     * 获取请求参数
     *
     * @return array<string, mixed>
     */
    public function getParameters(): array
    {
        return [];
    }

    /**
     * 获取请求头
     *
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return [];
    }

    /**
     * 验证请求参数
     *
     * @return ValidationResult
     */
    public function validate(): ValidationResult
    {
        return new BasicValidationResult(true);
    }

    /**
     * 获取请求摘要
     *
     * @return array<string, mixed>
     */
    public function getSummary(): array
    {
        return [
            'endpoint' => $this->getEndpoint(),
            'method' => $this->getMethod(),
            'parameters' => $this->getParameters(),
        ];
    }
}