<?php
declare(strict_types=1);

namespace PhpQbittorrent\Response\Search;

use PhpQbittorrent\Response\AbstractResponse;
use PhpQbittorrent\Collection\SearchResultCollection;
use PhpQbittorrent\Model\SearchResult;
use PhpQbittorrent\Model\SearchJob;

/**
 * 搜索结果响应对象
 */
class SearchResultsResponse extends AbstractResponse
{
    /** @var SearchResultCollection 搜索结果集合 */
    private SearchResultCollection $searchResults;

    /** @var string 搜索状态 */
    private string $status;

    /** @var int 总结果数 */
    private int $totalResults;

    /** @var int|null 搜索作业ID */
    private ?int $searchId;

    /**
     * 创建成功的搜索结果响应
     *
     * @param array<array<string, mixed>> $resultsData 搜索结果数据列表
     * @param string $status 搜索状态
     * @param int $totalResults 总结果数
     * @param int|null $searchId 搜索作业ID
     * @param array<string, string> $headers 响应头
     * @param int $statusCode HTTP状态码
     * @param string $rawResponse 原始响应内容
     * @return self 搜索结果响应实例
     */
    public static function success(
        array $resultsData,
        string $status = 'Running',
        int $totalResults = 0,
        ?int $searchId = null,
        array $headers = [],
        int $statusCode = 200,
        string $rawResponse = ''
    ): self {
        $searchResults = new SearchResultCollection();
        foreach ($resultsData as $resultData) {
            $searchResults->addResult(SearchResult::fromArray($resultData));
        }

        $instance = parent::success([
            'results' => $resultsData,
            'status' => $status,
            'total' => $totalResults,
        ], $headers, $statusCode, $rawResponse);
        $instance->searchResults = $searchResults;
        $instance->status = $status;
        $instance->totalResults = $totalResults;
        $instance->searchId = $searchId;

        return $instance;
    }

