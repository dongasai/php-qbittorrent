<?php
declare(strict_types=1);

namespace PhpQbittorrent\Response\Application;

use PhpQbittorrent\Response\AbstractResponse;

/**
 * 版本响应对象
 */
class VersionResponse extends AbstractResponse
{
    /** @var string 应用版本 */
    private string $version;

    /**
     * 创建版本响应（公共工厂方法）
     *
     * @param string $version 版本号
     * @param array<string, string> $headers 响应头
     * @param int $statusCode HTTP状态码
     * @param string $rawResponse 原始响应内容
     * @return self 版本响应实例
     */
    public static function create(
        string $version,
        array $headers = [],
        int $statusCode = 200,
        string $rawResponse = ''
    ): self {
        return self::success(['version' => $version], $headers, $statusCode, $rawResponse);
    }

    /**
     * 创建成功的版本响应
     *
     * @param array<string, mixed> $data 响应数据
     * @param array<string, string> $headers 响应头
     * @param int $statusCode HTTP状态码
     * @param string $rawResponse 原始响应内容
     * @return static 版本响应实例
     */
    protected static function success(
        array|string $data = [],
        array $headers = [],
        int $statusCode = 200,
        string $rawResponse = ''
    ): static {
        $version = '';
        if (is_string($data)) {
            $version = $data;
        } elseif (is_array($data)) {
            $version = $data['version'] ?? $data[0] ?? '';
        }

        // 如果版本是JSON编码的数组，尝试解析
        if (is_string($version) && str_starts_with($version, '[') && str_ends_with($version, ']')) {
            $decoded = json_decode($version, true);
            if (is_array($decoded) && !empty($decoded)) {
                $version = $decoded[0] ?? '';
            }
        }

        $instance = parent::success(['version' => $version], $headers, $statusCode, $rawResponse);
        $instance->version = $version;

        return $instance;
    }

    /**
     * 创建失败的版本响应
     *
     * @param array<string> $errors 错误信息
     * @param array<string, string> $headers 响应头
     * @param int $statusCode HTTP状态码
     * @param string $rawResponse 原始响应内容
     * @return static 版本响应实例
     */
    protected static function failure(
        array $errors = [],
        array $headers = [],
        int $statusCode = 400,
        string $rawResponse = ''
    ): static {
        $instance = parent::failure($errors, $headers, $statusCode, $rawResponse);
        $instance->version = '';

        return $instance;
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
            $version = $responseData['version'] ?? '';
            return self::success(['version' => $version], $headers, $statusCode, $rawResponse);
        } else {
            return parent::failure($errors, $headers, $statusCode, $rawResponse);
        }
    }

    /**
     * 从API响应创建响应对象
     *
     * @param string $version 版本号
     * @param array<string, string> $headers 响应头
     * @param int $statusCode HTTP状态码
     * @param string $rawResponse 原始响应内容
     * @return static 响应对象实例
     */
    public static function fromApiResponse(
        string $version,
        array $headers = [],
        int $statusCode = 200,
        string $rawResponse = ''
    ): static {
        return self::success($version, $headers, $statusCode, $rawResponse);
    }

    /**
     * 获取版本号
     *
     * @return string 版本号
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * 获取主版本号
     *
     * @return int 主版本号
     */
    public function getMajorVersion(): int
    {
        return $this->parseVersion()[0] ?? 0;
    }

    /**
     * 获取次版本号
     *
     * @return int 次版本号
     */
    public function getMinorVersion(): int
    {
        return $this->parseVersion()[1] ?? 0;
    }

    /**
     * 获取修订版本号
     *
     * @return int 修订版本号
     */
    public function getPatchVersion(): int
    {
        return $this->parseVersion()[2] ?? 0;
    }

    /**
     * 检查是否为指定版本或更高版本
     *
     * @param string $requiredVersion 要求的版本
     * @return bool 是否满足版本要求
     */
    public function isVersionAtLeast(string $requiredVersion): bool
    {
        return version_compare($this->version, $requiredVersion, '>=');
    }

    /**
     * 检查是否为指定版本或更低版本
     *
     * @param string $maxVersion 最大版本
     * @return bool 是否在版本范围内
     */
    public function isVersionAtMost(string $maxVersion): bool
    {
        return version_compare($this->version, $maxVersion, '<=');
    }

    /**
     * 检查是否在版本范围内
     *
     * @param string $minVersion 最小版本
     * @param string $maxVersion 最大版本
     * @return bool 是否在版本范围内
     */
    public function isVersionInRange(string $minVersion, string $maxVersion): bool
    {
        return $this->isVersionAtLeast($minVersion) && $this->isVersionAtMost($maxVersion);
    }

    /**
     * 解析版本号
     *
     * @return array<int> 版本号组件
     */
    private function parseVersion(): array
    {
        // 移除 'v' 前缀并解析
        $version = ltrim($this->version, 'v');
        $parts = explode('.', $version);

        return array_map(function ($part) {
            return (int) preg_replace('/[^0-9]/', '', $part);
        }, $parts);
    }

    /**
     * 获取格式化的版本信息
     *
     * @return array<string, mixed> 格式化的版本信息
     */
    public function getFormattedInfo(): array
    {
        return [
            'version' => $this->version,
            'major' => $this->getMajorVersion(),
            'minor' => $this->getMinorVersion(),
            'patch' => $this->getPatchVersion(),
            'is_stable' => $this->getPatchVersion() > 0,
            'formatted' => "v{$this->getMajorVersion()}.{$this->getMinorVersion()}.{$this->getPatchVersion()}",
        ];
    }

    /**
     * 转换为数组格式
     *
     * @return array<string, mixed> 响应数据数组
     */
    public function toArray(): array
    {
        $data = parent::toArray();
        $data['version'] = $this->version;
        $data['major_version'] = $this->getMajorVersion();
        $data['minor_version'] = $this->getMinorVersion();
        $data['patch_version'] = $this->getPatchVersion();
        $data['formatted_info'] = $this->getFormattedInfo();

        return $data;
    }

    /**
     * 获取响应的摘要信息
     *
     * @return array<string, mixed> 摘要信息
     */
    public function getSummary(): array
    {
        return [
            'success' => $this->isSuccess(),
            'version' => $this->version,
            'major_version' => $this->getMajorVersion(),
            'minor_version' => $this->getMinorVersion(),
            'patch_version' => $this->getPatchVersion(),
            'is_stable' => $this->getPatchVersion() > 0,
            'status_code' => $this->getStatusCode(),
            'error_count' => count($this->getErrors()),
        ];
    }
}