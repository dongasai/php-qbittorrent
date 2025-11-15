<?php
declare(strict_types=1);

namespace PhpQbittorrent\Response\Torrent;

use PhpQbittorrent\Response\AbstractResponse;
use PhpQbittorrent\Contract\ArrayableInterface;
use PhpQbittorrent\Model\TorrentPieceState;
use PhpQbittorrent\Exception\ResponseParseException;
use PhpQbittorrent\Contract\ValidationResult;
use PhpQbittorrent\Validation\BasicValidationResult;
use PhpQbittorrent\Exception\ValidationException;

/**
 * Torrent Piece状态响应对象
 *
 * 封装获取Piece状态列表的响应数据
 */
class TorrentPieceStatesResponse extends AbstractResponse implements ArrayableInterface
{
    /** @var array<TorrentPieceState> Piece状态列表 */
    private array $pieceStates;

    /** @var string Torrent哈希值 */
    private string $torrentHash;

    /**
     * 构造函数
     *
     * @param array<TorrentPieceState> $pieceStates Piece状态列表
     * @param string $torrentHash Torrent哈希值
     * @param array $rawResponse 原始响应数据
     * @param array $responseData 解析后的响应数据
     */
    public function __construct(
        array $pieceStates,
        string $torrentHash,
        array $rawResponse,
        array $responseData
    ) {
        $this->pieceStates = $pieceStates;
        $this->torrentHash = $torrentHash;

        parent::__construct($rawResponse, $responseData);
    }

