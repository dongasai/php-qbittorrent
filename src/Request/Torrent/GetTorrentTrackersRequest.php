<?php
declare(strict_types=1);

namespace PhpQbittorrent\Request\Torrent;

use PhpQbittorrent\Request\AbstractRequest;
use PhpQbittorrent\Contract\ValidationResult;
use PhpQbittorrent\Validation\BasicValidationResult;

/**
 * 获取Torrent Trackers请求对象
 *
 * 用于获取特定Torrent的Tracker列表
 */
class GetTorrentTrackersRequest extends AbstractRequest
{
    /** @var string 种子哈希 */
    private string $hash;

    /** @var int 最小哈希长度 */
    private const MIN_HASH_LENGTH = 40;

    /** @var int 最大哈希长度 */
    private const MAX_HASH_LENGTH = 40;

    /**
     * 私有构造函数
     *
     * @param string $hash 种子哈希
     */
    private function __construct(string $hash)
    {
        parent::__construct(['hash' => $hash]);

        $this->hash = $hash;
        $this->setEndpoint('/trackers')
             ->setMethod('GET')
             ->setRequiresAuthentication(true);
    }

    /**
     * 创建请求实例
     *
     * @param string $hash 种子哈希
     * @return self 请求实例
     */
    public static function create(string $hash): self
    {
        return new self($hash);
    }

    /**
     * 获取种子哈希
     *
     * @return string 种子哈希
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

        // 验证哈希长度
        if (strlen($this->hash) < self::MIN_HASH_LENGTH) {
            $result->addError("哈希长度不能少于 " . self::MIN_HASH_LENGTH . " 个字符");
        } elseif (strlen($this->hash) > self::MAX_HASH_LENGTH) {
            $result->addError("哈希长度不能超过 " . self::MAX_HASH_LENGTH . " 个字符");
        }

        // 验证哈希格式（40位十六进制字符）
        if (!preg_match('/^[a-fA-F0-9]{40}$/', $this->hash)) {
            $result->addError('哈希格式无效，必须是40位十六进制字符');
        }

        // 验证哈希不为空
        if (empty(trim($this->hash))) {
            $result->addError('种子哈希不能为空');
        }

        return $result;
    }

    /**
     * 转换为数组格式（用于HTTP查询参数）
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
     * 获取请求的摘要信息
     *
     * @return array<string, mixed> 摘要信息
     */
    public function getSummary(): array
    {
        return [
            'hash' => $this->hash,
            'hash_length' => strlen($this->hash),
            'endpoint' => $this->getEndpoint(),
            'method' => $this->getMethod(),
            'requires_auth' => $this->requiresAuthentication(),
        ];
    }
}