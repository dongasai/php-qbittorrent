<?php
declare(strict_types=1);

namespace PhpQbittorrent\Response\Torrent;

use PhpQbittorrent\Contract\ResponseInterface;
use PhpQbittorrent\Response\AbstractResponse;
use PhpQbittorrent\Model\TorrentProperties;

/**
 * Torrent属性响应
 *
 * 用于处理获取Torrent属性请求的响应
 */
class TorrentPropertiesResponse extends AbstractResponse implements ResponseInterface
{
    /** @var TorrentProperties|null Torrent属性 */
    private ?TorrentProperties $properties;

    /** @var array<string, mixed> 原始数据 */
    private array $rawData;

    /**
     * 从API响应创建TorrentPropertiesResponse实例
     *
     * @param array<string, mixed> $data API响应数据
     * @param array<string, string> $headers 响应头
     * @param int $statusCode HTTP状态码
     * @param string $rawResponse 原始响应内容
     * @return self TorrentPropertiesResponse实例
     */
    public static function fromApiData(
        array $data,
        array $headers = [],
        int $statusCode = 200,
        string $rawResponse = ''
    ): self {
        $response = new self();
        $response->statusCode = $statusCode;
        $response->headers = $headers;
        $response->rawResponse = $rawResponse;
        $response->rawData = $data;
        $response->errors = [];

        try {
            $response->properties = TorrentProperties::fromApiData($data);
        } catch (\Exception $e) {
            $response->properties = null;
            $response->errors[] = 'Torrent属性数据解析失败: ' . $e->getMessage();
        }

        return $response;
    }

    /**
     * 创建失败响应
     *
     * @param array<string> $errors 错误列表
     * @param array<string, string> $headers 响应头
     * @param int $statusCode HTTP状态码
     * @param string $rawResponse 原始响应内容
     * @return self TorrentPropertiesResponse实例
     */
    public static function failure(
        array $errors,
        array $headers = [],
        int $statusCode = 400,
        string $rawResponse = ''
    ): self {
        $response = new self();
        $response->statusCode = $statusCode;
        $response->headers = $headers;
        $response->rawResponse = $rawResponse;
        $response->errors = $errors;
        $response->properties = null;
        $response->rawData = [];

        return $response;
    }

    /**
     * 获取Torrent属性
     *
     * @return TorrentProperties|null Torrent属性，如果获取失败返回null
     */
    public function getProperties(): ?TorrentProperties
    {
        return $this->properties;
    }

    /**
     * 检查响应是否成功
     *
     * @return bool 是否成功
     */
    public function isSuccess(): bool
    {
        return $this->properties !== null && empty($this->errors);
    }

    /**
     * 获取原始数据
     *
     * @return array<string, mixed> 原始数据
     */
    public function getRawData(): array
    {
        return $this->rawData;
    }

    /**
     * 获取响应数据
     *
     * @return mixed Torrent属性或错误信息
     */
    public function getData(): mixed
    {
        if ($this->isSuccess()) {
            return $this->properties;
        }

        return [
            'properties' => $this->properties,
            'errors' => $this->errors,
            'raw_data' => $this->rawData,
        ];
    }

    /**
     * 转换为数组
     *
     * @return array<string, mixed> 响应数据
     */
    public function toArray(): array
    {
        return [
            'success' => $this->isSuccess(),
            'properties' => $this->properties ? $this->properties->toArray() : null,
            'errors' => $this->errors,
            'status_code' => $this->statusCode,
            'headers' => $this->headers,
            'raw_data' => $this->rawData,
        ];
    }

    /**
     * JSON序列化
     *
     * @return array<string, mixed> 序列化数据
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * 获取格式化的响应摘要
     *
     * @return array<string, mixed> 摘要信息
     */
    public function getSummary(): array
    {
        $summary = [
            'success' => $this->isSuccess(),
            'status_code' => $this->statusCode,
            'has_properties' => $this->properties !== null,
            'error_count' => count($this->errors),
        ];

        if ($this->properties !== null) {
            $summary['properties_info'] = [
                'hash_available' => true,
                'size_formatted' => $this->properties->getFormattedSize(),
                'upload_speed_formatted' => $this->properties->getFormattedUpSpeed(),
                'download_speed_formatted' => $this->properties->getFormattedDlSpeed(),
                'is_completed' => $this->properties->isCompleted(),
                'is_private' => $this->properties->isPrivate(),
                'share_ratio' => $this->properties->getShareRatio(),
                'peers_count' => $this->properties->getPeers(),
                'seeds_count' => $this->properties->getSeeds(),
            ];
        }

        return $summary;
    }
}