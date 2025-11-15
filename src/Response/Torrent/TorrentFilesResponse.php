<?php
declare(strict_types=1);

namespace PhpQbittorrent\Response\Torrent;

use PhpQbittorrent\Contract\ResponseInterface;
use PhpQbittorrent\Response\AbstractResponse;
use PhpQbittorrent\Model\TorrentFile;

/**
 * Torrent文件列表响应
 *
 * 用于处理获取种子文件列表请求的响应
 */
class TorrentFilesResponse extends AbstractResponse implements ResponseInterface
{
    /** @var array<TorrentFile> 文件列表 */
    private array $files;

    /** @var array<string, mixed> 原始数据 */
    private array $rawData;

    /**
     * 从API响应创建TorrentFilesResponse实例
     *
     * @param array<string, mixed> $data API响应数据
     * @param array<string, string> $headers 响应头
     * @param int $statusCode HTTP状态码
     * @param string $rawResponse 原始响应内容
     * @return self TorrentFilesResponse实例
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
            $response->files = [];
            foreach ($data as $fileData) {
                if (is_array($fileData)) {
                    $response->files[] = TorrentFile::fromApiData($fileData);
                }
            }
        } catch (\Exception $e) {
            $response->files = [];
            $response->errors[] = '文件列表解析失败: ' . $e->getMessage();
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
     * @return self TorrentFilesResponse实例
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
        $response->files = [];
        $response->rawData = [];

        return $response;
    }

    /**
     * 获取文件列表
     *
     * @return array<TorrentFile> 文件列表
     */
    public function getFiles(): array
    {
        return $this->files;
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
     * 检查响应是否成功
     *
     * @return bool 是否成功
     */
    public function isSuccess(): bool
    {
        return !empty($this->files) && empty($this->errors);
    }

    /**
     * 获取响应数据
     *
     * @return mixed 文件列表或错误信息
     */
    public function getData(): mixed
    {
        if ($this->isSuccess()) {
            return $this->files;
        }

        return [
            'files' => $this->files,
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
        $filesArray = [];
        foreach ($this->files as $file) {
            $filesArray[] = $file->toArray();
        }

        return [
            'success' => $this->isSuccess(),
            'total_files' => count($this->files),
            'total_size' => array_sum(array_map(fn($file) => $file->getSize(), $this->files)),
            'completed_files' => count(array_filter(fn($file) => $file->isCompleted(), $this->files)),
            'downloading_files' => count(array_filter(fn($file) => !$file->isCompleted() && $file->getProgress() > 0, $this->files)),
            'files' => $filesArray,
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
     * 获取格式化的响应摘要
     *
     * @return array<string, mixed> 摘要信息
     */
    public function getSummary(): array
    {
        $totalSize = array_sum(array_map(fn($file) => $file->getSize(), $this->files));
        $completedFiles = count(array_filter(fn($file) => $file->isCompleted(), $this->files));
        $downloadingFiles = count(array_filter(fn($file) => !$file->isCompleted() && $file->getProgress() > 0, $this->files));

        return [
            'success' => $this->isSuccess(),
            'total_files' => count($this->files),
            'total_size' => $totalSize,
            'completed_files' => $completedFiles,
            'downloading_files' => $downloadingFiles,
            'completion_rate' => count($this->files) > 0 ? round(($completedFiles / count($this->files)) * 100, 2) : 0,
            'average_file_size' => count($this->files) > 0 ? round($totalSize / count($this->files)) : 0,
            'largest_file' => !empty($this->files) ? array_reduce($this->files, fn($carry, $file) => $file->getSize() > $carry->getSize() ? $file : $carry) : null,
            'smallest_file' => !empty($this->files) ? array_reduce($this->files, fn($carry, $file) => $file->getSize() < $carry->getSize() ? $file : $carry) : null,
            'error_count' => count($this->errors),
            'status_code' => $this->statusCode,
        ];
    }
}