<?php
declare(strict_types=1);

namespace PhpQbittorrent\Response\Search;

use PhpQbittorrent\Response\AbstractResponse;
use PhpQbittorrent\Collection\SearchJobCollection;
use PhpQbittorrent\Model\SearchJob;

/**
 * 搜索状态响应对象
 */
class SearchStatusResponse extends AbstractResponse
{
    /** @var SearchJobCollection 搜索作业集合 */
    private SearchJobCollection $searchJobs;

    /**
     * 创建成功的搜索状态响应
     *
     * @param array<array<string, mixed>> $searchJobsData 搜索作业数据列表
     * @param array<string, string> $headers 响应头
     * @param int $statusCode HTTP状态码
     * @param string $rawResponse 原始响应内容
     * @return self 搜索状态响应实例
     */
    public static function success(
        array $searchJobsData,
        array $headers = [],
        int $statusCode = 200,
        string $rawResponse = ''
    ): self {
        $searchJobs = new SearchJobCollection();
        foreach ($searchJobsData as $jobData) {
            $searchJobs->addJob(SearchJob::fromArray($jobData));
        }

        $instance = parent::success(['search_jobs' => $searchJobsData], $headers, $statusCode, $rawResponse);
        $instance->searchJobs = $searchJobs;

        return $instance;
    }

    /**
     * 创建失败的搜索状态响应
     *
     * @param array<string> $errors 错误信息
     * @param array<string, string> $headers 响应头
     * @param int $statusCode HTTP状态码
     * @param string $rawResponse 原始响应内容
     * @return self 搜索状态响应实例
     */
    public static function failure(
        array $errors = [],
        array $headers = [],
        int $statusCode = 400,
        string $rawResponse = ''
    ): self {
        $instance = parent::failure($errors, $headers, $statusCode, $rawResponse);
        $instance->searchJobs = new SearchJobCollection();

        return $instance;
    }

    /**
     * 从数组数据创建响应对象
     *
     * @param array<string, mixed> $data 响应数据
     * @return static 响应对象实例
     */
    public static function fromArray(array $data): static
    {
        $success = ($data['success'] ?? false);
        $headers = $data['headers'] ?? [];
        $statusCode = $data['statusCode'] ?? 200;
        $rawResponse = $data['rawResponse'] ?? '';
        $errors = $data['errors'] ?? [];
        $responseData = $data['data'] ?? [];

        if ($success) {
            $searchJobsData = $responseData['search_jobs'] ?? [];
            return self::success($searchJobsData, $headers, $statusCode, $rawResponse);
        } else {
            return self::failure($errors, $headers, $statusCode, $rawResponse);
        }
    }

    /**
     * 从API响应创建响应对象
     *
     * @param array<array<string, mixed>> $apiResponse API响应数据
     * @param array<string, string> $headers 响应头
     * @param int $statusCode HTTP状态码
     * @param string $rawResponse 原始响应内容
     * @return static 响应对象实例
     */
    public static function fromApiResponse(
        array $apiResponse,
        array $headers = [],
        int $statusCode = 200,
        string $rawResponse = ''
    ): static {
        if (is_array($apiResponse) && !empty($apiResponse)) {
            return self::success($apiResponse, $headers, $statusCode, $rawResponse);
        } else {
            return self::failure(
                ['Invalid API response: expected array of search status data'],
                $headers,
                $statusCode,
                $rawResponse
            );
        }
    }

    /**
     * 获取搜索作业集合
     *
     * @return SearchJobCollection 搜索作业集合
     */
    public function getSearchJobs(): SearchJobCollection
    {
        return $this->searchJobs;
    }

    /**
     * 检查是否有搜索作业
     *
     * @return bool 是否有搜索作业
     */
    public function hasSearchJobs(): bool
    {
        return !$this->searchJobs->isEmpty();
    }

    /**
     * 获取搜索作业数量
     *
     * @return int 搜索作业数量
     */
    public function getSearchJobCount(): int
    {
        return $this->searchJobs->count();
    }

    /**
     * 根据ID获取搜索作业
     *
     * @param int $searchId 搜索作业ID
     * @return SearchJob|null 搜索作业，不存在时返回null
     */
    public function getSearchJobById(int $searchId): ?SearchJob
    {
        return $this->searchJobs->findById($searchId);
    }

