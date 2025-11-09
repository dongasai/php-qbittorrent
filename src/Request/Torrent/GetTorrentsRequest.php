<?php
declare(strict_types=1);

namespace Dongasai\qBittorrent\Request\Torrent;

use Dongasai\qBittorrent\Request\AbstractRequest;
use Dongasai\qBittorrent\Contract\ValidationResult;
use Dongasai\qBittorrent\Validation\BasicValidationResult;
use Dongasai\qBittorrent\Exception\ValidationException;
use Dongasai\qBittorrent\Enum\TorrentFilter;

/**
 * 获取Torrent列表请求对象
 *
 * 用于获取和过滤Torrent列表的请求参数封装
 */
class GetTorrentsRequest extends AbstractRequest
{
    /** @var TorrentFilter|null 过滤条件 */
    private ?TorrentFilter $filter = null;

    /** @var string|null 分类过滤 */
    private ?string $category = null;

    /** @var string|null 标签过滤 */
    private ?string $tag = null;

    /** @var string|null 排序字段 */
    private ?string $sort = null;

    /** @var bool 是否反向排序 */
    private bool $reverse = false;

    /** @var int|null 限制数量 */
    private ?int $limit = null;

    /** @var int|null 偏移量 */
    private ?int $offset = null;

    /** @var array<string>|null 哈希过滤 */
    private ?array $hashes = null;

    /** @var int 最大限制数量 */
    private const MAX_LIMIT = 1000;

    /** @var array<string> 允许的排序字段 */
    private const ALLOWED_SORT_FIELDS = [
        'hash', 'name', 'size', 'progress', 'dl_speed', 'up_speed',
        'priority', 'num_seeds', 'num_leechs', 'ratio', 'eta',
        'state', 'category', 'tags', 'save_path', 'added_on',
        'completion_on', 'tracker', 'dl_limit', 'up_limit',
        'downloaded', 'uploaded', 'downloaded_session', 'uploaded_session',
        'amount_left', 'time_active', 'seeding_time', 'last_activity'
    ];

    /**
     * 私有构造函数
     */
    private function __construct()
    {
        parent::__construct([]);

        $this->setEndpoint('/torrents/info')
             ->setMethod('GET')
             ->setRequiresAuthentication(true);
    }

    /**
     * 创建Builder实例
     *
     * @return GetTorrentsRequestBuilder Builder实例
     */
    public static function builder(): GetTorrentsRequestBuilder
    {
        return new GetTorrentsRequestBuilder();
    }

    /**
     * 直接创建请求实例
     *
     * @return GetTorrentsRequest 请求实例
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * 获取过滤条件
     *
     * @return TorrentFilter|null 过滤条件
     */
    public function getFilter(): ?TorrentFilter
    {
        return $this->filter;
    }

    /**
     * 获取分类过滤
     *
     * @return string|null 分类过滤
     */
    public function getCategory(): ?string
    {
        return $this->category;
    }

    /**
     * 获取标签过滤
     *
     * @return string|null 标签过滤
     */
    public function getTag(): ?string
    {
        return $this->tag;
    }

    /**
     * 获取排序字段
     *
     * @return string|null 排序字段
     */
    public function getSort(): ?string
    {
        return $this->sort;
    }

    /**
     * 是否反向排序
     *
     * @return bool 是否反向排序
     */
    public function isReverse(): bool
    {
        return $this->reverse;
    }

    /**
     * 获取限制数量
     *
     * @return int|null 限制数量
     */
    public function getLimit(): ?int
    {
        return $this->limit;
    }

    /**
     * 获取偏移量
     *
     * @return int|null 偏移量
     */
    public function getOffset(): ?int
    {
        return $this->offset;
    }

    /**
     * 获取哈希过滤
     *
     * @return array<string>|null 哈希过滤
     */
    public function getHashes(): ?array
    {
        return $this->hashes;
    }

    /**
     * 设置过滤条件
     *
     * @param TorrentFilter $filter 过滤条件
     * @return self 返回自身以支持链式调用
     */
    private function setFilter(TorrentFilter $filter): self
    {
        $this->filter = $filter;
        return $this;
    }

    /**
     * 设置分类过滤
     *
     * @param string $category 分类名称
     * @return self 返回自身以支持链式调用
     */
    private function setCategory(string $category): self
    {
        $this->category = $category;
        return $this;
    }

    /**
     * 设置标签过滤
     *
     * @param string $tag 标签名称
     * @return self 返回自身以支持链式调用
     */
    private function setTag(string $tag): self
    {
        $this->tag = $tag;
        return $this;
    }

