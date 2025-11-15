<?php
declare(strict_types=1);

namespace PhpQbittorrent\Contract;

/**
 * 可JSON序列化接口
 *
 * 表示对象可以转换为JSON格式
 */
interface JsonableInterface
{
    /**
     * 转换为JSON
     *
     * @return string JSON字符串
     */
    public function toJson(): string;
}