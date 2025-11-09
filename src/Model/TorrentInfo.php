<?php
declare(strict_types=1);

namespace PhpQbittorrent\Model;

use PhpQbittorrent\Util\ValidationHelper;

/**
 * Torrent信息模型
 *
 * 封装qBittorrent中的torrent信息数据
 */
final class TorrentInfo
{
    private string $hash;
    private string $name;
    private int $size;
    private float $progress;
    private int $dlSpeed;
    private int $upSpeed;
    private int $priority;
    private int $numSeeds;
    private int $numComplete;
    private int $numLeechs;
    private int $numIncomplete;
    private float $ratio;
    private int $eta;
    private string $state;
    private bool $seqDl;
    private bool $fLPiecePrio;
    private string $tracker;
    private int $additionDate;
    private int $completionDate;
    private int $trackerTier;
    private string $tags;
    private string $savePath;
    private ?string $comment = null;
    private ?int $totalSize = null;
    private ?int $completed = null;
    private ?int $maxRatio = null;
    private ?int $maxSeedingTime = null;
    private ?float $ratioLimit = null;
    private ?int $seedingTimeLimit = null;
    private ?float $downloaded = null;
    private ?float $uploaded = null;
    private ?int $downloadSpeedLimit = null;
    private ?int $uploadSpeedLimit = null;
    private ?int $seedingTime = null;
    private ?int $downloadTime = null;
    private ?int $inactiveSeedingTime = null;
    private ?int $reannounce = null;
    private ?int $lastActivity = null;
    private ?float $availability = null;
    private ?int $piecesNum = null;
    private ?int $pieceSize = null;
    private ?string $pieceLength = null;
    private ?bool $isChecking = null;
    private ?bool $isPaused = null;
    private ?bool $isAutoManaged = null;
    private ?bool $isForced = null;
    private ?bool $isSequential = null;
    private ?bool $isFirstLastPiece = null;

