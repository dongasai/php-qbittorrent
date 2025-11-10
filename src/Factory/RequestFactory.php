<?php
declare(strict_types=1);

namespace PhpQbittorrent\Factory;

use PhpQbittorrent\Request\Application\GetVersionRequest;
use PhpQbittorrent\Request\Application\GetWebApiVersionRequest;
use PhpQbittorrent\Request\Application\GetBuildInfoRequest;
use PhpQbittorrent\Request\Application\GetPreferencesRequest;
use PhpQbittorrent\Request\Application\SetPreferencesRequest;
use PhpQbittorrent\Request\Application\GetDefaultSavePathRequest;

use PhpQbittorrent\Request\Transfer\GetGlobalTransferInfoRequest;
use PhpQbittorrent\Request\Transfer\GetAlternativeSpeedLimitsStateRequest;
use PhpQbittorrent\Request\Transfer\ToggleAlternativeSpeedLimitsRequest;
use PhpQbittorrent\Request\Transfer\GetGlobalDownloadLimitRequest;
use PhpQbittorrent\Request\Transfer\SetGlobalDownloadLimitRequest;
use PhpQbittorrent\Request\Transfer\GetGlobalUploadLimitRequest;
use PhpQbittorrent\Request\Transfer\SetGlobalUploadLimitRequest;
use PhpQbittorrent\Request\Transfer\BanPeersRequest;

use PhpQbittorrent\Request\Torrent\GetTorrentsRequest;
use PhpQbittorrent\Request\Torrent\GetTorrentInfoRequest;
use PhpQbittorrent\Request\Torrent\AddTorrentRequest;
use PhpQbittorrent\Request\Torrent\DeleteTorrentsRequest;
use PhpQbittorrent\Request\Torrent\PauseTorrentsRequest;
use PhpQbittorrent\Request\Torrent\ResumeTorrentsRequest;
use PhpQbittorrent\Request\Torrent\RecheckTorrentsRequest;

use PhpQbittorrent\Request\RSS\GetAllItemsRequest;
use PhpQbittorrent\Request\RSS\MarkAsReadRequest;
use PhpQbittorrent\Request\RSS\RefreshItemRequest;

use PhpQbittorrent\Request\Search\StartSearchRequest;
use PhpQbittorrent\Request\Search\StopSearchRequest;
use PhpQbittorrent\Request\Search\GetSearchStatusRequest;
use PhpQbittorrent\Request\Search\GetSearchResultsRequest;
use PhpQbittorrent\Request\Search\DeleteSearchRequest;

/**
 * 请求工厂类
 *
 * 提供统一的请求对象创建接口
 */
class RequestFactory
{
    /**
     * 创建应用相关请求
     */

    public static function createGetVersionRequest(): GetVersionRequest
    {
        return GetVersionRequest::create();
    }

    public static function createGetWebApiVersionRequest(): GetWebApiVersionRequest
    {
        return GetWebApiVersionRequest::create();
    }

    public static function createGetBuildInfoRequest(): GetBuildInfoRequest
    {
        return GetBuildInfoRequest::create();
    }

    public static function createGetPreferencesRequest(): GetPreferencesRequest
    {
        return GetPreferencesRequest::create();
    }

    public static function createSetPreferencesRequest(array $preferences): SetPreferencesRequest
    {
        return SetPreferencesRequest::create($preferences);
    }

    public static function createGetDefaultSavePathRequest(): GetDefaultSavePathRequest
    {
        return GetDefaultSavePathRequest::create();
    }

    /**
     * 创建传输相关请求
     */

    public static function createGetGlobalTransferInfoRequest(): GetGlobalTransferInfoRequest
    {
        return GetGlobalTransferInfoRequest::create();
    }

    public static function createGetAlternativeSpeedLimitsStateRequest(): GetAlternativeSpeedLimitsStateRequest
    {
        return GetAlternativeSpeedLimitsStateRequest::create();
    }

    public static function createToggleAlternativeSpeedLimitsRequest(): ToggleAlternativeSpeedLimitsRequest
    {
        return ToggleAlternativeSpeedLimitsRequest::create();
    }

