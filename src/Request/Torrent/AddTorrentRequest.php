<?php
declare(strict_types=1);

namespace Dongasai\qBittorrent\Request\Torrent;

use Dongasai\qBittorrent\Request\AbstractRequest;
use Dongasai\qBittorrent\Contract\ValidationResult;
use Dongasai\qBittorrent\Validation\BasicValidationResult;
use Dongasai\qBittorrent\Exception\ValidationException;

/**
 * 添加Torrent请求对象
 *
 * 用于封装添加Torrent的请求参数和验证逻辑
 */
class AddTorrentRequest extends AbstractRequest
{
    /** @var array<string> 添加的URL列表 */
    private array $urls = [];

    /** @var array<array{name: string, content: string}> 添加的文件列表 */
    private array $torrents = [];

    /** @var string|null 保存路径 */
    private ?string $savepath = null;

    /** @var string|null 分类 */
    private ?string $category = null;

    /** @var array<string> 标签列表 */
    private array $tags = [];

    /** @var bool 是否跳过校验 */
    private bool $skipChecking = false;

    /** @var bool 是否暂停添加 */
    private bool $paused = false;

    /** @var bool 是否创建根文件夹 */
    private ?bool $rootFolder = null;

    /** @var string|null 重命名 */
    private ?string $rename = null;

    /** @var int|null 下载速度限制 */
    private ?int $dlLimit = null;

    /** @var int|null 上传速度限制 */
    private ?int $upLimit = null;

    /** @var float|null 分享率限制 */
    private ?float $ratioLimit = null;

    /** @var int|null 做种时间限制 */
    private ?int $seedingTimeLimit = null;

    /** @var bool|null 是否启用自动管理 */
    private ?bool $autoTMM = null;

    /** @var bool|null 是否顺序下载 */
    private ?bool $sequentialDownload = null;

    /** @var bool|null 是否优先下载首尾部分 */
    private ?bool $firstLastPiecePrio = null;

    /** @var int|null 最大连接数限制 */
    private ?int $maxConnections = null;

    /** @var int|null 最大上传槽限制 */
    private ?int $maxUploadSlots = null;

    /** @var array<string, mixed> 附加选项 */
    private array $additionalOptions = [];

    /** @var int 最大URL长度 */
    private const MAX_URL_LENGTH = 8192;

    /** @var int 最大文件数量 */
    private const MAX_FILES = 100;

    /** @var int 最大文件大小 */
    private const MAX_FILE_SIZE = 50 * 1024 * 1024; // 50MB

    /**
     * 私有构造函数
     */
    private function __construct()
    {
        parent::__construct([]);

        $this->setEndpoint('/torrents/add')
             ->setMethod('POST')
             ->setRequiresAuthentication(true);
    }

    /**
     * 创建Builder实例
     *
     * @return AddTorrentRequestBuilder Builder实例
     */
    public static function builder(): AddTorrentRequestBuilder
    {
        return new AddTorrentRequestBuilder();
    }

    /**
     * 创建URL方式添加请求
     *
     * @param array<string> $urls URL列表
     * @return self 添加请求实例
     * @throws ValidationException 如果参数无效
     */
    public static function fromUrls(array $urls): self
    {
        $request = new self();
        $request->urls = $urls;

        $validation = $request->validate();
        if (!$validation->isValid()) {
            throw ValidationException::fromValidationResult(
                $validation,
                'Add torrent from URLs validation failed'
            );
        }

        return $request;
    }

    /**
     * 创建文件方式添加请求
     *
     * @param array<array{name: string, content: string}> $torrents 文件列表
     * @return self 添加请求实例
     * @throws ValidationException 如果参数无效
     */
    public static function fromFiles(array $torrents): self
    {
        $request = new self();
        $request->torrents = $torrents;

        $validation = $request->validate();
        if (!$validation->isValid()) {
            throw ValidationException::fromValidationResult(
                $validation,
                'Add torrent from files validation failed'
            );
        }

        return $request;
    }

    /**
     * 获取URL列表
     *
     * @return array<string> URL列表
     */
    public function getUrls(): array
    {
        return $this->urls;
    }

    /**
     * 获取文件列表
     *
     * @return array<array{name: string, content: string}> 文件列表
     */
    public function getTorrents(): array
    {
        return $this->torrents;
    }

    /**
     * 检查是否有URL
     *
     * @return bool 是否有URL
     */
    public function hasUrls(): bool
    {
        return !empty($this->urls);
    }

    /**
     * 检查是否有文件
     *
     * @return bool 是否有文件
     */
    public function hasFiles(): bool
    {
        return !empty($this->torrents);
    }

    /**
     * 检查是否有任何源
     *
     * @return bool 是否有任何源
     */
    public function hasAnySource(): bool
    {
        return $this->hasUrls() || $this->hasFiles();
    }

    /**
     * 获取保存路径
     *
     * @return string|null 保存路径
     */
    public function getSavepath(): ?string
    {
        return $this->savepath;
    }

