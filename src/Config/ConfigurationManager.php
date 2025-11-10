<?php
declare(strict_types=1);

namespace PhpQbittorrent\Config;

use PhpQbittorrent\Transport\TransportInterface;
use PhpQbittorrent\Exception\ValidationException;
use PhpQbittorrent\Transport\CurlTransport;

/**
 * 配置管理器
 *
 * 提供统一的配置管理功能，支持多种配置来源
 */
class ConfigurationManager
{
    /** @var array<string, mixed> 配置数据 */
    private array $config = [];

    /** @var array<string, mixed> 默认配置 */
    private const DEFAULT_CONFIG = [
        'base_url' => '',
        'username' => '',
        'password' => '',
        'timeout' => 30,
        'connect_timeout' => 10,
        'retry_attempts' => 3,
        'retry_delay' => 1000, // 毫秒
        'verify_ssl' => true,
        'user_agent' => 'php-qbittorrent/2.0',
        'follow_redirects' => true,
        'max_redirects' => 5,
        'debug' => false,
        'log_requests' => false,
        'log_responses' => false,
        'auto_login' => true,
        'auto_logout' => true,
        'cache_enabled' => false,
        'cache_ttl' => 300, // 秒
        'transport_class' => CurlTransport::class,
        'transport_options' => [],
    ];

    /**
     * 构造函数
     *
     * @param array<string, mixed> $config 初始配置
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge(self::DEFAULT_CONFIG, $config);
        $this->validate();
    }

    /**
     * 从数组创建配置管理器
     *
     * @param array<string, mixed> $config 配置数组
     * @return self 配置管理器实例
     * @throws ValidationException 配置异常
     */
    public static function fromArray(array $config): self
    {
        return new self($config);
    }

    /**
     * 从JSON文件创建配置管理器
     *
     * @param string $filePath 配置文件路径
     * @return self 配置管理器实例
     * @throws ValidationException 配置异常
     */
    public static function fromJsonFile(string $filePath): self
    {
        if (!file_exists($filePath)) {
            throw new ValidationException("配置文件不存在: {$filePath}");
        }

        if (!is_readable($filePath)) {
            throw new ValidationException("配置文件不可读: {$filePath}");
        }

        $jsonContent = file_get_contents($filePath);
        if ($jsonContent === false) {
            throw new ValidationException("无法读取配置文件: {$filePath}");
        }

        $config = json_decode($jsonContent, true);
        if ($config === null) {
            throw new ValidationException("配置文件JSON格式无效: {$filePath}");
        }

        return new self($config);
    }

    /**
     * 从环境变量创建配置管理器
     *
     * @param string $prefix 环境变量前缀
     * @return self 配置管理器实例
     */
    public static function fromEnvironment(string $prefix = 'QBITTORRENT_'): self
    {
        $config = [];

        // 基础配置
        $envMappings = [
            $prefix . 'BASE_URL' => 'base_url',
            $prefix . 'USERNAME' => 'username',
            $prefix . 'PASSWORD' => 'password',
            $prefix . 'TIMEOUT' => 'timeout',
            $prefix . 'CONNECT_TIMEOUT' => 'connect_timeout',
            $prefix . 'RETRY_ATTEMPTS' => 'retry_attempts',
            $prefix . 'RETRY_DELAY' => 'retry_delay',
            $prefix . 'VERIFY_SSL' => 'verify_ssl',
            $prefix . 'USER_AGENT' => 'user_agent',
            $prefix . 'FOLLOW_REDIRECTS' => 'follow_redirects',
            $prefix . 'MAX_REDIRECTS' => 'max_redirects',
            $prefix . 'DEBUG' => 'debug',
            $prefix . 'LOG_REQUESTS' => 'log_requests',
            $prefix . 'LOG_RESPONSES' => 'log_responses',
            $prefix . 'AUTO_LOGIN' => 'auto_login',
            $prefix . 'AUTO_LOGOUT' => 'auto_logout',
            $prefix . 'CACHE_ENABLED' => 'cache_enabled',
            $prefix . 'CACHE_TTL' => 'cache_ttl',
        ];

        foreach ($envMappings as $envKey => $configKey) {
            $value = getenv($envKey);
            if ($value !== false) {
                $config[$configKey] = $this->convertEnvValue($value, $configKey);
            }
        }

        return new self($config);
    }

    /**
     * 转换环境变量值为适当的类型
     *
     * @param string $value 环境变量值
     * @param string $configKey 配置键
     * @return mixed 转换后的值
     */
    private function convertEnvValue(string $value, string $configKey): mixed
    {
        $booleanKeys = ['verify_ssl', 'follow_redirects', 'debug', 'log_requests', 'log_responses', 'auto_login', 'auto_logout', 'cache_enabled'];
        $integerKeys = ['timeout', 'connect_timeout', 'retry_attempts', 'retry_delay', 'max_redirects', 'cache_ttl'];

        if (in_array($configKey, $booleanKeys)) {
            return strtolower($value) === 'true' || $value === '1';
        }

        if (in_array($configKey, $integerKeys)) {
            return (int) $value;
        }

        return $value;
    }

