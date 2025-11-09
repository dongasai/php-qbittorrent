<?php
declare(strict_types=1);

namespace Dongasai\qBittorrent\Request\Transfer;

use Dongasai\qBittorrent\Request\AbstractRequest;
use Dongasai\qBittorrent\Contract\ValidationResult;
use Dongasai\qBittorrent\Validation\BasicValidationResult;

/**
 * 获取替代速度限制状态请求
 */
class GetAlternativeSpeedLimitsStateRequest extends AbstractRequest
{
    /**
     * {@inheritdoc}
     */
    public function getEndpoint(): string
    {
        return '/speedLimitsMode';
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
            'description' => 'Get alternative speed limits state',
        ];
    }

    /**
     * 创建GetAlternativeSpeedLimitsStateRequest实例
     */
    public static function create(): self
    {
        return new self();
    }
}