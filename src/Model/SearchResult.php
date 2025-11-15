<?php
declare(strict_types=1);

namespace PhpQbittorrent\Model;

use JsonSerializable;

/**
 * 搜索结果模型
 *
 * 封装搜索结果的完整信息
 */
class SearchResult implements JsonSerializable
{
    // 基本信息
    private string $descrLink;
    private string $fileName;
    private int $fileSize;
    private string $fileUrl;
    private int $nbLeechers;
    private int $nbSeeders;
    private string $siteUrl;

    // 扩展信息
    private ?string $category;
    private ?string $hash;
    private ?float $ratio;
    private ?int $addedTime;
    private ?string $magnetUri;

    // 状态信息
    private bool $isTrusted;
    private bool $isVerified;
    private int $relevance;

    /**
     * 构造函数
     */
    public function __construct(array $data = [])
    {
        $this->descrLink = $data['descrLink'] ?? '';
        $this->fileName = $data['fileName'] ?? '';
        $this->fileSize = $data['fileSize'] ?? 0;
        $this->fileUrl = $data['fileUrl'] ?? '';
        $this->nbLeechers = $data['nbLeechers'] ?? 0;
        $this->nbSeeders = $data['nbSeeders'] ?? 0;
        $this->siteUrl = $data['siteUrl'] ?? '';

        $this->category = $data['category'] ?? null;
        $this->hash = $data['hash'] ?? null;
        $this->ratio = $data['ratio'] ?? null;
        $this->addedTime = $data['addedTime'] ?? null;
        $this->magnetUri = $data['magnetUri'] ?? null;

        $this->isTrusted = $data['isTrusted'] ?? false;
        $this->isVerified = $data['isVerified'] ?? false;
        $this->relevance = $data['relevance'] ?? 0;
    }

    // 基本信息 getter/setter
    public function getDescrLink(): string { return $this->descrLink; }
    public function getFileName(): string { return $this->fileName; }
    public function getFileSize(): int { return $this->fileSize; }
    public function getFileUrl(): string { return $this->fileUrl; }
    public function getNbLeechers(): int { return $this->nbLeechers; }
    public function getNbSeeders(): int { return $this->nbSeeders; }
    public function getSiteUrl(): string { return $this->siteUrl; }

    // 扩展信息 getter/setter
    public function getCategory(): ?string { return $this->category; }
    public function getHash(): ?string { return $this->hash; }
    public function getRatio(): ?float { return $this->ratio; }
    public function getAddedTime(): ?int { return $this->addedTime; }
    public function getMagnetUri(): ?string { return $this->magnetUri; }

    // 状态信息 getter/setter
    public function isTrusted(): bool { return $this->isTrusted; }
    public function isVerified(): bool { return $this->isVerified; }
    public function getRelevance(): int { return $this->relevance; }

    // 格式化方法
    public function getFormattedSize(): string
    {
        return $this->formatBytes($this->fileSize);
    }

    public function getFormattedAddedTime(): string
    {
        if ($this->addedTime === null) return '未知';
        return date('Y-m-d H:i:s', $this->addedTime);
    }

    public function getFormattedRatio(): string
    {
        if ($this->ratio === null) return '未知';
        return number_format($this->ratio, 2);
    }

    public function getFormattedRelevance(): string
    {
        return match($this->relevance) {
            0 => '无',
            1 => '低',
            2 => '中',
            3 => '高',
            default => '未知'
        };
    }

    public function getFormattedFileName(): string
    {
        // 移除常见的垃圾信息
        $cleanName = preg_replace('/\[(.*?)\]/', '', $this->fileName);
        $cleanName = preg_replace('/\s+/', ' ', $cleanName);
        return trim($cleanName);
    }

    public function getAge(): ?int
    {
        if ($this->addedTime === null) return null;
        return time() - $this->addedTime;
    }

    public function getFormattedAge(): string
    {
        $age = $this->getAge();
        if ($age === null) return '未知';
        return $this->formatDuration($age);
    }

    // 状态判断方法
    public function hasSeeders(): bool
    {
        return $this->nbSeeders > 0;
    }

    public function hasLeechers(): bool
    {
        return $this->nbLeechers > 0;
    }

    public function hasPeers(): bool
    {
        return $this->hasSeeders() || $this->hasLeechers();
    }

    public function isHealthy(): bool
    {
        return $this->hasSeeders() && ($this->ratio === null || $this->ratio >= 0.5);
    }

    public function isPopular(): bool
    {
        return ($this->nbSeeders + $this->nbLeechers) >= 100;
    }

