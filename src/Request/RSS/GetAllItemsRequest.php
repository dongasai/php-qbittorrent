<?php
declare(strict_types=1);

namespace PhpQbittorrent\Request\RSS;

use PhpQbittorrent\Request\AbstractRequest;
use PhpQbittorrent\Contract\ValidationResult;
use PhpQbittorrent\Validation\BasicValidationResult;

/**
 * 获取所有RSS项目请求
 */
class GetAllItemsRequest extends AbstractRequest
{
    /** @var bool 是否包含当前文章 */
    private bool $withData;

    /** @var string|null RSS项目路径 */
    private ?string $itemPath;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->withData = false;
        $this->itemPath = null;
    }

    /**
     * 设置是否包含当前文章
     *
     * @param bool $withData 是否包含当前文章
     * @return self 返回自身以支持链式调用
     */
    public function setWithData(bool $withData): self
    {
        $this->withData = $withData;
        return $this;
    }

    /**
     * 获取是否包含当前文章
     *
     * @return bool 是否包含当前文章
     */
    public function isWithData(): bool
    {
        return $this->withData;
    }

    /**
     * 设置RSS项目路径
     *
     * @param string|null $itemPath RSS项目路径
     * @return self 返回自身以支持链式调用
     */
    public function setItemPath(?string $itemPath): self
    {
        $this->itemPath = $itemPath;
        return $this;
    }

    /**
     * 获取RSS项目路径
     *
     * @return string|null RSS项目路径
     */
    public function getItemPath(): ?string
    {
        return $this->itemPath;
    }

    /**
     * {@inheritdoc}
     */
    public function getEndpoint(): string
    {
        return '/items';
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
    public function toArray(): array
    {
        $data = [];

        if ($this->withData) {
            $data['withData'] = 'true';
        }

        if ($this->itemPath !== null) {
            $data['itemPath'] = $this->itemPath;
        }

        return $data;
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
            'with_data' => $this->withData,
            'item_path' => $this->itemPath,
            'description' => 'Get all RSS items and feeds',
        ];
    }

    /**
     * 创建GetAllItemsRequest实例
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * 创建包含数据的GetAllItemsRequest实例
     */
    public static function createWithData(): self
    {
        return (new self())->setWithData(true);
    }

    /**
     * 创建指定路径的GetAllItemsRequest实例
     */
    public static function createForPath(string $itemPath): self
    {
        return (new self())->setItemPath($itemPath);
    }

    /**
     * 创建包含数据且指定路径的GetAllItemsRequest实例
     */
    public static function createWithDataForPath(string $itemPath): self
    {
        return (new self())->setWithData(true)->setItemPath($itemPath);
    }
}