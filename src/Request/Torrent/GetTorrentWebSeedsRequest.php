<?php
declare(strict_types=1);

namespace PhpQbittorrent\Request\Torrent;

use PhpQbittorrent\Request\AbstractRequest;
use PhpQbittorrent\Contract\ValidationResult;
use PhpQbittorrent\Validation\BasicValidationResult;
use PhpQbittorrent\Exception\ValidationException;

/**
 * 获取Torrent Web种子请求对象
 *
 * 用于获取指定torrent的Web种子列表
 */
class GetTorrentWebSeedsRequest extends AbstractRequest
{
    /** @var string Torrent哈希值 */
    private string $hash;

    /** @var int 最小哈希长度 */
    private const MIN_HASH_LENGTH = 40;

    /** @var int 最大哈希长度 */
    private const MAX_HASH_LENGTH = 40;

    /**
     * 私有构造函数
     *
     * @param string $hash Torrent哈希值
     */
    private function __construct(string $hash)
    {
        $this->hash = $hash;

        parent::__construct([
            'hash' => $hash
        ]);

        $this->setEndpoint('/torrents/webseeds')
             ->setMethod('GET');
    }

    /**
     * 创建Builder实例
     *
     * @return GetTorrentWebSeedsRequestBuilder Builder实例
     */
    public static function builder(): GetTorrentWebSeedsRequestBuilder
    {
        return new GetTorrentWebSeedsRequestBuilder();
    }

    /**
     * 直接创建请求实例
     *
     * @param string $hash Torrent哈希值
     * @return self 请求实例
     * @throws ValidationException 如果参数无效
     */
    public static function create(string $hash): self
    {
        $request = new self($hash);
        $validation = $request->validate();

        if (!$validation->isValid()) {
            throw ValidationException::fromValidationResult(
                $validation,
                'GetTorrentWebSeeds request validation failed'
            );
        }

        return $request;
    }

    /**
     * 获取Torrent哈希值
     *
     * @return string Torrent哈希值
     */
    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * 验证请求参数
     *
     * @return ValidationResult 验证结果
     */
    public function validate(): ValidationResult
    {
        $result = BasicValidationResult::success();

        // 验证哈希值不为空
        if (empty(trim($this->hash))) {
            $result->addError('Torrent哈希值不能为空');
        }

        // 验证哈希值长度
        $hashLength = strlen(trim($this->hash));
        if ($hashLength < self::MIN_HASH_LENGTH || $hashLength > self::MAX_HASH_LENGTH) {
            $result->addError(sprintf(
                'Torrent哈希值长度必须为 %d 个字符，当前为 %d 个字符',
                self::MIN_HASH_LENGTH,
                $hashLength
            ));
        }

        // 验证哈希值格式（只允许十六进制字符）
        if (!ctype_xdigit(trim($this->hash))) {
            $result->addError('Torrent哈希值只能包含十六进制字符（0-9, a-f, A-F）');
        }

        // 验证字符编码
        if (!mb_check_encoding($this->hash, 'UTF-8')) {
            $result->addError('Torrent哈希值包含无效的字符编码');
        }

        return $result;
    }

    /**
     * 转换为数组格式（用于HTTP请求）
     *
     * @return array<string, mixed> 请求数据数组
     */
    public function toArray(): array
    {
        return [
            'hash' => $this->hash,
        ];
    }

    /**
     * 获取查询参数字符串
     *
     * @return string 查询参数字符串
     */
    public function getQueryString(): string
    {
        return http_build_query($this->toArray(), '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * 获取完整的请求URL
     *
     * @return string 完整请求URL
     */
    public function getFullUrl(): string
    {
        $baseUrl = $this->getEndpoint();
        $queryString = $this->getQueryString();

        return $queryString ? $baseUrl . '?' . $queryString : $baseUrl;
    }

    /**
     * 获取请求摘要信息
     *
     * @return array<string, mixed> 摘要信息
     */
    public function getSummary(): array
    {
        return [
            'hash' => $this->hash,
            'hash_length' => strlen($this->hash),
            'is_valid_hash' => ctype_xdigit($this->hash),
            'endpoint' => $this->getEndpoint(),
            'method' => $this->getMethod(),
            'requires_auth' => $this->requiresAuthentication(),
            'query_string' => $this->getQueryString(),
            'full_url' => $this->getFullUrl(),
        ];
    }
}

/**
 * 获取Torrent Web种子请求构建器
 *
 * 使用Builder模式创建GetTorrentWebSeedsRequest实例
 */
class GetTorrentWebSeedsRequestBuilder
{
    private ?string $hash = null;

    /**
     * 设置Torrent哈希值
     *
     * @param string $hash Torrent哈希值
     * @return self 返回自身以支持链式调用
     */
    public function hash(string $hash): self
    {
        $this->hash = $hash;
        return $this;
    }

    /**
     * 构建GetTorrentWebSeedsRequest实例
     *
     * @return GetTorrentWebSeedsRequest 请求实例
     * @throws ValidationException 如果参数无效
     */
    public function build(): GetTorrentWebSeedsRequest
    {
        if ($this->hash === null) {
            throw ValidationException::missingParameter('hash');
        }

        $request = new GetTorrentWebSeedsRequest($this->hash);

        $validation = $request->validate();
        if (!$validation->isValid()) {
            throw ValidationException::fromValidationResult(
                $validation,
                'GetTorrentWebSeeds request validation failed'
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
        $result = BasicValidationResult::success();

        if ($this->hash === null) {
            $result->addError('Torrent哈希值是必需的');
        }

        return $result;
    }
}