    /**
     * 创建成功响应
     *
     * @param array<TorrentPieceState> $pieceStates Piece状态列表
     * @param string $torrentHash Torrent哈希值
     * @param array $rawResponse 原始响应数据
     * @param array $responseData 解析后的响应数据
     * @return self 响应实例
     * @throws ValidationException 如果数据无效
     */
    public static function success(
        array $pieceStates,
        string $torrentHash,
        array $rawResponse,
        array $responseData
    ): self {
        $response = new self($pieceStates, $torrentHash, $rawResponse, $responseData);

        $validation = $response->validate();
        if (!$validation->isValid()) {
            throw ValidationException::fromValidationResult(
                $validation,
                'TorrentPieceStatesResponse validation failed'
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
        string $errorMessage = 'Failed to get torrent piece states'
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

        // 处理空响应的情况
        if (empty($responseData)) {
            return self::success([], $torrentHash, $rawResponse, []);
        }

        if (!is_array($responseData)) {
            throw new ResponseParseException('Response data must be an array');
        }

        $pieceStates = [];
        foreach ($responseData as $index => $stateValue) {
            try {
                if (!is_int($stateValue)) {
                    throw new ResponseParseException("Piece state at index {$index} must be an integer");
                }

                if ($stateValue < 0 || $stateValue > 2) {
                    throw new ResponseParseException("Invalid piece state value {$stateValue} at index {$index}");
                }

                $pieceStates[] = TorrentPieceState::fromState($stateValue, $index);
            } catch (\Exception $e) {
                throw new ResponseParseException(
                    "Failed to parse piece state at index {$index}: " . $e->getMessage()
                );
            }
        }

        return self::success($pieceStates, $torrentHash, $rawResponse, $responseData);
    }

    /**
     * 获取Piece状态列表
     *
     * @return array<TorrentPieceState> Piece状态列表
     */
    public function getPieceStates(): array
    {
        return $this->pieceStates;
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
     * 获取Piece总数
     *
     * @return int Piece总数
     */
    public function getCount(): int
    {
        return count($this->pieceStates);
    }

    /**
     * 检查是否有Piece数据
     *
     * @return bool 是否有Piece数据
     */
    public function hasPieces(): bool
    {
        return !empty($this->pieceStates);
    }

    /**
     * 根据索引查找Piece状态
     *
     * @param int $index Piece索引
     * @return TorrentPieceState|null 找到的Piece状态或null
     */
    public function findByIndex(int $index): ?TorrentPieceState
    {
        foreach ($this->pieceStates as $pieceState) {
            if ($pieceState->getIndex() === $index) {
                return $pieceState;
            }
        }

        return null;
    }

    /**
     * 获取所有未下载的Piece
     *
     * @return array<TorrentPieceState> 未下载的Piece列表
     */
    public function getNotDownloadedPieces(): array
    {
        return array_filter(
            $this->pieceStates,
            fn($pieceState) => $pieceState->isNotDownloaded()
        );
    }

    /**
     * 获取所有正在下载的Piece
     *
     * @return array<TorrentPieceState> 正在下载的Piece列表
     */
    public function getDownloadingPieces(): array
    {
        return array_filter(
            $this->pieceStates,
            fn($pieceState) => $pieceState->isDownloading()
        );
    }

    /**
     * 获取所有已下载的Piece
     *
     * @return array<TorrentPieceState> 已下载的Piece列表
     */
    public function getDownloadedPieces(): array
    {
        return array_filter(
            $this->pieceStates,
            fn($pieceState) => $pieceState->isDownloaded()
        );
    }

    /**
     * 获取下载进度百分比
     *
     * @return float 下载进度百分比（0-100）
     */
    public function getDownloadProgress(): float
    {
        if (empty($this->pieceStates)) {
            return 0.0;
        }

        $totalCount = count($this->pieceStates);
        $downloadedCount = count($this->getDownloadedPieces());
        $downloadingCount = count($this->getDownloadingPieces());

        // 已下载的计为100%，正在下载的计为50%
        $progress = ($downloadedCount * 1.0 + $downloadingCount * 0.5) / $totalCount * 100;

        return min(100.0, max(0.0, $progress));
    }

    /**
     * 获取完成度统计
     *
     * @return array<string, mixed> 完成度统计
     */
    public function getCompletionStats(): array
    {
        $total = count($this->pieceStates);
        if ($total === 0) {
            return [
                'total' => 0,
                'not_downloaded' => 0,
                'downloading' => 0,
                'downloaded' => 0,
                'not_downloaded_percent' => 0.0,
                'downloading_percent' => 0.0,
                'downloaded_percent' => 0.0,
                'download_progress' => 0.0,
            ];
        }

        $notDownloaded = count($this->getNotDownloadedPieces());
        $downloading = count($this->getDownloadingPieces());
        $downloaded = count($this->getDownloadedPieces());

        return [
            'total' => $total,
            'not_downloaded' => $notDownloaded,
            'downloading' => $downloading,
            'downloaded' => $downloaded,
            'not_downloaded_percent' => round(($notDownloaded / $total) * 100, 2),
            'downloading_percent' => round(($downloading / $total) * 100, 2),
            'downloaded_percent' => round(($downloaded / $total) * 100, 2),
            'download_progress' => round($this->getDownloadProgress(), 2),
        ];
    }

    /**
     * 验证响应数据
     *
     * @return ValidationResult 验证结果
     */
    public function validate(): ValidationResult
    {
        $result = BasicValidationResult::success();

        // 验证Piece状态列表
        foreach ($this->pieceStates as $index => $pieceState) {
            if (!$pieceState instanceof TorrentPieceState) {
                $result->addError("Piece state at index {$index} is not a TorrentPieceState instance");
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
            'piece_states' => array_map(fn($pieceState) => $pieceState->toArray(), $this->pieceStates),
            'torrent_hash' => $this->torrentHash,
            'count' => $this->getCount(),
            'has_pieces' => $this->hasPieces(),
            'completion_stats' => $this->getCompletionStats(),
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
            'pieces_count' => $this->getCount(),
            'has_pieces' => $this->hasPieces(),
            'download_progress' => round($this->getDownloadProgress(), 2),
            'completion_stats' => $this->getCompletionStats(),
            'response_valid' => $this->validate()->isValid(),
            'response_data_size' => count($this->getResponseData()),
        ];
    }
}