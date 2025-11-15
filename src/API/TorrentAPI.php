<?php
declare(strict_types=1);

namespace PhpQbittorrent\API;

use PhpQbittorrent\Contract\ApiInterface;
use PhpQbittorrent\Contract\TransportInterface;
use PhpQbittorrent\Request\Torrent\GetTorrentsRequest;
use PhpQbittorrent\Request\Torrent\AddTorrentRequest;
use PhpQbittorrent\Request\Torrent\AddTrackersRequest;
use PhpQbittorrent\Request\Torrent\DeleteTorrentsRequest;
use PhpQbittorrent\Request\Torrent\PauseTorrentsRequest;
use PhpQbittorrent\Request\Torrent\ResumeTorrentsRequest;
use PhpQbittorrent\Request\Torrent\GetTorrentPropertiesRequest;
use PhpQbittorrent\Request\Torrent\GetTorrentTrackersRequest;
use PhpQbittorrent\Request\Torrent\GetTorrentWebSeedsRequest;
use PhpQbittorrent\Request\Torrent\GetTorrentPieceStatesRequest;
use PhpQbittorrent\Response\Torrent\TorrentListResponse;
use PhpQbittorrent\Response\Torrent\TorrentPropertiesResponse;
use PhpQbittorrent\Response\Torrent\TorrentTrackersResponse;
use PhpQbittorrent\Response\Torrent\TorrentWebSeedsResponse;
use PhpQbittorrent\Response\Torrent\TorrentPieceStatesResponse;
use PhpQbittorrent\Exception\NetworkException;
use PhpQbittorrent\Exception\ApiRuntimeException;
use PhpQbittorrent\Exception\ValidationException;

/**
 * Torrent API 参数对象化
 *
 * 提供Torrent管理相关的API功能
 */
class TorrentAPI implements ApiInterface
{
    /** @var TransportInterface 传输层实例 */
    private TransportInterface $transport;

    /**
     * 构造函数
     *
     * @param TransportInterface $transport 传输层实例
     */
    public function __construct(TransportInterface $transport)
    {
        $this->transport = $transport;
    }

    /**
     * 获取API的基础路径
     *
     * @return string API基础路径
     */
    public function getBasePath(): string
    {
        return '/api/v2/torrents';
    }

    /**
     * 获取传输层实例
     *
     * @return TransportInterface 传输层实例
     */
    public function getTransport(): TransportInterface
    {
        return $this->transport;
    }

    /**
     * 设置传输层实例
     *
     * @param TransportInterface $transport 传输层实例
     * @return static 返回自身以支持链式调用
     */
    public function setTransport(TransportInterface $transport): static
    {
        $this->transport = $transport;
        return $this;
    }

    /**
     * 执行GET请求
     *
     * @param string $endpoint API端点
     * @param array<string, mixed> $parameters 请求参数
     * @param array<string, string> $headers 请求头
     * @return \PhpQbittorrent\Contract\ResponseInterface 响应对象
     */
    public function get(string $endpoint, array $parameters = [], array $headers = []): \PhpQbittorrent\Contract\ResponseInterface
    {
        $url = $this->getBasePath() . $endpoint;
        $transportResponse = $this->transport->get($url, $parameters, $headers);
        return $this->createGenericResponse($transportResponse);
    }

    /**
     * 执行POST请求
     *
     * @param string $endpoint API端点
     * @param array<string, mixed> $data 请求数据
     * @param array<string, string> $headers 请求头
     * @return \PhpQbittorrent\Contract\ResponseInterface 响应对象
     */
    public function post(string $endpoint, array $data = [], array $headers = []): \PhpQbittorrent\Contract\ResponseInterface
    {
        $url = $this->getBasePath() . $endpoint;
        $transportResponse = $this->transport->post($url, $data, $headers);
        return $this->createGenericResponse($transportResponse);
    }

    /**
     * 获取Torrent列表
     *
     * @param GetTorrentsRequest $request 获取Torrent列表请求
     * @return TorrentListResponse Torrent列表响应
     * @throws NetworkException 网络异常
     * @throws ValidationException 验证异常
     * @throws ApiRuntimeException API运行时异常
     */
    public function getTorrents(GetTorrentsRequest $request): TorrentListResponse
    {
        // 验证请求
        $validation = $request->validate();
        if (!$validation->isValid()) {
            throw new ValidationException(
                'GetTorrents request validation failed',
                'VALIDATION_ERROR',
                $validation->getErrors()
            );
        }

        try {
            // 发送请求
            $url = $this->getBasePath() . $request->getEndpoint();
            $transportResponse = $this->transport->get(
                $url,
                $request->toArray(),
                $request->getHeaders()
            );

            // 处理响应
            return $this->handleTorrentListResponse($transportResponse, $request);

        } catch (NetworkException $e) {
            throw new ApiRuntimeException(
                'Get torrents failed due to network error: ' . $e->getMessage(),
                'GET_TORRENTS_NETWORK_ERROR',
                ['original_error' => $e->getMessage()],
                $url ?? '',
                'GET',
                null,
                ['request_summary' => $request->getSummary()],
                $e
            );
        }
    }

    /**
     * 获取Torrent列表（别名方法）
     *
     * @return TorrentListResponse Torrent列表响应
     * @throws NetworkException 网络异常
     * @throws ValidationException 验证异常
     * @throws ApiRuntimeException API运行时异常
     */
    public function getTorrentList(): TorrentListResponse
    {
        return $this->getTorrents(\PhpQbittorrent\Request\Torrent\GetTorrentsRequest::create());
    }

    /**
     * 获取Torrent统计信息（别名方法）
     *
     * @return array<string, int> Torrent统计信息
     * @throws NetworkException 网络异常
     * @throws ValidationException 验证异常
     * @throws ApiRuntimeException API运行时异常
     */
    public function getTorrentStats(): array
    {
        try {
            $torrentListResponse = $this->getTorrentList();
            $torrents = $torrentListResponse->getTorrents();

            $stats = [
                'total' => count($torrents),
                'downloading' => 0,
                'seeding' => 0,
                'completed' => 0,
                'paused' => 0,
                'error' => 0,
                'inactive' => 0,
            ];

            foreach ($torrents as $torrent) {
                $state = $torrent->getState() ?? '';
                switch ($state) {
                    case 'downloading':
                    case 'metaDL':
                    case 'forcedDL':
                        $stats['downloading']++;
                        break;
                    case 'uploading':
                    case 'forcedUP':
                    case 'stalledUP':
                        $stats['seeding']++;
                        break;
                    case 'pausedUP':
                    case 'pausedDL':
                        $stats['paused']++;
                        break;
                    case 'error':
                    case 'missingFiles':
                        $stats['error']++;
                        break;
                    case 'stalledDL':
                        $stats['inactive']++;
                        break;
                    default:
                        if ($torrent->getProgress() >= 1.0) {
                            $stats['completed']++;
                        } else {
                            $stats['inactive']++;
                        }
                        break;
                }
            }

            return $stats;
        } catch (\Exception $e) {
            // 如果获取失败，返回空的统计数组
            return [
                'total' => 0,
                'downloading' => 0,
                'seeding' => 0,
                'completed' => 0,
                'paused' => 0,
                'error' => 0,
                'inactive' => 0,
            ];
        }
    }

