<?php
declare(strict_types=1);

namespace PhpQbittorrent\Collection;

use ArrayIterator;
use Countable;
use PhpQbittorrent\Contract\CollectionInterface as CollectionContract;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

/**
 * 抽象集合基类
 *
 * 为所有集合类提供通用实现和强大的数据操作功能
 */
abstract class AbstractCollection implements IteratorAggregate, Countable, JsonSerializable, CollectionContract
{
    /** @var array<int, mixed> 集合元素 */
    protected array $items = [];

    /**
     * 构造函数
     *
     * @param array<int, mixed> $items 初始元素
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    /**
     * 获取迭代器
     *
     * @return ArrayIterator 数组迭代器
     */
    public function getIterator(): \ArrayIterator
    {
        return new ArrayIterator($this->items);
    }

    /**
     * 获取集合元素数量
     *
     * @return int 元素数量
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * 获取第一个元素
     *
     * @return mixed 第一个元素，如果集合为空则返回null
     */
    public function first(): mixed
    {
        return $this->items[0] ?? null;
    }

    /**
     * 获取最后一个元素
     *
     * @return mixed 最后一个元素，如果集合为空则返回null
     */
    public function last(): mixed
    {
        return $this->items[array_key_last($this->items)] ?? null;
    }

    /**
     * 检查集合是否为空
     *
     * @return bool 是否为空
     */
    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    /**
     * 过滤集合元素
     *
     * @param callable $callback 过滤回调函数
     * @return static 过滤后的新集合
     */
    public function filter(callable $callback): static
    {
        return new static(array_filter($this->items, $callback));
    }

    /**
     * 映射集合元素
     *
     * @param callable $callback 映射回调函数
     * @return array<int, mixed> 映射后的数组
     */
    public function map(callable $callback): array
    {
        return array_map($callback, $this->items);
    }

