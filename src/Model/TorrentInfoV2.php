<?php
declare(strict_types=1);

namespace Dongasai\qBittorrent\Model;

use JsonSerializable;
use Dongasai\qBittorrent\Enum\TorrentState;

/**
 * Torrent信息模型 v2
 *
 * 封装Torrent的完整信息，提供便捷的访问方法和状态判断
 */
class TorrentInfoV2 implements JsonSerializable
{
    // 基本信息
    private string $hash;
    private string $name;
    private int $size;
    private int $totalSize;
    private float $progress;
    private TorrentState $state;
    private int $priority;
    private int $addedOn;
    private ?int $completionOn;
    private ?int $seenComplete;

    // 状态和速度
    private int $dlspeed;
    private int $upspeed;
    private int $dlLimit;
    private int $upLimit;
    private int $eta;
    private bool $forceStart;
    private int $numSeeds;
    private int $numLeechs;
    private int $numComplete;
    private int $numIncomplete;

    // 连接和跟踪器
    private string $tracker;
    private float $ratio;
    private float $maxRatio;
    private int $ratioLimit;
    private int $seedingTimeLimit;
    private int $seedingTime;
    private bool $autoTmm;
    private bool $superSeeding;

    // 路径和分类
    private string $savePath;
    private string $contentPath;
    private string $category;
    private string $tags;

    // 下载和上传统计
    private int $downloaded;
    private int $uploaded;
    private int $downloadedSession;
    private int $uploadedSession;
    private int $amountLeft;
    private int $timeActive;
    private int $lastActivity;

    // 配置选项
    private bool $seqDl;
    private bool $fLPiecePrio;
    private bool $isPrivate;
    private string $magnetUri;

    // 重新公告
    private ?int $reannounce;

    // 构造函数
    public function __construct(array $data)
    {
        $this->hash = $data['hash'] ?? '';
        $this->name = $data['name'] ?? '';
        $this->size = $data['size'] ?? 0;
        $this->totalSize = $data['total_size'] ?? $data['size'] ?? 0;
        $this->progress = $data['progress'] ?? 0.0;
        $this->state = TorrentState::fromString($data['state'] ?? 'unknown');
        $this->priority = $data['priority'] ?? 0;
        $this->addedOn = $data['added_on'] ?? 0;
        $this->completionOn = $data['completion_on'] ?? null;
        $this->seenComplete = $data['seen_complete'] ?? null;

        $this->dlspeed = $data['dlspeed'] ?? 0;
        $this->upspeed = $data['upspeed'] ?? 0;
        $this->dlLimit = $data['dl_limit'] ?? -1;
        $this->upLimit = $data['up_limit'] ?? -1;
        $this->eta = $data['eta'] ?? -1;
        $this->forceStart = $data['force_start'] ?? false;
        $this->numSeeds = $data['num_seeds'] ?? 0;
        $this->numLeechs = $data['num_leechs'] ?? 0;
        $this->numComplete = $data['num_complete'] ?? -1;
        $this->numIncomplete = $data['num_incomplete'] ?? -1;

        $this->tracker = $data['tracker'] ?? '';
        $this->ratio = $data['ratio'] ?? 0.0;
        $this->maxRatio = $data['max_ratio'] ?? -1.0;
        $this->ratioLimit = $data['ratio_limit'] ?? -1.0;
        $this->seedingTimeLimit = $data['seeding_time_limit'] ?? -1;
        $this->seedingTime = $data['seeding_time'] ?? 0;
        $this->autoTmm = $data['auto_tmm'] ?? false;
        $this->superSeeding = $data['super_seeding'] ?? false;

        $this->savePath = $data['save_path'] ?? '';
        $this->contentPath = $data['content_path'] ?? '';
        $this->category = $data['category'] ?? '';
        $this->tags = $data['tags'] ?? '';

        $this->downloaded = $data['downloaded'] ?? 0;
        $this->uploaded = $data['uploaded'] ?? 0;
        $this->downloadedSession = $data['downloaded_session'] ?? 0;
        $this->uploadedSession = $data['uploaded_session'] ?? 0;
        $this->amountLeft = $data['amount_left'] ?? $this->size;
        $this->timeActive = $data['time_active'] ?? 0;
        $this->lastActivity = $data['last_activity'] ?? 0;

        $this->seqDl = $data['seq_dl'] ?? false;
        $this->fLPiecePrio = $data['f_l_piece_prio'] ?? false;
        $this->isPrivate = $data['isPrivate'] ?? false;
        $this->magnetUri = $data['magnet_uri'] ?? '';

        $this->reannounce = $data['reannounce'] ?? null;
    }

