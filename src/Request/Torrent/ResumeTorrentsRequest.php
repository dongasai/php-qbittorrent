<?php
declare(strict_types=1);

namespace PhpQbittorrent\Request\Torrent;

use PhpQbittorrent\Request\AbstractRequest;
use PhpQbittorrent\Contract\ValidationResult;
use PhpQbittorrent\Validation\BasicValidationResult;
use PhpQbittorrent\Exception\ValidationException;

/**
 * 恢复Torrent请求对象
 *
 * 用于恢复一个或多个Torrent的下载
 */
class ResumeTorrentsRequest extends AbstractRequest
{
    /** @var array<string> 要恢复的哈希列表 */
    private array $hashes;

    /** @var bool 是否恢复所有 */
    private bool $resumeAll;

    /** @var int 最大哈希数量 */
    private const MAX_HASHES = 1000;

    /**
     * 私有构造函数
     *
     * @param array<string> $hashes 哈希列表
     * @param bool $resumeAll 是否恢复所有
     */
    private function __construct(array $hashes, bool $resumeAll = false)
    {
        $this->hashes = $hashes;
        $this->resumeAll = $resumeAll;

        parent::__construct([
            'hashes' => $hashes,
            'resumeAll' => $resumeAll
        ]);

        $this->setEndpoint('/start')
             ->setMethod('POST')
             ->setRequiresAuthentication(true);
    }

    /**
     * 创建Builder实例
     *
     * @return ResumeTorrentsRequestBuilder Builder实例
     */
    public static function builder(): ResumeTorrentsRequestBuilder
    {
        return new ResumeTorrentsRequestBuilder();
    }

    /**
     * 创建恢复指定哈希的请求
     *
     * @param array<string> $hashes 哈希列表
     * @return self 恢复请求实例
     * @throws ValidationException 如果参数无效
     */
    public static function forHashes(array $hashes): self
    {
        $request = new self($hashes, false);

        $validation = $request->validate();
        if (!$validation->isValid()) {
            throw new ValidationException(
                'Resume torrents request validation failed',
                'VALIDATION_ERROR',
                $validation->getErrors()
            );
        }

        return $request;
    }

    /**
     * 创建恢复所有Torrent的请求
     *
     * @return self 恢复请求实例
     */
    public static function resumeAll(): self
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
     * 是否恢复所有
     *
     * @return bool 是否恢复所有
     */
    public function shouldResumeAll(): bool
    {
        return $this->resumeAll;
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
     * 检查是否为批量恢复
     *
     * @return bool 是否为批量恢复
     */
    public function isBatchResume(): bool
    {
        return count($this->hashes) > 1 || $this->resumeAll;
    }

    /**
     * 验证请求参数
     *
     * @return ValidationResult 验证结果
     */
    public function validate(): ValidationResult
    {
        $result = BasicValidationResult::success();

        // 如果不是恢复所有，验证哈希列表
        if (!$this->resumeAll) {
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
        if ($this->resumeAll) {
            return ['hashes' => 'all'];
        }

        return ['hashes' => implode('|', $this->hashes)];
    }

    /**
     * 获取恢复操作的描述
     *
     * @return string 操作描述
     */
    public function getOperationDescription(): string
    {
        if ($this->resumeAll) {
            return '恢复所有Torrent';
        }

        $count = count($this->hashes);
        if ($count === 1) {
            return '恢复1个Torrent';
        } else {
            return "恢复{$count}个Torrent";
        }
    }

    /**
     * 获取恢复请求的摘要信息
     *
     * @return array<string, mixed> 摘要信息
     */
    public function getSummary(): array
    {
        return [
            'hash_count' => $this->getHashCount(),
            'resume_all' => $this->resumeAll,
            'is_batch' => $this->isBatchResume(),
            'operation' => $this->getOperationDescription(),
            'endpoint' => $this->getEndpoint(),
            'method' => $this->getMethod(),
            'requires_auth' => $this->requiresAuthentication(),
        ];
    }
}

/**
 * 恢复Torrent请求构建器
 *
 * 使用Builder模式创建ResumeTorrentsRequest实例
 */
class ResumeTorrentsRequestBuilder
{
    private array $hashes = [];
    private bool $resumeAll = false;

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
     * 设置是否恢复所有
     *
     * @param bool $resumeAll 是否恢复所有
     * @return self 返回自身以支持链式调用
     */
    public function resumeAll(bool $resumeAll = true): self
    {
        $this->resumeAll = $resumeAll;
        return $this;
    }

    /**
     * 构建ResumeTorrentsRequest实例
     *
     * @return ResumeTorrentsRequest 恢复Torrent请求实例
     * @throws ValidationException 如果参数无效
     */
    public function build(): ResumeTorrentsRequest
    {
        if (!$this->resumeAll && empty($this->hashes)) {
            throw ValidationException::missingParameter('hashes');
        }

        $request = new ResumeTorrentsRequest($this->hashes, $this->resumeAll);

        $validation = $request->validate();
        if (!$validation->isValid()) {
            throw new ValidationException(
                'Resume torrents request validation failed',
                'VALIDATION_ERROR',
                $validation->getErrors()
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
        if (!$this->resumeAll && empty($this->hashes)) {
            return \PhpQbittorrent\Validation\BasicValidationResult::failure(
                ['哈希列表不能为空']
            );
        }

        $request = new ResumeTorrentsRequest($this->hashes, $this->resumeAll);
        return $request->validate();
    }
}