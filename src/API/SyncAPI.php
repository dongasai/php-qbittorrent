<?php
declare(strict_types=1);

namespace PhpQbittorrent\API;

use PhpQbittorrent\Contract\ApiInterface;
use PhpQbittorrent\Contract\TransportInterface;
use PhpQbittorrent\Request\Sync\GetMainDataRequest;
use PhpQbittorrent\Request\Sync\GetTorrentPeersRequest;
use PhpQbittorrent\Response\Sync\MainDataResponse;
use PhpQbittorrent\Response\Sync\TorrentPeersResponse;

/**
 * Sync API - qBittorrent同步数据API
 *
 * 提供实时同步qBittorrent数据的功能，支持增量更新
 *
 * @package PhpQbittorrent\API
 */
class SyncAPI implements ApiInterface
{
    private TransportInterface $transport;

    public function __construct(TransportInterface $transport)
    {
        $this->transport = $transport;
    }

    /**
     * 获取主要数据同步
     *
     * 获取qBittorrent的主要数据，包括torrent列表、分类、标签和服务器状态
     * 支持通过rid参数进行增量更新，避免获取完整数据
     *
     * @param int $rid 响应ID，用于增量更新。如果不提供或与上次不同，将返回完整更新
     * @return MainDataResponse 包含同步数据的响应对象
     * @throws \PhpQbittorrent\Exception\NetworkException 网络请求异常
     * @throws \PhpQbittorrent\Exception\ApiRuntimeException API运行时异常
     */
    public function getMainData(int $rid = 0): MainDataResponse
    {
        $request = new GetMainDataRequest($rid);
        $response = $this->transport->send($request);

        return new MainDataResponse($response);
    }

    /**
     * 获取特定torrent的peers数据
     *
     * 获取指定torrent的连接peers信息，支持增量更新
     *
     * @param string $hash Torrent哈希值
     * @param int $rid 响应ID，用于增量更新
     * @return TorrentPeersResponse 包含peers数据的响应对象
     * @throws \PhpQbittorrent\Exception\NetworkException 网络请求异常
     * @throws \PhpQbittorrent\Exception\ApiRuntimeException API运行时异常
     * @throws \InvalidArgumentException 当torrent哈希为空时抛出异常
     */
    public function getTorrentPeers(string $hash, int $rid = 0): TorrentPeersResponse
    {
        if (empty($hash)) {
            throw new \InvalidArgumentException('Torrent hash cannot be empty');
        }

        $request = new GetTorrentPeersRequest($hash, $rid);
        $response = $this->transport->send($request);

        return new TorrentPeersResponse($response);
    }

    /**
     * {@inheritdoc}
     */
    public function getTransport(): TransportInterface
    {
        return $this->transport;
    }
}