<?php
declare(strict_types=1);

namespace PhpQbittorrent\Request\Search;

use PhpQbittorrent\Request\AbstractRequest;
use PhpQbittorrent\Contract\ValidationResult;
use PhpQbittorrent\Validation\BasicValidationResult;

/**
 * 获取搜索结果请求
 */
class GetSearchResultsRequest extends AbstractRequest
{
    /** @var int 搜索作业ID */
    private int $searchId;

    /** @var int|null 结果限制数量，0或负数表示无限制 */
    private ?int $limit;

    /** @var int|null 结果偏移量 */
    private ?int $offset;

    /**
     * 构造函数
     *
     * @param int $searchId 搜索作业ID
     * @param int|null $limit 结果限制数量，null表示使用默认值
     * @param int|null $offset 结果偏移量，null表示使用默认值
     */
    public function __construct(int $searchId, ?int $limit = null, ?int $offset = null)
    {
        $this->searchId = $searchId;
        $this->limit = $limit;
        $this->offset = $offset;
    }

    /**
     * {@inheritdoc}
     */
    public function getEndpoint(): string
    {
        return '/results';
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

        // 验证搜索ID
        if ($this->searchId <= 0) {
            $errors[] = '搜索作业ID必须是正整数';
        }

        // 验证限制数量
        if ($this->limit !== null && $this->limit < 0 && $this->limit !== 0) {
            $errors[] = '结果限制数量不能为负数（0表示无限制）';
        }

        // 验证偏移量
        if ($this->offset !== null && $this->offset < 0) {
            $errors[] = '结果偏移量不能为负数';
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
        $parameters = [
            'id' => $this->searchId,
        ];

        if ($this->limit !== null) {
            $parameters['limit'] = $this->limit;
        }

        if ($this->offset !== null) {
            $parameters['offset'] = $this->offset;
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
            'limit' => $this->limit,
            'offset' => $this->offset,
            'has_limit' => $this->limit !== null,
            'has_offset' => $this->offset !== null,
            'description' => 'Get search results for specified search job with optional pagination',
        ];
    }

    /**
     * 获取搜索作业ID
     *
     * @return int 搜索作业ID
     */
    public function getSearchId(): int
    {
        return $this->searchId;
    }

    /**
     * 设置搜索作业ID
     *
     * @param int $searchId 搜索作业ID
     * @return static 返回自身以支持链式调用
     */
    public function setSearchId(int $searchId): static
    {
        $this->searchId = $searchId;
        return $this;
    }

    /**
     * 获取结果限制数量
     *
     * @return int|null 结果限制数量，null表示未设置
     */
    public function getLimit(): ?int
    {
        return $this->limit;
    }

    /**
     * 设置结果限制数量
     *
     * @param int|null $limit 结果限制数量，null表示取消限制
     * @return static 返回自身以支持链式调用
     */
    public function setLimit(?int $limit): static
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * 设置无限制结果数量
     *
     * @return static 返回自身以支持链式调用
     */
    public function setNoLimit(): static
    {
        $this->limit = 0;
        return $this;
    }

    /**
     * 获取结果偏移量
     *
     * @return int|null 结果偏移量，null表示未设置
     */
    public function getOffset(): ?int
    {
        return $this->offset;
    }

    /**
     * 设置结果偏移量
     *
     * @param int|null $offset 结果偏移量，null表示取消偏移
     * @return static 返回自身以支持链式调用
     */
    public function setOffset(?int $offset): static
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * 检查搜索ID是否有效
     *
     * @return bool 是否有效
     */
    public function isValidSearchId(): bool
    {
        return $this->searchId > 0;
    }

    /**
     * 检查是否设置了限制
     *
     * @return bool 是否设置了限制
     */
    public function hasLimit(): bool
    {
        return $this->limit !== null;
    }

    /**
     * 检查是否设置了偏移量
     *
     * @return bool 是否设置了偏移量
     */
    public function hasOffset(): bool
    {
        return $this->offset !== null;
    }

    /**
     * 检查是否为无限制
     *
     * @return bool 是否为无限制
     */
    public function isNoLimit(): bool
    {
        return $this->limit === 0;
    }

    /**
     * 检查是否启用分页
     *
     * @return bool 是否启用分页
     */
    public function isPaginationEnabled(): bool
    {
        return $this->hasLimit() || $this->hasOffset();
    }

    /**
     * 获取分页信息
     *
     * @return array<string, mixed> 分页信息
     */
    public function getPaginationInfo(): array
    {
        return [
            'limit' => $this->limit,
            'offset' => $this->offset,
            'has_limit' => $this->hasLimit(),
            'has_offset' => $this->hasOffset(),
            'is_no_limit' => $this->isNoLimit(),
            'pagination_enabled' => $this->isPaginationEnabled(),
        ];
    }

    /**
     * 创建基本的搜索结果请求
     *
     * @param int $searchId 搜索作业ID
     * @return self 搜索结果请求实例
     */
    public static function create(int $searchId): self
    {
        return new self($searchId);
    }

    /**
     * 创建带限制的搜索结果请求
     *
     * @param int $searchId 搜索作业ID
     * @param int $limit 结果限制数量
     * @param int|null $offset 结果偏移量
     * @return self 搜索结果请求实例
     */
    public static function createWithLimit(int $searchId, int $limit, ?int $offset = null): self
    {
        return new self($searchId, $limit, $offset);
    }

    /**
     * 创建分页的搜索结果请求
     *
     * @param int $searchId 搜索作业ID
     * @param int $page 页码（从1开始）
     * @param int $pageSize 每页大小
     * @return self 搜索结果请求实例
     */
    public static function createWithPagination(int $searchId, int $page, int $pageSize): self
    {
        $offset = ($page - 1) * $pageSize;
        return new self($searchId, $pageSize, $offset);
    }

    /**
     * 创建无限制的搜索结果请求
     *
     * @param int $searchId 搜索作业ID
     * @return self 搜索结果请求实例
     */
    public static function createWithNoLimit(int $searchId): self
    {
        return new self($searchId, 0);
    }
}