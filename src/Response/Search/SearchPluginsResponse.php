<?php
declare(strict_types=1);

namespace PhpQbittorrent\Response\Search;

use PhpQbittorrent\Response\AbstractResponse;

/**
 * 搜索插件响应对象
 */
class SearchPluginsResponse extends AbstractResponse
{
    /** @var array<SearchPlugin> 搜索插件列表 */
    private array $plugins;

    /**
     * 创建搜索插件响应（公共工厂方法）
     *
     * @param array<SearchPlugin> $plugins 插件列表
     * @param array<string, string> $headers 响应头
     * @param int $statusCode HTTP状态码
     * @param string $rawResponse 原始响应内容
     * @return self 搜索插件响应实例
     */
    public static function create(
        array $plugins,
        array $headers = [],
        int $statusCode = 200,
        string $rawResponse = ''
    ): self {
        return self::success(['plugins' => $plugins], $headers, $statusCode, $rawResponse);
    }

    /**
     * 创建成功的搜索插件响应
     *
     * @param array<string, mixed> $data 响应数据
     * @param array<string, string> $headers 响应头
     * @param int $statusCode HTTP状态码
     * @param string $rawResponse 原始响应内容
     * @return static 搜索插件响应实例
     */
    protected static function success(
        array $data = [],
        array $headers = [],
        int $statusCode = 200,
        string $rawResponse = ''
    ): static {
        $instance = parent::success($data, $headers, $statusCode, $rawResponse);

        $pluginsData = $data['plugins'] ?? [];
        $instance->plugins = array_map(function ($pluginData) {
            return new SearchPlugin($pluginData);
        }, $pluginsData);

        return $instance;
    }

    /**
     * 创建失败的搜索插件响应
     *
     * @param array<string> $errors 错误信息
     * @param array<string, string> $headers 响应头
     * @param int $statusCode HTTP状态码
     * @param string $rawResponse 原始响应内容
     * @return static 搜索插件响应实例
     */
    protected static function failure(
        array $errors = [],
        array $headers = [],
        int $statusCode = 400,
        string $rawResponse = ''
    ): static {
        return parent::failure($errors, $headers, $statusCode, $rawResponse);
    }

    /**
     * 从数组数据创建响应对象
     *
     * @param array<string, mixed> $data 响应数据
     * @return static 响应对象实例
     */
    public static function fromArray(array $data): static
    {
        $success = ($data['success'] ?? false);
        $headers = $data['headers'] ?? [];
        $statusCode = $data['statusCode'] ?? 200;
        $rawResponse = $data['rawResponse'] ?? '';
        $errors = $data['errors'] ?? [];
        $responseData = $data['data'] ?? [];

        if ($success) {
            return self::success($responseData, $headers, $statusCode, $rawResponse);
        } else {
            return parent::failure($errors, $headers, $statusCode, $rawResponse);
        }
    }

    /**
     * 从API响应创建响应对象
     *
     * @param array<string, mixed> $responseData API响应数据
     * @param array<string, string> $headers 响应头
     * @param int $statusCode HTTP状态码
     * @param string $rawResponse 原始响应内容
     * @return static 响应对象实例
     */
    public static function fromApiResponse(
        array $responseData,
        array $headers = [],
        int $statusCode = 200,
        string $rawResponse = ''
    ): static {
        return self::success($responseData, $headers, $statusCode, $rawResponse);
    }

    /**
     * 获取搜索插件列表
     *
     * @return array<SearchPlugin>
     */
    public function getPlugins(): array
    {
        return $this->plugins;
    }

    /**
     * 检查是否有插件
     *
     * @return bool
     */
    public function hasPlugins(): bool
    {
        return !empty($this->plugins);
    }

    /**
     * 获取插件数量
     *
     * @return int
     */
    public function getPluginCount(): int
    {
        return count($this->plugins);
    }
}

/**
 * 搜索插件信息对象
 */
class SearchPlugin
{
    /** @var string 插件名称 */
    private string $name;

    /** @var string 插件完整名称 */
    private string $fullName;

    /** @var string 插件版本 */
    private string $version;

    /** @var string 插件URL */
    private string $url;

    /** @var bool 是否启用 */
    private bool $enabled;

    /** @var array<string> 支持的分类 */
    private array $supportedCategories;

    /**
     * 构造函数
     *
     * @param array<string, mixed> $data 插件数据
     */
    public function __construct(array $data)
    {
        $this->name = $data['name'] ?? '';
        $this->fullName = $data['fullName'] ?? $data['full_name'] ?? '';
        $this->version = $data['version'] ?? '';
        $this->url = $data['url'] ?? '';
        $this->enabled = $data['enabled'] ?? false;
        $this->supportedCategories = $data['supportedCategories'] ?? $data['supported_categories'] ?? [];
    }

    /**
     * 获取插件名称
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * 获取插件完整名称
     *
     * @return string
     */
    public function getFullName(): string
    {
        return $this->fullName;
    }

    /**
     * 获取插件版本
     *
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * 获取插件URL
     *
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * 检查是否启用
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * 获取支持的分类
     *
     * @return array<string>
     */
    public function getSupportedCategories(): array
    {
        return $this->supportedCategories;
    }

    /**
     * 转换为数组
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'fullName' => $this->fullName,
            'version' => $this->version,
            'url' => $this->url,
            'enabled' => $this->enabled,
            'supportedCategories' => $this->supportedCategories,
        ];
    }
}