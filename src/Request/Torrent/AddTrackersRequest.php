<?php
declare(strict_types=1);

namespace PhpQbittorrent\Request\Torrent;

use PhpQbittorrent\Request\AbstractRequest;
use PhpQbittorrent\Contract\ValidationResult;
use PhpQbittorrent\Validation\BasicValidationResult;
use PhpQbittorrent\Exception\ValidationException;

/**
 * 添加Tracker请求对象
 *
 * 用于封装向Torrent添加Tracker的请求参数和验证逻辑
 */
class AddTrackersRequest extends AbstractRequest
{
    /** @var string 种子哈希 */
    private string $hash;

    /** @var array<string> Tracker URL列表 */
    private array $urls = [];

    /** @var int 最大URL数量 */
    private const MAX_URLS = 100;

    /** @var int 最大URL长度 */
    private const MAX_URL_LENGTH = 2048;

    /** @var int 种子哈希长度 */
    private const HASH_LENGTH = 40;

    /**
     * 私有构造函数
     */
    private function __construct()
    {
        parent::__construct([]);

        $this->setEndpoint('/addTrackers')
             ->setMethod('POST')
             ->setRequiresAuthentication(true);
    }

    /**
     * 创建实例
     *
     * @param string $hash 种子哈希
     * @param array<string> $urls Tracker URL列表
     * @return self 添加Tracker请求实例
     * @throws ValidationException 如果参数无效
     */
    public static function create(string $hash, array $urls): self
    {
        $request = new self();
        $request->hash = $hash;
        $request->urls = $urls;

        $validation = $request->validate();
        if (!$validation->isValid()) {
            throw ValidationException::fromValidationResult(
                $validation,
                'Add trackers request validation failed'
            );
        }

        return $request;
    }

    /**
     * 创建Builder实例
     *
     * @return AddTrackersRequestBuilder Builder实例
     */
    public static function builder(): AddTrackersRequestBuilder
    {
        return new AddTrackersRequestBuilder();
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
     * 获取Tracker URL列表
     *
     * @return array<string> Tracker URL列表
     */
    public function getUrls(): array
    {
        return $this->urls;
    }

    /**
     * 验证请求参数
     *
     * @return ValidationResult 验证结果
     */
    public function validate(): ValidationResult
    {
        $result = BasicValidationResult::success();

        // 验证种子哈希
        if (empty(trim($this->hash))) {
            $result->addError('种子哈希不能为空');
        } elseif (strlen($this->hash) !== self::HASH_LENGTH) {
            $result->addError('种子哈希长度必须为' . self::HASH_LENGTH . '个字符');
        } elseif (!ctype_xdigit($this->hash)) {
            $result->addError('种子哈希只能包含十六进制字符');
        }

        // 验证URL列表
        if (empty($this->urls)) {
            $result->addError('Tracker URL列表不能为空');
        } elseif (count($this->urls) > self::MAX_URLS) {
            $result->addError("Tracker URL数量不能超过 " . self::MAX_URLS);
        } else {
            foreach ($this->urls as $url) {
                if (empty(trim($url))) {
                    $result->addError('Tracker URL不能为空');
                } elseif (strlen($url) > self::MAX_URL_LENGTH) {
                    $result->addError("Tracker URL长度不能超过 " . self::MAX_URL_LENGTH . " 个字符");
                } elseif (!$this->isValidTrackerUrl($url)) {
                    $result->addError("Tracker URL格式无效: {$url}");
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
        return [
            'hash' => $this->hash,
            'urls' => implode("\n", $this->urls)
        ];
    }

    /**
     * 检查Tracker URL是否有效
     *
     * @param string $url Tracker URL字符串
     * @return bool 是否有效
     */
    private function isValidTrackerUrl(string $url): bool
    {
        // 基本URL格式验证
        $url = trim($url);

        // 检查是否为空
        if (empty($url)) {
            return false;
        }

        // 支持的协议
        $validProtocols = ['http://', 'https://', 'udp://'];
        $hasValidProtocol = false;

        foreach ($validProtocols as $protocol) {
            if (str_starts_with($url, $protocol)) {
                $hasValidProtocol = true;
                break;
            }
        }

        if (!$hasValidProtocol) {
            return false;
        }

        // 基本URL格式检查（不严格验证，因为有些tracker URL格式特殊）
        // 主要检查是否有基本的URL结构
        $pattern = '/^(http|https|udp):\/\/[a-zA-Z0-9.-]+(:\d+)?\/.*$/';
        return preg_match($pattern, $url) === 1;
    }

    /**
     * 获取添加请求的摘要信息
     *
     * @return array<string, mixed> 摘要信息
     */
    public function getSummary(): array
    {
        return [
            'hash' => $this->hash,
            'url_count' => count($this->urls),
            'urls' => $this->urls,
            'endpoint' => $this->getEndpoint(),
            'method' => $this->getMethod(),
            'requires_auth' => $this->requiresAuthentication(),
        ];
    }
}