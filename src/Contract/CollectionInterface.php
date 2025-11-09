<?php
declare(strict_types=1);

namespace Dongasai\qBittorrent\Contract;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;

/**
 * 集合接口
 *
 * 所有集合类必须实现此接口，确保统一的集合操作方式
 */
interface CollectionInterface extends IteratorAggregate, Countable, JsonSerializable
{
    /**
     * 获取第一个元素
     *
     * @return mixed 第一个元素，如果集合为空则返回null
     */
    public function first(): mixed;

    /**
     * 获取最后一个元素
     *
     * @return mixed 最后一个元素，如果集合为空则返回null
     */
    public function last(): mixed;

    /**
     * 检查集合是否为空
     *
     * @return bool 是否为空
     */
    public function isEmpty(): bool;

    /**
     * 过滤集合元素
     *
     * @param callable $callback 过滤回调函数
     * @return static 过滤后的新集合
     */
    public function filter(callable $callback): static;

    /**
     * 映射集合元素
     *
     * @param callable $callback 映射回调函数
     * @return array<string, mixed> 映射后的数组
     */
    public function map(callable $callback): array;

    /**
     * 规约集合元素
     *
     * @param callable $callback 规约回调函数
     * @param mixed $initial 初始值
     * @return mixed 规约结果
     */
    public function reduce(callable $callback, mixed $initial = null): mixed;

    /**
     * 转换为数组
     *
     * @return array<string, mixed> 数组格式
     */
    public function toArray(): array;

    /**
     * 获取迭代器
     *
     * @return ArrayIterator 数组迭代器
     */
    public function getIterator(): ArrayIterator;

    /**
     * 获取集合元素数量
     *
     * @return int 元素数量
     */
    public function count(): int;
}