    // 基本信息
    public function getHash(): string { return $this->hash; }
    public function getName(): string { return $this->name; }
    public function getSize(): int { return $this->size; }
    public function getTotalSize(): int { return $this->totalSize; }
    public function getProgress(): float { return $this->progress; }
    public function getState(): TorrentState { return $this->state; }
    public function getPriority(): int { return $this->priority; }
    public function getAddedOn(): int { return $this->addedOn; }
    public function getCompletionOn(): ?int { return $this->completionOn; }
    public function getSeenComplete(): ?int { return $this->seenComplete; }

    // 状态和速度
    public function getDownloadSpeed(): int { return $this->dlspeed; }
    public function getUploadSpeed(): int { return $this->upspeed; }
    public function getDownloadLimit(): int { return $this->dlLimit; }
    public function getUploadLimit(): int { return $this->upLimit; }
    public function getEta(): int { return $this->eta; }
    public function isForceStarted(): bool { return $this->forceStart; }

    // 连接信息
    public function getSeedCount(): int { return $this->numSeeds; }
    public function getLeechCount(): int { return $this->numLeechs; }
    public function getTotalSeedCount(): int { return $this->numComplete; }
    public function getTotalLeechCount(): int { return $this->numIncomplete; }

    // 跟踪器和比率
    public function getTracker(): string { return $this->tracker; }
    public function getRatio(): float { return $this->ratio; }
    public function getMaxRatio(): float { return $this->maxRatio; }
    public function getRatioLimit(): float { return $this->ratioLimit; }

    // 路径和分类
    public function getSavePath(): string { return $this->savePath; }
    public function getContentPath(): string { return $this->contentPath; }
    public function getCategory(): string { return $this->category; }
    public function getTags(): string { return $this->tags; }

    // 统计信息
    public function getDownloaded(): int { return $this->downloaded; }
    public function getUploaded(): int { return $this->uploaded; }
    public function getSessionDownloaded(): int { return $this->downloadedSession; }
    public function getSessionUploaded(): int { return $this->uploadedSession; }
    public function getAmountLeft(): int { return $this->amountLeft; }
    public function getTimeActive(): int { return $this->timeActive; }
    public function getLastActivity(): int { return $this->lastActivity; }

    // 配置
    public function isSequentialDownload(): bool { return $this->seqDl; }
    public function isFirstLastPiecePriority(): bool { return $this->fLPiecePrio; }
    public function isPrivate(): bool { return $this->isPrivate; }
    public function getMagnetUri(): string { return $this->magnetUri; }
    public function getReannounce(): ?int { return $this->reannounce; }

    // 格式化方法
    public function getFormattedSize(): string { return $this->formatBytes($this->size); }
    public function getFormattedProgress(): string { return number_format($this->progress * 100, 2) . '%'; }
    public function getFormattedDownloadSpeed(): string { return $this->formatSpeed($this->dlspeed); }
    public function getFormattedUploadSpeed(): string { return $this->formatSpeed($this->upspeed); }
    public function getFormattedRatio(): string { return number_format($this->ratio, 3); }
    public function getFormattedEta(): string { return $this->formatTime($this->eta); }

    // 状态判断方法
    public function isCompleted(): bool { return $this->state->isCompleted(); }
    public function isDownloading(): bool { return $this->state->isDownloading(); }
    public function isUploading(): bool { return $this->state->isUploading(); }
    public function isPaused(): bool { return $this->state->isPaused(); }
    public function isActive(): bool { return $this->state->isActive(); }
    public function isStalled(): bool { return in_array($this->state, [TorrentState::STALLED_DL, TorrentState::STALLED_UP]); }
    public function hasError(): bool { return $this->state->isError(); }

    // 便利方法
    public function getTagArray(): array
    {
        return empty($this->tags) ? [] : explode(',', $this->tags);
    }

    public function hasTag(string $tag): bool
    {
        return in_array($tag, $this->getTagArray());
    }

    public function hasCategory(): bool
    {
        return !empty($this->category);
    }

