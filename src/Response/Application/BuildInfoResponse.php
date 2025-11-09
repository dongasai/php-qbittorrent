<?php
declare(strict_types=1);

namespace Dongasai\qBittorrent\Response\Application;

use Dongasai\qBittorrent\Response\AbstractResponse;

/**
 * 构建信息响应对象
 */
class BuildInfoResponse extends AbstractResponse
{
    /** @var string QT版本 */
    private string $qt;

    /** @var string libtorrent版本 */
    private string $libtorrent;

    /** @var string Boost版本 */
    private string $boost;

    /** @var string OpenSSL版本 */
    private string $openssl;

    /** @var int 应用位数 */
    private int $bitness;

    /**
     * 创建成功的构建信息响应
     *
     * @param array<string, mixed> $buildInfo 构建信息
     * @param array<string, string> $headers 响应头
     * @param int $statusCode HTTP状态码
     * @param string $rawResponse 原始响应内容
     * @return self 构建信息响应实例
     */
    public static function success(
        array $buildInfo,
        array $headers = [],
        int $statusCode = 200,
        string $rawResponse = ''
    ): self {
        $instance = parent::success($buildInfo, $headers, $statusCode, $rawResponse);
        $instance->qt = $buildInfo['qt'] ?? '';
        $instance->libtorrent = $buildInfo['libtorrent'] ?? '';
        $instance->boost = $buildInfo['boost'] ?? '';
        $instance->openssl = $buildInfo['openssl'] ?? '';
        $instance->bitness = $buildInfo['bitness'] ?? 0;

        return $instance;
    }