    /**
     * 验证配置
     *
     * @throws ValidationException 配置异常
     */
    private function validate(): void
    {
        // 验证必需的基础配置
        if (empty($this->config['base_url'])) {
            throw new ValidationException('base_url不能为空');
        }

        if (!filter_var($this->config['base_url'], FILTER_VALIDATE_URL) &&
            !str_starts_with($this->config['base_url'], 'http://') &&
            !str_starts_with($this->config['base_url'], 'https://')) {
            throw new ValidationException('base_url格式无效，必须以http://或https://开头');
        }

        // 验证数值配置
        $numericFields = ['timeout', 'connect_timeout', 'retry_attempts', 'retry_delay', 'max_redirects', 'cache_ttl'];
        foreach ($numericFields as $field) {
            if (!is_numeric($this->config[$field]) || $this->config[$field] < 0) {
                throw new ValidationException("{$field}必须是非负数");
            }
        }

        // 验证布尔配置
        $booleanFields = ['verify_ssl', 'follow_redirects', 'debug', 'log_requests', 'log_responses', 'auto_login', 'auto_logout', 'cache_enabled'];
        foreach ($booleanFields as $field) {
            if (!is_bool($this->config[$field])) {
                throw new ValidationException("{$field}必须是布尔值");
            }
        }

        // 验证传输类
        if (!class_exists($this->config['transport_class'])) {
            throw new ValidationException("传输类不存在: {$this->config['transport_class']}");
        }

        if (!is_subclass_of($this->config['transport_class'], TransportInterface::class)) {
            throw new ValidationException("传输类必须实现TransportInterface接口");
        }
    }

    /**
     * 获取配置值
     *
     * @param string $key 配置键
     * @param mixed $default 默认值
     * @return mixed 配置值
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * 设置配置值
     *
     * @param string $key 配置键
     * @param mixed $value 配置值
     * @return self 返回自身以支持链式调用
     * @throws ValidationException 配置异常
     */
    public function set(string $key, mixed $value): self
    {
        $this->config[$key] = $value;

        // 重新验证配置
        $this->validate();

        return $this;
    }

    /**
     * 批量设置配置
     *
     * @param array<string, mixed> $config 配置数组
     * @return self 返回自身以支持链式调用
     * @throws ValidationException 配置异常
     */
    public function setMany(array $config): self
    {
        $this->config = array_merge($this->config, $config);
        $this->validate();
        return $this;
    }

    /**
     * 检查配置键是否存在
     *
     * @param string $key 配置键
     * @return bool 是否存在
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->config);
    }

    /**
     * 移除配置
     *
     * @param string $key 配置键
     * @return self 返回自身以支持链式调用
     */
    public function remove(string $key): self
    {
        unset($this->config[$key]);
        return $this;
    }

    /**
     * 获取所有配置
     *
     * @return array<string, mixed> 所有配置
     */
    public function all(): array
    {
        return $this->config;
    }

    /**
     * 获取基础URL
     *
     * @return string 基础URL
     */
    public function getBaseUrl(): string
    {
        return rtrim($this->config['base_url'], '/');
    }

    /**
     * 设置基础URL
     *
     * @param string $baseUrl 基础URL
     * @return self 返回自身以支持链式调用
     */
    public function setBaseUrl(string $baseUrl): self
    {
        return $this->set('base_url', $baseUrl);
    }

    /**
     * 获取认证信息
     *
     * @return array{username: string, password: string} 认证信息
     */
    public function getAuthCredentials(): array
    {
        return [
            'username' => $this->config['username'],
            'password' => $this->config['password'],
        ];
    }

    /**
     * 设置认证信息
     *
     * @param string $username 用户名
     * @param string $password 密码
     * @return self 返回自身以支持链式调用
     */
    public function setAuthCredentials(string $username, string $password): self
    {
        return $this->setMany([
            'username' => $username,
            'password' => $password,
        ]);
    }

    /**
     * 获取超时配置
     *
     * @return array{timeout: int, connect_timeout: int} 超时配置
     */
    public function getTimeoutConfig(): array
    {
        return [
            'timeout' => $this->config['timeout'],
            'connect_timeout' => $this->config['connect_timeout'],
        ];
    }

    /**
     * 设置超时配置
     *
     * @param int $timeout 超时时间
     * @param int $connectTimeout 连接超时时间
     * @return self 返回自身以支持链式调用
     */
    public function setTimeoutConfig(int $timeout, int $connectTimeout): self
    {
        return $this->setMany([
            'timeout' => $timeout,
            'connect_timeout' => $connectTimeout,
        ]);
    }

    /**
     * 获取重试配置
     *
     * @return array{retry_attempts: int, retry_delay: int} 重试配置
     */
    public function getRetryConfig(): array
    {
        return [
            'retry_attempts' => $this->config['retry_attempts'],
            'retry_delay' => $this->config['retry_delay'],
        ];
    }