    /**
     * 添加Torrent
     *
     * @param AddTorrentRequest $request 添加Torrent请求
     * @return \PhpQbittorrent\Contract\ResponseInterface 添加响应
     * @throws NetworkException 网络异常
     * @throws ValidationException 验证异常
     * @throws ApiRuntimeException API运行时异常
     */
    public function addTorrents(AddTorrentRequest $request): \PhpQbittorrent\Contract\ResponseInterface
    {
        // 验证请求
        $validation = $request->validate();
        if (!$validation->isValid()) {
            throw new ValidationException(
                'AddTorrents request validation failed',
                'VALIDATION_ERROR',
                $validation->getErrors()
            );
        }

        try {
            $url = $this->getBasePath() . $request->getEndpoint();
            $requestData = $request->toArray();
            $fileFields = $request->getFileFields();

            // 如果有文件字段，使用multipart请求
            if (!empty($fileFields)) {
                $transportResponse = $this->transport->post(
                    $url,
                    $requestData,
                    $request->getHeaders(),
                    $fileFields
                );
            } else {
                $transportResponse = $this->transport->post(
                    $url,
                    $requestData,
                    $request->getHeaders()
                );
            }

            // 处理响应
            return $this->handleAddTorrentResponse($transportResponse, $request);

        } catch (NetworkException $e) {
            throw new ApiRuntimeException(
                'Add torrents failed due to network error: ' . $e->getMessage(),
                'ADD_TORRENTS_NETWORK_ERROR',
                ['original_error' => $e->getMessage()],
                $url ?? '',
                'POST',
                null,
                ['request_summary' => $request->getSummary()],
                $e
            );
        }
    }

    /**
     * 删除Torrent
     *
     * @param DeleteTorrentsRequest $request 删除Torrent请求
     * @return \PhpQbittorrent\Contract\ResponseInterface 删除响应
     * @throws NetworkException 网络异常
     * @throws ValidationException 验证异常
     * @throws ApiRuntimeException API运行时异常
     */
    public function deleteTorrents(DeleteTorrentsRequest $request): \PhpQbittorrent\Contract\ResponseInterface
    {
        // 验证请求
        $validation = $request->validate();
        if (!$validation->isValid()) {
            throw new ValidationException(
                'DeleteTorrents request validation failed',
                'VALIDATION_ERROR',
                $validation->getErrors()
            );
        }

        try {
            $url = $this->getBasePath() . $request->getEndpoint();
            $transportResponse = $this->transport->post(
                $url,
                $request->toArray(),
                $request->getHeaders()
            );

            // 处理响应
            return $this->handleDeleteTorrentResponse($transportResponse, $request);

        } catch (NetworkException $e) {
            throw new ApiRuntimeException(
                'Delete torrents failed due to network error: ' . $e->getMessage(),
                'DELETE_TORRENTS_NETWORK_ERROR',
                ['original_error' => $e->getMessage()],
                $url ?? '',
                'POST',
                null,
                ['request_summary' => $request->getSummary()],
                $e
            );
        }
    }

    /**
     * 暂停Torrent
     *
     * @param PauseTorrentsRequest $request 暂停Torrent请求
     * @return \PhpQbittorrent\Contract\ResponseInterface 暂停响应
     * @throws NetworkException 网络异常
     * @throws ValidationException 验证异常
     * @throws ApiRuntimeException API运行时异常
     */
    public function pauseTorrents(PauseTorrentsRequest $request): \PhpQbittorrent\Contract\ResponseInterface
    {
        // 验证请求
        $validation = $request->validate();
        if (!$validation->isValid()) {
            throw new ValidationException(
                'PauseTorrents request validation failed',
                'VALIDATION_ERROR',
                $validation->getErrors()
            );
        }

        try {
            $url = $this->getBasePath() . $request->getEndpoint();
            $transportResponse = $this->transport->post(
                $url,
                $request->toArray(),
                $request->getHeaders()
            );

            // 处理响应
            return $this->handlePauseTorrentResponse($transportResponse, $request);

        } catch (NetworkException $e) {
            throw new ApiRuntimeException(
                'Pause torrents failed due to network error: ' . $e->getMessage(),
                'PAUSE_TORRENTS_NETWORK_ERROR',
                ['original_error' => $e->getMessage()],
                $url ?? '',
                'POST',
                null,
                ['request_summary' => $request->getSummary()],
                $e
            );
        }
    }

    /**
     * 恢复Torrent
     *
     * @param ResumeTorrentsRequest $request 恢复Torrent请求
     * @return \PhpQbittorrent\Contract\ResponseInterface 恢复响应
     * @throws NetworkException 网络异常
     * @throws ValidationException 验证异常
     * @throws ApiRuntimeException API运行时异常
     */
    public function resumeTorrents(ResumeTorrentsRequest $request): \PhpQbittorrent\Contract\ResponseInterface
    {
        // 验证请求
        $validation = $request->validate();
        if (!$validation->isValid()) {
            throw new ValidationException(
                'ResumeTorrents request validation failed',
                'VALIDATION_ERROR',
                $validation->getErrors()
            );
        }

        try {
            $url = $this->getBasePath() . $request->getEndpoint();
            $transportResponse = $this->transport->post(
                $url,
                $request->toArray(),
                $request->getHeaders()
            );

            // 处理响应
            return $this->handleResumeTorrentResponse($transportResponse, $request);

        } catch (NetworkException $e) {
            throw new ApiRuntimeException(
                'Resume torrents failed due to network error: ' . $e->getMessage(),
                'RESUME_TORRENTS_NETWORK_ERROR',
                ['original_error' => $e->getMessage()],
                $url ?? '',
                'POST',
                null,
                ['request_summary' => $request->getSummary()],
                $e
            );
        }
    }

    /**
     * 处理Torrent列表响应
     *
     * @param \PhpQbittorrent\Contract\TransportResponse $transportResponse 传输响应
     * @param GetTorrentsRequest $request 请求对象
     * @return TorrentListResponse Torrent列表响应
     */
    private function handleTorrentListResponse(
        \PhpQbittorrent\Contract\TransportResponse $transportResponse,
        GetTorrentsRequest $request
    ): TorrentListResponse {
        $statusCode = $transportResponse->getStatusCode();
        $headers = $transportResponse->getHeaders();
        $rawResponse = $transportResponse->getBody();

        if ($statusCode === 200) {
            try {
                $torrentsData = $transportResponse->getJson() ?? [];
                return TorrentListResponse::fromApiResponse(
                    $torrentsData,
                    $request->toArray()
                );
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
                ["获取Torrent列表失败，状态码: {$statusCode}"],
                $headers,
                $statusCode,
                $rawResponse
            );
        }
    }

