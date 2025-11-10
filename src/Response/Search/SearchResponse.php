<?php
declare(strict_types=1);

namespace PhpQbittorrent\Response\Search;

use PhpQbittorrent\Response\AbstractResponse;

/**
 * 搜索响应对象
 */
class SearchResponse extends AbstractResponse
{
    /** @var int 搜索作业ID */
    private int $searchId;

    /**
     * 创建成功的搜索响应
     *
     * @param int $searchId 搜索作业ID
     * @param array<string, string> $headers 响应头
     * @param int $statusCode HTTP状态码
     * @param string $rawResponse 原始响应内容
     * @return self 搜索响应实例
     */
    public static function success(
        int $searchId,
        array $headers = [],
        int $statusCode = 200,
        string $rawResponse = ''
    ): self {
        $instance = parent::success(['search_id' => $searchId], $headers, $statusCode, $rawResponse);
        $instance->searchId = $searchId;

        return $instance;
    }

    /**
     * 创建失败的搜索响应
     *
     * @param array<string> $errors 错误信息
     * @param array<string, string> $headers 响应头
     * @param int $statusCode HTTP状态码
     * @param string $rawResponse 原始响应内容
     * @return self 搜索响应实例
     */
    public static function failure(
        array $errors = [],
        array $headers = [],
        int $statusCode = 400,
        string $rawResponse = ''
    ): self {
        $instance = parent::failure($errors, $headers, $statusCode, $rawResponse);
        $instance->searchId = 0;

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
            $searchId = $responseData['search_id'] ?? 0;
            return self::success($searchId, $headers, $statusCode, $rawResponse);
        } else {
            return self::failure($errors, $headers, $statusCode, $rawResponse);
        }
    }

    /**
     * 从API响应创建响应对象
     *
     * @param array<string, mixed> $apiResponse API响应数据
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
        if (isset($apiResponse['id']) && is_int($apiResponse['id'])) {
            return self::success($apiResponse['id'], $headers, $statusCode, $rawResponse);
        } else {
            return self::failure(
                ['Invalid API response: missing or invalid search ID'],
                $headers,
                $statusCode,
                $rawResponse
            );
        }
    }

    /**
     * 获取搜索作业ID
     *
     * @return int 搜索作业ID
     */
    public function getSearchId(): int
    {
        return $this->searchId;
    }

    /**
     * 检查搜索作业ID是否有效
     *
     * @return bool 是否有效
     */
    public function isValidSearchId(): bool
    {
        return $this->searchId > 0;
    }

    /**
     * 检查是否成功开始搜索
     *
     * @return bool 是否成功
     */
    public function isSearchStarted(): bool
    {
        return $this->isSuccess() && $this->isValidSearchId();
    }

    /**
     * 获取搜索作业状态描述
     *
     * @return string 状态描述
     */
    public function getStatusDescription(): string
    {
        if (!$this->isSuccess()) {
            return '搜索启动失败';
        }

        if (!$this->isValidSearchId()) {
            return '搜索作业ID无效';
        }

        return "搜索作业已启动 (ID: {$this->searchId})";
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
            'search_id' => $this->searchId,
            'is_valid_search_id' => $this->isValidSearchId(),
            'search_started' => $this->isSearchStarted(),
            'status_description' => $this->getStatusDescription(),
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
        $data['search_id'] = $this->searchId;
        $data['is_valid_search_id'] = $this->isValidSearchId();
        $data['search_started'] = $this->isSearchStarted();
        $data['status_description'] = $this->getStatusDescription();
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
            'search_id' => $this->searchId,
            'is_valid_search_id' => $this->isValidSearchId(),
            'search_started' => $this->isSearchStarted(),
            'status_code' => $this->getStatusCode(),
            'error_count' => count($this->getErrors()),
        ];
    }
}