    public static function createGetGlobalDownloadLimitRequest(): GetGlobalDownloadLimitRequest
    {
        return GetGlobalDownloadLimitRequest::create();
    }

    public static function createSetGlobalDownloadLimitRequest(int $limit): SetGlobalDownloadLimitRequest
    {
        return SetGlobalDownloadLimitRequest::create($limit);
    }

    public static function createGetGlobalUploadLimitRequest(): GetGlobalUploadLimitRequest
    {
        return GetGlobalUploadLimitRequest::create();
    }

    public static function createSetGlobalUploadLimitRequest(int $limit): SetGlobalUploadLimitRequest
    {
        return SetGlobalUploadLimitRequest::create($limit);
    }

    public static function createBanPeersRequest(string $peers): BanPeersRequest
    {
        return BanPeersRequest::create($peers);
    }

    /**
     * 创建种子相关请求
     */

    public static function createGetTorrentsRequest(
        ?string $filter = null,
        ?string $category = null,
        ?string $tag = null,
        ?string $sort = null,
        ?bool $reverse = null,
        ?int $limit = null,
        ?int $offset = null,
        ?string $hashes = null
    ): GetTorrentsRequest {
        return GetTorrentsRequest::create($filter, $category, $tag, $sort, $reverse, $limit, $offset, $hashes);
    }

    public static function createGetTorrentInfoRequest(string $hash): GetTorrentInfoRequest
    {
        return GetTorrentInfoRequest::create($hash);
    }

    public static function createAddTorrentRequest(array $options = []): AddTorrentRequest
    {
        return AddTorrentRequest::create($options);
    }

    public static function createDeleteTorrentsRequest(string $hashes, bool $deleteFiles = false): DeleteTorrentsRequest
    {
        return DeleteTorrentsRequest::create($hashes, $deleteFiles);
    }

    public static function createPauseTorrentsRequest(string $hashes): PauseTorrentsRequest
    {
        return PauseTorrentsRequest::create($hashes);
    }

    public static function createResumeTorrentsRequest(string $hashes): ResumeTorrentsRequest
    {
        return ResumeTorrentsRequest::create($hashes);
    }

    public static function createRecheckTorrentsRequest(string $hashes): RecheckTorrentsRequest
    {
        return RecheckTorrentsRequest::create($hashes);
    }

    /**
     * 创建RSS相关请求
     */

    public static function createGetAllItemsRequest(?string $path = null, bool $withData = false): GetAllItemsRequest
    {
        return GetAllItemsRequest::create($path, $withData);
    }

    public static function createMarkAsReadRequest(string $itemPath, ?string $articleId = null): MarkAsReadRequest
    {
        return MarkAsReadRequest::create($itemPath, $articleId);
    }

    public static function createRefreshItemRequest(string $itemPath): RefreshItemRequest
    {
        return RefreshItemRequest::create($itemPath);
    }

    /**
     * 创建搜索相关请求
     */

    public static function createStartSearchRequest(
        string $pattern,
        array $plugins = [],
        string $category = 'all'
    ): StartSearchRequest {
        return StartSearchRequest::create($pattern, $plugins, $category);
    }

    public static function createStartSearchWithAllPluginsRequest(
        string $pattern,
        string $category = 'all'
    ): StartSearchRequest {
        return StartSearchRequest::createWithAllPlugins($pattern, $category);
    }

    public static function createStartSearchWithEnabledPluginsRequest(
        string $pattern,
        string $category = 'all'
    ): StartSearchRequest {
        return StartSearchRequest::createWithEnabledPlugins($pattern, $category);
    }

    public static function createStopSearchRequest(int $searchId): StopSearchRequest
    {
        return StopSearchRequest::create($searchId);
    }

    public static function createGetSearchStatusRequest(?int $searchId = null): GetSearchStatusRequest
    {
        if ($searchId === null) {
            return GetSearchStatusRequest::createForAll();
        } else {
            return GetSearchStatusRequest::createForSearch($searchId);
        }
    }