    /**
     * 处理添加Torrent响应
     *
     * @param \PhpQbittorrent\Contract\TransportResponse $transportResponse 传输响应
     * @param AddTorrentRequest $request 请求对象
     * @return \PhpQbittorrent\Contract\ResponseInterface 添加响应
     */
    private function handleAddTorrentResponse(
        \PhpQbittorrent\Contract\TransportResponse $transportResponse,
        AddTorrentRequest $request
    ): \PhpQbittorrent\Contract\ResponseInterface {
        $statusCode = $transportResponse->getStatusCode();
        $headers = $transportResponse->getHeaders();
        $rawResponse = $transportResponse->getBody();

        if ($statusCode === 200) {
            return $this->createGenericResponse($transportResponse);
        } elseif ($statusCode === 415) {
            return $this->createGenericResponse(
                $transportResponse,
                ['error' => 'Torrent文件无效']
            );
        } else {
            return $this->createGenericResponse(
                $transportResponse,
                ['error' => "添加Torrent失败，状态码: {$statusCode}"]
            );
        }
    }

    /**
     * 处理删除Torrent响应
     *
     * @param \PhpQbittorrent\Contract\TransportResponse $transportResponse 传输响应
     * @param DeleteTorrentsRequest $request 请求对象
     * @return \PhpQbittorrent\Contract\ResponseInterface 删除响应
     */
    private function handleDeleteTorrentResponse(
        \PhpQbittorrent\Contract\TransportResponse $transportResponse,
        DeleteTorrentsRequest $request
    ): \PhpQbittorrent\Contract\ResponseInterface {
        $statusCode = $transportResponse->getStatusCode();

        if ($statusCode === 200) {
            return $this->createGenericResponse($transportResponse);
        } else {
            return $this->createGenericResponse(
                $transportResponse,
                ['error' => "删除Torrent失败，状态码: {$statusCode}"]
            );
        }
    }

    /**
     * 处理暂停Torrent响应
     *
     * @param \PhpQbittorrent\Contract\TransportResponse $transportResponse 传输响应
     * @param PauseTorrentsRequest $request 请求对象
     * @return \PhpQbittorrent\Contract\ResponseInterface 暂停响应
     */
    private function handlePauseTorrentResponse(
        \PhpQbittorrent\Contract\TransportResponse $transportResponse,
        PauseTorrentsRequest $request
    ): \PhpQbittorrent\Contract\ResponseInterface {
        $statusCode = $transportResponse->getStatusCode();

        if ($statusCode === 200) {
            return $this->createGenericResponse($transportResponse);
        } else {
            return $this->createGenericResponse(
                $transportResponse,
                ['error' => "暂停Torrent失败，状态码: {$statusCode}"]
            );
        }
    }

    /**
     * 处理恢复Torrent响应
     *
     * @param \PhpQbittorrent\Contract\TransportResponse $transportResponse 传输响应
     * @param ResumeTorrentsRequest $request 请求对象
     * @return \PhpQbittorrent\Contract\ResponseInterface 恢复响应
     */
    private function handleResumeTorrentResponse(
        \PhpQbittorrent\Contract\TransportResponse $transportResponse,
        ResumeTorrentsRequest $request
    ): \PhpQbittorrent\Contract\ResponseInterface {
        $statusCode = $transportResponse->getStatusCode();

        if ($statusCode === 200) {
            return $this->createGenericResponse($transportResponse);
        } else {
            return $this->createGenericResponse(
                $transportResponse,
                ['error' => "恢复Torrent失败，状态码: {$statusCode}"]
            );
        }
    }

    /**
     * 创建通用响应对象
     *
     * @param \PhpQbittorrent\Contract\TransportResponse $transportResponse 传输响应
     * @param array<string, mixed> $additionalData 额外数据
     * @return \PhpQbittorrent\Contract\ResponseInterface 响应对象
     */
    private function createGenericResponse(
        \PhpQbittorrent\Contract\TransportResponse $transportResponse,
        array $additionalData = []
    ): \PhpQbittorrent\Contract\ResponseInterface {
        // 这里可以创建一个通用的响应对象
        // 为了简化，我们创建一个简单的响应数组
        return new class($transportResponse, $additionalData) implements \PhpQbittorrent\Contract\ResponseInterface {
            private \PhpQbittorrent\Contract\TransportResponse $response;
            private array $data;
            private array $additionalData;

            public function __construct(
                \PhpQbittorrent\Contract\TransportResponse $response,
                array $additionalData = []
            ) {
                $this->response = $response;
                $this->data = $response->getJson() ?? [];
                $this->additionalData = $additionalData;
            }

            public static function fromArray(array $data): static {
                return new self(new class($data) implements \PhpQbittorrent\Contract\TransportResponse {
                    private array $data;
                    public function __construct(array $data) { $this->data = $data; }
                    public function getStatusCode(): int { return $this->data['status_code'] ?? 200; }
                    public function getHeaders(): array { return $this->data['headers'] ?? []; }
                    public function getBody(): string { return $this->data['body'] ?? ''; }
                    public function getJson(): ?array { return $this->data['json'] ?? null; }
                    public function isSuccess(int ...$acceptableCodes): bool { return true; }
                    public function isJson(): bool { return true; }
                    public function getHeader(string $name): ?string { return null; }
                }, $additionalData);
            }

            public function isSuccess(): bool { return $this->response->isSuccess(); }
            public function getErrors(): array { return $this->additionalData['errors'] ?? []; }
            public function getData(): mixed { return array_merge($this->data, $this->additionalData); }
            public function getStatusCode(): int { return $this->response->getStatusCode(); }
            public function getHeaders(): array { return $this->response->getHeaders(); }
            public function getRawResponse(): string { return $this->response->getBody(); }
            public function toArray(): array { return array_merge($this->data, $this->additionalData); }
            public function jsonSerialize(): array { return $this->toArray(); }
        };
    }

    /**
     * 重新校验Torrent
     *
     * @param string $hashes 种子哈希列表，用|分隔
     * @return \PhpQbittorrent\Contract\ResponseInterface 校验响应
     * @throws NetworkException 网络异常
     * @throws ApiRuntimeException API运行时异常
     */
    public function recheckTorrents(string $hashes): \PhpQbittorrent\Contract\ResponseInterface
    {
        try {
            $url = $this->getBasePath() . '/recheck';
            $transportResponse = $this->transport->post(
                $url,
                ['hashes' => $hashes],
                $this->getDefaultHeaders()
            );

            return $this->createGenericResponse($transportResponse);

        } catch (NetworkException $e) {
            throw new ApiRuntimeException(
                'Recheck torrents failed due to network error: ' . $e->getMessage(),
                'RECHECK_TORRENTS_NETWORK_ERROR',
                ['original_error' => $e->getMessage()],
                $url,
                'POST',
                $transportResponse->getStatusCode() ?? null,
                ['hashes' => $hashes],
                $e
            );
        }
    }

