<?php
declare(strict_types=1);

namespace PhpQbittorrent\Config;

/**
 * qBittorrent客户端配置类
 *
 * 管理客户端连接和行为配置
 */
final class ClientConfig
{
    private string $url;
    private ?string $username = null;
    private ?string $password = null;
    private float $timeout = 30.0;
    private float $connectTimeout = 10.0;
    private bool $verifySSL = true;
    private ?string $sslCertPath = null;
    private ?string $proxy = null;
    private ?string $proxyAuth = null;
    private string $userAgent;

    /**
     * 配置验证错误集合
     */
    private array $errors = [];

    public function __construct(string $url, ?string $username = null, ?string $password = null)
    {
        $this->url = rtrim($url, '/');
        $this->username = $username;
        $this->password = $password;
        $this->userAgent = 'php-qbittorrent/1.0.0';
    }

    /**
     * 从数组创建配置
     */
    public static function fromArray(array $config): self
    {
        $instance = new self(
            $config['url'] ?? '',
            $config['username'] ?? null,
            $config['password'] ?? null
        );

        if (isset($config['timeout'])) {
            $instance->setTimeout((float) $config['timeout']);
        }

        if (isset($config['connect_timeout'])) {
            $instance->setConnectTimeout((float) $config['connect_timeout']);
        }

        if (isset($config['verify_ssl'])) {
            $instance->setVerifySSL((bool) $config['verify_ssl']);
        }

        if (isset($config['ssl_cert_path'])) {
            $instance->setSSLCertPath($config['ssl_cert_path']);
        }

        if (isset($config['proxy'])) {
            $instance->setProxy($config['proxy'], $config['proxy_auth'] ?? null);
        }

        if (isset($config['user_agent'])) {
            $instance->setUserAgent($config['user_agent']);
        }

        return $instance;
    }

    /**
     * 验证配置是否有效
     */
    public function validate(): bool
    {
        $this->errors = [];

        // 验证URL
        if (empty($this->url)) {
            $this->errors['url'] = 'URL不能为空';
        } elseif (!filter_var($this->url, FILTER_VALIDATE_URL)) {
            $this->errors['url'] = 'URL格式无效';
        }

        // 验证超时设置
        if ($this->timeout <= 0) {
            $this->errors['timeout'] = '超时时间必须大于0';
        }

        if ($this->connectTimeout <= 0) {
            $this->errors['connect_timeout'] = '连接超时时间必须大于0';
        }

        // 验证代理配置
        if ($this->proxy && !filter_var($this->proxy, FILTER_VALIDATE_URL)) {
            $this->errors['proxy'] = '代理URL格式无效';
        }

        return empty($this->errors);
    }

    /**
     * 获取验证错误
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    // Getters
    public function getUrl(): string
    {
        return $this->url;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getTimeout(): float
    {
        return $this->timeout;
    }

    public function getConnectTimeout(): float
    {
        return $this->connectTimeout;
    }

    public function isVerifySSL(): bool
    {
        return $this->verifySSL;
    }

    public function getSSLCertPath(): ?string
    {
        return $this->sslCertPath;
    }

    public function getProxy(): ?string
    {
        return $this->proxy;
    }

    public function getProxyAuth(): ?string
    {
        return $this->proxyAuth;
    }

    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    public function hasCredentials(): bool
    {
        return !empty($this->username) && !empty($this->password);
    }

    // Setters
    public function setUrl(string $url): void
    {
        $this->url = rtrim($url, '/');
    }

    public function setUsername(?string $username): void
    {
        $this->username = $username;
    }

    public function setPassword(?string $password): void
    {
        $this->password = $password;
    }

    public function setTimeout(float $timeout): void
    {
        $this->timeout = $timeout;
    }

    public function setConnectTimeout(float $connectTimeout): void
    {
        $this->connectTimeout = $connectTimeout;
    }

    public function setVerifySSL(bool $verifySSL): void
    {
        $this->verifySSL = $verifySSL;
    }

    public function setSSLCertPath(?string $sslCertPath): void
    {
        $this->sslCertPath = $sslCertPath;
    }

    public function setProxy(?string $proxy, ?string $auth = null): void
    {
        $this->proxy = $proxy;
        $this->proxyAuth = $auth;
    }

    public function setUserAgent(string $userAgent): void
    {
        $this->userAgent = $userAgent;
    }

    /**
     * 转换为数组格式
     */
    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'username' => $this->username,
            'password' => $this->password,
            'timeout' => $this->timeout,
            'connect_timeout' => $this->connectTimeout,
            'verify_ssl' => $this->verifySSL,
            'ssl_cert_path' => $this->sslCertPath,
            'proxy' => $this->proxy,
            'proxy_auth' => $this->proxyAuth,
            'user_agent' => $this->userAgent,
        ];
    }
}