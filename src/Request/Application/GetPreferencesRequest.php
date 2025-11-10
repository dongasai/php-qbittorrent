<?php
declare(strict_types=1);

namespace PhpQbittorrent\Request\Application;

use PhpQbittorrent\Request\AbstractRequest;

/**
 * 获取应用偏好设置请求
 */
class GetPreferencesRequest extends AbstractRequest
{
    public static function create(): self
    {
        return new self();
    }
    public function getMethod(): string
    {
        return 'GET';
    }

    public function getEndpoint(): string
    {
        return '/preferences';
    }

    public function getUri(): string
    {
        return '/api/v2/app/preferences';
    }

    public function getOptions(): array
    {
        return [];
    }
}