    public function isCompletedOrSeeding(): bool
    {
        return $this->progress >= 1.0 || $this->isUploading();
    }

    public function getCompletionPercentage(): float
    {
        return $this->progress * 100;
    }

    public function getRemainingSize(): int
    {
        return $this->amountLeft;
    }

    public function getDownloadedPercentage(): float
    {
        if ($this->totalSize == 0) return 0;
        return (($this->totalSize - $this->amountLeft) / $this->totalSize) * 100;
    }

    public function getAge(): int
    {
        return time() - $this->addedOn;
    }

    public function getFormattedAge(): string
    {
        return $this->formatTime($this->getAge());
    }

    public function hasActivity(): bool
    {
        return ($this->dlspeed > 0 || $this->upspeed > 0);
    }

    public function getSpeedRank(): string
    {
        $totalSpeed = $this->dlspeed + $this->upspeed;

        if ($totalSpeed >= 10 * 1024 * 1024) return '高速';
        if ($totalSpeed >= 1 * 1024 * 1024) return '中速';
        if ($totalSpeed > 0) return '低速';
        return '静止';
    }

    // 静态工具方法
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    // 私有格式化方法
    private function formatBytes(int $bytes): string
    {
        if ($bytes == 0) return '0 B';

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    private function formatSpeed(int $bytesPerSecond): string
    {
        return $this->formatBytes($bytesPerSecond) . '/s';
    }

    private function formatTime(int $seconds): string
    {
        if ($seconds <= 0) return '∞';
        if ($seconds < 60) return "{$seconds}秒";
        if ($seconds < 3600) return floor($seconds / 60) . '分钟';
        if ($seconds < 86400) return floor($seconds / 3600) . '小时';
        return floor($seconds / 86400) . '天';
    }

    // 转换为数组
    public function toArray(): array
    {
        return [
            // 基本信息
            'hash' => $this->hash,
            'name' => $this->name,
            'size' => $this->size,
            'total_size' => $this->totalSize,
            'progress' => $this->progress,
            'state' => $this->state->value,
            'priority' => $this->priority,
            'added_on' => $this->addedOn,
            'completion_on' => $this->completionOn,
            'seen_complete' => $this->seenComplete,

            // 状态和速度
            'dlspeed' => $this->dlspeed,
            'upspeed' => $this->upspeed,
            'dl_limit' => $this->dlLimit,
            'up_limit' => $this->upLimit,
            'eta' => $this->eta,
            'force_start' => $this->forceStart,
            'num_seeds' => $this->numSeeds,
            'num_leechs' => $this->numLeechs,
            'num_complete' => $this->numComplete,
            'num_incomplete' => $this->numIncomplete,

            // 跟踪器和比率
            'tracker' => $this->tracker,
            'ratio' => $this->ratio,
            'max_ratio' => $this->maxRatio,
            'ratio_limit' => $this->ratioLimit,

            // 路径和分类
            'save_path' => $this->savePath,
            'content_path' => $this->contentPath,
            'category' => $this->category,
            'tags' => $this->tags,

            // 统计信息
            'downloaded' => $this->downloaded,
            'uploaded' => $this->uploaded,
            'downloaded_session' => $this->downloadedSession,
            'uploaded_session' => $this->uploadedSession,
            'amount_left' => $this->amountLeft,
            'time_active' => $this->timeActive,
            'last_activity' => $this->lastActivity,

            // 配置
            'seq_dl' => $this->seqDl,
            'f_l_piece_prio' => $this->fLPiecePrio,
            'isPrivate' => $this->isPrivate,
            'magnet_uri' => $this->magnetUri,
            'reannounce' => $this->reannounce,

            // 计算属性
            'formatted_size' => $this->getFormattedSize(),
            'formatted_progress' => $this->getFormattedProgress(),
            'formatted_download_speed' => $this->getFormattedDownloadSpeed(),
            'formatted_upload_speed' => $this->getFormattedUploadSpeed(),
            'formatted_ratio' => $this->getFormattedRatio(),
            'formatted_eta' => $this->getFormattedEta(),
            'tag_array' => $this->getTagArray(),
            'completion_percentage' => $this->getCompletionPercentage(),
            'age' => $this->getAge(),
            'formatted_age' => $this->getFormattedAge(),
            'speed_rank' => $this->getSpeedRank(),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}