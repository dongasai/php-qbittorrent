<?php
declare(strict_types=1);

namespace PhpQbittorrent\Response\Torrent;

use PhpQbittorrent\Contract\ResponseInterface;
use PhpQbittorrent\Response\AbstractResponse;
use PhpQbittorrent\Model\TorrentTracker;

/**
 * Torrent Trackers响应
 *
 * 用于处理获取Torrent Trackers列表请求的响应
 */
class TorrentTrackersResponse extends AbstractResponse implements ResponseInterface
{
    /** @var array<TorrentTracker> Tracker列表 */
    private array $trackers;

    /** @var array<string, mixed> 原始数据 */
    private array $rawData;

    /**
     * 从API响应创建TorrentTrackersResponse实例
     *
     * @param array<string, mixed> $data API响应数据
     * @param array<string, string> $headers 响应头
     * @param int $statusCode HTTP状态码
     * @param string $rawResponse 原始响应内容
     * @return self TorrentTrackersResponse实例
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
            $response->trackers = [];
            foreach ($data as $trackerData) {
                if (is_array($trackerData)) {
                    $response->trackers[] = TorrentTracker::fromApiData($trackerData);
                }
            }
        } catch (\Exception $e) {
            $response->trackers = [];
            $response->errors[] = 'Trackers数据解析失败: ' . $e->getMessage();
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
     * @return self TorrentTrackersResponse实例
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
        $response->trackers = [];
        $response->rawData = [];

        return $response;
    }

    /**
     * 获取Tracker列表
     *
     * @return array<TorrentTracker> Tracker列表
     */
    public function getTrackers(): array
    {
        return $this->trackers;
    }

    /**
     * 获取活跃的Trackers
     *
     * @return array<TorrentTracker> 活跃的Tracker列表
     */
    public function getActiveTrackers(): array
    {
        return array_filter($this->trackers, function (TorrentTracker $tracker) {
            return $tracker->isActive();
        });
    }

    /**
     * 获取工作中的Trackers
     *
     * @return array<TorrentTracker> 工作中的Tracker列表
     */
    public function getWorkingTrackers(): array
    {
        return array_filter($this->trackers, function (TorrentTracker $tracker) {
            return $tracker->isWorking();
        });
    }

    /**
     * 获取不工作的Trackers
     *
     * @return array<TorrentTracker> 不工作的Tracker列表
     */
    public function getNonWorkingTrackers(): array
    {
        return array_filter($this->trackers, function (TorrentTracker $tracker) {
            return $tracker->isNotWorking() || $tracker->isNotContacted();
        });
    }

    /**
     * 检查响应是否成功
     *
     * @return bool 是否成功
     */
    public function isSuccess(): bool
    {
        return !empty($this->trackers) && empty($this->errors);
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
     * @return mixed Trackers列表或错误信息
     */
    public function getData(): mixed
    {
        if ($this->isSuccess()) {
            return $this->trackers;
        }

        return [
            'trackers' => $this->trackers,
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
        $trackersArray = [];
        foreach ($this->trackers as $tracker) {
            $trackersArray[] = $tracker->toArray();
        }

        return [
            'success' => $this->isSuccess(),
            'trackers' => $trackersArray,
            'active_trackers' => count($this->getActiveTrackers()),
            'working_trackers' => count($this->getWorkingTrackers()),
            'non_working_trackers' => count($this->getNonWorkingTrackers()),
            'total_trackers' => count($this->trackers),
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
     * 获取按状态分组的Trackers
     *
     * @return array<string, array<TorrentTracker>> 按状态分组的Trackers
     */
    public function getTrackersByStatus(): array
    {
        $grouped = [
            'disabled' => [],
            'not_contacted' => [],
            'working' => [],
            'updating' => [],
            'not_working' => [],
        ];

        foreach ($this->trackers as $tracker) {
            if ($tracker->isDisabled()) {
                $grouped['disabled'][] = $tracker;
            } elseif ($tracker->isNotContacted()) {
                $grouped['not_contacted'][] = $tracker;
            } elseif ($tracker->isWorking()) {
                $grouped['working'][] = $tracker;
            } elseif ($tracker->isUpdating()) {
                $grouped['updating'][] = $tracker;
            } elseif ($tracker->isNotWorking()) {
                $grouped['not_working'][] = $tracker;
            }
        }

        return $grouped;
    }

    /**
     * 获取格式化的响应摘要
     *
     * @return array<string, mixed> 摘要信息
     */
    public function getSummary(): array
    {
        $totalTrackers = count($this->trackers);
        $activeTrackers = count($this->getActiveTrackers());
        $workingTrackers = count($this->getWorkingTrackers());
        $totalPeers = array_sum(array_map(function ($tracker) {
            return $tracker->getNumPeers();
        }, $this->trackers));

        $totalSeeds = array_sum(array_map(function ($tracker) {
            return $tracker->getNumSeeds();
        }, $this->trackers));

        return [
            'success' => $this->isSuccess(),
            'status_code' => $this->statusCode,
            'total_trackers' => $totalTrackers,
            'active_trackers' => $activeTrackers,
            'working_trackers' => $workingTrackers,
            'success_rate' => $totalTrackers > 0 ? round(($workingTrackers / $totalTrackers) * 100, 2) : 0,
            'total_peers' => $totalPeers,
            'total_seeds' => $totalSeeds,
            'error_count' => count($this->errors),
        ];
    }
}