    /**
     * 设置Torrent位置
     *
     * @param string $hashes 种子哈希列表，用|分隔
     * @param string $location 新位置
     * @return \PhpQbittorrent\Contract\ResponseInterface 设置响应
     * @throws NetworkException 网络异常
     * @throws ApiRuntimeException API运行时异常
     */
    public function setTorrentLocation(string $hashes, string $location): \PhpQbittorrent\Contract\ResponseInterface
    {
        try {
            $url = $this->getBasePath() . '/setLocation';
            $transportResponse = $this->transport->post(
                $url,
                [
                    'hashes' => $hashes,
                    'location' => $location
                ],
                $this->getDefaultHeaders()
            );

            return $this->createGenericResponse($transportResponse);

        } catch (NetworkException $e) {
            throw new ApiRuntimeException(
                'Set torrent location failed due to network error: ' . $e->getMessage(),
                'SET_TORRENT_LOCATION_NETWORK_ERROR',
                ['original_error' => $e->getMessage()],
                $url,
                'POST',
                $transportResponse->getStatusCode() ?? null,
                ['hashes' => $hashes, 'location' => $location],
                $e
            );
        }
    }

    /**
     * 设置Torrent分类
     *
     * @param string $hashes 种子哈希列表，用|分隔
     * @param string $category 分类名称
     * @return \PhpQbittorrent\Contract\ResponseInterface 设置响应
     * @throws NetworkException 网络异常
     * @throws ApiRuntimeException API运行时异常
     */
    public function setTorrentCategory(string $hashes, string $category): \PhpQbittorrent\Contract\ResponseInterface
    {
        try {
            $url = $this->getBasePath() . '/setCategory';
            $transportResponse = $this->transport->post(
                $url,
                [
                    'hashes' => $hashes,
                    'category' => $category
                ],
                $this->getDefaultHeaders()
            );

            return $this->createGenericResponse($transportResponse);

        } catch (NetworkException $e) {
            throw new ApiRuntimeException(
                'Set torrent category failed due to network error: ' . $e->getMessage(),
                'SET_TORRENT_CATEGORY_NETWORK_ERROR',
                ['original_error' => $e->getMessage()],
                $url,
                'POST',
                $transportResponse->getStatusCode() ?? null,
                ['hashes' => $hashes, 'category' => $category],
                $e
            );
        }
    }

    /**
     * 添加Torrent标签
     *
     * @param string $hashes 种子哈希列表，用|分隔
     * @param string $tags 标签列表，用逗号分隔
     * @return \PhpQbittorrent\Contract\ResponseInterface 添加响应
     * @throws NetworkException 网络异常
     * @throws ApiRuntimeException API运行时异常
     */
    public function addTorrentTags(string $hashes, string $tags): \PhpQbittorrent\Contract\ResponseInterface
    {
        try {
            $url = $this->getBasePath() . '/addTags';
            $transportResponse = $this->transport->post(
                $url,
                [
                    'hashes' => $hashes,
                    'tags' => $tags
                ],
                $this->getDefaultHeaders()
            );

            return $this->createGenericResponse($transportResponse);

        } catch (NetworkException $e) {
            throw new ApiRuntimeException(
                'Add torrent tags failed due to network error: ' . $e->getMessage(),
                'ADD_TORRENT_TAGS_NETWORK_ERROR',
                ['original_error' => $e->getMessage()],
                $url,
                'POST',
                $transportResponse->getStatusCode() ?? null,
                ['hashes' => $hashes, 'tags' => $tags],
                $e
            );
        }
    }

    /**
     * 移除Torrent标签
     *
     * @param string $hashes 种子哈希列表，用|分隔
     * @param string $tags 标签列表，用逗号分隔
     * @return \PhpQbittorrent\Contract\ResponseInterface 移除响应
     * @throws NetworkException 网络异常
     * @throws ApiRuntimeException API运行时异常
     */
    public function removeTorrentTags(string $hashes, string $tags): \PhpQbittorrent\Contract\ResponseInterface
    {
        try {
            $url = $this->getBasePath() . '/removeTags';
            $transportResponse = $this->transport->post(
                $url,
                [
                    'hashes' => $hashes,
                    'tags' => $tags
                ],
                $this->getDefaultHeaders()
            );

            return $this->createGenericResponse($transportResponse);

        } catch (NetworkException $e) {
            throw new ApiRuntimeException(
                'Remove torrent tags failed due to network error: ' . $e->getMessage(),
                'REMOVE_TORRENT_TAGS_NETWORK_ERROR',
                ['original_error' => $e->getMessage()],
                $url,
                'POST',
                $transportResponse->getStatusCode() ?? null,
                ['hashes' => $hashes, 'tags' => $tags],
                $e
            );
        }
    }

    /**
     * 设置强制启动
     *
     * @param string $hashes 种子哈希列表，用|分隔
     * @param bool $force 是否强制启动
     * @return \PhpQbittorrent\Contract\ResponseInterface 设置响应
     * @throws NetworkException 网络异常
     * @throws ApiRuntimeException API运行时异常
     */
    public function setForceStart(string $hashes, bool $force = true): \PhpQbittorrent\Contract\ResponseInterface
    {
        try {
            $url = $this->getBasePath() . '/setForceStart';
            $transportResponse = $this->transport->post(
                $url,
                [
                    'hashes' => $hashes,
                    'value' => $force ? 'true' : 'false'
                ],
                $this->getDefaultHeaders()
            );

            return $this->createGenericResponse($transportResponse);

        } catch (NetworkException $e) {
            throw new ApiRuntimeException(
                'Set force start failed due to network error: ' . $e->getMessage(),
                'SET_FORCE_START_NETWORK_ERROR',
                ['original_error' => $e->getMessage()],
                $url,
                'POST',
                $transportResponse->getStatusCode() ?? null,
                ['hashes' => $hashes, 'force' => $force],
                $e
            );
        }
    }

    /**
     * 切换顺序下载
     *
     * @param string $hashes 种子哈希列表，用|分隔
     * @return \PhpQbittorrent\Contract\ResponseInterface 切换响应
     * @throws NetworkException 网络异常
     * @throws ApiRuntimeException API运行时异常
     */
    public function toggleSequentialDownload(string $hashes): \PhpQbittorrent\Contract\ResponseInterface
    {
        try {
            $url = $this->getBasePath() . '/toggleSequentialDownload';
            $transportResponse = $this->transport->post(
                $url,
                ['hashes' => $hashes],
                $this->getDefaultHeaders()
            );

            return $this->createGenericResponse($transportResponse);

        } catch (NetworkException $e) {
            throw new ApiRuntimeException(
                'Toggle sequential download failed due to network error: ' . $e->getMessage(),
                'TOGGLE_SEQUENTIAL_DOWNLOAD_NETWORK_ERROR',
                ['original_error' => $e->getMessage()],
                $url,
                'POST',
                $transportResponse->getStatusCode() ?? null,
                ['hashes' => $hashes],
                $e
            );
        }
    }

