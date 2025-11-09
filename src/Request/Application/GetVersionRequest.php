<?php
declare(strict_types=1);

namespace Dongasai\qBittorrent\Request\Application;

use Dongasai\qBittorrent\Request\AbstractRequest;
use Dongasai\qBittorrent\Contract\ValidationResult;
use Dongasai\qBittorrent\Validation\BasicValidationResult;

/**
 * 获取应用版本请求
 */
class GetVersionRequest extends AbstractRequest
{
    /**
     * {@inheritdoc}
     */
    public function getEndpoint(): string
    {
        return '/version';
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
            'description' => 'Get qBittorrent application version',
        ];
    }

    /**
     * 创建GetVersionRequest实例
     */
    public static function create(): self
    {
        return new self();
    }
}