    /**
     * 设置重试配置
     *
     * @param int $attempts 重试次数
     * @param int $delay 重试延迟（毫秒）
     * @return self 返回自身以支持链式调用
     */
    public function setRetryConfig(int $attempts, int $delay): self
    {
        return $this->setMany([
            'retry_attempts' => $attempts,
            'retry_delay' => $delay,
        ]);
    }

    /**
     * 获取SSL配置
     *
     * @return array{verify_ssl: bool} SSL配置
     */
    public function getSslConfig(): array
    {
        return [
            'verify_ssl' => $this->config['verify_ssl'],
        ];
    }

    /**
     * 设置SSL配置
     *
     * @param bool $verifySsl 是否验证SSL
     * @return self 返回自身以支持链式调用
     */
    public function setSslConfig(bool $verifySsl): self
    {
        return $this->set('verify_ssl', $verifySsl);
    }

    /**
     * 获取日志配置
     *
     * @return array{debug: bool, log_requests: bool, log_responses: bool} 日志配置
     */
    public function getLogConfig(): array
    {
        return [
            'debug' => $this->config['debug'],
            'log_requests' => $this->config['log_requests'],
            'log_responses' => $this->config['log_responses'],
        ];
    }

    /**
     * 设置日志配置
     *
     * @param bool $debug 调试模式
     * @param bool $logRequests 记录请求
     * @param bool $logResponses 记录响应
     * @return self 返回自身以支持链式调用
     */
    public function setLogConfig(bool $debug, bool $logRequests = false, bool $logResponses = false): self
    {
        return $this->setMany([
            'debug' => $debug,
            'log_requests' => $logRequests,
            'log_responses' => $logResponses,
        ]);
    }

    /**
     * 获取缓存配置
     *
     * @return array{enabled: bool, ttl: int} 缓存配置
     */
    public function getCacheConfig(): array
    {
        return [
            'enabled' => $this->config['cache_enabled'],
            'ttl' => $this->config['cache_ttl'],
        ];
    }

    /**
     * 设置缓存配置
     *
     * @param bool $enabled 是否启用缓存
     * @param int $ttl 缓存TTL（秒）
     * @return self 返回自身以支持链式调用
     */
    public function setCacheConfig(bool $enabled, int $ttl): self
    {
        return $this->setMany([
            'cache_enabled' => $enabled,
            'cache_ttl' => $ttl,
        ]);
    }

    /**
     * 获取传输配置
     *
     * @return array{class: class-string, options: array<string, mixed>} 传输配置
     */
    public function getTransportConfig(): array
    {
        return [
            'class' => $this->config['transport_class'],
            'options' => $this->config['transport_options'],
        ];
    }

    /**
     * 设置传输配置
     *
     * @param class-string $transportClass 传输类
     * @param array<string, mixed> $options 传输选项
     * @return self 返回自身以支持链式调用
     */
    public function setTransportConfig(string $transportClass, array $options = []): self
    {
        return $this->setMany([
            'transport_class' => $transportClass,
            'transport_options' => $options,
        ]);
    }

    /**
     * 创建传输实例
     *
     * @return TransportInterface 传输实例
     */
    public function createTransport(): TransportInterface
    {
        $transportClass = $this->config['transport_class'];
        $options = $this->config['transport_options'];

        // 传递常用配置选项
        $options = array_merge($options, [
            'timeout' => $this->config['timeout'],
            'connect_timeout' => $this->config['connect_timeout'],
            'verify_ssl' => $this->config['verify_ssl'],
            'user_agent' => $this->config['user_agent'],
            'follow_redirects' => $this->config['follow_redirects'],
            'max_redirects' => $this->config['max_redirects'],
            'debug' => $this->config['debug'],
        ]);

        // 创建PSR-7工厂实例
        $requestFactory = new \Nyholm\Psr7\Factory\Psr17Factory();
        $streamFactory = new \Nyholm\Psr7\Factory\Psr17Factory();

        return new $transportClass($requestFactory, $streamFactory);
    }

    /**
     * 保存配置到JSON文件
     *
     * @param string $filePath 文件路径
     * @return bool 是否保存成功
     * @throws ValidationException 配置异常
     */
    public function saveToJsonFile(string $filePath): bool
    {
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true)) {
                throw new ValidationException("无法创建目录: {$dir}");
            }
        }

        $jsonData = json_encode($this->config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($jsonData === false) {
            throw new ValidationException("配置序列化失败");
        }

        return file_put_contents($filePath, $jsonData) !== false;
    }

    /**
     * 合并其他配置
     *
     * @param ConfigurationManager $other 其他配置管理器
     * @return self 返回自身以支持链式调用
     * @throws ValidationException 配置异常
     */
    public function merge(ConfigurationManager $other): self
    {
        return $this->setMany($other->all());
    }

    /**
     * 克隆配置管理器
     *
     * @return self 新的配置管理器实例
     */
    public function clone(): self
    {
        return new self($this->config);
    }

    /**
     * 转换为数组
     *
     * @return array<string, mixed> 配置数组
     */
    public function toArray(): array
    {
        return $this->config;
    }

    /**
     * 转换为JSON字符串
     *
     * @return string JSON字符串
     */
    public function toJson(): string
    {
        return json_encode($this->config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}