    /**
     * 设置首尾Piece优先级
     *
     * @param string $hashes 种子哈希列表，用|分隔
     * @return \PhpQbittorrent\Contract\ResponseInterface 设置响应
     * @throws NetworkException 网络异常
     * @throws ApiRuntimeException API运行时异常
     */
    public function setFirstLastPiecePriority(string $hashes): \PhpQbittorrent\Contract\ResponseInterface
    {
        try {
            $url = $this->getBasePath() . '/setFirstLastPiecePrio';
            $transportResponse = $this->transport->post(
                $url,
                ['hashes' => $hashes],
                $this->getDefaultHeaders()
            );

            return $this->createGenericResponse($transportResponse);

        } catch (NetworkException $e) {
            throw new ApiRuntimeException(
                'Set first last piece priority failed due to network error: ' . $e->getMessage(),
                'SET_FIRST_LAST_PIECE_PRIORITY_NETWORK_ERROR',
                ['original_error' => $e->getMessage()],
                $url,
                'POST',
                $transportResponse->getStatusCode() ?? null,
                ['hashes' => $hashes],
                $e
            );
        }
    }

    /**
     * 获取种子属性（使用类型化请求）
     *
     * @param GetTorrentPropertiesRequest $request 获取Torrent属性请求
     * @return TorrentPropertiesResponse Torrent属性响应
     * @throws NetworkException 网络异常
     * @throws ValidationException 验证异常
     * @throws ApiRuntimeException API运行时异常
     */
    public function getTorrentProperties(GetTorrentPropertiesRequest $request): TorrentPropertiesResponse
    {
        // 验证请求
        $validation = $request->validate();
        if (!$validation->isValid()) {
            throw new ValidationException(
                'GetTorrentProperties request validation failed',
                'VALIDATION_ERROR',
                $validation->getErrors()
            );
        }

        try {
            // 发送请求
            $url = $this->getBasePath() . $request->getEndpoint();
            $transportResponse = $this->transport->get(
                $url,
                $request->toArray(),
                $request->getHeaders()
            );

            // 处理响应
            return $this->handleTorrentPropertiesResponse($transportResponse, $request);

        } catch (NetworkException $e) {
            throw new ApiRuntimeException(
                'Get torrent properties failed due to network error: ' . $e->getMessage(),
                'GET_TORRENT_PROPERTIES_NETWORK_ERROR',
                ['original_error' => $e->getMessage()],
                $url ?? '',
                'GET',
                null,
                ['request_summary' => $request->getSummary()],
                $e
            );
        }
    }

    /**
     * 获取种子属性（兼容方法）
     *
     * @param string $hash 种子哈希
     * @return array<string, mixed> 种子属性
     * @throws NetworkException 网络异常
     * @throws ValidationException 验证异常
     * @throws ApiRuntimeException API运行时异常
     */
    public function getTorrentPropertiesLegacy(string $hash): array
    {
        try {
            $url = $this->getBasePath() . '/properties';
            $transportResponse = $this->transport->get(
                $url,
                ['hash' => $hash],
                $this->getDefaultHeaders()
            );

            if ($transportResponse->getStatusCode() === 200) {
                return $transportResponse->getJson() ?? [];
            } else {
                return [];
            }

        } catch (NetworkException $e) {
            throw new ApiRuntimeException(
                'Get torrent properties failed due to network error: ' . $e->getMessage(),
                'GET_TORRENT_PROPERTIES_NETWORK_ERROR',
                ['original_error' => $e->getMessage()],
                $url,
                'GET',
                $transportResponse->getStatusCode() ?? null,
                ['hash' => $hash],
                $e
            );
        }
    }

    /**
     * 处理Torrent Web种子响应
     *
     * @param \PhpQbittorrent\Contract\TransportResponse $transportResponse 传输响应
     * @param GetTorrentWebSeedsRequest $request 请求对象
     * @return TorrentWebSeedsResponse Torrent Web种子响应
     */
    private function handleTorrentWebSeedsResponse(
        \PhpQbittorrent\Contract\TransportResponse $transportResponse,
        GetTorrentWebSeedsRequest $request
    ): TorrentWebSeedsResponse {
        $statusCode = $transportResponse->getStatusCode();
        $headers = $transportResponse->getHeaders();
        $rawResponse = $transportResponse->getBody();

        if ($statusCode === 200) {
            try {
                $webSeedsData = $transportResponse->getJson() ?? [];
                return TorrentWebSeedsResponse::fromApiResponse(
                    [
                        'data' => $webSeedsData,
                        'headers' => $headers,
                        'status_code' => $statusCode,
                        'body' => $rawResponse
                    ],
                    $request->getHash()
                );
            } catch (\Exception $e) {
                return TorrentWebSeedsResponse::error(
                    $headers,
                    $statusCode,
                    $rawResponse,
                    '响应解析失败: ' . $e->getMessage()
                );
            }
        } elseif ($statusCode === 404) {
            return TorrentWebSeedsResponse::error(
                $headers,
                $statusCode,
                $rawResponse,
                'Torrent不存在或哈希无效'
            );
        } else {
            return TorrentWebSeedsResponse::error(
                $headers,
                $statusCode,
                $rawResponse,
                "获取Torrent Web种子失败，状态码: {$statusCode}"
            );
        }
    }

    /**
     * 处理Torrent Trackers响应
     *
     * @param \PhpQbittorrent\Contract\TransportResponse $transportResponse 传输响应
     * @param GetTorrentTrackersRequest $request 请求对象
     * @return TorrentTrackersResponse Torrent Trackers响应
     */
    private function handleTorrentTrackersResponse(
        \PhpQbittorrent\Contract\TransportResponse $transportResponse,
        GetTorrentTrackersRequest $request
    ): TorrentTrackersResponse {
        $statusCode = $transportResponse->getStatusCode();
        $headers = $transportResponse->getHeaders();
        $rawResponse = $transportResponse->getBody();

        if ($statusCode === 200) {
            try {
                $trackersData = $transportResponse->getJson() ?? [];
                return TorrentTrackersResponse::fromApiData(
                    $trackersData,
                    $headers,
                    $statusCode,
                    $rawResponse
                );
            } catch (\Exception $e) {
                return TorrentTrackersResponse::failure(
                    ['响应解析失败: ' . $e->getMessage()],
                    $headers,
                    $statusCode,
                    $rawResponse
                );
            }
        } elseif ($statusCode === 404) {
            return TorrentTrackersResponse::failure(
                ['Torrent不存在或哈希无效'],
                $headers,
                $statusCode,
                $rawResponse
            );
        } else {
            return TorrentTrackersResponse::failure(
                ["获取Torrent Trackers失败，状态码: {$statusCode}"],
                $headers,
                $statusCode,
                $rawResponse
            );
        }
    }