    /**
     * 规约集合元素
     *
     * @param callable $callback 规约回调函数
     * @param mixed $initial 初始值
     * @return mixed 规约结果
     */
    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        return array_reduce($this->items, $callback, $initial);
    }

    /**
     * 转换为数组
     *
     * @return array<int, mixed> 数组格式
     */
    public function toArray(): array
    {
        return $this->items;
    }

    /**
     * JSON序列化
     *
     * @return array<int, mixed> 序列化数据
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * 检查集合中是否包含指定元素
     *
     * @param mixed $item 要检查的元素
     * @param bool $strict 是否严格比较
     * @return bool 是否包含
     */
    public function contains(mixed $item, bool $strict = false): bool
    {
        return in_array($item, $this->items, $strict);
    }

    /**
     * 查找元素在集合中的位置
     *
     * @param mixed $item 要查找的元素
     * @param bool $strict 是否严格比较
     * @return int|false 元素位置，未找到返回false
     */
    public function indexOf(mixed $item, bool $strict = false): int|false
    {
        return array_search($item, $this->items, $strict);
    }

    /**
     * 获取指定位置的元素
     *
     * @param int $index 元素位置
     * @return mixed 元素值，位置无效返回null
     */
    public function get(int $index): mixed
    {
        return $this->items[$index] ?? null;
    }

    /**
     * 添加元素到集合末尾
     *
     * @param mixed $item 要添加的元素
     * @return static 返回自身以支持链式调用
     */
    public function add(mixed $item): static
    {
        $this->items[] = $item;
        return $this;
    }

    /**
     * 添加多个元素到集合末尾
     *
     * @param array<int, mixed> $items 要添加的元素数组
     * @return static 返回自身以支持链式调用
     */
    public function addAll(array $items): static
    {
        $this->items = array_merge($this->items, $items);
        return $this;
    }

    /**
     * 在指定位置插入元素
     *
     * @param int $index 插入位置
     * @param mixed $item 要插入的元素
     * @return static 返回自身以支持链式调用
     */
    public function insert(int $index, mixed $item): static
    {
        array_splice($this->items, $index, 0, [$item]);
        return $this;
    }

    /**
     * 移除指定位置的元素
     *
     * @param int $index 要移除的位置
     * @return static 返回自身以支持链式调用
     */
    public function remove(int $index): static
    {
        array_splice($this->items, $index, 1);
        return $this;
    }

    /**
     * 移除指定元素
     *
     * @param mixed $item 要移除的元素
     * @param bool $strict 是否严格比较
     * @return static 返回自身以支持链式调用
     */
    public function removeItem(mixed $item, bool $strict = false): static
    {
        $index = $this->indexOf($item, $strict);
        if ($index !== false) {
            $this->remove($index);
        }
        return $this;
    }

    /**
     * 清空集合
     *
     * @return static 返回自身以支持链式调用
     */
    public function clear(): static
    {
        $this->items = [];
        return $this;
    }

    /**
     * 获取前N个元素
     *
     * @param int $count 元素数量
     * @return static 包含前N个元素的新集合
     */
    public function take(int $count): static
    {
        return new static(array_slice($this->items, 0, $count));
    }

    /**
     * 跳过前N个元素
     *
     * @param int $count 要跳过的元素数量
     * @return static 跳过前N个元素的新集合
     */
    public function skip(int $count): static
    {
        return new static(array_slice($this->items, $count));
    }

    /**
     * 获取指定范围内的元素
     *
     * @param int $offset 起始位置
     * @param int|null $length 元素数量，null表示到末尾
     * @return static 指定范围内的元素集合
     */
    public function slice(int $offset, ?int $length = null): static
    {
        return new static(array_slice($this->items, $offset, $length));
    }

    /**
     * 对集合进行排序
     *
     * @param callable|null $callback 排序回调函数，null表示自然排序
     * @param bool $descending 是否降序
     * @return static 排序后的新集合
     */
    public function sort(?callable $callback = null, bool $descending = false): static
    {
        $items = $this->items;

        if ($callback !== null) {
            usort($items, $callback);
        } else {
            sort($items);
        }

        if ($descending) {
            $items = array_reverse($items);
        }

        return new static($items);
    }

    /**
     * 按指定字段对对象集合进行排序
     *
     * @param string $field 字段名
     * @param bool $descending 是否降序
     * @return static 排序后的新集合
     */
    public function sortBy(string $field, bool $descending = false): static
    {
        return $this->sort(function ($a, $b) use ($field) {
            $aValue = is_object($a) ? ($a->$field ?? null) : ($a[$field] ?? null);
            $bValue = is_object($b) ? ($b->$field ?? null) : ($b[$field] ?? null);
            return $aValue <=> $bValue;
        }, $descending);
    }

    /**
     * 反转集合元素顺序
     *
     * @return static 反转后的新集合
     */
    public function reverse(): static
    {
        return new static(array_reverse($this->items));
    }

    /**
     * 打乱集合元素顺序
     *
     * @return static 打乱后的新集合
     */
    public function shuffle(): static
    {
        $items = $this->items;
        shuffle($items);
        return new static($items);
    }

    /**
     * 检查是否所有元素都满足条件
     *
     * @param callable $callback 条件回调函数
     * @return bool 是否所有元素都满足条件
     */
    public function every(callable $callback): bool
    {
        foreach ($this->items as $item) {
            if (!$callback($item)) {
                return false;
            }
        }
        return true;
    }

    /**
     * 检查是否有元素满足条件
     *
     * @param callable $callback 条件回调函数
     * @return bool 是否有元素满足条件
     */
    public function some(callable $callback): bool
    {
        foreach ($this->items as $item) {
            if ($callback($item)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 连接多个集合
     *
     * @param static ...$collections 要连接的集合
     * @return static 连接后的新集合
     */
    public function concat(self ...$collections): static
    {
        $items = $this->items;
        foreach ($collections as $collection) {
            $items = array_merge($items, $collection->toArray());
        }
        return new static($items);
    }

    /**
     * 获取唯一的元素集合
     *
     * @param callable|null $keyCallback 用于生成唯一键的回调函数
     * @return static 唯一元素集合
     */
    public function unique(?callable $keyCallback = null): static
    {
        if ($keyCallback === null) {
            return new static(array_unique($this->items));
        }

        $uniqueItems = [];
        $seenKeys = [];

        foreach ($this->items as $item) {
            $key = $keyCallback($item);
            if (!in_array($key, $seenKeys, true)) {
                $uniqueItems[] = $item;
                $seenKeys[] = $key;
            }
        }

        return new static($uniqueItems);
    }

    /**
     * 分组集合元素
     *
     * @param callable $keyCallback 分组键生成回调函数
     * @return array<string, static> 分组后的集合数组
     */
    public function groupBy(callable $keyCallback): array
    {
        $groups = [];

        foreach ($this->items as $item) {
            $key = (string) $keyCallback($item);
            if (!isset($groups[$key])) {
                $groups[$key] = new static();
            }
            $groups[$key]->add($item);
        }

        return $groups;
    }

    /**
     * 将集合分割成指定大小的块
     *
     * @param int $size 块大小
     * @return array<static> 分割后的集合数组
     */
    public function chunk(int $size): array
    {
        return array_map(fn($chunk) => new static($chunk), array_chunk($this->items, $size));
    }

    /**
     * 创建字符串表示
     *
     * @param string $separator 分隔符
     * @param callable|null $stringCallback 元素转字符串回调函数
     * @return string 字符串表示
     */
    public function join(string $separator = ', ', ?callable $stringCallback = null): string
    {
        if ($stringCallback === null) {
            return implode($separator, $this->items);
        }

        return implode($separator, array_map($stringCallback, $this->items));
    }

    /**
     * 调试输出集合信息
     *
     * @return void
     */
    public function debug(): void
    {
        echo "Collection Debug Info:\n";
        echo "Count: " . $this->count() . "\n";
        echo "Items: " . json_encode($this->items, JSON_PRETTY_PRINT) . "\n";
    }
}