    public static function createGetSearchResultsRequest(
        int $searchId,
        ?int $limit = null,
        ?int $offset = null
    ): GetSearchResultsRequest {
        return GetSearchResultsRequest::create($searchId, $limit, $offset);
    }

    public static function createGetSearchResultsWithLimitRequest(
        int $searchId,
        int $limit,
        ?int $offset = null
    ): GetSearchResultsRequest {
        return GetSearchResultsRequest::createWithLimit($searchId, $limit, $offset);
    }

    public static function createGetSearchResultsWithPaginationRequest(
        int $searchId,
        int $page,
        int $pageSize
    ): GetSearchResultsRequest {
        return GetSearchResultsRequest::createWithPagination($searchId, $page, $pageSize);
    }

    public static function createGetSearchResultsWithNoLimitRequest(int $searchId): GetSearchResultsRequest
    {
        return GetSearchResultsRequest::createWithNoLimit($searchId);
    }

    public static function createDeleteSearchRequest(int $searchId): DeleteSearchRequest
    {
        return DeleteSearchRequest::create($searchId);
    }

    /**
     * 批量创建常用请求组合
     */

    /**
     * 创建基础信息请求组合
     *
     * @return array<string, object> 基础信息请求集合
     */
    public static function createBasicInfoRequests(): array
    {
        return [
            'version' => self::createGetVersionRequest(),
            'webapi_version' => self::createGetWebApiVersionRequest(),
            'build_info' => self::createGetBuildInfoRequest(),
            'transfer_info' => self::createGetGlobalTransferInfoRequest(),
            'preferences' => self::createGetPreferencesRequest(),
        ];
    }

    /**
     * 创建种子管理请求组合
     *
     * @param string $hashes 种子哈希列表
     * @return array<string, object> 种子管理请求集合
     */
    public static function createTorrentManagementRequests(string $hashes): array
    {
        return [
            'info' => self::createGetTorrentInfoRequest($hashes),
            'pause' => self::createPauseTorrentsRequest($hashes),
            'resume' => self::createResumeTorrentsRequest($hashes),
            'recheck' => self::createRecheckTorrentsRequest($hashes),
            'delete' => self::createDeleteTorrentsRequest($hashes),
        ];
    }

    /**
     * 创建速度控制请求组合
     *
     * @param int|null $downloadLimit 下载限制，null表示不设置
     * @param int|null $uploadLimit 上传限制，null表示不设置
     * @return array<string, object> 速度控制请求集合
     */
    public static function createSpeedControlRequests(?int $downloadLimit = null, ?int $uploadLimit = null): array
    {
        $requests = [
            'get_download_limit' => self::createGetGlobalDownloadLimitRequest(),
            'get_upload_limit' => self::createGetGlobalUploadLimitRequest(),
            'get_alt_speed_state' => self::createGetAlternativeSpeedLimitsStateRequest(),
            'toggle_alt_speed' => self::createToggleAlternativeSpeedLimitsRequest(),
        ];

        if ($downloadLimit !== null) {
            $requests['set_download_limit'] = self::createSetGlobalDownloadLimitRequest($downloadLimit);
        }

        if ($uploadLimit !== null) {
            $requests['set_upload_limit'] = self::createSetGlobalUploadLimitRequest($uploadLimit);
        }

        return $requests;
    }

    /**
     * 创建搜索完整流程请求组合
     *
     * @param string $pattern 搜索模式
     * @param array<string> $plugins 搜索插件
     * @param string $category 搜索分类
     * @param int|null $limit 结果限制
     * @return array<string, object> 搜索流程请求集合
     */
    public static function createSearchFlowRequests(
        string $pattern,
        array $plugins = [],
        string $category = 'all',
        ?int $limit = null
    ): array {
        $requests = [
            'start_search' => self::createStartSearchRequest($pattern, $plugins, $category),
            'get_status' => self::createGetSearchStatusRequest(), // 将在搜索开始后设置searchId
        ];

        if ($limit !== null) {
            $requests['get_results'] = self::createGetSearchResultsWithLimitRequest(0, $limit); // searchId将在搜索开始后设置
        } else {
            $requests['get_results'] = self::createGetSearchResultsRequest(0); // searchId将在搜索开始后设置
        }

        $requests['delete_search'] = self::createDeleteSearchRequest(0); // searchId将在搜索开始后设置

        return $requests;
    }

