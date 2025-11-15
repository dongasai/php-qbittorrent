<?php
declare(strict_types=1);

namespace PhpQbittorrent\Model;

use PhpQbittorrent\Contract\ArrayableInterface;
use PhpQbittorrent\Contract\JsonSerializableInterface;
use PhpQbittorrent\Exception\ValidationException;
use PhpQbittorrent\Contract\ValidationResult;
use PhpQbittorrent\Validation\BasicValidationResult;

/**
 * Torrent Web种子模型
 *
 * 表示Web种子的信息
 */
class TorrentWebSeed implements ArrayableInterface, JsonSerializableInterface
{
    /** @var string Web种子URL */
    private string $url;

    /** @var int 最大URL长度 */
    private const MAX_URL_LENGTH = 2048;

    /**
     * 构造函数
     *
     * @param string $url Web种子URL
     * @throws ValidationException 如果URL无效
     */
    public function __construct(string $url)
    {
        $this->url = $url;

        $validation = $this->validate();
        if (!$validation->isValid()) {
            throw ValidationException::fromValidationResult(
                $validation,
                'Invalid TorrentWebSeed'
            );
        }
    }

    /**
     * 获取URL
     *
     * @return string Web种子URL
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * 设置URL
     *
     * @param string $url Web种子URL
     * @return self 返回自身以支持链式调用
     * @throws ValidationException 如果URL无效
     */
    public function setUrl(string $url): self
    {
        $this->url = $url;

        $validation = $this->validate();
        if (!$validation->isValid()) {
            throw ValidationException::fromValidationResult(
                $validation,
                'Invalid URL for TorrentWebSeed'
            );
        }

        return $this;
    }

    /**
     * 验证Web种子数据
     *
     * @return ValidationResult 验证结果
     */
    public function validate(): ValidationResult
    {
        $result = BasicValidationResult::success();

        // 验证URL不为空
        if (empty(trim($this->url))) {
            $result->addError('Web种子URL不能为空');
            return $result;
        }

        // 验证URL长度
        if (strlen($this->url) > self::MAX_URL_LENGTH) {
            $result->addError('Web种子URL长度不能超过 ' . self::MAX_URL_LENGTH . ' 个字符');
            return $result;
        }

        // 验证URL格式
        if (!filter_var($this->url, FILTER_VALIDATE_URL)) {
            $result->addError('Web种子URL格式无效');
            return $result;
        }

        // 验证URL协议（只允许HTTP和HTTPS）
        $scheme = parse_url($this->url, PHP_URL_SCHEME);
        if (!in_array($scheme, ['http', 'https'], true)) {
            $result->addError('Web种子URL必须使用HTTP或HTTPS协议');
        }

        // 验证字符编码
        if (!mb_check_encoding($this->url, 'UTF-8')) {
            $result->addError('Web种子URL包含无效的字符编码');
        }

        return $result;
    }

    /**
     * 从数组创建TorrentWebSeed实例
     *
     * @param array<string, mixed> $data 包含url字段的数据数组
     * @return self TorrentWebSeed实例
     * @throws ValidationException 如果数据无效
     */
    public static function fromArray(array $data): self
    {
        if (!isset($data['url'])) {
            throw new ValidationException('Missing required field: url');
        }

        if (!is_string($data['url'])) {
            throw new ValidationException('Field "url" must be a string');
        }

        return new self($data['url']);
    }

    /**
     * 转换为数组
     *
     * @return array<string, mixed> 数组表示
     */
    public function toArray(): array
    {
        return [
            'url' => $this->url,
        ];
    }

    /**
     * 转换为JSON
     *
     * @return string JSON字符串
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }

    /**
     * 转换为JSON序列化格式（用于json_encode）
     *
     * @return array<string, mixed> 可序列化的数据
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * 获取字符串表示
     *
     * @return string URL字符串
     */
    public function __toString(): string
    {
        return $this->url;
    }

    /**
     * 比较两个Web种子是否相等
     *
     * @param TorrentWebSeed $other 另一个Web种子
     * @return bool 是否相等
     */
    public function equals(TorrentWebSeed $other): bool
    {
        return $this->url === $other->getUrl();
    }

    /**
     * 获取摘要信息
     *
     * @return array<string, mixed> 摘要信息
     */
    public function getSummary(): array
    {
        $parsedUrl = parse_url($this->url);

        return [
            'url' => $this->url,
            'scheme' => $parsedUrl['scheme'] ?? null,
            'host' => $parsedUrl['host'] ?? null,
            'port' => $parsedUrl['port'] ?? null,
            'path' => $parsedUrl['path'] ?? null,
            'length' => strlen($this->url),
            'is_valid' => $this->validate()->isValid(),
        ];
    }
}