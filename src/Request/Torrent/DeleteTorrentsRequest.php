<?php
declare(strict_types=1);

namespace PhpQbittorrent\Request\Torrent;

use PhpQbittorrent\Request\AbstractRequest;
use PhpQbittorrent\Contract\ValidationResult;
use PhpQbittorrent\Validation\BasicValidationResult;
use PhpQbittorrent\Exception\ValidationException;

/**
 * 删除Torrent请求对象
 *
 * 用于封装删除Torrent的请求参数和验证逻辑
 */
class DeleteTorrentsRequest extends AbstractRequest
{
    /** @var array<string> 要删除的哈希列表 */
    private array $hashes;

    /** @var bool 是否删除文件 */
    private bool $deleteFiles;

    /** @var bool 是否删除所有 */
    private bool $deleteAll;

    /** @var int 最大哈希数量 */
    private const MAX_HASHES = 1000;

    /**
     * 私有构造函数
     *
     * @param array<string> $hashes 哈希列表
     * @param bool $deleteFiles 是否删除文件
     * @param bool $deleteAll 是否删除所有
     */
    private function __construct(array $hashes, bool $deleteFiles = false, bool $deleteAll = false)
    {
        $this->hashes = $hashes;
        $this->deleteFiles = $deleteFiles;
        $this->deleteAll = $deleteAll;

        parent::__construct([
            'hashes' => $hashes,
            'deleteFiles' => $deleteFiles,
            'deleteAll' => $deleteAll
        ]);

        $this->setEndpoint('/torrents/delete')
             ->setMethod('POST')
             ->setRequiresAuthentication(true);
    }

    /**
     * 创建Builder实例
     *
     * @return DeleteTorrentsRequestBuilder Builder实例
     */
    public static function builder(): DeleteTorrentsRequestBuilder
    {
        return new DeleteTorrentsRequestBuilder();
    }

    /**
     * 创建删除指定哈希的请求
     *
     * @param array<string> $hashes 哈希列表
     * @param bool $deleteFiles 是否删除文件
     * @return self 删除请求实例
     * @throws ValidationException 如果参数无效
     */
    public static function forHashes(array $hashes, bool $deleteFiles = false): self
    {
        $request = new self($hashes, $deleteFiles, false);

        $validation = $request->validate();
        if (!$validation->isValid()) {
            throw ValidationException::fromValidationResult(
                $validation,
                'Delete torrents request validation failed'
            );
        }

        return $request;
    }

