<?php
declare(strict_types=1);

namespace PhpQbittorrent\Contract;

/**
 * 可数组化接口
 *
 * 表示对象可以转换为数组格式
 */
interface ArrayableInterface
{
    /**
     * 转换为数组
     *
     * @return array<string, mixed> 数组表示
     */
    public function toArray(): array;
}