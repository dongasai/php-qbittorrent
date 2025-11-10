<?php
declare(strict_types=1);

namespace PhpQbittorrent\Builder;

use PhpQbittorrent\Response\Application\VersionResponse;
use PhpQbittorrent\Response\Application\BuildInfoResponse;
use PhpQbittorrent\Response\Application\PreferencesResponse;

use PhpQbittorrent\Response\Transfer\GlobalTransferInfoResponse;
use PhpQbittorrent\Response\Transfer\AlternativeSpeedLimitsStateResponse;

use PhpQbittorrent\Response\Torrent\TorrentListResponse;
use PhpQbittorrent\Response\Torrent\TorrentInfoResponse;

use PhpQbittorrent\Response\RSS\RSSItemsResponse;

use PhpQbittorrent\Response\Search\SearchResponse;
use PhpQbittorrent\Response\Search\SearchStatusResponse;
use PhpQbittorrent\Response\Search\SearchResultsResponse;

use PhpQbittorrent\Contract\TransportResponse;
use PhpQbittorrent\Contract\ResponseInterface;

/**
 * 响应构建器类
 *
 * 提供统一的响应对象构建和转换接口
 */
class ResponseBuilder
{
    /**
     * 应用相关响应构建
     */

    /**
     * 构建版本响应
     *
     * @param TransportResponse $transportResponse 传输响应
     * @return VersionResponse 版本响应
     */
    public static function buildVersionResponse(TransportResponse $transportResponse): VersionResponse
    {
        $statusCode = $transportResponse->getStatusCode();
        $headers = $transportResponse->getHeaders();
        $rawResponse = $transportResponse->getBody();

        if ($statusCode === 200) {
            try {
                $version = $transportResponse->getBody(); // 版本信息直接在body中
                return VersionResponse::success($version, $headers, $statusCode, $rawResponse);
            } catch (\Exception $e) {
                return VersionResponse::failure(
                    ['响应解析失败: ' . $e->getMessage()],
                    $headers,
                    $statusCode,
                    $rawResponse
                );
            }
        } else {
            return VersionResponse::failure(
                ["获取版本信息失败，状态码: {$statusCode}"],
                $headers,
                $statusCode,
                $rawResponse
            );
        }
    }

    /**
     * 构建构建信息响应
     *
     * @param TransportResponse $transportResponse 传输响应
     * @return BuildInfoResponse 构建信息响应
     */
    public static function buildBuildInfoResponse(TransportResponse $transportResponse): BuildInfoResponse
    {
        $statusCode = $transportResponse->getStatusCode();
        $headers = $transportResponse->getHeaders();
        $rawResponse = $transportResponse->getBody();

        if ($statusCode === 200) {
            try {
                $buildInfo = $transportResponse->getJson() ?? [];
                return BuildInfoResponse::fromApiResponse($buildInfo, $headers, $statusCode, $rawResponse);
            } catch (\Exception $e) {
                return BuildInfoResponse::failure(
                    ['响应解析失败: ' . $e->getMessage()],
                    $headers,
                    $statusCode,
                    $rawResponse
                );
            }
        } else {
            return BuildInfoResponse::failure(
                ["获取构建信息失败，状态码: {$statusCode}"],
                $headers,
                $statusCode,
                $rawResponse
            );
        }
    }

    /**
     * 构建偏好设置响应
     *
     * @param TransportResponse $transportResponse 传输响应
     * @return PreferencesResponse 偏好设置响应
     */
    public static function buildPreferencesResponse(TransportResponse $transportResponse): PreferencesResponse
    {
        $statusCode = $transportResponse->getStatusCode();
        $headers = $transportResponse->getHeaders();
        $rawResponse = $transportResponse->getBody();

        if ($statusCode === 200) {
            try {
                $preferences = $transportResponse->getJson() ?? [];
                return PreferencesResponse::fromApiResponse($preferences, $headers, $statusCode, $rawResponse);
            } catch (\Exception $e) {
                return PreferencesResponse::failure(
                    ['响应解析失败: ' . $e->getMessage()],
                    $headers,
                    $statusCode,
                    $rawResponse
                );
            }
        } else {
            return PreferencesResponse::failure(
                ["获取偏好设置失败，状态码: {$statusCode}"],
                $headers,
                $statusCode,
                $rawResponse
            );
        }
    }

    /**
     * 传输相关响应构建
     */