    /**
     * 处理Torrent属性响应
     *
     * @param \PhpQbittorrent\Contract\TransportResponse $transportResponse 传输响应
     * @param GetTorrentPropertiesRequest $request 请求对象
     * @return TorrentPropertiesResponse Torrent属性响应
     */
    private function handleTorrentPropertiesResponse(
        \PhpQbittorrent\Contract\TransportResponse $transportResponse,
        GetTorrentPropertiesRequest $request
    ): TorrentPropertiesResponse {
        $statusCode = $transportResponse->getStatusCode();
        $headers = $transportResponse->getHeaders();
        $rawResponse = $transportResponse->getBody();

        if ($statusCode === 200) {
            try {
                $propertiesData = $transportResponse->getJson() ?? [];
                return TorrentPropertiesResponse::fromApiData(
                    $propertiesData,
                    $headers,
                    $statusCode,
                    $rawResponse
                );
            } catch (\Exception $e) {
                return TorrentPropertiesResponse::failure(
                    ['响应解析失败: ' . $e->getMessage()],
                    $headers,
                    $statusCode,
                    $rawResponse
                );
            }
        } elseif ($statusCode === 404) {
            return TorrentPropertiesResponse::failure(
                ['Torrent不存在或哈希无效'],
                $headers,
                $statusCode,
                $rawResponse
            );
        } else {
            return TorrentPropertiesResponse::failure(
                ["获取Torrent属性失败，状态码: {$statusCode}"],
                $headers,
                $statusCode,
                $rawResponse
            );
        }
    }

    /**
     * 获取种子文件列表
     *
     * @param string $hash 种子哈希
     * @param array<int>|null $indexes 文件索引列表（可选）
     * @return array<array<string, mixed>> 文件列表
     * @throws NetworkException 网络异常
     * @throws ApiRuntimeException API运行时异常
     */
    public function getTorrentFiles(string $hash, ?array $indexes = null): array
    {
        try {
            $url = $this->getBasePath() . '/files';
            $params = ['hash' => $hash];
            
            if ($indexes !== null) {
                $params['indexes'] = implode('|', $indexes);
            }

            $transportResponse = $this->transport->get(
                $url,
                $params,
                $this->getDefaultHeaders()
            );

            if ($transportResponse->getStatusCode() === 200) {
                return $transportResponse->getJson() ?? [];
            } else {
                return [];
            }

        } catch (NetworkException $e) {
            throw new ApiRuntimeException(
                'Get torrent files failed due to network error: ' . $e->getMessage(),
                'GET_TORRENT_FILES_NETWORK_ERROR',
                ['original_error' => $e->getMessage()],
                $url,
                'GET',
                $transportResponse->getStatusCode() ?? null,
                ['hash' => $hash, 'indexes' => $indexes],
                $e
            );
        }
    }

    /**
     * 设置文件优先级
     *
     * @param string $hash 种子哈希
     * @param array<string> $fileIndexes 文件索引列表
     * @param int $priority 优先级（0=不下载, 1=正常, 6=高, 7=最高）
     * @return \PhpQbittorrent\Contract\ResponseInterface 设置响应
     * @throws NetworkException 网络异常
     * @throws ApiRuntimeException API运行时异常
     */
    public function setFilePriority(string $hash, array $fileIndexes, int $priority): \PhpQbittorrent\Contract\ResponseInterface
    {
        try {
            $url = $this->getBasePath() . '/filePrio';
            $transportResponse = $this->transport->post(
                $url,
                [
                    'hash' => $hash,
                    'id' => implode('|', $fileIndexes),
                    'priority' => $priority
                ],
                $this->getDefaultHeaders()
            );

            return $this->createGenericResponse($transportResponse);

        } catch (NetworkException $e) {
            throw new ApiRuntimeException(
                'Set file priority failed due to network error: ' . $e->getMessage(),
                'SET_FILE_PRIORITY_NETWORK_ERROR',
                ['original_error' => $e->getMessage()],
                $url,
                'POST',
                $transportResponse->getStatusCode() ?? null,
                ['hash' => $hash, 'fileIndexes' => $fileIndexes, 'priority' => $priority],
                $e
            );
        }
    }

    /**
     * 获取所有分类
     *
     * @return array<string, array<string, mixed>> 分类列表
     * @throws NetworkException 网络异常
     * @throws ApiRuntimeException API运行时异常
     */
    public function getCategories(): array
    {
        try {
            $url = $this->getBasePath() . '/categories';
            $transportResponse = $this->transport->get(
                $url,
                [],
                $this->getDefaultHeaders()
            );

            if ($transportResponse->getStatusCode() === 200) {
                return $transportResponse->getJson() ?? [];
            } else {
                return [];
            }

        } catch (NetworkException $e) {
            throw new ApiRuntimeException(
                'Get categories failed due to network error: ' . $e->getMessage(),
                'GET_CATEGORIES_NETWORK_ERROR',
                ['original_error' => $e->getMessage()],
                $url,
                'GET',
                $transportResponse->getStatusCode() ?? null,
                [],
                $e
            );
        }
    }

    /**
     * 创建分类
     *
     * @param string $category 分类名称
     * @param string $savePath 保存路径
     * @return \PhpQbittorrent\Contract\ResponseInterface 创建响应
     * @throws NetworkException 网络异常
     * @throws ApiRuntimeException API运行时异常
     */
    public function createCategory(string $category, string $savePath): \PhpQbittorrent\Contract\ResponseInterface
    {
        try {
            $url = $this->getBasePath() . '/createCategory';
            $transportResponse = $this->transport->post(
                $url,
                [
                    'category' => $category,
                    'savePath' => $savePath
                ],
                $this->getDefaultHeaders()
            );

            return $this->createGenericResponse($transportResponse);

        } catch (NetworkException $e) {
            throw new ApiRuntimeException(
                'Create category failed due to network error: ' . $e->getMessage(),
                'CREATE_CATEGORY_NETWORK_ERROR',
                ['original_error' => $e->getMessage()],
                $url,
                'POST',
                $transportResponse->getStatusCode() ?? null,
                ['category' => $category, 'savePath' => $savePath],
                $e
            );
        }
    }

    /**
     * 编辑分类
     *
     * @param string $category 分类名称
     * @param string $savePath 新保存路径
     * @return \PhpQbittorrent\Contract\ResponseInterface 编辑响应
     * @throws NetworkException 网络异常
     * @throws ApiRuntimeException API运行时异常
     */
    public function editCategory(string $category, string $savePath): \PhpQbittorrent\Contract\ResponseInterface
    {
        try {
            $url = $this->getBasePath() . '/editCategory';
            $transportResponse = $this->transport->post(
                $url,
                [
                    'category' => $category,
                    'savePath' => $savePath
                ],
                $this->getDefaultHeaders()
            );

            return $this->createGenericResponse($transportResponse);

        } catch (NetworkException $e) {
            throw new ApiRuntimeException(
                'Edit category failed due to network error: ' . $e->getMessage(),
                'EDIT_CATEGORY_NETWORK_ERROR',
                ['original_error' => $e->getMessage()],
                $url,
                'POST',
                $transportResponse->getStatusCode() ?? null,
                ['category' => $category, 'savePath' => $savePath],
                $e
            );
        }
    }