    /**
     * 设置排序字段
     *
     * @param string $sort 排序字段
     * @return self 返回自身以支持链式调用
     */
    private function setSort(string $sort): self
    {
        $this->sort = $sort;
        return $this;
    }

    /**
     * 设置是否反向排序
     *
     * @param bool $reverse 是否反向排序
     * @return self 返回自身以支持链式调用
     */
    private function setReverse(bool $reverse): self
    {
        $this->reverse = $reverse;
        return $this;
    }

    /**
     * 设置限制数量
     *
     * @param int $limit 限制数量
     * @return self 返回自身以支持链式调用
     */
    private function setLimit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * 设置偏移量
     *
     * @param int $offset 偏移量
     * @return self 返回自身以支持链式调用
     */
    private function setOffset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * 设置哈希过滤
     *
     * @param array<string> $hashes 哈希数组
     * @return self 返回自身以支持链式调用
     */
    private function setHashes(array $hashes): self
    {
        $this->hashes = $hashes;
        return $this;
    }

    /**
     * 验证请求参数
     *
     * @return ValidationResult 验证结果
     */
    public function validate(): ValidationResult
    {
        $result = BasicValidationResult::success();

        // 验证排序字段
        if ($this->sort !== null && !in_array($this->sort, self::ALLOWED_SORT_FIELDS)) {
            $result->addError("无效的排序字段: {$this->sort}，允许的字段: " . implode(', ', self::ALLOWED_SORT_FIELDS));
        }

        // 验证限制数量
        if ($this->limit !== null) {
            if ($this->limit <= 0) {
                $result->addError('限制数量必须大于0');
            } elseif ($this->limit > self::MAX_LIMIT) {
                $result->addError("限制数量不能超过 " . self::MAX_LIMIT);
            }
        }

        // 验证偏移量
        if ($this->offset !== null && $this->offset < 0) {
            $result->addError('偏移量不能为负数');
        }

        // 验证分类名称
        if ($this->category !== null) {
            if (empty(trim($this->category))) {
                $result->addError('分类名称不能为空');
            } elseif (strlen($this->category) > 255) {
                $result->addError('分类名称长度不能超过255个字符');
            }
        }

        // 验证标签名称
        if ($this->tag !== null) {
            if (empty(trim($this->tag))) {
                $result->addError('标签名称不能为空');
            } elseif (strlen($this->tag) > 255) {
                $result->addError('标签名称长度不能超过255个字符');
            }
        }

        // 验证哈希数组
        if ($this->hashes !== null) {
            if (empty($this->hashes)) {
                $result->addError('哈希数组不能为空');
            } else {
                foreach ($this->hashes as $hash) {
                    if (!preg_match('/^[a-fA-F0-9]{40}$/', $hash)) {
                        $result->addError("无效的哈希格式: {$hash}");
                    }
                }
            }
        }

        // 验证参数组合
        if ($this->hashes !== null && ($this->filter !== null || $this->category !== null || $this->tag !== null)) {
            $result->addWarning('使用哈希过滤时，其他过滤条件将被忽略');
        }

        return $result;
    }

    /**
     * 转换为数组格式（用于HTTP查询参数）
     *
     * @return array<string, mixed> 请求数据数组
     */
    public function toArray(): array
    {
        $params = [];

        if ($this->filter !== null) {
            $params['filter'] = $this->filter->value;
        }

        if ($this->category !== null) {
            $params['category'] = $this->category;
        }

        if ($this->tag !== null) {
            $params['tag'] = $this->tag;
        }

        if ($this->sort !== null) {
            $params['sort'] = $this->sort;
        }

        if ($this->reverse) {
            $params['reverse'] = 'true';
        }

        if ($this->limit !== null) {
            $params['limit'] = (string) $this->limit;
        }

        if ($this->offset !== null) {
            $params['offset'] = (string) $this->offset;
        }

        if ($this->hashes !== null) {
            $params['hashes'] = implode('|', $this->hashes);
        }

        return $params;
    }

    /**
     * 获取请求的摘要信息
     *
     * @return array<string, mixed> 摘要信息
     */
    public function getSummary(): array
    {
        return [
            'filter' => $this->filter?->value,
            'category' => $this->category,
            'tag' => $this->tag,
            'sort' => $this->sort,
            'reverse' => $this->reverse,
            'limit' => $this->limit,
            'offset' => $this->offset,
            'hashes_count' => $this->hashes ? count($this->hashes) : null,
            'endpoint' => $this->getEndpoint(),
            'method' => $this->getMethod(),
            'requires_auth' => $this->requiresAuthentication(),
        ];
    }
}

