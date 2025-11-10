<?php
declare(strict_types=1);

namespace PhpQbittorrent\Request\Torrent;

use PhpQbittorrent\Request\AbstractRequest;

/**
 * 恢复Torrent请求对象
 *
 * 用于恢复一个或多个Torrent的下载
 */
class ResumeTorrentsRequest extends AbstractRequest
{
    /** @var string 种子哈希列表 */
    private string $hashes;

    /**
     * 构造函数
     *
     * @param string $hashes 种子哈希列表，用|分隔
     */
    public function __construct(string $hashes)
    {
        parent::__construct([]);
        $this->hashes = $hashes;
    }

    /**
     * 获取种子哈希列表
     *
     * @return string 种子哈希列表
     */
    public function getHashes(): string
    {
        return $this->hashes;
    }

    /**
     * 获取API端点
     *
     * @return string API端点
     */
    public function getEndpoint(): string
    {
        return '/resume';
    }

    /**
     * 获取请求方法
     *
     * @return string 请求方法
     */
    public function getMethod(): string
    {
        return 'POST';
    }

    /**
     * 转换为数组格式
     *
     * @return array<string, mixed> 请求数据数组
     */
    public function toArray(): array
    {
        return [
            'hashes' => $this->hashes,
        ];
    }

    /**
     * 获取请求摘要
     *
     * @return array<string, mixed> 请求摘要
     */
    public function getSummary(): array
    {
        return [
            'hashes' => $this->hashes,
            'endpoint' => $this->getEndpoint(),
            'method' => $this->getMethod(),
        ];
    }
}