    /**
     * 删除分类
     *
     * @param string $categories 分类名称（多个用换行符分隔）
     * @return \PhpQbittorrent\Contract\ResponseInterface 删除响应
     * @throws NetworkException 网络异常
     * @throws ApiRuntimeException API运行时异常
     */
    public function removeCategories(string $categories): \PhpQbittorrent\Contract\ResponseInterface
    {
        try {
            $url = $this->getBasePath() . '/removeCategories';
            $transportResponse = $this->transport->post(
                $url,
                ['categories' => $categories],
                $this->getDefaultHeaders()
            );

            return $this->createGenericResponse($transportResponse);

        } catch (NetworkException $e) {
            throw new ApiRuntimeException(
                'Remove categories failed due to network error: ' . $e->getMessage(),
                'REMOVE_CATEGORIES_NETWORK_ERROR',
                ['original_error' => $e->getMessage()],
                $url,
                'POST',
                $transportResponse->getStatusCode() ?? null,
                ['categories' => $categories],
                $e
            );
        }
    }

    /**
     * 获取所有标签
     *
     * @return array<string> 标签列表
     * @throws NetworkException 网络异常
     * @throws ApiRuntimeException API运行时异常
     */
    public function getTags(): array
    {
        try {
            $url = $this->getBasePath() . '/tags';
            $transportResponse = $this->transport->get(
                $url,
                [],
                $this->getDefaultHeaders()
            );

            if ($transportResponse->getStatusCode() === 200) {
                return $transportResponse->getJson() ?? [];
            } else {
                return [];
            }

        } catch (NetworkException $e) {
            throw new ApiRuntimeException(
                'Get tags failed due to network error: ' . $e->getMessage(),
                'GET_TAGS_NETWORK_ERROR',
                ['original_error' => $e->getMessage()],
                $url,
                'GET',
                $transportResponse->getStatusCode() ?? null,
                [],
                $e
            );
        }
    }

    /**
     * 创建标签
     *
     * @param array<string> $tags 标签列表
     * @return \PhpQbittorrent\Contract\ResponseInterface 创建响应
     * @throws NetworkException 网络异常
     * @throws ApiRuntimeException API运行时异常
     */
    public function createTags(array $tags): \PhpQbittorrent\Contract\ResponseInterface
    {
        try {
            $url = $this->getBasePath() . '/createTags';
            $transportResponse = $this->transport->post(
                $url,
                ['tags' => implode(',', $tags)],
                $this->getDefaultHeaders()
            );

            return $this->createGenericResponse($transportResponse);

        } catch (NetworkException $e) {
            throw new ApiRuntimeException(
                'Create tags failed due to network error: ' . $e->getMessage(),
                'CREATE_TAGS_NETWORK_ERROR',
                ['original_error' => $e->getMessage()],
                $url,
                'POST',
                $transportResponse->getStatusCode() ?? null,
                ['tags' => $tags],
                $e
            );
        }
    }

    /**
     * 删除标签
     *
     * @param array<string> $tags 标签列表
     * @return \PhpQbittorrent\Contract\ResponseInterface 删除响应
     * @throws NetworkException 网络异常
     * @throws ApiRuntimeException API运行时异常
     */
    public function deleteTags(array $tags): \PhpQbittorrent\Contract\ResponseInterface
    {
        try {
            $url = $this->getBasePath() . '/deleteTags';
            $transportResponse = $this->transport->post(
                $url,
                ['tags' => implode(',', $tags)],
                $this->getDefaultHeaders()
            );

            return $this->createGenericResponse($transportResponse);

        } catch (NetworkException $e) {
            throw new ApiRuntimeException(
                'Delete tags failed due to network error: ' . $e->getMessage(),
                'DELETE_TAGS_NETWORK_ERROR',
                ['original_error' => $e->getMessage()],
                $url,
                'POST',
                $transportResponse->getStatusCode() ?? null,
                ['tags' => $tags],
                $e
            );
        }
    }

    /**
     * 获取Torrent Piece状态
     *
     * @param GetTorrentPieceStatesRequest $request Piece状态请求
     * @return TorrentPieceStatesResponse Piece状态响应
     * @throws NetworkException 网络异常
     * @throws ValidationException 验证异常
     * @throws ApiRuntimeException API运行时异常
     */
    public function getTorrentPieceStates(GetTorrentPieceStatesRequest $request): TorrentPieceStatesResponse
    {
        // 验证请求
        $validation = $request->validate();
        if (!$validation->isValid()) {
            throw new ValidationException(
                'GetTorrentPieceStates request validation failed',
                'VALIDATION_ERROR',
                $validation->getErrors()
            );
        }

        try {
            // 发送请求
            $url = $this->getBasePath() . $request->getEndpoint();
            $transportResponse = $this->transport->get(
                $url,
                $request->toArray(),
                $request->getHeaders()
            );

            // 处理Piece状态响应
            return $this->handleTorrentPieceStatesResponse($transportResponse, $request);

        } catch (NetworkException $e) {
            throw new ApiRuntimeException(
                'Get torrent piece states failed due to network error: ' . $e->getMessage(),
                'GET_TORRENT_PIECE_STATES_NETWORK_ERROR',
                ['original_error' => $e->getMessage()],
                $url ?? '',
                'GET',
                null,
                ['request_summary' => $request->getSummary()],
                $e
            );
        }
    }

    /**
     * 处理Torrent Piece状态API响应
     *
     * @param \PhpQbittorrent\Contract\TransportResponseInterface $transportResponse 传输响应
     * @param GetTorrentPieceStatesRequest $request 原始请求
     * @return TorrentPieceStatesResponse Piece状态响应
     * @throws ApiRuntimeException API处理异常
     */
    private function handleTorrentPieceStatesResponse(
        \PhpQbittorrent\Contract\TransportResponseInterface $transportResponse,
        GetTorrentPieceStatesRequest $request
    ): TorrentPieceStatesResponse {
        $statusCode = $transportResponse->getStatusCode();
        $responseData = $transportResponse->getBody();

        // 检查HTTP状态码
        if ($statusCode === 404) {
            return TorrentPieceStatesResponse::error(
                $responseData,
                [],
                'Torrent not found'
            );
        }

        if ($statusCode < 200 || $statusCode >= 300) {
            throw new ApiRuntimeException(
                "Get torrent piece states failed with HTTP status: {$statusCode}",
                'HTTP_STATUS_ERROR',
                [
                    'status_code' => $statusCode,
                    'response_data' => $responseData,
                ],
                $this->getBasePath() . $request->getEndpoint(),
                'GET',
                null,
                ['request_summary' => $request->getSummary()]
            );
        }

        // 解析JSON响应
        $decodedResponse = json_decode($responseData, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ApiRuntimeException(
                'Failed to decode torrent piece states JSON response: ' . json_last_error_msg(),
                'JSON_DECODE_ERROR',
                [
                    'json_error' => json_last_error(),
                    'response_data' => $responseData,
                ],
                $this->getBasePath() . $request->getEndpoint(),
                'GET',
                null,
                ['request_summary' => $request->getSummary()]
            );
        }

        // 创建成功响应
        return TorrentPieceStatesResponse::fromApiResponse($decodedResponse, $request->getHash());
    }