    /**
     * 获取分类
     *
     * @return string|null 分类
     */
    public function getCategory(): ?string
    {
        return $this->category;
    }

    /**
     * 获取标签列表
     *
     * @return array<string> 标签列表
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * 验证请求参数
     *
     * @return ValidationResult 验证结果
     */
    public function validate(): ValidationResult
    {
        $result = BasicValidationResult::success();

        // 验证是否有源
        if (!$this->hasAnySource()) {
            $result->addError('必须提供URL或文件');
        }

        // 验证URL列表
        if ($this->hasUrls()) {
            if (count($this->urls) === 0) {
                $result->addError('URL列表不能为空');
            } else {
                foreach ($this->urls as $url) {
                    if (empty(trim($url))) {
                        $result->addError('URL不能为空');
                    } elseif (strlen($url) > self::MAX_URL_LENGTH) {
                        $result->addError("URL长度不能超过 " . self::MAX_URL_LENGTH . " 个字符");
                    } elseif (!$this->isValidUrl($url)) {
                        $result->addError("URL格式无效: {$url}");
                    }
                }
            }
        }

        // 验证文件列表
        if ($this->hasFiles()) {
            if (count($this->torrents) === 0) {
                $result->addError('文件列表不能为空');
            } elseif (count($this->torrents) > self::MAX_FILES) {
                $result->addError("文件数量不能超过 " . self::MAX_FILES);
            } else {
                foreach ($this->torrents as $file) {
                    if (empty($file['name']) || !isset($file['content'])) {
                        $result->addError('文件信息不完整，必须包含name和content');
                    } elseif (strlen($file['content']) > self::MAX_FILE_SIZE) {
                        $result->addError("文件大小不能超过 " . (self::MAX_FILE_SIZE / 1024 / 1024) . "MB");
                    } elseif (!$this->isValidTorrentName($file['name'])) {
                        $result->addError("文件名无效: {$file['name']}");
                    }
                }
            }
        }

        // 验证保存路径
        if ($this->savepath !== null) {
            if (empty(trim($this->savepath))) {
                $result->addError('保存路径不能为空');
            } elseif (strlen($this->savepath) > 4096) {
                $result->addError('保存路径长度不能超过4096个字符');
            } elseif (!$this->isValidPath($this->savepath)) {
                $result->addError('保存路径格式无效');
            }
        }

        // 验证分类
        if ($this->category !== null) {
            if (empty(trim($this->category))) {
                $result->addError('分类名称不能为空');
            } elseif (strlen($this->category) > 255) {
                $result->addError('分类名称长度不能超过255个字符');
            } elseif (!$this->isValidCategoryName($this->category)) {
                $result->addError('分类名称包含无效字符');
            }
        }

        // 验证标签
        if (!empty($this->tags)) {
            foreach ($this->tags as $tag) {
                if (empty(trim($tag))) {
                    $result->addError('标签名称不能为空');
                } elseif (strlen($tag) > 255) {
                    $result->addError('标签名称长度不能超过255个字符');
                } elseif (!$this->isValidTagName($tag)) {
                    $result->addError("标签名称格式无效: {$tag}");
                }
            }
        }

        // 验证速度限制
        if ($this->dlLimit !== null) {
            if ($this->dlLimit < -1) {
                $result->addError('下载速度限制不能小于-1');
            } elseif ($this->dlLimit > 1000000) { // 1000MB/s
                $result->addWarning('下载速度限制设置过高，可能导致问题');
            }
        }

        if ($this->upLimit !== null) {
            if ($this->upLimit < -1) {
                $result->addError('上传速度限制不能小于-1');
            } elseif ($this->upLimit > 1000000) { // 1000MB/s
                $result->addWarning('上传速度限制设置过高，可能导致问题');
            }
        }

        // 验证比率限制
        if ($this->ratioLimit !== null) {
            if ($this->ratioLimit < -2) {
                $result->addError('分享率限制不能小于-2');
            } elseif ($this->ratioLimit > 1000) {
                $result->addWarning('分享率限制设置过高，可能导致问题');
            }
        }

        // 验证做种时间限制
        if ($this->seedingTimeLimit !== null) {
            if ($this->seedingTimeLimit < -2) {
                $result->addError('做种时间限制不能小于-2');
            } elseif ($this->seedingTimeLimit > 1000000) { // 约11.5天
                $result->addWarning('做种时间限制设置过高，可能导致问题');
            }
        }

        // 验证连接数限制
        if ($this->maxConnections !== null) {
            if ($this->maxConnections < -1 || $this->maxConnections > 10000) {
                $result->addError('最大连接数必须在-1到10000之间');
            }
        }

        // 验证上传槽限制
        if ($this->maxUploadSlots !== null) {
            if ($this->maxUploadSlots < -1 || $this->maxUploadSlots > 1000) {
                $result->addError('最大上传槽数必须在-1到1000之间');
            }
        }

        // 验证重命名
        if ($this->rename !== null) {
            if (empty(trim($this->rename))) {
                $result->addError('重命名不能为空');
            } elseif (strlen($this->rename) > 255) {
                $result->addError('重命名长度不能超过255个字符');
            } elseif (!$this->isValidRename($this->rename)) {
                $result->addError('重命名包含无效字符');
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

        // 添加URL
        if ($this->hasUrls()) {
            $data['urls'] = implode('\n', $this->urls);
        }

        // 添加保存路径
        if ($this->savepath !== null) {
            $data['savepath'] = $this->savepath;
        }

        // 添加分类
        if ($this->category !== null) {
            $data['category'] = $this->category;
        }

        // 添加标签
        if (!empty($this->tags)) {
            $data['tags'] = implode(',', $this->tags);
        }

        // 添加其他选项
        $options = [
            'skip_checking' => $this->skipChecking ? 'true' : 'false',
            'paused' => $this->paused ? 'true' : 'false',
            'dlLimit' => $this->dlLimit,
            'upLimit' => $this->upLimit,
            'ratioLimit' => $this->ratioLimit,
            'seedingTimeLimit' => $this->seedingTimeLimit,
            'autoTMM' => $this->autoTMM,
            'sequentialDownload' => $this->sequentialDownload,
            'firstLastPiecePrio' => $this->firstLastPiecePrio,
            'rename' => $this->rename,
        ];

        // 添加root_folder选项
        if ($this->rootFolder !== null) {
            $options['root_folder'] = $this->rootFolder ? 'true' : 'false';
        }

        // 添加连接数限制
        if ($this->maxConnections !== null) {
            $options['max_connections'] = $this->maxConnections;
        }

        // 添加上传槽限制
        if ($this->maxUploadSlots !== null) {
            $options['max_upload_slots'] = $this->maxUploadSlots;
        }

        // 合并选项，只添加非null值
        foreach ($options as $key => $value) {
            if ($value !== null) {
                $data[$key] = $value;
            }
        }

        return $data;
    }

    /**
     * 获取文件字段（用于multipart请求）
     *
     * @return array<array{name: string, content: string}> 文件字段
     */
    public function getFileFields(): array
    {
        return $this->torrents;
    }

    /**
     * 检查URL是否有效
     *
     * @param string $url URL字符串
     * @return bool 是否有效
     */
    private function isValidUrl(string $url): bool
    {
        // 支持的协议
        $protocols = ['http://', 'https://', 'magnet:', 'bc://bt/'];

        foreach ($protocols as $protocol) {
            if (str_starts_with($url, $protocol)) {
                if ($protocol === 'magnet:' || $protocol === 'bc://bt/') {
                    return true; // Magnet和BC链接不需要进一步验证
                }

                return filter_var($url, FILTER_VALIDATE_URL) !== false;
            }
        }

        return false;
    }

    /**
     * 检查路径是否有效
     *
     * @param string $path 路径字符串
     * @return bool 是否有效
     */
    private function isValidPath(string $path): bool
    {
        // 基本路径验证
        if (preg_match('/[<>:"|?*]/', $path)) {
            return false;
        }

        // 检查是否为绝对路径（可选）
        // 这里只做基本格式检查

        return true;
    }

    /**
     * 检查分类名称是否有效
     *
     * @param string $name 分类名称
     * @return bool 是否有效
     */
    private function isValidCategoryName(string $name): bool
    {
        return !preg_match('/[<>:"|?*\\/]/', $name) && !str_contains($name, '..');
    }

    /**
     * 检查标签名称是否有效
     *
     * @param string $tag 标签名称
     * @return bool 是否有效
     */
    private function isValidTagName(string $tag): bool
    {
        return !preg_match('/[<>:"|?*,]/', $tag) && !str_contains($tag, '..');
    }

    /**
     * 检查种子文件名是否有效
     *
     * @param string $filename 文件名
     * @return bool 是否有效
     */
    private function isValidTorrentName(string $filename): bool
    {
        return preg_match('/^[^\\/?%*:|"<>]+$/', $filename);
    }

    /**
     * 检查重命名是否有效
     *
     * @param string $rename 重命名
     * @return bool 是否有效
     */
    private function isValidRename(string $rename): bool
    {
        return !preg_match('/[<>:"|?*]/', $rename) && !str_contains($rename, '..');
    }

    /**
     * 获取添加请求的摘要信息
     *
     * @return array<string, mixed> 摘要信息
     */
    public function getSummary(): array
    {
        return [
            'has_urls' => $this->hasUrls(),
            'url_count' => count($this->urls),
            'has_files' => $this->hasFiles(),
            'file_count' => count($this->torrents),
            'savepath' => $this->savepath,
            'category' => $this->category,
            'tag_count' => count($this->tags),
            'skip_checking' => $this->skipChecking,
            'paused' => $this->paused,
            'root_folder' => $this->rootFolder,
            'endpoint' => $this->getEndpoint(),
            'method' => $this->getMethod(),
            'requires_auth' => $this->requiresAuthentication(),
        ];
    }
}