/**
 * 获取Torrent列表请求构建器
 *
 * 使用Builder模式创建GetTorrentsRequest实例
 */
class GetTorrentsRequestBuilder
{
    private ?TorrentFilter $filter = null;
    private ?string $category = null;
    private ?string $tag = null;
    private ?string $sort = null;
    private bool $reverse = false;
    private ?int $limit = null;
    private ?int $offset = null;
    private ?array $hashes = null;

    /**
     * 设置过滤条件
     *
     * @param TorrentFilter $filter 过滤条件
     * @return self 返回自身以支持链式调用
     */
    public function filter(TorrentFilter $filter): self
    {
        $this->filter = $filter;
        return $this;
    }

    /**
     * 设置分类过滤
     *
     * @param string $category 分类名称
     * @return self 返回自身以支持链式调用
     */
    public function category(string $category): self
    {
        $this->category = $category;
        return $this;
    }

    /**
     * 设置标签过滤
     *
     * @param string $tag 标签名称
     * @return self 返回自身以支持链式调用
     */
    public function tag(string $tag): self
    {
        $this->tag = $tag;
        return $this;
    }

    /**
     * 设置排序字段
     *
     * @param string $sort 排序字段
     * @return self 返回自身以支持链式调用
     */
    public function sortBy(string $sort): self
    {
        $this->sort = $sort;
        return $this;
    }

    /**
     * 设置反向排序
     *
     * @param bool $reverse 是否反向排序
     * @return self 返回自身以支持链式调用
     */
    public function setReverse(bool $reverse = true): self
    {
        $this->reverse = $reverse;
        return $this;
    }

    /**
     * 设置限制数量
     *
     * @param int $limit 限制数量
     * @return self 返回自身以支持链式调用
     */
    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * 设置偏移量
     *
     * @param int $offset 偏移量
     * @return self 返回自身以支持链式调用
     */
    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * 设置哈希过滤
     *
     * @param array<string> $hashes 哈希数组
     * @return self 返回自身以支持链式调用
     */
    public function hashes(array $hashes): self
    {
        $this->hashes = $hashes;
        return $this;
    }

    /**
     * 添加单个哈希过滤
     *
     * @param string $hash 哈希值
     * @return self 返回自身以支持链式调用
     */
    public function addHash(string $hash): self
    {
        if ($this->hashes === null) {
            $this->hashes = [];
        }
        $this->hashes[] = $hash;
        return $this;
    }

    /**
     * 构建GetTorrentsRequest实例
     *
     * @return GetTorrentsRequest 获取Torrent列表请求实例
     * @throws ValidationException 如果参数无效
     */
    public function build(): GetTorrentsRequest
    {
        $request = new GetTorrentsRequest();

        if ($this->filter !== null) {
            $request->setFilter($this->filter);
        }

        if ($this->category !== null) {
            $request->setCategory($this->category);
        }

        if ($this->tag !== null) {
            $request->setTag($this->tag);
        }

        if ($this->sort !== null) {
            $request->setSort($this->sort);
        }

        $request->setReverse($this->reverse);

        if ($this->limit !== null) {
            $request->setLimit($this->limit);
        }

        if ($this->offset !== null) {
            $request->setOffset($this->offset);
        }

        if ($this->hashes !== null) {
            $request->setHashes($this->hashes);
        }

        $validation = $request->validate();
        if (!$validation->isValid()) {
            throw ValidationException::fromValidationResult(
                $validation,
                'GetTorrents request validation failed'
            );
        }

        return $request;
    }

    /**
     * 验证当前配置
     *
     * @return ValidationResult 验证结果
     */
    public function validate(): ValidationResult
    {
        $request = new GetTorrentsRequest();

        if ($this->filter !== null) {
            $request->setFilter($this->filter);
        }

        if ($this->category !== null) {
            $request->setCategory($this->category);
        }

        if ($this->tag !== null) {
            $request->setTag($this->tag);
        }

        if ($this->sort !== null) {
            $request->setSort($this->sort);
        }

        $request->setReverse($this->reverse);

        if ($this->limit !== null) {
            $request->setLimit($this->limit);
        }

        if ($this->offset !== null) {
            $request->setOffset($this->offset);
        }

        if ($this->hashes !== null) {
            $request->setHashes($this->hashes);
        }

        return $request->validate();
    }
}