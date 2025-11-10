<?php
declare(strict_types=1);

namespace PhpQbittorrent\API;

use PhpQbittorrent\Contract\ApiInterface;
use PhpQbittorrent\Contract\TransportInterface;
use PhpQbittorrent\Request\Torrent\GetTorrentsRequest;
use PhpQbittorrent\Request\Torrent\AddTorrentRequest;
use PhpQbittorrent\Request\Torrent\DeleteTorrentsRequest;
use PhpQbittorrent\Request\Torrent\PauseTorrentsRequest;
use PhpQbittorrent\Request\Torrent\ResumeTorrentsRequest;
use PhpQbittorrent\Response\Torrent\TorrentListResponse;
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
            throw \PhpQbittorrent\Exception\ValidationException::fromValidationResult(
                $validation,
                'GetTorrents request validation failed'
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
                $url,
                'GET',
                $transportResponse->getStatusCode() ?? null,
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
            throw ValidationException::fromValidationResult(
                $validation,
                'AddTorrents request validation failed'
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
                $url,
                'POST',
                $transportResponse->getStatusCode() ?? null,
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
            throw ValidationException::fromValidationResult(
                $validation,
                'DeleteTorrents request validation failed'
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
                $url,
                'POST',
                $transportResponse->getStatusCode() ?? null,
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
            throw ValidationException::fromValidationResult(
                $validation,
                'PauseTorrents request validation failed'
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
                $url,
                'POST',
                $transportResponse->getStatusCode() ?? null,
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
            throw ValidationException::fromValidationResult(
                $validation,
                'ResumeTorrents request validation failed'
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
                $url,
                'POST',
                $transportResponse->getStatusCode() ?? null,
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