    /**
     * 创建失败的构建信息响应
     *
     * @param array<string> $errors 错误信息
     * @param array<string, string> $headers 响应头
     * @param int $statusCode HTTP状态码
     * @param string $rawResponse 原始响应内容
     * @return self 构建信息响应实例
     */
    public static function failure(
        array $errors = [],
        array $headers = [],
        int $statusCode = 400,
        string $rawResponse = ''
    ): self {
        $instance = parent::failure($errors, $headers, $statusCode, $rawResponse);
        $instance->qt = '';
        $instance->libtorrent = '';
        $instance->boost = '';
        $instance->openssl = '';
        $instance->bitness = 0;

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
            return self::success($responseData, $headers, $statusCode, $rawResponse);
        } else {
            return self::failure($errors, $headers, $statusCode, $rawResponse);
        }
    }

    /**
     * 从API响应创建响应对象
     *
     * @param array<string, mixed> $buildInfo 构建信息
     * @param array<string, string> $headers 响应头
     * @param int $statusCode HTTP状态码
     * @param string $rawResponse 原始响应内容
     * @return static 响应对象实例
     */
    public static function fromApiResponse(
        array $buildInfo,
        array $headers = [],
        int $statusCode = 200,
        string $rawResponse = ''
    ): static {
        return self::success($buildInfo, $headers, $statusCode, $rawResponse);
    }

    /**
     * 获取QT版本
     *
     * @return string QT版本
     */
    public function getQtVersion(): string
    {
        return $this->qt;
    }

    /**
     * 获取libtorrent版本
     *
     * @return string libtorrent版本
     */
    public function getLibtorrentVersion(): string
    {
        return $this->libtorrent;
    }

    /**
     * 获取Boost版本
     *
     * @return string Boost版本
     */
    public function getBoostVersion(): string
    {
        return $this->boost;
    }

    /**
     * 获取OpenSSL版本
     *
     * @return string OpenSSL版本
     */
    public function getOpensslVersion(): string
    {
        return $this->openssl;
    }

    /**
     * 获取应用位数
     *
     * @return int 应用位数 (32 或 64)
     */
    public function getBitness(): int
    {
        return $this->bitness;
    }

    /**
     * 检查是否为64位应用
     *
     * @return bool 是否为64位
     */
    public function is64Bit(): bool
    {
        return $this->bitness === 64;
    }

    /**
     * 检查是否为32位应用
     *
     * @return bool 是否为32位
     */
    public function is32Bit(): bool
    {
        return $this->bitness === 32;
    }

    /**
     * 获取QT主版本号
     *
     * @return int QT主版本号
     */
    public function getQtMajorVersion(): int
    {
        return $this->parseVersion($this->qt)[0] ?? 0;
    }

    /**
     * 获取libtorrent主版本号
     *
     * @return int libtorrent主版本号
     */
    public function getLibtorrentMajorVersion(): int
    {
        return $this->parseVersion($this->libtorrent)[0] ?? 0;
    }

    /**
     * 获取Boost主版本号
     *
     * @return int Boost主版本号
     */
    public function getBoostMajorVersion(): int
    {
        return $this->parseVersion($this->boost)[0] ?? 0;
    }

    /**
     * 获取OpenSSL主版本号
     *
     * @return int OpenSSL主版本号
     */
    public function getOpensslMajorVersion(): int
    {
        return $this->parseVersion($this->openssl)[0] ?? 0;
    }

    /**
     * 解析版本号
     *
     * @param string $version 版本字符串
     * @return array<int> 版本号组件
     */
    private function parseVersion(string $version): array
    {
        // 移除非数字字符，保留点号
        $cleanVersion = preg_replace('/[^0-9.]/', '', $version);
        $parts = explode('.', $cleanVersion);

        return array_map(function ($part) {
            return (int) $part;
        }, array_filter($parts));
    }

    /**
     * 获取格式化的构建信息
     *
     * @return array<string, mixed> 格式化的构建信息
     */
    public function getFormattedInfo(): array
    {
        return [
            'qt' => [
                'version' => $this->qt,
                'major' => $this->getQtMajorVersion(),
                'formatted' => "Qt {$this->qt}",
            ],
            'libtorrent' => [
                'version' => $this->libtorrent,
                'major' => $this->getLibtorrentMajorVersion(),
                'formatted' => "libtorrent {$this->libtorrent}",
            ],
            'boost' => [
                'version' => $this->boost,
                'major' => $this->getBoostMajorVersion(),
                'formatted' => "Boost {$this->boost}",
            ],
            'openssl' => [
                'version' => $this->openssl,
                'major' => $this->getOpensslMajorVersion(),
                'formatted' => "OpenSSL {$this->openssl}",
            ],
            'bitness' => [
                'value' => $this->bitness,
                'is_64bit' => $this->is64Bit(),
                'is_32bit' => $this->is32Bit(),
                'formatted' => "{$this->bitness}-bit",
            ],
        ];
    }

    /**
     * 获取系统兼容性信息
     *
     * @return array<string, mixed> 系统兼容性信息
     */
    public function getCompatibilityInfo(): array
    {
        return [
            'supports_64bit_operations' => $this->is64Bit(),
            'qt_version' => $this->getQtMajorVersion(),
            'libtorrent_version' => $this->getLibtorrentMajorVersion(),
            'modern_encryption' => $this->getOpensslMajorVersion() >= 1,
            'boost_libraries' => $this->getBoostMajorVersion() >= 1,
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
        $data['qt'] = $this->qt;
        $data['libtorrent'] = $this->libtorrent;
        $data['boost'] = $this->boost;
        $data['openssl'] = $this->openssl;
        $data['bitness'] = $this->bitness;
        $data['formatted_info'] = $this->getFormattedInfo();
        $data['compatibility_info'] = $this->getCompatibilityInfo();

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
            'qt_version' => $this->qt,
            'libtorrent_version' => $this->libtorrent,
            'boost_version' => $this->boost,
            'openssl_version' => $this->openssl,
            'bitness' => $this->bitness,
            'is_64bit' => $this->is64Bit(),
            'status_code' => $this->getStatusCode(),
            'error_count' => count($this->getErrors()),
        ];
    }
}