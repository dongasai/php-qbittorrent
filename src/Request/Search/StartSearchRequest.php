<?php
declare(strict_types=1);

namespace PhpQbittorrent\Request\Search;

use PhpQbittorrent\Request\AbstractRequest;
use PhpQbittorrent\Contract\ValidationResult;
use PhpQbittorrent\Validation\BasicValidationResult;

/**
 * 开始搜索请求
 */
class StartSearchRequest extends AbstractRequest
{
    /** @var string 搜索模式 */
    private string $pattern;

    /** @var array<string> 搜索插件列表 */
    private array $plugins;

    /** @var string 搜索分类 */
    private string $category;

    /**
     * 构造函数
     *
     * @param string $pattern 搜索模式
     * @param array<string> $plugins 搜索插件列表
     * @param string $category 搜索分类
     */
    public function __construct(string $pattern, array $plugins = [], string $category = 'all')
    {
        $this->pattern = $pattern;
        $this->plugins = empty($plugins) ? ['all'] : $plugins;
        $this->category = $category;
    }

    /**
     * {@inheritdoc}
     */
    public function getEndpoint(): string
    {
        return '/start';
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

        // 验证搜索模式
        if (empty(trim($this->pattern))) {
            $errors[] = '搜索模式不能为空';
        }

        // 验证插件列表
        if (empty($this->plugins)) {
            $errors[] = '至少需要指定一个搜索插件';
        }

        // 验证分类
        if (empty(trim($this->category))) {
            $errors[] = '搜索分类不能为空';
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
            'pattern' => $this->pattern,
            'plugins' => implode('|', $this->plugins),
            'category' => $this->category,
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
            'pattern' => $this->pattern,
            'plugins' => $this->plugins,
            'category' => $this->category,
            'description' => 'Start search with specified pattern, plugins, and category',
        ];
    }

    /**
     * 获取搜索模式
     *
     * @return string 搜索模式
     */
    public function getPattern(): string
    {
        return $this->pattern;
    }

    /**
     * 设置搜索模式
     *
     * @param string $pattern 搜索模式
     * @return static 返回自身以支持链式调用
     */
    public function setPattern(string $pattern): static
    {
        $this->pattern = $pattern;
        return $this;
    }

    /**
     * 获取搜索插件列表
     *
     * @return array<string> 搜索插件列表
     */
    public function getPlugins(): array
    {
        return $this->plugins;
    }

    /**
     * 设置搜索插件列表
     *
     * @param array<string> $plugins 搜索插件列表
     * @return static 返回自身以支持链式调用
     */
    public function setPlugins(array $plugins): static
    {
        $this->plugins = empty($plugins) ? ['all'] : $plugins;
        return $this;
    }

    /**
     * 添加搜索插件
     *
     * @param string $plugin 搜索插件
     * @return static 返回自身以支持链式调用
     */
    public function addPlugin(string $plugin): static
    {
        if (!in_array($plugin, $this->plugins)) {
            $this->plugins[] = $plugin;
        }
        return $this;
    }

    /**
     * 移除搜索插件
     *
     * @param string $plugin 搜索插件
     * @return static 返回自身以支持链式调用
     */
    public function removePlugin(string $plugin): static
    {
        $key = array_search($plugin, $this->plugins);
        if ($key !== false) {
            unset($this->plugins[$key]);
            $this->plugins = array_values($this->plugins);
        }
        return $this;
    }

    /**
     * 获取搜索分类
     *
     * @return string 搜索分类
     */
    public function getCategory(): string
    {
        return $this->category;
    }

    /**
     * 设置搜索分类
     *
     * @param string $category 搜索分类
     * @return static 返回自身以支持链式调用
     */
    public function setCategory(string $category): static
    {
        $this->category = $category;
        return $this;
    }

    /**
     * 使用所有插件
     *
     * @return static 返回自身以支持链式调用
     */
    public function useAllPlugins(): static
    {
        $this->plugins = ['all'];
        return $this;
    }

    /**
     * 使用启用的插件
     *
     * @return static 返回自身以支持链式调用
     */
    public function useEnabledPlugins(): static
    {
        $this->plugins = ['enabled'];
        return $this;
    }

    /**
     * 检查是否使用所有插件
     *
     * @return bool 是否使用所有插件
     */
    public function isUsingAllPlugins(): bool
    {
        return in_array('all', $this->plugins);
    }

    /**
     * 检查是否使用启用的插件
     *
     * @return bool 是否使用启用的插件
     */
    public function isUsingEnabledPlugins(): bool
    {
        return in_array('enabled', $this->plugins);
    }

    /**
     * 检查是否包含特定插件
     *
     * @param string $plugin 插件名称
     * @return bool 是否包含该插件
     */
    public function hasPlugin(string $plugin): bool
    {
        return in_array($plugin, $this->plugins);
    }

    /**
     * 获取插件数量
     *
     * @return int 插件数量
     */
    public function getPluginCount(): int
    {
        return count($this->plugins);
    }

    /**
     * 检查搜索模式是否为空
     *
     * @return bool 是否为空
     */
    public function isPatternEmpty(): bool
    {
        return empty(trim($this->pattern));
    }

    /**
     * 获取搜索模式的长度
     *
     * @return int 搜索模式长度
     */
    public function getPatternLength(): int
    {
        return mb_strlen($this->pattern);
    }

    /**
     * 创建StartSearchRequest实例
     *
     * @param string $pattern 搜索模式
     * @param array<string> $plugins 搜索插件列表
     * @param string $category 搜索分类
     * @return self 搜索请求实例
     */
    public static function create(string $pattern, array $plugins = [], string $category = 'all'): self
    {
        return new self($pattern, $plugins, $category);
    }

    /**
     * 创建使用所有插件的搜索请求
     *
     * @param string $pattern 搜索模式
     * @param string $category 搜索分类
     * @return self 搜索请求实例
     */
    public static function createWithAllPlugins(string $pattern, string $category = 'all'): self
    {
        return new self($pattern, ['all'], $category);
    }

    /**
     * 创建使用启用插件的搜索请求
     *
     * @param string $pattern 搜索模式
     * @param string $category 搜索分类
     * @return self 搜索请求实例
     */
    public static function createWithEnabledPlugins(string $pattern, string $category = 'all'): self
    {
        return new self($pattern, ['enabled'], $category);
    }
}