    public function isRecent(int $days = 7): bool
    {
        $age = $this->getAge();
        return $age !== null && $age < ($days * 86400);
    }

    public function isLarge(int $minSizeGB = 1): bool
    {
        return $this->fileSize >= ($minSizeGB * 1073741824);
    }

    public function containsKeywords(array $keywords): bool
    {
        $text = strtolower($this->fileName);
        foreach ($keywords as $keyword) {
            if (str_contains($text, strtolower($keyword))) {
                return true;
            }
        }
        return false;
    }

    public function matchesFilter(?string $filter): bool
    {
        if ($filter === null || trim($filter) === '') return true;

        $text = strtolower($this->fileName . ' ' . $this->descrLink);
        return str_contains($text, strtolower($filter));
    }

    public function getPeerCount(): int
    {
        return $this->nbSeeders + $this->nbLeechers;
    }

    public function getSeedLeechRatio(): float
    {
        if ($this->nbLeechers === 0) return $this->nbSeeders > 0 ? 999.9 : 0.0;
        return round($this->nbSeeders / $this->nbLeechers, 2);
    }

    public function getScore(): int
    {
        $score = 0;

        // 种子数评分
        if ($this->hasSeeders()) {
            $score += min($this->nbSeeders * 10, 500);
        }

        // 健康度评分
        if ($this->isHealthy()) {
            $score += 100;
        }

        // 受信任度评分
        if ($this->isTrusted()) {
            $score += 50;
        }

        // 验证度评分
        if ($this->isVerified()) {
            $score += 30;
        }

        // 相关性评分
        $score += $this->relevance * 20;

        // 大小适中性评分（不是太大也不是太小）
        if ($this->fileSize > 104857600 && $this->fileSize < 10737418240) { // 100MB - 10GB
            $score += 20;
        }

        return $score;
    }

    public function getRank(): string
    {
        $score = $this->getScore();

        if ($score >= 500) return '优秀';
        if ($score >= 300) return '良好';
        if ($score >= 150) return '一般';
        if ($score >= 50) return '较差';
        return '差';
    }

    // 静态创建方法
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    public static function create(
        string $fileName,
        string $fileUrl,
        string $descrLink = '',
        string $siteUrl = '',
        int $fileSize = 0
    ): self {
        return new self([
            'fileName' => $fileName,
            'fileUrl' => $fileUrl,
            'descrLink' => $descrLink,
            'siteUrl' => $siteUrl,
            'fileSize' => $fileSize,
            'addedTime' => time(),
        ]);
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

    private function formatDuration(int $seconds): string
    {
        if ($seconds < 60) return "{$seconds}秒";
        if ($seconds < 3600) return floor($seconds / 60) . '分钟';
        if ($seconds < 86400) return floor($seconds / 3600) . '小时';
        return floor($seconds / 86400) . '天';
    }

    /**
     * 转换为数组
     */
    public function toArray(): array
    {
        return [
            // 基本信息
            'descrLink' => $this->descrLink,
            'fileName' => $this->fileName,
            'fileSize' => $this->fileSize,
            'fileUrl' => $this->fileUrl,
            'nbLeechers' => $this->nbLeechers,
            'nbSeeders' => $this->nbSeeders,
            'siteUrl' => $this->siteUrl,

            // 扩展信息
            'category' => $this->category,
            'hash' => $this->hash,
            'ratio' => $this->ratio,
            'addedTime' => $this->addedTime,
            'magnetUri' => $this->magnetUri,

            // 状态信息
            'isTrusted' => $this->isTrusted,
            'isVerified' => $this->isVerified,
            'relevance' => $this->relevance,

            // 计算字段
            'peerCount' => $this->getPeerCount(),
            'seedLeechRatio' => $this->getSeedLeechRatio(),
            'score' => $this->getScore(),
            'rank' => $this->getRank(),

            // 格式化字段
            'formattedSize' => $this->getFormattedSize(),
            'formattedAddedTime' => $this->getFormattedAddedTime(),
            'formattedRatio' => $this->getFormattedRatio(),
            'formattedRelevance' => $this->getFormattedRelevance(),
            'formattedFileName' => $this->getFormattedFileName(),
            'age' => $this->getAge(),
            'formattedAge' => $this->getFormattedAge(),

            // 状态判断
            'hasSeeders' => $this->hasSeeders(),
            'hasLeechers' => $this->hasLeechers(),
            'hasPeers' => $this->hasPeers(),
            'isHealthy' => $this->isHealthy(),
            'isPopular' => $this->isPopular(),
            'isRecent' => $this->isRecent(),
            'isLarge' => $this->isLarge(),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}