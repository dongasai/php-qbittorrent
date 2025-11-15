<?php
declare(strict_types=1);

namespace PhpQbittorrent\Model;

use PhpQbittorrent\Contract\ArrayableInterface;
use PhpQbittorrent\Contract\JsonableInterface;
use PhpQbittorrent\Exception\ValidationException;

/**
 * Torrent Piece状态模型
 *
 * 表示torrent中单个piece的状态
 */
class TorrentPieceState implements ArrayableInterface, JsonableInterface
{
    /**
     * Piece状态常量
     */
    public const STATE_NOT_DOWNLOADED = 0;
    public const STATE_DOWNLOADING = 1;
    public const STATE_DOWNLOADED = 2;

    /**
     * 状态映射
     */
    private const STATE_MAP = [
        self::STATE_NOT_DOWNLOADED => 'Not downloaded',
        self::STATE_DOWNLOADING => 'Now downloading',
        self::STATE_DOWNLOADED => 'Already downloaded',
    ];

    /** @var int Piece状态值 */
    private int $state;

    /** @var int Piece索引 */
    private int $index;

    /**
     * 构造函数
     *
     * @param int $state Piece状态值
     * @param int $index Piece索引
     * @throws ValidationException 如果参数无效
     */
    public function __construct(int $state, int $index)
    {
        $this->validateState($state);
        $this->validateIndex($index);

        $this->state = $state;
        $this->index = $index;
    }

    /**
     * 从数值创建状态
     *
     * @param int $state 状态值
     * @param int $index Piece索引
     * @return self 状态实例
     * @throws ValidationException 如果状态无效
     */
    public static function fromState(int $state, int $index): self
    {
        return new self($state, $index);
    }

    /**
     * 从数组创建状态
     *
     * @param array{state: int, index: int} $data 数组数据
     * @return self 状态实例
     * @throws ValidationException 如果数据无效
     */
    public static function fromArray(array $data): self
    {
        if (!isset($data['state'])) {
            throw new ValidationException('Missing required field: state');
        }

        if (!isset($data['index'])) {
            throw new ValidationException('Missing required field: index');
        }

        if (!is_int($data['state'])) {
            throw new ValidationException('Field "state" must be an integer');
        }

        if (!is_int($data['index'])) {
            throw new ValidationException('Field "index" must be an integer');
        }

        return new self($data['state'], $data['index']);
    }

    /**
     * 从JSON创建状态
     *
     * @param string $json JSON字符串
     * @return self 状态实例
     * @throws ValidationException 如果JSON无效或数据无效
     */
    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ValidationException('Invalid JSON: ' . json_last_error_msg());
        }

        if (!is_array($data)) {
            throw new ValidationException('JSON must decode to an array');
        }

        return self::fromArray($data);
    }

    /**
     * 获取状态值
     *
     * @return int 状态值
     */
    public function getState(): int
    {
        return $this->state;
    }

    /**
     * 获取Piece索引
     *
     * @return int Piece索引
     */
    public function getIndex(): int
    {
        return $this->index;
    }

    /**
     * 设置状态值
     *
     * @param int $state 新的状态值
     * @return self 返回自身以支持链式调用
     * @throws ValidationException 如果状态无效
     */
    public function setState(int $state): self
    {
        $this->validateState($state);
        $this->state = $state;
        return $this;
    }

    /**
     * 设置Piece索引
     *
     * @param int $index 新的索引值
     * @return self 返回自身以支持链式调用
     * @throws ValidationException 如果索引无效
     */
    public function setIndex(int $index): self
    {
        $this->validateIndex($index);
        $this->index = $index;
        return $this;
    }

    /**
     * 检查是否未下载
     *
     * @return bool 是否未下载
     */
    public function isNotDownloaded(): bool
    {
        return $this->state === self::STATE_NOT_DOWNLOADED;
    }

    /**
     * 检查是否正在下载
     *
     * @return bool 是否正在下载
     */
    public function isDownloading(): bool
    {
        return $this->state === self::STATE_DOWNLOADING;
    }

    /**
     * 检查是否已下载
     *
     * @return bool 是否已下载
     */
    public function isDownloaded(): bool
    {
        return $this->state === self::STATE_DOWNLOADED;
    }

    /**
     * 获取状态描述
     *
     * @return string 状态描述
     */
    public function getStateDescription(): string
    {
        return self::STATE_MAP[$this->state] ?? 'Unknown state';
    }

    /**
     * 获取所有可能的状态
     *
     * @return array<int, string> 状态映射
     */
    public static function getAllStates(): array
    {
        return self::STATE_MAP;
    }

    /**
     * 验证状态值
     *
     * @param int $state 状态值
     * @throws ValidationException 如果状态无效
     */
    private function validateState(int $state): void
    {
        if (!isset(self::STATE_MAP[$state])) {
            throw new ValidationException(sprintf(
                'Invalid state value: %d. Valid values are: %s',
                $state,
                implode(', ', array_keys(self::STATE_MAP))
            ));
        }
    }

    /**
     * 验证索引值
     *
     * @param int $index 索引值
     * @throws ValidationException 如果索引无效
     */
    private function validateIndex(int $index): void
    {
        if ($index < 0) {
            throw new ValidationException('Piece index cannot be negative');
        }
    }

    /**
     * 验证状态对象
     *
     * @return ValidationResult 验证结果
     */
    public function validate(): \PhpQbittorrent\Contract\ValidationResult
    {
        $result = \PhpQbittorrent\Validation\BasicValidationResult::success();

        try {
            $this->validateState($this->state);
        } catch (ValidationException $e) {
            $result->addError('Invalid state: ' . $e->getMessage());
        }

        try {
            $this->validateIndex($this->index);
        } catch (ValidationException $e) {
            $result->addError('Invalid index: ' . $e->getMessage());
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
            'index' => $this->index,
            'state' => $this->state,
            'state_description' => $this->getStateDescription(),
        ];
    }

    /**
     * 转换为JSON
     *
     * @return string JSON字符串
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * 转换为字符串
     *
     * @return string 字符串表示
     */
    public function __toString(): string
    {
        return sprintf(
            'Piece #%d: %s (%d)',
            $this->index,
            $this->getStateDescription(),
            $this->state
        );
    }

    /**
     * 检查两个状态是否相等
     *
     * @param TorrentPieceState $other 另一个状态对象
     * @return bool 是否相等
     */
    public function equals(TorrentPieceState $other): bool
    {
        return $this->state === $other->getState()
            && $this->index === $other->getIndex();
    }

    /**
     * 获取摘要信息
     *
     * @return array<string, mixed> 摘要信息
     */
    public function getSummary(): array
    {
        return [
            'index' => $this->index,
            'state' => $this->state,
            'state_description' => $this->getStateDescription(),
            'is_not_downloaded' => $this->isNotDownloaded(),
            'is_downloading' => $this->isDownloading(),
            'is_downloaded' => $this->isDownloaded(),
        ];
    }
}