    /**
     * 获取正在运行的搜索作业
     *
     * @return SearchJobCollection 正在运行的搜索作业集合
     */
    public function getRunningSearchJobs(): SearchJobCollection
    {
        return $this->searchJobs->getRunning();
    }

    /**
     * 获取已停止的搜索作业
     *
     * @return SearchJobCollection 已停止的搜索作业集合
     */
    public function getStoppedSearchJobs(): SearchJobCollection
    {
        return $this->searchJobs->getStopped();
    }

    /**
     * 获取总搜索结果数
     *
     * @return int 总搜索结果数
     */
    public function getTotalResults(): int
    {
        return $this->searchJobs->getTotalResults();
    }

    /**
     * 检查是否有正在运行的搜索作业
     *
     * @return bool 是否有正在运行的搜索作业
     */
    public function hasRunningSearchJobs(): bool
    {
        return $this->searchJobs->hasRunningJobs();
    }

    /**
     * 检查是否有已停止的搜索作业
     *
     * @return bool 是否有已停止的搜索作业
     */
    public function hasStoppedSearchJobs(): bool
    {
        return $this->searchJobs->hasStoppedJobs();
    }

    /**
     * 获取运行中的搜索作业数量
     *
     * @return int 运行中的搜索作业数量
     */
    public function getRunningSearchJobCount(): int
    {
        return $this->searchJobs->getRunningJobCount();
    }

    /**
     * 获取已停止的搜索作业数量
     *
     * @return int 已停止的搜索作业数量
     */
    public function getStoppedSearchJobCount(): int
    {
        return $this->searchJobs->getStoppedJobCount();
    }

    /**
     * 获取搜索状态摘要
     *
     * @return array<string, mixed> 搜索状态摘要
     */
    public function getStatusSummary(): array
    {
        return [
            'total_jobs' => $this->getSearchJobCount(),
            'running_jobs' => $this->getRunningSearchJobCount(),
            'stopped_jobs' => $this->getStoppedSearchJobCount(),
            'total_results' => $this->getTotalResults(),
            'has_running_jobs' => $this->hasRunningSearchJobs(),
            'has_stopped_jobs' => $this->hasStoppedSearchJobs(),
        ];
    }

    /**
     * 获取格式化的状态信息
     *
     * @return array<string, mixed> 格式化的状态信息
     */
    public function getFormattedInfo(): array
    {
        $summary = $this->getStatusSummary();

        return [
            'success' => $this->isSuccess(),
            'summary' => $summary,
            'jobs_detail' => $this->searchJobs->toArray(),
            'jobs_count' => $this->getSearchJobCount(),
            'has_jobs' => $this->hasSearchJobs(),
            'error_count' => count($this->getErrors()),
            'timestamp' => time(),
        ];
    }

    /**
     * 转换为数组格式
     *
     * @return array<string, mixed> 响应数据数组
     */
    public function toArray(): array
    {
        $data = parent::toArray();
        $data['search_jobs'] = $this->searchJobs->toArray();
        $data['search_jobs_count'] = $this->getSearchJobCount();
        $data['has_search_jobs'] = $this->hasSearchJobs();
        $data['running_search_jobs'] = $this->getRunningSearchJobs()->toArray();
        $data['stopped_search_jobs'] = $this->getStoppedSearchJobs()->toArray();
        $data['status_summary'] = $this->getStatusSummary();
        $data['formatted_info'] = $this->getFormattedInfo();

        return $data;
    }

    /**
     * 获取响应的摘要信息
     *
     * @return array<string, mixed> 摘要信息
     */
    public function getSummary(): array
    {
        return [
            'success' => $this->isSuccess(),
            'search_jobs_count' => $this->getSearchJobCount(),
            'running_jobs_count' => $this->getRunningSearchJobCount(),
            'stopped_jobs_count' => $this->getStoppedSearchJobCount(),
            'total_results' => $this->getTotalResults(),
            'has_running_jobs' => $this->hasRunningSearchJobs(),
            'status_code' => $this->getStatusCode(),
            'error_count' => count($this->getErrors()),
        ];
    }
}