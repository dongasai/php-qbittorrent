<?php
declare(strict_types=1);

namespace PhpQbittorrent\Request\Search;

use PhpQbittorrent\Request\AbstractRequest;
use PhpQbittorrent\Contract\ValidationResult;
use PhpQbittorrent\Validation\BasicValidationResult;

/**
 * 获取搜索状态请求
 */
class GetSearchStatusRequest extends AbstractRequest
{
    /** @var int|null 搜索作业ID，null表示获取所有搜索作业状态 */
    private ?int $searchId;

    /**
     * 构造函数
     *
     * @param int|null $searchId 搜索作业ID，null表示获取所有搜索作业状态
     */
    public function __construct(?int $searchId = null)
    {
        $this->searchId = $searchId;
    }

    /**
     * {@inheritdoc}
     */
    public function getEndpoint(): string
    {
        return '/status';
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
        $errors = [];

        // 验证搜索ID（如果提供了的话）
        if ($this->searchId !== null && $this->searchId <= 0) {
            $errors[] = '搜索作业ID必须是正整数';
        }

        if (empty($errors)) {
            return BasicValidationResult::success();
        }

        return BasicValidationResult::failure($errors);
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        $parameters = [];

        if ($this->searchId !== null) {
            $parameters['id'] = $this->searchId;
        }

        return $parameters;
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
            'search_id' => $this->searchId,
            'get_all' => $this->searchId === null,
            'description' => $this->searchId === null
                ? 'Get status of all search jobs'
                : 'Get status of specific search job',
        ];
    }

    /**
     * 获取搜索作业ID
     *
     * @return int|null 搜索作业ID，null表示获取所有搜索作业状态
     */
    public function getSearchId(): ?int
    {
        return $this->searchId;
    }

    /**
     * 设置搜索作业ID
     *
     * @param int|null $searchId 搜索作业ID，null表示获取所有搜索作业状态
     * @return static 返回自身以支持链式调用
     */
    public function setSearchId(?int $searchId): static
    {
        $this->searchId = $searchId;
        return $this;
    }

    /**
     * 设置为获取所有搜索作业状态
     *
     * @return static 返回自身以支持链式调用
     */
    public function setGetAll(): static
    {
        $this->searchId = null;
        return $this;
    }

    /**
     * 检查是否获取所有搜索作业状态
     *
     * @return bool 是否获取所有搜索作业状态
     */
    public function isGetAll(): bool
    {
        return $this->searchId === null;
    }

    /**
     * 检查是否获取特定搜索作业状态
     *
     * @return bool 是否获取特定搜索作业状态
     */
    public function isGetSpecific(): bool
    {
        return $this->searchId !== null;
    }

    /**
     * 检查搜索ID是否有效
     *
     * @return bool 是否有效
     */
    public function isValidSearchId(): bool
    {
        return $this->searchId === null || $this->searchId > 0;
    }

    /**
     * 创建获取所有搜索作业状态的请求
     *
     * @return self 搜索状态请求实例
     */
    public static function createForAll(): self
    {
        return new self(null);
    }

    /**
     * 创建获取特定搜索作业状态的请求
     *
     * @param int $searchId 搜索作业ID
     * @return self 搜索状态请求实例
     */
    public static function createForSearch(int $searchId): self
    {
        return new self($searchId);
    }
}