    /**
     * 创建失败的搜索结果响应
     *
     * @param array<string> $errors 错误信息
     * @param array<string, string> $headers 响应头
     * @param int $statusCode HTTP状态码
     * @param string $rawResponse 原始响应内容
     * @return self 搜索结果响应实例
     */
    public static function failure(
        array $errors = [],
        array $headers = [],
        int $statusCode = 400,
        string $rawResponse = ''
    ): self {
        $instance = parent::failure($errors, $headers, $statusCode, $rawResponse);
        $instance->searchResults = new SearchResultCollection();
        $instance->status = 'Failed';
        $instance->totalResults = 0;
        $instance->searchId = null;

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
            $resultsData = $responseData['results'] ?? [];
            $status = $responseData['status'] ?? 'Unknown';
            $totalResults = $responseData['total'] ?? 0;
            $searchId = $responseData['search_id'] ?? null;

            return self::success($resultsData, $status, $totalResults, $searchId, $headers, $statusCode, $rawResponse);
        } else {
            return self::failure($errors, $headers, $statusCode, $rawResponse);
        }
    }

    /**
     * 从API响应创建响应对象
     *
     * @param array<string, mixed> $apiResponse API响应数据
     * @param int|null $searchId 搜索作业ID
     * @param array<string, string> $headers 响应头
     * @param int $statusCode HTTP状态码
     * @param string $rawResponse 原始响应内容
     * @return static 响应对象实例
     */
    public static function fromApiResponse(
        array $apiResponse,
        ?int $searchId = null,
        array $headers = [],
        int $statusCode = 200,
        string $rawResponse = ''
    ): static {
        if (isset($apiResponse['results']) && isset($apiResponse['status'])) {
            return self::success(
                $apiResponse['results'],
                $apiResponse['status'],
                $apiResponse['total'] ?? 0,
                $searchId,
                $headers,
                $statusCode,
                $rawResponse
            );
        } else {
            return self::failure(
                ['Invalid API response: missing required fields'],
                $headers,
                $statusCode,
                $rawResponse
            );
        }
    }

    /**
     * 获取搜索结果集合
     *
     * @return SearchResultCollection 搜索结果集合
     */
    public function getSearchResults(): SearchResultCollection
    {
        return $this->searchResults;
    }

    /**
     * 获取搜索状态
     *
     * @return string 搜索状态
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * 获取总结果数
     *
     * @return int 总结果数
     */
    public function getTotalResults(): int
    {
        return $this->totalResults;
    }

    /**
     * 获取搜索作业ID
     *
     * @return int|null 搜索作业ID
     */
    public function getSearchId(): ?int
    {
        return $this->searchId;
    }

    /**
     * 检查是否有搜索结果
     *
     * @return bool 是否有搜索结果
     */
    public function hasSearchResults(): bool
    {
        return !$this->searchResults->isEmpty();
    }

    /**
     * 获取实际返回的结果数
     *
     * @return int 实际返回的结果数
     */
    public function getActualResultsCount(): int
    {
        return $this->searchResults->count();
    }

    /**
     * 检查是否有更多结果（返回的结果数少于总数）
     *
     * @return bool 是否有更多结果
     */
    public function hasMoreResults(): bool
    {
        return $this->getActualResultsCount() < $this->totalResults;
    }

    /**
     * 检查搜索是否仍在运行
     *
     * @return bool 是否仍在运行
     */
    public function isSearchRunning(): bool
    {
        return $this->status === 'Running';
    }

    /**
     * 检查搜索是否已完成
     *
     * @return bool 是否已完成
     */
    public function isSearchCompleted(): bool
    {
        return $this->status === 'Stopped';
    }

    /**
     * 检查搜索是否失败
     *
     * @return bool 是否失败
     */
    public function isSearchFailed(): bool
    {
        return $this->status === 'Failed';
    }

    /**
     * 获取搜索状态描述
     *
     * @return string 搜索状态描述
     */
    public function getStatusDescription(): string
    {
        return match ($this->status) {
            'Running' => '搜索正在进行中',
            'Stopped' => '搜索已完成',
            'Failed' => '搜索失败',
            default => '未知状态',
        };
    }

    /**
     * 获取有种子数的搜索结果
     *
     * @return SearchResultCollection 有种子数的搜索结果集合
     */
    public function getResultsWithSeeders(): SearchResultCollection
    {
        return $this->searchResults->getWithSeeders();
    }

    /**
     * 获取健康的搜索结果
     *
     * @return SearchResultCollection 健康的搜索结果集合
     */
    public function getHealthyResults(): SearchResultCollection
    {
        return $this->searchResults->getHealthy();
    }

    /**
     * 获取热门的搜索结果
     *
     * @return SearchResultCollection 热门的搜索结果集合
     */
    public function getPopularResults(): SearchResultCollection
    {
        return $this->searchResults->getPopular();
    }

    /**
     * 获取统计信息
     *
     * @return array<string, mixed> 统计信息
     */
    public function getStatistics(): array
    {
        $stats = $this->searchResults->getStatistics();

        return array_merge($stats, [
            'total_results' => $this->totalResults,
            'actual_results' => $this->getActualResultsCount(),
            'has_more_results' => $this->hasMoreResults(),
            'search_status' => $this->status,
            'search_running' => $this->isSearchRunning(),
            'search_completed' => $this->isSearchCompleted(),
            'search_failed' => $this->isSearchFailed(),
        ]);
    }

    /**
     * 获取格式化的响应信息
     *
     * @return array<string, mixed> 格式化的响应信息
     */
    public function getFormattedInfo(): array
    {
        return [
            'success' => $this->isSuccess(),
            'search_info' => [
                'search_id' => $this->searchId,
                'status' => $this->status,
                'status_description' => $this->getStatusDescription(),
                'total_results' => $this->totalResults,
                'actual_results' => $this->getActualResultsCount(),
                'has_more_results' => $this->hasMoreResults(),
                'is_running' => $this->isSearchRunning(),
                'is_completed' => $this->isSearchCompleted(),
                'is_failed' => $this->isSearchFailed(),
            ],
            'results_summary' => $this->searchResults->getFormattedSummary(),
            'statistics' => $this->getStatistics(),
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
        $data['search_results'] = $this->searchResults->toArray();
        $data['status'] = $this->status;
        $data['total_results'] = $this->totalResults;
        $data['search_id'] = $this->searchId;
        $data['actual_results_count'] = $this->getActualResultsCount();
        $data['has_more_results'] = $this->hasMoreResults();
        $data['is_search_running'] = $this->isSearchRunning();
        $data['is_search_completed'] = $this->isSearchCompleted();
        $data['is_search_failed'] = $this->isSearchFailed();
        $data['status_description'] = $this->getStatusDescription();
        $data['statistics'] = $this->getStatistics();
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
            'status' => $this->status,
            'total_results' => $this->totalResults,
            'actual_results' => $this->getActualResultsCount(),
            'has_more_results' => $this->hasMoreResults(),
            'is_search_running' => $this->isSearchRunning(),
            'search_id' => $this->searchId,
            'status_code' => $this->getStatusCode(),
            'error_count' => count($this->getErrors()),
        ];
    }
}