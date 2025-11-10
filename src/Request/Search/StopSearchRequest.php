<?php
declare(strict_types=1);

namespace PhpQbittorrent\Request\Search;

use PhpQbittorrent\Request\AbstractRequest;
use PhpQbittorrent\Contract\ValidationResult;
use PhpQbittorrent\Validation\BasicValidationResult;

/**
 * 停止搜索请求
 */
class StopSearchRequest extends AbstractRequest
{
    /** @var int 搜索作业ID */
    private int $searchId;

    /**
     * 构造函数
     *
     * @param int $searchId 搜索作业ID
     */
    public function __construct(int $searchId)
    {
        $this->searchId = $searchId;
    }

    /**
     * {@inheritdoc}
     */
    public function getEndpoint(): string
    {
        return '/stop';
    }

    /**
     * {@inheritdoc}
     */
    public function getMethod(): string
    {
        return 'POST';
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
        return [
            'id' => $this->searchId,
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
            'search_id' => $this->searchId,
            'description' => 'Stop search with specified ID',
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
     * 检查搜索ID是否有效
     *
     * @return bool 是否有效
     */
    public function isValidSearchId(): bool
    {
        return $this->searchId > 0;
    }

    /**
     * 创建StopSearchRequest实例
     *
     * @param int $searchId 搜索作业ID
     * @return self 停止搜索请求实例
     */
    public static function create(int $searchId): self
    {
        return new self($searchId);
    }
}