    /**
     * 设置下载位置（别名方法）
     *
     * @param array<string> $hashes 种子哈希列表
     * @param string $location 新位置
     * @return \PhpQbittorrent\Contract\ResponseInterface 设置响应
     * @throws NetworkException 网络异常
     * @throws ApiRuntimeException API运行时异常
     */
    public function setDownloadLocation(array $hashes, string $location): \PhpQbittorrent\Contract\ResponseInterface
    {
        $hashesString = implode('|', $hashes);
        return $this->setTorrentLocation($hashesString, $location);
    }

    /**
     * 获取种子文件列表（类型化实现）
     *
     * @param GetTorrentFilesRequest $request 文件列表请求
     * @return TorrentFilesResponse 文件列表响应
     * @throws NetworkException 网络异常
     * @throws ValidationException 验证异常
     * @throws ApiRuntimeException API运行时异常
     */
    public function getTorrentFiles(GetTorrentFilesRequest $request): TorrentFilesResponse
    {
        // 验证请求
        $validation = $request->validate();
        if (!$validation->isValid()) {
            throw new ValidationException(
                'GetTorrentWebSeeds request validation failed',
                'VALIDATION_ERROR',
                $validation->getErrors()
            );
        }

        try {
            // 发送请求
            $url = $this->getBasePath() . $request->getEndpoint();
            $transportResponse = $this->transport->get(
                $url,
                $request->toArray(),
                $request->getHeaders()
            );

            // 处理Torrent文件列表响应
            return $this->handleTorrentFilesResponse($transportResponse, $request);

        } catch (NetworkException $e) {
            throw new ApiRuntimeException(
                'Get torrent webseeds failed due to network error: ' . $e->getMessage(),
                'GET_TORRENT_WEBSEEDS_NETWORK_ERROR',
                ['original_error' => $e->getMessage()],
                $url ?? '',
                'GET',
                null,
                ['request_summary' => $request->getSummary()],
                $e
            );
        }
    }

    /**
     * 获取Torrent Trackers
     *
     * @param GetTorrentTrackersRequest $request 获取Torrent Trackers请求
     * @return TorrentTrackersResponse Torrent Trackers响应
     * @throws NetworkException 网络异常
     * @throws ValidationException 验证异常
     * @throws ApiRuntimeException API运行时异常
     */
    public function getTorrentTrackers(GetTorrentTrackersRequest $request): TorrentTrackersResponse
    {
        // 验证请求
        $validation = $request->validate();
        if (!$validation->isValid()) {
            throw new ValidationException(
                'GetTorrentTrackers request validation failed',
                'VALIDATION_ERROR',
                $validation->getErrors()
            );
        }

        try {
            // 发送请求
            $url = $this->getBasePath() . $request->getEndpoint();
            $transportResponse = $this->transport->get(
                $url,
                $request->toArray(),
                $request->getHeaders()
            );

            // 处理响应
            return $this->handleTorrentTrackersResponse($transportResponse, $request);

        } catch (NetworkException $e) {
            throw new ApiRuntimeException(
                'Get torrent trackers failed due to network error: ' . $e->getMessage(),
                'GET_TORRENT_TRACKERS_NETWORK_ERROR',
                ['original_error' => $e->getMessage()],
                $url ?? '',
                'GET',
                null,
                ['request_summary' => $request->getSummary()],
                $e
            );
        }
    }

    /**
     * 添加Tracker到Torrent
     *
     * @param AddTrackersRequest $request 添加Tracker请求
     * @return \PhpQbittorrent\Contract\ResponseInterface 添加响应
     * @throws NetworkException 网络异常
     * @throws ValidationException 验证异常
     * @throws ApiRuntimeException API运行时异常
     */
    public function addTrackers(AddTrackersRequest $request): \PhpQbittorrent\Contract\ResponseInterface
    {
        // 验证请求
        $validation = $request->validate();
        if (!$validation->isValid()) {
            throw new ValidationException(
                'AddTrackers request validation failed',
                'VALIDATION_ERROR',
                $validation->getErrors()
            );
        }

        try {
            $url = $this->getBasePath() . $request->getEndpoint();
            $transportResponse = $this->transport->post(
                $url,
                $request->toArray(),
                $request->getHeaders()
            );

            // 处理响应
            return $this->handleAddTrackersResponse($transportResponse, $request);

        } catch (NetworkException $e) {
            throw new ApiRuntimeException(
                'Add trackers failed due to network error: ' . $e->getMessage(),
                'ADD_TRACKERS_NETWORK_ERROR',
                ['original_error' => $e->getMessage()],
                $url ?? '',
                'POST',
                null,
                ['request_summary' => $request->getSummary()],
                $e
            );
        }
    }

    /**
     * 处理添加Tracker响应
     *
     * @param \PhpQbittorrent\Contract\TransportResponse $transportResponse 传输响应
     * @param AddTrackersRequest $request 请求对象
     * @return \PhpQbittorrent\Contract\ResponseInterface 添加响应
     */
    private function handleAddTrackersResponse(
        \PhpQbittorrent\Contract\TransportResponse $transportResponse,
        AddTrackersRequest $request
    ): \PhpQbittorrent\Contract\ResponseInterface {
        $statusCode = $transportResponse->getStatusCode();
        $headers = $transportResponse->getHeaders();
        $rawResponse = $transportResponse->getBody();

        if ($statusCode === 200) {
            return $this->createGenericResponse($transportResponse);
        } elseif ($statusCode === 404) {
            return $this->createGenericResponse(
                $transportResponse,
                ['error' => 'Torrent不存在或哈希无效']
            );
        } elseif ($statusCode === 409) {
            return $this->createGenericResponse(
                $transportResponse,
                ['error' => 'Tracker已存在或URL无效']
            );
        } else {
            return $this->createGenericResponse(
                $transportResponse,
                ['error' => "添加Tracker失败，状态码: {$statusCode}"]
            );
        }
    }

    /**
     * 获取默认请求头
     *
     * @return array<string, string> 默认请求头
     */
    private function getDefaultHeaders(): array
    {
        return [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Accept' => 'application/json',
        ];
    }
}