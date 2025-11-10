<?php
declare(strict_types=1);

namespace PhpQbittorrent\Request\RSS;

use PhpQbittorrent\Request\AbstractRequest;
use PhpQbittorrent\Contract\ValidationResult;
use PhpQbittorrent\Validation\BasicValidationResult;

/**
 * 刷新RSS项目请求
 */
class RefreshItemRequest extends AbstractRequest
{
    /** @var string RSS项目路径 */
    private string $itemPath;

    /**
     * 构造函数
     *
     * @param string $itemPath RSS项目路径
     */
    public function __construct(string $itemPath)
    {
        $this->itemPath = $itemPath;
    }

    /**
     * 获取RSS项目路径
     *
     * @return string RSS项目路径
     */
    public function getItemPath(): string
    {
        return $this->itemPath;
    }

    /**
     * 设置RSS项目路径
     *
     * @param string $itemPath RSS项目路径
     * @return self 返回自身以支持链式调用
     */
    public function setItemPath(string $itemPath): self
    {
        $this->itemPath = $itemPath;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getEndpoint(): string
    {
        return '/refreshItem';
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
        $result = BasicValidationResult::success();

        // 验证路径不为空
        if (empty(trim($this->itemPath))) {
            $result->addError('RSS项目路径不能为空');
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        return [
            'itemPath' => $this->itemPath,
        ];
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
            'item_path' => $this->itemPath,
            'description' => 'Refresh RSS feed or folder',
        ];
    }

    /**
     * 创建RefreshItemRequest实例
     *
     * @param string $itemPath RSS项目路径
     * @return self 请求实例
     */
    public static function create(string $itemPath): self
    {
        return new self($itemPath);
    }
}