    /**
     * 创建删除所有Torrent的请求
     *
     * @param bool $deleteFiles 是否删除文件
     * @return self 删除请求实例
     */
    public static function deleteAll(bool $deleteFiles = false): self
    {
        return new self(['all'], $deleteFiles, true);
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
     * 是否删除文件
     *
     * @return bool 是否删除文件
     */
    public function shouldDeleteFiles(): bool
    {
        return $this->deleteFiles;
    }

    /**
     * 是否删除所有
     *
     * @return bool 是否删除所有
     */
    public function shouldDeleteAll(): bool
    {
        return $this->deleteAll;
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
     * 检查是否为批量删除
     *
     * @return bool 是否为批量删除
     */
    public function isBatchDelete(): bool
    {
        return count($this->hashes) > 1 || $this->deleteAll;
    }

    /**
     * 验证请求参数
     *
     * @return ValidationResult 验证结果
     */
    public function validate(): ValidationResult
    {
        $result = BasicValidationResult::success();

        // 如果不是删除所有，验证哈希列表
        if (!$this->deleteAll) {
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

        // 批量删除的安全警告
        if ($this->isBatchDelete()) {
            if ($this->deleteFiles) {
                $result->addWarning('您即将批量删除Torrent并删除数据文件，此操作不可恢复');
            } else {
                $result->addWarning('您即将批量删除Torrent，请确认操作');
            }
        }

        // 删除所有操作的特殊警告
        if ($this->deleteAll) {
            if ($this->deleteFiles) {
                $result->addWarning('您即将删除所有Torrent并删除数据文件，此操作不可恢复');
            } else {
                $result->addWarning('您即将删除所有Torrent，请确认操作');
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
        $data = [];

        if ($this->deleteAll) {
            $data['hashes'] = 'all';
        } else {
            $data['hashes'] = implode('|', $this->hashes);
        }

        if ($this->deleteFiles) {
            $data['deleteFiles'] = 'true';
        }

        return $data;
    }

    /**
     * 获取请求头
     *
     * @return array<string, string> 请求头数组
     */
    public function getHeaders(): array
    {
        $headers = parent::getHeaders();

        // 添加警告头
        if ($this->isBatchDelete() || $this->deleteAll) {
            $headers['X-Warning'] = 'Batch delete operation';
        }

        if ($this->deleteFiles) {
            $headers['X-Delete-Files'] = 'true';
        }

        return $headers;
    }

    /**
     * 获取删除操作的描述
     *
     * @return string 操作描述
     */
    public function getOperationDescription(): string
    {
        if ($this->deleteAll) {
            if ($this->deleteFiles) {
                return '删除所有Torrent并删除数据文件';
            } else {
                return '删除所有Torrent';
            }
        }

        $count = count($this->hashes);
        if ($count === 1) {
            if ($this->deleteFiles) {
                return '删除1个Torrent并删除数据文件';
            } else {
                return '删除1个Torrent';
            }
        } else {
            if ($this->deleteFiles) {
                return "删除{$count}个Torrent并删除数据文件";
            } else {
                return "删除{$count}个Torrent";
            }
        }
    }

    /**
     * 获取操作的风险等级
     *
     * @return string 风险等级
     */
    public function getRiskLevel(): string
    {
        if ($this->deleteAll && $this->deleteFiles) {
            return '极高';
        } elseif ($this->deleteAll) {
            return '高';
        } elseif ($this->deleteFiles && $this->isBatchDelete()) {
            return '高';
        } elseif ($this->deleteFiles) {
            return '中';
        } elseif ($this->isBatchDelete()) {
            return '中';
        } else {
            return '低';
        }
    }

    /**
     * 获取删除请求的摘要信息
     *
     * @return array<string, mixed> 摘要信息
     */
    public function getSummary(): array
    {
        return [
            'hash_count' => $this->getHashCount(),
            'delete_files' => $this->deleteFiles,
            'delete_all' => $this->deleteAll,
            'is_batch' => $this->isBatchDelete(),
            'operation' => $this->getOperationDescription(),
            'risk_level' => $this->getRiskLevel(),
            'endpoint' => $this->getEndpoint(),
            'method' => $this->getMethod(),
            'requires_auth' => $this->requiresAuthentication(),
        ];
    }
}

/**
 * 删除Torrent请求构建器
 *
 * 使用Builder模式创建DeleteTorrentsRequest实例
 */
class DeleteTorrentsRequestBuilder
{
    private array $hashes = [];
    private bool $deleteFiles = false;
    private bool $deleteAll = false;

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
     * 设置是否删除文件
     *
     * @param bool $deleteFiles 是否删除文件
     * @return self 返回自身以支持链式调用
     */
    public function deleteFiles(bool $deleteFiles = true): self
    {
        $this->deleteFiles = $deleteFiles;
        return $this;
    }

    /**
     * 设置是否删除所有
     *
     * @param bool $deleteAll 是否删除所有
     * @return self 返回自身以支持链式调用
     */
    public function deleteAll(bool $deleteAll = true): self
    {
        $this->deleteAll = $deleteAll;
        return $this;
    }

    /**
     * 构建DeleteTorrentsRequest实例
     *
     * @return DeleteTorrentsRequest 删除Torrent请求实例
     * @throws ValidationException 如果参数无效
     */
    public function build(): DeleteTorrentsRequest
    {
        if (!$this->deleteAll && empty($this->hashes)) {
            throw ValidationException::missingParameter('hashes');
        }

        $request = new DeleteTorrentsRequest($this->hashes, $this->deleteFiles, $this->deleteAll);

        $validation = $request->validate();
        if (!$validation->isValid()) {
            throw ValidationException::fromValidationResult(
                $validation,
                'Delete torrents request validation failed'
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
        if (!$this->deleteAll && empty($this->hashes)) {
            return \PhpQbittorrent\Validation\BasicValidationResult::failure(
                ['哈希列表不能为空']
            );
        }

        $request = new DeleteTorrentsRequest($this->hashes, $this->deleteFiles, $this->deleteAll);
        return $request->validate();
    }
}