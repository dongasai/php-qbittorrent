<?php
declare(strict_types=1);

namespace PhpQbittorrent\Request\Torrent;

use PhpQbittorrent\Request\AbstractRequest;
use PhpQbittorrent\Contract\ValidationResult;
use PhpQbittorrent\Validation\BasicValidationResult;

/**
 * 获取种子文件列表请求对象
 *
 * 用于获取特定种子包含的所有文件信息
 */
class GetTorrentFilesRequest extends AbstractRequest
{
    /** @var string 种子哈希 */
    private string $hash;

    /** @var array<int>|null 文件索引列表 */
    private ?array $indexes;

    /** @var int 最小哈希长度 */
    private const MIN_HASH_LENGTH = 40;

    /** @var int 最大哈希长度 */
    private const MAX_HASH_LENGTH = 40;

    /**
     * 私有构造函数
     *
     * @param string $hash 种子哈希
     * @param array<int>|null $indexes 文件索引列表（可选）
     */
    private function __construct(string $hash, ?array $indexes = null)
    {
        parent::__construct(['hash' => $hash, 'indexes' => $indexes]);

        $this->hash = $hash;
        $this->indexes = $indexes;
    }

    /**
     * 创建请求实例
     *
     * @param string $hash 种子哈希
     * @param array<int>|null $indexes 文件索引列表（可选）
     * @return self 请求实例
     */
    public static function create(string $hash, ?array $indexes = null): self
    {
        return new self($hash, $indexes);
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
     * 获取文件索引列表
     *
     * @return array<int>|null 文件索引列表
     */
    public function getIndexes(): ?array
    {
        return $this->indexes;
    }

    /**
     * 验证请求参数
     *
     * @return ValidationResult 验证结果
     */
    public function validate(): ValidationResult
    {
        $result = BasicValidationResult::success();

        // 验证哈希不为空
        if (empty($this->hash) || trim($this->hash) === '') {
            $result->addError('种子哈希不能为空');
        }

        // 验证哈希长度
        if (strlen($this->hash) < self::MIN_HASH_LENGTH) {
            $result->addError("哈希长度不能少于 " . self::MIN_HASH_LENGTH . " 个字符");
        } elseif (strlen($this->hash) > self::MAX_HASH_LENGTH) {
            $result->addError("哈希长度不能超过 " . self::MAX_HASH_LENGTH . " 个字符");
        }

        // 验证哈希格式（40位十六进制字符）- 只有在长度正确时才检查格式
        if (strlen($this->hash) === self::MIN_HASH_LENGTH && strlen($this->hash) === self::MAX_HASH_LENGTH && !preg_match('/^[a-fA-F0-9]{40}$/', $this->hash)) {
            $result->addError('哈希格式无效，必须是40位十六进制字符');
        }

        // 验证索引数组（如果提供）
        if ($this->indexes !== null) {
            if (!is_array($this->indexes)) {
                $result->addError('文件索引必须是数组');
            } else {
                foreach ($this->indexes as $index) {
                    if (!is_int($index) || $index < 0) {
                        $result->addError('文件索引必须是非负整数');
                        break;
                    }
                }
            }
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
        $params = [
            'hash' => $this->hash,
        ];

        if ($this->indexes !== null) {
            $params['indexes'] = implode('|', $this->indexes);
        }

        return $params;
    }

    /**
     * 获取请求端点
     *
     * @return string 请求端点
     */
    public function getEndpoint(): string
    {
        return '/files';
    }

    /**
     * 获取请求方法
     *
     * @return string 请求方法
     */
    public function getMethod(): string
    {
        return 'GET';
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
            'indexes_count' => $this->indexes !== null ? count($this->indexes) : 0,
            'endpoint' => $this->getEndpoint(),
            'method' => $this->getMethod(),
        ];
    }
}