    /**
     * 从API响应数据创建TorrentInfo实例
     *
     * @param array $data API响应数据
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $instance = new self();

        // 必需字段
        $instance->hash = ValidationHelper::stringLength($data['hash'] ?? '', 1, 40, 'hash');
        $instance->name = ValidationHelper::stringLength($data['name'] ?? '', 1, 1024, 'name');

        // 数字字段
        $instance->size = ValidationHelper::integer($data['size'] ?? 0, 0, null, 'size');
        $instance->progress = ValidationHelper::float($data['progress'] ?? 0.0, 0.0, 1.0, 'progress');
        $instance->dlSpeed = ValidationHelper::integer($data['dlspeed'] ?? 0, 0, null, 'dl_speed');
        $instance->upSpeed = ValidationHelper::integer($data['upspeed'] ?? 0, 0, null, 'up_speed');
        $instance->priority = ValidationHelper::integer($data['priority'] ?? 1, null, null, 'priority');
        $instance->numSeeds = ValidationHelper::integer($data['num_seeds'] ?? 0, 0, null, 'num_seeds');
        $instance->numComplete = ValidationHelper::integer($data['num_complete'] ?? 0, 0, null, 'num_complete');
        $instance->numLeechs = ValidationHelper::integer($data['num_leechs'] ?? 0, 0, null, 'num_leechs');
        $instance->numIncomplete = ValidationHelper::integer($data['num_incomplete'] ?? 0, 0, null, 'num_incomplete');
        $instance->ratio = ValidationHelper::float($data['ratio'] ?? 0.0, 0.0, null, 'ratio');
        $instance->eta = ValidationHelper::integer($data['eta'] ?? -1, -1, null, 'eta');
        $instance->additionDate = ValidationHelper::integer($data['added_on'] ?? 0, 0, null, 'addition_date');
        $instance->completionDate = ValidationHelper::integer($data['completion_on'] ?? 0, 0, null, 'completion_date');
        $instance->trackerTier = ValidationHelper::integer($data['tracker_tier'] ?? 0, null, null, 'tracker_tier');

        // 字符串字段
        $instance->state = ValidationHelper::enum(
            $data['state'] ?? 'unknown',
            ['unknown', 'error', 'missingFiles', 'uploading', 'pausedUP', 'stalledUP', 'checkingUP', 'forcedUP', 'allocating', 'downloading', 'metaDL', 'pausedDL', 'stalledDL', 'checkingDL', 'forcedDL', 'checkingResumeData', 'moving', 'queued', 'queuedDL', 'queuedUP'],
            'state'
        );
        $instance->tracker = ValidationHelper::stringLength($data['tracker'] ?? '', 0, 2048, 'tracker');
        $instance->tags = ValidationHelper::stringLength($data['tags'] ?? '', 0, 1024, 'tags');
        $instance->savePath = ValidationHelper::stringLength($data['save_path'] ?? '', 1, 2048, 'save_path');

        // 布尔字段
        $instance->seqDl = ValidationHelper::boolean($data['seq_dl'] ?? false, 'seq_dl');
        $instance->fLPiecePrio = ValidationHelper::boolean($data['f_l_piece_prio'] ?? false, 'f_l_piece_prio');

        // 可选字段
        $instance->comment = $data['comment'] ?? null;
        $instance->totalSize = isset($data['total_size']) ? ValidationHelper::integer($data['total_size'], 0, null, 'total_size') : null;
        $instance->completed = isset($data['completed']) ? ValidationHelper::integer($data['completed'], 0, null, 'completed') : null;
        $instance->maxRatio = isset($data['max_ratio']) ? ValidationHelper::integer($data['max_ratio'], -1, null, 'max_ratio') : null;
        $instance->maxSeedingTime = isset($data['max_seeding_time']) ? ValidationHelper::integer($data['max_seeding_time'], -1, null, 'max_seeding_time') : null;
        $instance->ratioLimit = isset($data['ratio_limit']) ? ValidationHelper::float($data['ratio_limit'], -1.0, null, 'ratio_limit') : null;
        $instance->seedingTimeLimit = isset($data['seeding_time_limit']) ? ValidationHelper::integer($data['seeding_time_limit'], -1, null, 'seeding_time_limit') : null;
        $instance->downloaded = isset($data['downloaded']) ? ValidationHelper::float($data['downloaded'], 0.0, null, 'downloaded') : null;
        $instance->uploaded = isset($data['uploaded']) ? ValidationHelper::float($data['uploaded'], 0.0, null, 'uploaded') : null;
        $instance->downloadSpeedLimit = isset($data['dl_limit']) ? ValidationHelper::integer($data['dl_limit'], 0, null, 'dl_limit') : null;
        $instance->uploadSpeedLimit = isset($data['up_limit']) ? ValidationHelper::integer($data['up_limit'], 0, null, 'up_limit') : null;
        $instance->seedingTime = isset($data['seeding_time']) ? ValidationHelper::integer($data['seeding_time'], 0, null, 'seeding_time') : null;
        $instance->downloadTime = isset($data['download_time']) ? ValidationHelper::integer($data['download_time'], 0, null, 'download_time') : null;
        $instance->inactiveSeedingTime = isset($data['inactive_seeding_time']) ? ValidationHelper::integer($data['inactive_seeding_time'], 0, null, 'inactive_seeding_time') : null;
        $instance->reannounce = isset($data['reannounce']) ? ValidationHelper::integer($data['reannounce'], 0, null, 'reannounce') : null;
        $instance->lastActivity = isset($data['last_activity']) ? ValidationHelper::integer($data['last_activity'], 0, null, 'last_activity') : null;
        $instance->availability = isset($data['availability']) ? ValidationHelper::float($data['availability'], 0.0, 1.0, 'availability') : null;
        $instance->piecesNum = isset($data['pieces_num']) ? ValidationHelper::integer($data['pieces_num'], 0, null, 'pieces_num') : null;
        $instance->pieceSize = isset($data['piece_size']) ? ValidationHelper::integer($data['piece_size'], 0, null, 'piece_size') : null;
        $instance->pieceLength = isset($data['piece_length']) ? ValidationHelper::stringLength($data['piece_length'], 0, 20, 'piece_length') : null;
        $instance->isChecking = isset($data['is_checking']) ? ValidationHelper::boolean($data['is_checking'], 'is_checking') : null;
        $instance->isPaused = isset($data['is_paused']) ? ValidationHelper::boolean($data['is_paused'], 'is_paused') : null;
        $instance->isAutoManaged = isset($data['is_auto_managed']) ? ValidationHelper::boolean($data['is_auto_managed'], 'is_auto_managed') : null;
        $instance->isForced = isset($data['is_forced']) ? ValidationHelper::boolean($data['is_forced'], 'is_forced') : null;
        $instance->isSequential = isset($data['is_sequential']) ? ValidationHelper::boolean($data['is_sequential'], 'is_sequential') : null;
        $instance->isFirstLastPiece = isset($data['is_first_last_piece']) ? ValidationHelper::boolean($data['is_first_last_piece'], 'is_first_last_piece') : null;

        return $instance;
    }

    // Getters
    public function getHash(): string
    {
        return $this->hash;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getProgress(): float
    {
        return $this->progress;
    }

    public function getProgressPercentage(): int
    {
        return (int) ($this->progress * 100);
    }

    public function getDownloadSpeed(): int
    {
        return $this->dlSpeed;
    }

    public function getUploadSpeed(): int
    {
        return $this->upSpeed;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function getNumSeeds(): int
    {
        return $this->numSeeds;
    }

    public function getNumComplete(): int
    {
        return $this->numComplete;
    }

    public function getNumLeechs(): int
    {
        return $this->numLeechs;
    }

    public function getNumIncomplete(): int
    {
        return $this->numIncomplete;
    }

    public function getRatio(): float
    {
        return $this->ratio;
    }

    public function getEta(): int
    {
        return $this->eta;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function isSeqDl(): bool
    {
        return $this->seqDl;
    }

    public function isFLPiecePrio(): bool
    {
        return $this->fLPiecePrio;
    }

    public function getTracker(): string
    {
        return $this->tracker;
    }

    public function getAdditionDate(): int
    {
        return $this->additionDate;
    }

    public function getCompletionDate(): int
    {
        return $this->completionDate;
    }

    public function getTrackerTier(): int
    {
        return $this->trackerTier;
    }

    public function getTags(): string
    {
        return $this->tags;
    }

    public function getSavePath(): string
    {
        return $this->savePath;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function getTotalSize(): ?int
    {
        return $this->totalSize;
    }

    public function getCompleted(): ?int
    {
        return $this->completed;
    }

    public function getMaxRatio(): ?int
    {
        return $this->maxRatio;
    }

    public function getMaxSeedingTime(): ?int
    {
        return $this->maxSeedingTime;
    }

    public function getRatioLimit(): ?float
    {
        return $this->ratioLimit;
    }

    public function getSeedingTimeLimit(): ?int
    {
        return $this->seedingTimeLimit;
    }

    public function getDownloaded(): ?float
    {
        return $this->downloaded;
    }

    public function getUploaded(): ?float
    {
        return $this->uploaded;
    }

    public function getDownloadSpeedLimit(): ?int
    {
        return $this->downloadSpeedLimit;
    }

    public function getUploadSpeedLimit(): ?int
    {
        return $this->uploadSpeedLimit;
    }

    public function getSeedingTime(): ?int
    {
        return $this->seedingTime;
    }

    public function getDownloadTime(): ?int
    {
        return $this->downloadTime;
    }

    public function getInactiveSeedingTime(): ?int
    {
        return $this->inactiveSeedingTime;
    }

    public function getReannounce(): ?int
    {
        return $this->reannounce;
    }

    public function getLastActivity(): ?int
    {
        return $this->lastActivity;
    }

    public function getAvailability(): ?float
    {
        return $this->availability;
    }

    public function getPiecesNum(): ?int
    {
        return $this->piecesNum;
    }

    public function getPieceSize(): ?int
    {
        return $this->pieceSize;
    }

    public function getPieceLength(): ?string
    {
        return $this->pieceLength;
    }

    public function isChecking(): ?bool
    {
        return $this->isChecking;
    }

    public function isPaused(): ?bool
    {
        return $this->isPaused;
    }

    public function isAutoManaged(): ?bool
    {
        return $this->isAutoManaged;
    }

    public function isForced(): ?bool
    {
        return $this->isForced;
    }

    public function isSequential(): ?bool
    {
        return $this->isSequential;
    }

    public function isFirstLastPiece(): ?bool
    {
        return $this->isFirstLastPiece;
    }

    // 状态判断方法
    public function isActive(): bool
    {
        return in_array($this->state, ['downloading', 'uploading', 'forcedDL', 'forcedUP', 'stalledDL', 'stalledUP']);
    }

    public function isCompleted(): bool
    {
        return $this->progress >= 1.0 || in_array($this->state, ['uploading', 'stalledUP', 'forcedUP', 'pausedUP', 'checkingUP']);
    }

    public function isDownloading(): bool
    {
        return in_array($this->state, ['downloading', 'forcedDL', 'stalledDL']);
    }

    public function isUploading(): bool
    {
        return in_array($this->state, ['uploading', 'stalledUP', 'forcedUP']);
    }

    public function isPaused(): bool
    {
        return in_array($this->state, ['pausedDL', 'pausedUP']);
    }

    public function isQueued(): bool
    {
        return in_array($this->state, ['queuedDL', 'queuedUP']);
    }

    public function hasError(): bool
    {
        return $this->state === 'error';
    }

    public function isStalled(): bool
    {
        return in_array($this->state, ['stalledDL', 'stalledUP']);
    }

    public function getFormattedSize(): string
    {
        return $this->formatBytes($this->size);
    }

    public function getFormattedDownloadSpeed(): string
    {
        return $this->formatBytes($this->dlSpeed) . '/s';
    }

    public function getFormattedUploadSpeed(): string
    {
        return $this->formatBytes($this->upSpeed) . '/s';
    }

    public function getFormattedRatio(): string
    {
        return number_format($this->ratio, 2);
    }

    public function getFormattedEta(): string
    {
        if ($this->eta <= 0) {
            return '∞';
        }

        $hours = floor($this->eta / 3600);
        $minutes = floor(($this->eta % 3600) / 60);
        $seconds = $this->eta % 60;

        if ($hours > 24) {
            $days = floor($hours / 24);
            $hours = $hours % 24;
            return sprintf('%dd %02d:%02d:%02d', $days, $hours, $minutes, $seconds);
        }

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }

    public function getFormattedAdditionDate(): string
    {
        return date('Y-m-d H:i:s', $this->additionDate);
    }

    public function getFormattedCompletionDate(): string
    {
        return $this->completionDate > 0 ? date('Y-m-d H:i:s', $this->completionDate) : 'Never';
    }

    public function getStateDisplayName(): string
    {
        $stateNames = [
            'unknown' => 'Unknown',
            'error' => 'Error',
            'missingFiles' => 'Missing Files',
            'uploading' => 'Uploading',
            'pausedUP' => 'Paused (Upload)',
            'stalledUP' => 'Stalled (Upload)',
            'checkingUP' => 'Checking (Upload)',
            'forcedUP' => 'Forced (Upload)',
            'allocating' => 'Allocating',
            'downloading' => 'Downloading',
            'metaDL' => 'Downloading Metadata',
            'pausedDL' => 'Paused (Download)',
            'stalledDL' => 'Stalled (Download)',
            'checkingDL' => 'Checking (Download)',
            'forcedDL' => 'Forced (Download)',
            'checkingResumeData' => 'Checking Resume Data',
            'moving' => 'Moving',
            'queued' => 'Queued',
            'queuedDL' => 'Queued (Download)',
            'queuedUP' => 'Queued (Upload)'
        ];

        return $stateNames[$this->state] ?? 'Unknown';
    }

    public function toArray(): array
    {
        $data = [
            'hash' => $this->hash,
            'name' => $this->name,
            'size' => $this->size,
            'progress' => $this->progress,
            'progress_percentage' => $this->getProgressPercentage(),
            'dl_speed' => $this->dlSpeed,
            'up_speed' => $this->upSpeed,
            'priority' => $this->priority,
            'num_seeds' => $this->numSeeds,
            'num_complete' => $this->numComplete,
            'num_leechs' => $this->numLeechs,
            'num_incomplete' => $this->numIncomplete,
            'ratio' => $this->ratio,
            'eta' => $this->eta,
            'state' => $this->state,
            'state_display_name' => $this->getStateDisplayName(),
            'seq_dl' => $this->seqDl,
            'f_l_piece_prio' => $this->fLPiecePrio,
            'tracker' => $this->tracker,
            'addition_date' => $this->additionDate,
            'completion_date' => $this->completionDate,
            'tracker_tier' => $this->trackerTier,
            'tags' => $this->tags,
            'save_path' => $this->savePath,
        ];

        // 添加可选字段
        $optionalFields = [
            'comment', 'total_size', 'completed', 'max_ratio', 'max_seeding_time',
            'ratio_limit', 'seeding_time_limit', 'downloaded', 'uploaded',
            'dl_limit', 'up_limit', 'seeding_time', 'download_time',
            'inactive_seeding_time', 'reannounce', 'last_activity',
            'availability', 'pieces_num', 'piece_size', 'piece_length',
            'is_checking', 'is_paused', 'is_auto_managed', 'is_forced',
            'is_sequential', 'is_first_last_piece'
        ];

        foreach ($optionalFields as $field) {
            $property = lcfirst(str_replace('_', '', ucwords($field, '_')));
            if ($this->$property !== null) {
                $data[$field] = $this->$property;
            }
        }

        // 添加格式化字段
        $data['formatted'] = [
            'size' => $this->getFormattedSize(),
            'download_speed' => $this->getFormattedDownloadSpeed(),
            'upload_speed' => $this->getFormattedUploadSpeed(),
            'ratio' => $this->getFormattedRatio(),
            'eta' => $this->getFormattedEta(),
            'addition_date' => $this->getFormattedAdditionDate(),
            'completion_date' => $this->getFormattedCompletionDate()
        ];

        // 添加状态标志
        $data['status_flags'] = [
            'is_active' => $this->isActive(),
            'is_completed' => $this->isCompleted(),
            'is_downloading' => $this->isDownloading(),
            'is_uploading' => $this->isUploading(),
            'is_paused' => $this->isPaused(),
            'is_queued' => $this->isQueued(),
            'has_error' => $this->hasError(),
            'is_stalled' => $this->isStalled()
        ];

        return $data;
    }

    /**
     * 格式化字节数为可读格式
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * 验证torrent状态是否一致
     *
     * @return array 验证结果
     */
    public function validate(): array
    {
        $errors = [];

        if (empty($this->hash)) {
            $errors['hash'] = 'Torrent hash不能为空';
        }

        if (empty($this->name)) {
            $errors['name'] = 'Torrent名称不能为空';
        }

        if ($this->size < 0) {
            $errors['size'] = 'Torrent大小不能为负数';
        }

        if ($this->progress < 0 || $this->progress > 1) {
            $errors['progress'] = '进度值必须在0-1之间';
        }

        if ($this->dlSpeed < 0) {
            $errors['dl_speed'] = '下载速度不能为负数';
        }

        if ($this->upSpeed < 0) {
            $errors['up_speed'] = '上传速度不能为负数';
        }

        if ($this->ratio < 0) {
            $errors['ratio'] = '分享比例不能为负数';
        }

        return $errors;
    }
}