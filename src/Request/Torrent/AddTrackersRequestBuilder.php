<?php
declare(strict_types=1);

namespace PhpQbittorrent\Request\Torrent;

/**
 * AddTrackers请求构建器
 *
 * 提供流式接口来构建AddTrackers请求
 */
class AddTrackersRequestBuilder
{
    /** @var string|null 种子哈希 */
    private ?string $hash = null;

    /** @var array<string> Tracker URL列表 */
    private array $urls = [];

    /**
     * 设置种子哈希
     *
     * @param string $hash 种子哈希
     * @return static 返回自身以支持链式调用
     */
    public function withHash(string $hash): static
    {
        $this->hash = $hash;
        return $this;
    }

    /**
     * 添加Tracker URL
     *
     * @param string $url Tracker URL
     * @return static 返回自身以支持链式调用
     */
    public function addUrl(string $url): static
    {
        $this->urls[] = $url;
        return $this;
    }

    /**
     * 设置Tracker URL列表
     *
     * @param array<string> $urls Tracker URL列表
     * @return static 返回自身以支持链式调用
     */
    public function withUrls(array $urls): static
    {
        $this->urls = $urls;
        return $this;
    }

    /**
     * 添加多个Tracker URL
     *
     * @param string ...$urls Tracker URL列表
     * @return static 返回自身以支持链式调用
     */
    public function addUrls(string ...$urls): static
    {
        foreach ($urls as $url) {
            $this->urls[] = $url;
        }
        return $this;
    }

    /**
     * 清空Tracker URL列表
     *
     * @return static 返回自身以支持链式调用
     */
    public function clearUrls(): static
    {
        $this->urls = [];
        return $this;
    }

    /**
     * 构建AddTrackers请求
     *
     * @return AddTrackersRequest 添加Tracker请求实例
     * @throws \InvalidArgumentException 如果缺少必要参数
     */
    public function build(): AddTrackersRequest
    {
        if ($this->hash === null) {
            throw new \InvalidArgumentException('种子哈希是必需的');
        }

        if (empty($this->urls)) {
            throw new \InvalidArgumentException('至少需要一个Tracker URL');
        }

        return AddTrackersRequest::create($this->hash, $this->urls);
    }

    /**
     * 重置构建器状态
     *
     * @return static 返回自身以支持链式调用
     */
    public function reset(): static
    {
        $this->hash = null;
        $this->urls = [];
        return $this;
    }

    /**
     * 获取当前设置的种子哈希
     *
     * @return string|null 种子哈希
     */
    public function getHash(): ?string
    {
        return $this->hash;
    }

    /**
     * 获取当前设置的Tracker URL列表
     *
     * @return array<string> Tracker URL列表
     */
    public function getUrls(): array
    {
        return $this->urls;
    }

    /**
     * 获取构建器状态摘要
     *
     * @return array<string, mixed> 状态摘要
     */
    public function getSummary(): array
    {
        return [
            'hash' => $this->hash,
            'url_count' => count($this->urls),
            'urls' => $this->urls,
            'has_hash' => $this->hash !== null,
            'has_urls' => !empty($this->urls),
        ];
    }
}