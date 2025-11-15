<?php
declare(strict_types=1);

namespace PhpQbittorrent\Response\Torrent;

use PhpQbittorrent\Response\AbstractResponse;
use PhpQbittorrent\Contract\ArrayableInterface;
use PhpQbittorrent\Model\TorrentWebSeed;
use PhpQbittorrent\Exception\ResponseParseException;
use PhpQbittorrent\Contract\ValidationResult;
use PhpQbittorrent\Validation\BasicValidationResult;
use PhpQbittorrent\Exception\ValidationException;

/**
 * Torrent Web种子响应对象
 *
 * 封装获取Web种子列表的响应数据
 */
class TorrentWebSeedsResponse extends AbstractResponse implements ArrayableInterface
{
    /** @var array<TorrentWebSeed> Web种子列表 */
    private array $webSeeds;

    /** @var string Torrent哈希值 */
    private string $torrentHash;

    /**
     * 构造函数
     *
     * @param array<TorrentWebSeed> $webSeeds Web种子列表
     * @param string $torrentHash Torrent哈希值
     * @param array $rawResponse 原始响应数据
     * @param array $responseData 解析后的响应数据
     */
    public function __construct(
        array $webSeeds,
        string $torrentHash,
        array $rawResponse,
        array $responseData
    ) {
        $this->webSeeds = $webSeeds;
        $this->torrentHash = $torrentHash;

        parent::__construct($rawResponse, $responseData);
    }

    /**
     * 创建成功响应
     *
     * @param array<TorrentWebSeed> $webSeeds Web种子列表
     * @param string $torrentHash Torrent哈希值
     * @param array $rawResponse 原始响应数据
     * @param array $responseData 解析后的响应数据
     * @return self 响应实例
     * @throws ValidationException 如果数据无效
     */
    public static function success(
        array $webSeeds,
        string $torrentHash,
        array $rawResponse,
        array $responseData
    ): self {
        $response = new self($webSeeds, $torrentHash, $rawResponse, $responseData);

        $validation = $response->validate();
        if (!$validation->isValid()) {
            throw ValidationException::fromValidationResult(
                $validation,
                'TorrentWebSeedsResponse validation failed'
            );
        }

        return $response;
    }

    /**
     * 创建错误响应
     *
     * @param array $rawResponse 原始响应数据
     * @param array $responseData 解析后的响应数据
     * @param string $errorMessage 错误消息
     * @return self 响应实例
     */
    public static function error(
        array $rawResponse,
        array $responseData,
        string $errorMessage = 'Failed to get torrent webseeds'
    ): self {
        return new self([], '', $rawResponse, $responseData);
    }

    /**
     * 从API响应创建响应实例
     *
     * @param array $apiResponse API响应数据
     * @param string $torrentHash Torrent哈希值
     * @return self 响应实例
     * @throws ResponseParseException 如果解析失败
     * @throws ValidationException 如果数据无效
     */
    public static function fromApiResponse(array $apiResponse, string $torrentHash): self
    {
        if (!isset($apiResponse['data'])) {
            throw new ResponseParseException('Missing data field in API response');
        }

        $responseData = $apiResponse['data'];
        $rawResponse = $apiResponse;

        if (!is_array($responseData)) {
            throw new ResponseParseException('Response data must be an array');
        }

        $webSeeds = [];
        foreach ($responseData as $index => $webSeedData) {
            try {
                if (!is_array($webSeedData)) {
                    throw new ResponseParseException("Web seed data at index {$index} must be an array");
                }

                $webSeeds[] = TorrentWebSeed::fromArray($webSeedData);
            } catch (\Exception $e) {
                throw new ResponseParseException(
                    "Failed to parse web seed at index {$index}: " . $e->getMessage()
                );
            }
        }

        return self::success($webSeeds, $torrentHash, $rawResponse, $responseData);
    }

    /**
     * 获取Web种子列表
     *
     * @return array<TorrentWebSeed> Web种子列表
     */
    public function getWebSeeds(): array
    {
        return $this->webSeeds;
    }

    /**
     * 获取Torrent哈希值
     *
     * @return string Torrent哈希值
     */
    public function getTorrentHash(): string
    {
        return $this->torrentHash;
    }

    /**
     * 获取Web种子数量
     *
     * @return int Web种子数量
     */
    public function getCount(): int
    {
        return count($this->webSeeds);
    }

    /**
     * 检查是否有Web种子
     *
     * @return bool 是否有Web种子
     */
    public function hasWebSeeds(): bool
    {
        return !empty($this->webSeeds);
    }

    /**
     * 根据URL查找Web种子
     *
     * @param string $url URL
     * @return TorrentWebSeed|null 找到的Web种子或null
     */
    public function findByUrl(string $url): ?TorrentWebSeed
    {
        foreach ($this->webSeeds as $webSeed) {
            if ($webSeed->getUrl() === $url) {
                return $webSeed;
            }
        }

        return null;
    }

    /**
     * 获取所有URL
     *
     * @return array<string> URL列表
     */
    public function getUrls(): array
    {
        return array_map(fn($webSeed) => $webSeed->getUrl(), $this->webSeeds);
    }

    /**
     * 过滤HTTP协议的Web种子
     *
     * @return array<TorrentWebSeed> HTTP Web种子列表
     */
    public function getHttpWebSeeds(): array
    {
        return array_filter(
            $this->webSeeds,
            fn($webSeed) => str_starts_with($webSeed->getUrl(), 'http://')
        );
    }

    /**
     * 过滤HTTPS协议的Web种子
     *
     * @return array<TorrentWebSeed> HTTPS Web种子列表
     */
    public function getHttpsWebSeeds(): array
    {
        return array_filter(
            $this->webSeeds,
            fn($webSeed) => str_starts_with($webSeed->getUrl(), 'https://')
        );
    }

    /**
     * 验证响应数据
     *
     * @return ValidationResult 验证结果
     */
    public function validate(): ValidationResult
    {
        $result = BasicValidationResult::success();

        // 验证Web种子列表
        foreach ($this->webSeeds as $index => $webSeed) {
            if (!$webSeed instanceof TorrentWebSeed) {
                $result->addError("Web seed at index {$index} is not a TorrentWebSeed instance");
            }
        }

        // 验证Torrent哈希值
        if (!empty($this->torrentHash)) {
            if (strlen($this->torrentHash) !== 40 || !ctype_xdigit($this->torrentHash)) {
                $result->addError('Torrent hash is invalid');
            }
        }

        return $result;
    }

    /**
     * 转换为数组
     *
     * @return array<string, mixed> 数组表示
     */
    public function toArray(): array
    {
        return [
            'webseeds' => array_map(fn($webSeed) => $webSeed->toArray(), $this->webSeeds),
            'torrent_hash' => $this->torrentHash,
            'count' => $this->getCount(),
            'has_webseeds' => $this->hasWebSeeds(),
            'urls' => $this->getUrls(),
            'http_count' => count($this->getHttpWebSeeds()),
            'https_count' => count($this->getHttpsWebSeeds()),
        ];
    }

    /**
     * 获取摘要信息
     *
     * @return array<string, mixed> 摘要信息
     */
    public function getSummary(): array
    {
        return [
            'torrent_hash' => $this->torrentHash,
            'webseeds_count' => $this->getCount(),
            'has_webseeds' => $this->hasWebSeeds(),
            'http_count' => count($this->getHttpWebSeeds()),
            'https_count' => count($this->getHttpsWebSeeds()),
            'response_valid' => $this->validate()->isValid(),
            'response_data_size' => count($this->getResponseData()),
        ];
    }
}