    /**
     * 构建全局传输信息响应
     *
     * @param TransportResponse $transportResponse 传输响应
     * @return GlobalTransferInfoResponse 全局传输信息响应
     */
    public static function buildGlobalTransferInfoResponse(TransportResponse $transportResponse): GlobalTransferInfoResponse
    {
        $statusCode = $transportResponse->getStatusCode();
        $headers = $transportResponse->getHeaders();
        $rawResponse = $transportResponse->getBody();

        if ($statusCode === 200) {
            try {
                $transferInfo = $transportResponse->getJson() ?? [];
                return GlobalTransferInfoResponse::fromApiResponse($transferInfo, $headers, $statusCode, $rawResponse);
            } catch (\Exception $e) {
                return GlobalTransferInfoResponse::failure(
                    ['响应解析失败: ' . $e->getMessage()],
                    $headers,
                    $statusCode,
                    $rawResponse
                );
            }
        } else {
            return GlobalTransferInfoResponse::failure(
                ["获取全局传输信息失败，状态码: {$statusCode}"],
                $headers,
                $statusCode,
                $rawResponse
            );
        }
    }

    /**
     * 构建替代速度限制状态响应
     *
     * @param TransportResponse $transportResponse 传输响应
     * @return AlternativeSpeedLimitsStateResponse 替代速度限制状态响应
     */
    public static function buildAlternativeSpeedLimitsStateResponse(TransportResponse $transportResponse): AlternativeSpeedLimitsStateResponse
    {
        $statusCode = $transportResponse->getStatusCode();
        $headers = $transportResponse->getHeaders();
        $rawResponse = $transportResponse->getBody();

        if ($statusCode === 200) {
            try {
                $state = $transportResponse->getBody(); // 状态信息直接在body中
                return AlternativeSpeedLimitsStateResponse::fromApiResponse($state, $headers, $statusCode, $rawResponse);
            } catch (\Exception $e) {
                return AlternativeSpeedLimitsStateResponse::failure(
                    ['响应解析失败: ' . $e->getMessage()],
                    $headers,
                    $statusCode,
                    $rawResponse
                );
            }
        } else {
            return AlternativeSpeedLimitsStateResponse::failure(
                ["获取替代速度限制状态失败，状态码: {$statusCode}"],
                $headers,
                $statusCode,
                $rawResponse
            );
        }
    }

    /**
     * 种子相关响应构建
     */

    /**
     * 构建种子列表响应
     *
     * @param TransportResponse $transportResponse 传输响应
     * @return TorrentListResponse 种子列表响应
     */
    public static function buildTorrentListResponse(TransportResponse $transportResponse): TorrentListResponse
    {
        $statusCode = $transportResponse->getStatusCode();
        $headers = $transportResponse->getHeaders();
        $rawResponse = $transportResponse->getBody();

        if ($statusCode === 200) {
            try {
                $torrentsData = $transportResponse->getJson() ?? [];
                return TorrentListResponse::fromApiResponse($torrentsData, $headers, $statusCode, $rawResponse);
            } catch (\Exception $e) {
                return TorrentListResponse::failure(
                    ['响应解析失败: ' . $e->getMessage()],
                    $headers,
                    $statusCode,
                    $rawResponse
                );
            }
        } else {
            return TorrentListResponse::failure(
                ["获取种子列表失败，状态码: {$statusCode}"],
                $headers,
                $statusCode,
                $rawResponse
            );
        }
    }

    /**
     * 构建种子信息响应
     *
     * @param TransportResponse $transportResponse 传输响应
     * @return TorrentInfoResponse 种子信息响应
     */
    public static function buildTorrentInfoResponse(TransportResponse $transportResponse): TorrentInfoResponse
    {
        $statusCode = $transportResponse->getStatusCode();
        $headers = $transportResponse->getHeaders();
        $rawResponse = $transportResponse->getBody();

        if ($statusCode === 200) {
            try {
                $torrentInfo = $transportResponse->getJson() ?? [];
                return TorrentInfoResponse::fromApiResponse($torrentInfo, $headers, $statusCode, $rawResponse);
            } catch (\Exception $e) {
                return TorrentInfoResponse::failure(
                    ['响应解析失败: ' . $e->getMessage()],
                    $headers,
                    $statusCode,
                    $rawResponse
                );
            }
        } else {
            return TorrentInfoResponse::failure(
                ["获取种子信息失败，状态码: {$statusCode}"],
                $headers,
                $statusCode,
                $rawResponse
            );
        }
    }

    /**
     * RSS相关响应构建
     */

