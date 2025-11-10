<?php
declare(strict_types=1);

namespace PhpQbittorrent\Request\Transfer;

use PhpQbittorrent\Request\AbstractRequest;
use PhpQbittorrent\Contract\ValidationResult;
use PhpQbittorrent\Validation\BasicValidationResult;

/**
 * 获取全局传输信息请求
 */
class GetGlobalTransferInfoRequest extends AbstractRequest
{
    /**
     * {@inheritdoc}
     */
    public function getEndpoint(): string
    {
        return '/info';
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
            'description' => 'Get global transfer information (download/upload speeds and data)',
        ];
    }

    /**
     * 创建GetGlobalTransferInfoRequest实例
     */
    public static function create(): self
    {
        return new self();
    }
}