    /**
     * 根据类型创建请求
     *
     * @param string $type 请求类型
     * @param array<string, mixed> $parameters 参数
     * @return object 请求对象
     * @throws \InvalidArgumentException 无效参数异常
     */
    public static function createByType(string $type, array $parameters = []): object
    {
        return match ($type) {
            // Application
            'get_version' => self::createGetVersionRequest(),
            'get_webapi_version' => self::createGetWebApiVersionRequest(),
            'get_build_info' => self::createGetBuildInfoRequest(),
            'get_preferences' => self::createGetPreferencesRequest(),
            'set_preferences' => self::createSetPreferencesRequest($parameters['preferences'] ?? []),
            'get_default_save_path' => self::createGetDefaultSavePathRequest(),

            // Transfer
            'get_global_transfer_info' => self::createGetGlobalTransferInfoRequest(),
            'get_alt_speed_limits_state' => self::createGetAlternativeSpeedLimitsStateRequest(),
            'toggle_alt_speed_limits' => self::createToggleAlternativeSpeedLimitsRequest(),
            'get_global_download_limit' => self::createGetGlobalDownloadLimitRequest(),
            'set_global_download_limit' => self::createSetGlobalDownloadLimitRequest($parameters['limit'] ?? 0),
            'get_global_upload_limit' => self::createGetGlobalUploadLimitRequest(),
            'set_global_upload_limit' => self::createSetGlobalUploadLimitRequest($parameters['limit'] ?? 0),
            'ban_peers' => self::createBanPeersRequest($parameters['peers'] ?? ''),

            // Torrent
            'get_torrents' => self::createGetTorrentsRequest(
                $parameters['filter'] ?? null,
                $parameters['category'] ?? null,
                $parameters['tag'] ?? null,
                $parameters['sort'] ?? null,
                $parameters['reverse'] ?? null,
                $parameters['limit'] ?? null,
                $parameters['offset'] ?? null,
                $parameters['hashes'] ?? null
            ),
            'get_torrent_info' => self::createGetTorrentInfoRequest($parameters['hash'] ?? ''),
            'add_torrent' => self::createAddTorrentRequest($parameters),
            'delete_torrents' => self::createDeleteTorrentsRequest(
                $parameters['hashes'] ?? '',
                $parameters['deleteFiles'] ?? false
            ),
            'pause_torrents' => self::createPauseTorrentsRequest($parameters['hashes'] ?? ''),
            'resume_torrents' => self::createResumeTorrentsRequest($parameters['hashes'] ?? ''),
            'recheck_torrents' => self::createRecheckTorrentsRequest($parameters['hashes'] ?? ''),

            // RSS
            'get_all_rss_items' => self::createGetAllItemsRequest(
                $parameters['path'] ?? null,
                $parameters['withData'] ?? false
            ),
            'mark_rss_as_read' => self::createMarkAsReadRequest(
                $parameters['itemPath'] ?? '',
                $parameters['articleId'] ?? null
            ),
            'refresh_rss_item' => self::createRefreshItemRequest($parameters['itemPath'] ?? ''),

            // Search
            'start_search' => self::createStartSearchRequest(
                $parameters['pattern'] ?? '',
                $parameters['plugins'] ?? [],
                $parameters['category'] ?? 'all'
            ),
            'stop_search' => self::createStopSearchRequest($parameters['searchId'] ?? 0),
            'get_search_status' => self::createGetSearchStatusRequest($parameters['searchId'] ?? null),
            'get_search_results' => self::createGetSearchResultsRequest(
                $parameters['searchId'] ?? 0,
                $parameters['limit'] ?? null,
                $parameters['offset'] ?? null
            ),
            'delete_search' => self::createDeleteSearchRequest($parameters['searchId'] ?? 0),

            default => throw new \InvalidArgumentException("未知的请求类型: {$type}"),
        };
    }
}