    /**
     * 构建RSS项目响应
     *
     * @param TransportResponse $transportResponse 传输响应
     * @return RSSItemsResponse RSS项目响应
     */
    public static function buildRSSItemsResponse(TransportResponse $transportResponse): RSSItemsResponse
    {
        $statusCode = $transportResponse->getStatusCode();
        $headers = $transportResponse->getHeaders();
        $rawResponse = $transportResponse->getBody();

        if ($statusCode === 200) {
            try {
                $rssData = $transportResponse->getJson() ?? [];
                return RSSItemsResponse::fromApiResponse($rssData, true, $headers, $statusCode, $rawResponse);
            } catch (\Exception $e) {
                return RSSItemsResponse::failure(
                    ['响应解析失败: ' . $e->getMessage()],
                    $headers,
                    $statusCode,
                    $rawResponse
                );
            }
        } else {
            return RSSItemsResponse::failure(
                ["获取RSS项目失败，状态码: {$statusCode}"],
                $headers,
                $statusCode,
                $rawResponse
            );
        }
    }

    /**
     * 搜索相关响应构建
     */

    /**
     * 构建搜索响应
     *
     * @param TransportResponse $transportResponse 传输响应
     * @return SearchResponse 搜索响应
     */
    public static function buildSearchResponse(TransportResponse $transportResponse): SearchResponse
    {
        $statusCode = $transportResponse->getStatusCode();
        $headers = $transportResponse->getHeaders();
        $rawResponse = $transportResponse->getBody();

        if ($statusCode === 200 || $statusCode === 201) {
            try {
                $responseData = $transportResponse->getJson() ?? [];
                return SearchResponse::fromApiResponse($responseData, $headers, $statusCode, $rawResponse);
            } catch (\Exception $e) {
                return SearchResponse::failure(
                    ['响应解析失败: ' . $e->getMessage()],
                    $headers,
                    $statusCode,
                    $rawResponse
                );
            }
        } else {
            return SearchResponse::failure(
                ["搜索启动失败，状态码: {$statusCode}"],
                $headers,
                $statusCode,
                $rawResponse
            );
        }
    }

    /**
     * 构建搜索状态响应
     *
     * @param TransportResponse $transportResponse 传输响应
     * @return SearchStatusResponse 搜索状态响应
     */
    public static function buildSearchStatusResponse(TransportResponse $transportResponse): SearchStatusResponse
    {
        $statusCode = $transportResponse->getStatusCode();
        $headers = $transportResponse->getHeaders();
        $rawResponse = $transportResponse->getBody();

        if ($statusCode === 200) {
            try {
                $responseData = $transportResponse->getJson() ?? [];
                return SearchStatusResponse::fromApiResponse($responseData, $headers, $statusCode, $rawResponse);
            } catch (\Exception $e) {
                return SearchStatusResponse::failure(
                    ['响应解析失败: ' . $e->getMessage()],
                    $headers,
                    $statusCode,
                    $rawResponse
                );
            }
        } else {
            return SearchStatusResponse::failure(
                ["获取搜索状态失败，状态码: {$statusCode}"],
                $headers,
                $statusCode,
                $rawResponse
            );
        }
    }

    /**
     * 构建搜索结果响应
     *
     * @param TransportResponse $transportResponse 传输响应
     * @param int|null $searchId 搜索作业ID
     * @return SearchResultsResponse 搜索结果响应
     */
    public static function buildSearchResultsResponse(TransportResponse $transportResponse, ?int $searchId = null): SearchResultsResponse
    {
        $statusCode = $transportResponse->getStatusCode();
        $headers = $transportResponse->getHeaders();
        $rawResponse = $transportResponse->getBody();

        if ($statusCode === 200) {
            try {
                $responseData = $transportResponse->getJson() ?? [];
                return SearchResultsResponse::fromApiResponse($responseData, $searchId, $headers, $statusCode, $rawResponse);
            } catch (\Exception $e) {
                return SearchResultsResponse::failure(
                    ['响应解析失败: ' . $e->getMessage()],
                    $headers,
                    $statusCode,
                    $rawResponse
                );
            }
        } else {
            return SearchResultsResponse::failure(
                ["获取搜索结果失败，状态码: {$statusCode}"],
                $headers,
                $statusCode,
                $rawResponse
            );
        }
    }

    /**
     * 通用响应构建
     */

