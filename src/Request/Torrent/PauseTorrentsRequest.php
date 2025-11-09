<?php
declare(strict_types=1);

namespace Dongasai\qBittorrent\Request\Torrent;

use Dongasai\qBittorrent\Request\AbstractRequest;
use Dongasai\qBittorrent\Contract\ValidationResult;
use Dongasai\qBittorrent\Validation\BasicValidationResult;
use Dongasai\qBittorrent\Exception\ValidationException;

/**
 * 暂停Torrent请求对象
 *
 * 用于封装暂停Torrent的请求参数和验证逻辑
 */
class PauseTorrentsRequest extends AbstractRequest
{
    /** @var array<string> 要暂停的哈希列表 */
    private array $hashes;

    /** @var bool 是否暂停所有 */
    private bool $pauseAll;

    /** @var int 最大哈希数量 */
    private const MAX_HASHES = 1000;

    /**
     * 私有构造函数
     *
     * @param array<string> $hashes 哈希列表
     * @param bool $pauseAll 是否暂停所有
     */
    private function __construct(array $hashes, bool $pauseAll = false)
    {
        $this->hashes = $hashes;
        $this->pauseAll = $pauseAll;

        parent::__construct([
            'hashes' => $hashes,
            'pauseAll' => $pauseAll
        ]);

        $this->setEndpoint('/torrents/stop')
             ->setMethod('POST')
             ->setRequiresAuthentication(true);
    }

    /**
     * 创建Builder实例
     *
     * @return PauseTorrentsRequestBuilder Builder实例
     */
    public static function builder(): PauseTorrentsRequestBuilder
    {
        return new PauseTorrentsRequestBuilder();
    }

    /**
     * 创建暂停指定哈希的请求
     *
     * @param array<string> $hashes 哈希列表
     * @return self 暂停请求实例
     * @throws ValidationException 如果参数无效
     */
    public static function forHashes(array $hashes): self
    {
        $request = new self($hashes, false);

        $validation = $request->validate();
        if (!$validation->isValid()) {
            throw ValidationException::fromValidationResult(
                $validation,
                'Pause torrents request validation failed'
            );
        }

        return $request;
    }

    /**
     * 创建暂停所有Torrent的请求
     *
     * @return self 暂停请求实例
     */
    public static function pauseAll(): self
    {
        return new self(['all'], true);
    }

    /**
     * 获取哈希列表
     *
     * @return array<string> 哈希列表
     */
    public function getHashes(): array
    {
        return $this->hashes;
    }

    /**
     * 是否暂停所有
     *
     * @return bool 是否暂停所有
     */
    public function shouldPauseAll(): bool
    {
        return $this->pauseAll;
    }

    /**
     * 获取哈希数量
     *
     * @return int 哈希数量
     */
    public function getHashCount(): int
    {
        return count($this->hashes);
    }

    /**
     * 检查是否为批量暂停
     *
     * @return bool 是否为批量暂停
     */
    public function isBatchPause(): bool
    {
        return count($this->hashes) > 1 || $this->pauseAll;
    }

    /**
     * 验证请求参数
     *
     * @return ValidationResult 验证结果
     */
    public function validate(): ValidationResult
    {
        $result = BasicValidationResult::success();

        // 如果不是暂停所有，验证哈希列表
        if (!$this->pauseAll) {
            if (empty($this->hashes)) {
                $result->addError('哈希列表不能为空');
            } elseif (count($this->hashes) > self::MAX_HASHES) {
                $result->addError("哈希数量不能超过 " . self::MAX_HASHES);
            } else {
                foreach ($this->hashes as $hash) {
                    if (empty(trim($hash))) {
                        $result->addError('哈希不能为空');
                    } elseif (!preg_match('/^[a-fA-F0-9]{40}$/', $hash)) {
                        $result->addError("无效的哈希格式: {$hash}");
                    }
                }
            }
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
        if ($this->pauseAll) {
            return ['hashes' => 'all'];
        }

        return ['hashes' => implode('|', $this->hashes)];
    }

    /**
     * 获取暂停操作的描述
     *
     * @return string 操作描述
     */
    public function getOperationDescription(): string
    {
        if ($this->pauseAll) {
            return '暂停所有Torrent';
        }

        $count = count($this->hashes);
        if ($count === 1) {
            return '暂停1个Torrent';
        } else {
            return "暂停{$count}个Torrent";
        }
    }

    /**
     * 获取暂停请求的摘要信息
     *
     * @return array<string, mixed> 摘要信息
     */
    public function getSummary(): array
    {
        return [
            'hash_count' => $this->getHashCount(),
            'pause_all' => $this->pauseAll,
            'is_batch' => $this->isBatchPause(),
            'operation' => $this->getOperationDescription(),
            'endpoint' => $this->getEndpoint(),
            'method' => $this->getMethod(),
            'requires_auth' => $this->requiresAuthentication(),
        ];
    }
}

/**
 * 暂停Torrent请求构建器
 *
 * 使用Builder模式创建PauseTorrentsRequest实例
 */
class PauseTorrentsRequestBuilder
{
    private array $hashes = [];
    private bool $pauseAll = false;

    /**
     * 添加哈希
     *
     * @param string $hash 哈希值
     * @return self 返回自身以支持链式调用
     */
    public function addHash(string $hash): self
    {
        $this->hashes[] = $hash;
        return $this;
    }

    /**
     * 添加多个哈希
     *
     * @param array<string> $hashes 哈希列表
     * @return self 返回自身以支持链式调用
     */
    public function addHashes(array $hashes): self
    {
        $this->hashes = array_merge($this->hashes, $hashes);
        return $this;
    }

    /**
     * 设置哈希列表
     *
     * @param array<string> $hashes 哈希列表
     * @return self 返回自身以支持链式调用
     */
    public function hashes(array $hashes): self
    {
        $this->hashes = $hashes;
        return $this;
    }

    /**
     * 设置是否暂停所有
     *
     * @param bool $pauseAll 是否暂停所有
     * @return self 返回自身以支持链式调用
     */
    public function pauseAll(bool $pauseAll = true): self
    {
        $this->pauseAll = $pauseAll;
        return $this;
    }

    /**
     * 构建PauseTorrentsRequest实例
     *
     * @return PauseTorrentsRequest 暂停Torrent请求实例
     * @throws ValidationException 如果参数无效
     */
    public function build(): PauseTorrentsRequest
    {
        if (!$this->pauseAll && empty($this->hashes)) {
            throw ValidationException::missingParameter('hashes');
        }

        $request = new PauseTorrentsRequest($this->hashes, $this->pauseAll);

        $validation = $request->validate();
        if (!$validation->isValid()) {
            throw ValidationException::fromValidationResult(
                $validation,
                'Pause torrents request validation failed'
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
        if (!$this->pauseAll && empty($this->hashes)) {
            return \Dongasai\qBittorrent\Validation\BasicValidationResult::failure(
                ['哈希列表不能为空']
            );
        }

        $request = new PauseTorrentsRequest($this->hashes, $this->pauseAll);
        return $request->validate();
    }
}