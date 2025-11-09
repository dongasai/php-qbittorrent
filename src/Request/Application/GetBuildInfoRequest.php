<?php
declare(strict_types=1);

namespace Dongasai\qBittorrent\Request\Application;

use Dongasai\qBittorrent\Request\AbstractRequest;
use Dongasai\qBittorrent\Contract\ValidationResult;
use Dongasai\qBittorrent\Validation\BasicValidationResult;

/**
 * 获取构建信息请求
 */
class GetBuildInfoRequest extends AbstractRequest
{
    /**
     * {@inheritdoc}
     */
    public function getEndpoint(): string
    {
        return '/buildInfo';
    }

    /**
     * {@inheritdoc}
     */
    public function getMethod(): string
    {
        return 'GET';
    }

    /**
     * {@inheritdoc}
     */
    public function requiresAuthentication(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function validate(): ValidationResult
    {
        return BasicValidationResult::success();
    }

    /**
     * {@inheritdoc}
     */
    public function getSummary(): array
    {
        return [
            'endpoint' => $this->getEndpoint(),
            'method' => $this->getMethod(),
            'requires_auth' => $this->requiresAuthentication(),
            'description' => 'Get qBittorrent build information',
        ];
    }

    /**
     * 创建GetBuildInfoRequest实例
     */
    public static function create(): self
    {
        return new self();
    }
}