    /**
     * 构建通用成功响应
     *
     * @param TransportResponse $transportResponse 传输响应
     * @param array<string, mixed> $additionalData 额外数据
     * @return ResponseInterface 通用响应
     */
    public static function buildSuccessResponse(TransportResponse $transportResponse, array $additionalData = []): ResponseInterface
    {
        $defaultData = [
            'success' => true,
            'message' => '操作成功',
            'timestamp' => time(),
        ];

        $data = array_merge($defaultData, $additionalData);

        return new class($transportResponse, $data) implements ResponseInterface {
            private TransportResponse $response;
            private array $data;

            public function __construct(TransportResponse $response, array $data) {
                $this->response = $response;
                $this->data = array_merge($response->getJson() ?? [], $data);
            }

            public function isSuccess(): bool { return true; }
            public function getErrors(): array { return []; }
            public function getData(): mixed { return $this->data; }
            public function getStatusCode(): int { return $this->response->getStatusCode(); }
            public function getHeaders(): array { return $this->response->getHeaders(); }
            public function getRawResponse(): string { return $this->response->getBody(); }
            public function toArray(): array { return $this->data; }
            public function jsonSerialize(): array { return $this->toArray(); }
        };
    }

    /**
     * 构建通用失败响应
     *
     * @param TransportResponse $transportResponse 传输响应
     * @param string $message 错误消息
     * @param array<string> $errors 错误列表
     * @return ResponseInterface 通用响应
     */
    public static function buildFailureResponse(TransportResponse $transportResponse, string $message, array $errors = []): ResponseInterface
    {
        $statusCode = $transportResponse->getStatusCode();

        $data = [
            'success' => false,
            'message' => $message,
            'error' => "操作失败，状态码: {$statusCode}",
            'errors' => $errors,
            'timestamp' => time(),
        ];

        return new class($transportResponse, $data) implements ResponseInterface {
            private TransportResponse $response;
            private array $data;

            public function __construct(TransportResponse $response, array $data) {
                $this->response = $response;
                $this->data = array_merge($response->getJson() ?? [], $data);
            }

            public function isSuccess(): bool { return false; }
            public function getErrors(): array { return $this->data['errors'] ?? []; }
            public function getData(): mixed { return $this->data; }
            public function getStatusCode(): int { return $this->response->getStatusCode(); }
            public function getHeaders(): array { return $this->response->getHeaders(); }
            public function getRawResponse(): string { return $this->response->getBody(); }
            public function toArray(): array { return $this->data; }
            public function jsonSerialize(): array { return $this->toArray(); }
        };
    }

    /**
     * 根据状态码和响应体自动构建响应
     *
     * @param TransportResponse $transportResponse 传输响应
     * @param string $responseType 响应类型
     * @param array<string, mixed> $context 上下文信息
     * @return ResponseInterface 响应对象
     */
    public static function buildAutoResponse(TransportResponse $transportResponse, string $responseType, array $context = []): ResponseInterface
    {
        $statusCode = $transportResponse->getStatusCode();

        // 检查是否为成功状态码
        if ($statusCode >= 200 && $statusCode < 300) {
            return match ($responseType) {
                'version' => self::buildVersionResponse($transportResponse),
                'build_info' => self::buildBuildInfoResponse($transportResponse),
                'preferences' => self::buildPreferencesResponse($transportResponse),
                'global_transfer_info' => self::buildGlobalTransferInfoResponse($transportResponse),
                'alt_speed_limits_state' => self::buildAlternativeSpeedLimitsStateResponse($transportResponse),
                'torrent_list' => self::buildTorrentListResponse($transportResponse),
                'torrent_info' => self::buildTorrentInfoResponse($transportResponse),
                'rss_items' => self::buildRSSItemsResponse($transportResponse),
                'search' => self::buildSearchResponse($transportResponse),
                'search_status' => self::buildSearchStatusResponse($transportResponse),
                'search_results' => self::buildSearchResultsResponse($transportResponse, $context['search_id'] ?? null),
                default => self::buildSuccessResponse($transportResponse, $context),
            };
        } else {
            $message = $context['error_message'] ?? "请求失败，状态码: {$statusCode}";
            $errors = $context['errors'] ?? [];
            return self::buildFailureResponse($transportResponse, $message, $errors);
        }
    }

    /**
     * 批量构建响应
     *
     * @param array<TransportResponse> $transportResponses 传输响应数组
     * @param array<string> $responseTypes 响应类型数组
     * @param array<array<string, mixed>> $contexts 上下文信息数组
     * @return array<ResponseInterface> 响应对象数组
     */
    public static function buildBatchResponses(array $transportResponses, array $responseTypes, array $contexts = []): array
    {
        $responses = [];
        $count = min(count($transportResponses), count($responseTypes));

        for ($i = 0; $i < $count; $i++) {
            $context = $contexts[$i] ?? [];
            $responses[] = self::buildAutoResponse($transportResponses[$i], $responseTypes[$i], $context);
        }

        return $responses;
    }
}