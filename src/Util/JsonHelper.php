<?php
declare(strict_types=1);

namespace PhpQbittorrent\Util;

use PhpQbittorrent\Exception\ClientException;

/**
 * JSON处理助手
 *
 * 提供统一的JSON编码和解码功能
 */
final class JsonHelper
{
    /**
     * 解码JSON字符串
     *
     * @param string $json JSON字符串
     * @param bool $assoc 是否返回关联数组
     * @param int $depth 最大递归深度
     * @return array|object 解码后的数据
     * @throws ClientException 解码失败时抛出异常
     */
    public static function decode(string $json, bool $assoc = true, int $depth = 512)
    {
        if (empty($json)) {
            return $assoc ? [] : (object) [];
        }

        $data = json_decode($json, $assoc, $depth, JSON_UNESCAPED_UNICODE);

        $jsonError = json_last_error();
        if ($jsonError !== JSON_ERROR_NONE) {
            throw new ClientException(
                "JSON解码失败: " . json_last_error_msg(),
                'JSON_DECODE_ERROR',
                [
                    'json_error' => $jsonError,
                    'json_error_msg' => json_last_error_msg(),
                    'input_length' => strlen($json),
                    'input_preview' => substr($json, 0, 100)
                ]
            );
        }

        return $data;
    }

    /**
     * 编码为JSON字符串
     *
     * @param mixed $value 要编码的值
     * @param int $flags JSON编码标志
     * @param int $depth 最大递归深度
     * @return string JSON字符串
     * @throws ClientException 编码失败时抛出异常
     */
    public static function encode($value, int $flags = JSON_UNESCAPED_UNICODE, int $depth = 512): string
    {
        $json = json_encode($value, $flags, $depth);

        $jsonError = json_last_error();
        if ($jsonError !== JSON_ERROR_NONE) {
            throw new ClientException(
                "JSON编码失败: " . json_last_error_msg(),
                'JSON_ENCODE_ERROR',
                [
                    'json_error' => $jsonError,
                    'json_error_msg' => json_last_error_msg(),
                    'value_type' => gettype($value),
                    'value_preview' => is_string($value) ? substr($value, 0, 100) : print_r($value, true)
                ]
            );
        }

        return $json;
    }

    /**
     * 安全地解码可能包含非UTF-8字符的JSON
     *
     * @param string $json JSON字符串
     * @param bool $assoc 是否返回关联数组
     * @return array|object 解码后的数据
     */
    public static function safeDecode(string $json, bool $assoc = true)
    {
        // 尝试修复常见的编码问题
        $json = mb_convert_encoding($json, 'UTF-8', 'UTF-8');
        $json = preg_replace('/[\x00-\x1F\x7F]/', '', $json);
        $json = preg_replace('/[\xC0-\xC1][\x80-\xBF]/', '', $json);
        $json = preg_replace('/[\xE0][\x80-\x9F][\x80-\xBF]/', '', $json);
        $json = preg_replace('/[\xF0][\x80-\x8F][\x80-\xBF]{2}/', '', $json);

        return self::decode($json, $assoc);
    }

    /**
     * 验证JSON字符串是否有效
     *
     * @param string $json JSON字符串
     * @return bool 是否有效
     */
    public static function isValid(string $json): bool
    {
        if (empty($json)) {
            return false;
        }

        json_decode($json);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * 格式化JSON字符串
     *
     * @param string $json JSON字符串
     * @param int $indent 缩进空格数
     * @return string 格式化后的JSON
     * @throws ClientException 格式化失败时抛出异常
     */
    public static function format(string $json, int $indent = 2): string
    {
        $data = self::decode($json);
        return self::encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * 合并多个JSON对象或数组
     *
     * @param array ...$jsonArrays 要合并的JSON数组
     * @return string 合并后的JSON字符串
     * @throws ClientException 合并失败时抛出异常
     */
    public static function merge(...$jsonArrays): string
    {
        $result = [];

        foreach ($jsonArrays as $jsonArray) {
            if (is_string($jsonArray)) {
                $decoded = self::decode($jsonArray);
                if (!is_array($decoded)) {
                    throw new ClientException('只能合并数组类型的JSON');
                }
                $result = array_merge_recursive($result, $decoded);
            } elseif (is_array($jsonArray)) {
                $result = array_merge_recursive($result, $jsonArray);
            } else {
                throw new ClientException('合并的参数必须是JSON字符串或数组');
            }
        }

        return self::encode($result);
    }

    /**
     * 从JSON路径提取值
     *
     * @param array|object $data JSON数据
     * @param string $path 路径，如 'user.profile.name'
     * @param mixed $default 默认值
     * @return mixed 提取的值
     */
    public static function extract($data, string $path, $default = null)
    {
        $keys = explode('.', $path);
        $current = $data;

        foreach ($keys as $key) {
            if (is_array($current) && isset($current[$key])) {
                $current = $current[$key];
            } elseif (is_object($current) && property_exists($current, $key)) {
                $current = $current->$key;
            } else {
                return $default;
            }
        }

        return $current;
    }

    /**
     * 获取JSON数据的大小（字节）
     *
     * @param string $json JSON字符串
     * @return int 字节数
     */
    public static function getSize(string $json): int
    {
        return strlen($json);
    }

    /**
     * 压缩JSON（移除空格和换行）
     *
     * @param string $json JSON字符串
     * @return string 压缩后的JSON
     * @throws ClientException 压缩失败时抛出异常
     */
    public static function compress(string $json): string
    {
        $data = self::decode($json);
        return self::encode($data, 0); // 不使用美化标志
    }

    /**
     * 创建JSON错误响应
     *
     * @param string $message 错误消息
     * @param string $code 错误代码
     * @param array $details 错误详情
     * @return string JSON格式的错误响应
     */
    public static function createErrorResponse(string $message, string $code = 'ERROR', array $details = []): string
    {
        $error = [
            'success' => false,
            'error' => [
                'message' => $message,
                'code' => $code,
                'timestamp' => time(),
                'details' => $details
            ]
        ];

        return self::encode($error);
    }

    /**
     * 创建JSON成功响应
     *
     * @param mixed $data 响应数据
     * @param string|null $message 成功消息
     * @return string JSON格式的成功响应
     */
    public static function createSuccessResponse($data = null, ?string $message = null): string
    {
        $response = [
            'success' => true,
            'timestamp' => time()
        ];

        if ($message !== null) {
            $response['message'] = $message;
        }

        if ($data !== null) {
            $response['data'] = $data;
        }

        return self::encode($response);
    }
}