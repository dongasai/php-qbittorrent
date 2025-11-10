<?php
declare(strict_types=1);

namespace PhpQbittorrent\Request\RSS;

use PhpQbittorrent\Request\AbstractRequest;
use PhpQbittorrent\Contract\ValidationResult;
use PhpQbittorrent\Validation\BasicValidationResult;

/**
 * 标记为已读请求
 */
class MarkAsReadRequest extends AbstractRequest
{
    /** @var string RSS项目路径 */
    private string $itemPath;

    /** @var string|null 文章ID */
    private ?string $articleId;

    /**
     * 构造函数
     *
     * @param string $itemPath RSS项目路径
     * @param string|null $articleId 文章ID，如果为null则标记整个feed为已读
     */
    public function __construct(string $itemPath, ?string $articleId = null)
    {
        $this->itemPath = $itemPath;
        $this->articleId = $articleId;
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
     * 获取文章ID
     *
     * @return string|null 文章ID
     */
    public function getArticleId(): ?string
    {
        return $this->articleId;
    }

    /**
     * 设置文章ID
     *
     * @param string|null $articleId 文章ID
     * @return self 返回自身以支持链式调用
     */
    public function setArticleId(?string $articleId): self
    {
        $this->articleId = $articleId;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getEndpoint(): string
    {
        return '/markAsRead';
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
        $data = [
            'itemPath' => $this->itemPath,
        ];

        if ($this->articleId !== null) {
            $data['articleId'] = $this->articleId;
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
            'item_path' => $this->itemPath,
            'article_id' => $this->articleId,
            'is_entire_feed' => $this->articleId === null,
            'description' => $this->articleId === null
                ? 'Mark entire RSS feed as read'
                : 'Mark specific RSS article as read',
        ];
    }

    /**
     * 检查是否标记整个feed为已读
     *
     * @return bool 是否标记整个feed为已读
     */
    public function isEntireFeed(): bool
    {
        return $this->articleId === null;
    }

    /**
     * 检查是否标记特定文章为已读
     *
     * @return bool 是否标记特定文章为已读
     */
    public function isSpecificArticle(): bool
    {
        return $this->articleId !== null;
    }

    /**
     * 创建标记整个feed为已读的请求
     *
     * @param string $itemPath RSS项目路径
     * @return self 请求实例
     */
    public static function createForFeed(string $itemPath): self
    {
        return new self($itemPath);
    }

    /**
     * 创建标记特定文章为已读的请求
     *
     * @param string $itemPath RSS项目路径
     * @param string $articleId 文章ID
     * @return self 请求实例
     */
    public static function createForArticle(string $itemPath, string $articleId): self
    {
        return new self($itemPath, $articleId);
    }
}