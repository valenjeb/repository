<?php

declare(strict_types=1);

namespace Devly;

use ArrayAccess;
use ArrayIterator;
use Countable;
use Exception;
use IteratorAggregate;
use JsonSerializable;
use stdClass;
use Traversable;

use function array_key_exists;
use function array_pop;
use function array_replace;
use function array_replace_recursive;
use function count;
use function explode;
use function is_array;
use function json_encode;
use function sprintf;
use function str_contains;

/**
 * @implements ArrayAccess<int|string, mixed>
 * @implements IteratorAggregate<int|string, mixed>
 */
class Repository implements ArrayAccess, Countable, JsonSerializable, IteratorAggregate
{
    /** @var array<string, mixed> */
    protected array $items = [];

    /** @var array<string, mixed> */
    protected array $itemsCache = [];

    public function __construct(mixed $items = [])
    {
        if ($items instanceof Repository) {
            $items = $items->all();
        }

        $this->items = is_array($items) ? $items : (array) $items;
    }

    /**
     * Create a new instance of Repository object
     *
     * @param array<array-key, mixed>|Repository|stdClass $items
     *
     * @return Repository
     */
    public static function new(mixed $items = []): self
    {
        return new self($items);
    }

    /**
     * Create a new Repository from an item in the current repository
     *
     * @throws Exception
     */
    public function createFrom(string|int $key): self
    {
        if (! $this->has($key)) {
            throw new Exception(sprintf('Key "%s" does not exist.', $key));
        }

        $items = $this->get($key);

        $items ??= [];

        return new self((array) $items);
    }

    /**
     * Determines whether an item exists in the repository.
     */
    public function has(string|int $key): bool
    {
        if (array_key_exists($key, $this->itemsCache) || array_key_exists($key, $this->items)) {
            return true;
        }

        $items = $this->items;

        foreach (explode('.', $key) as $segment) {
            if (! is_array($items) || ! $this->exists($items, $segment)) {
                return false;
            }

            $items = $items[$segment];
        }

        return true;
    }

    /**
     * Set an item on the repository.
     *
     * @return $this
     */
    public function set(int|string $key, mixed $value): self
    {
        $key = (string) $key;

        if (! str_contains($key, '.')) {
            $this->items[$key] = $value;
        }

        $items = &$this->items;

        foreach (explode('.', $key) as $id) {
            if (! isset($items[$id]) || ! is_array($items[$id])) {
                $items[$id] = [];
            }

            $items = &$items[$id];
        }

        $items = $value;

        $this->itemsCache[$key] = $value;

        return $this;
    }

    /**
     * Get an item from the repository.
     */
    public function get(string|int $key, mixed $default = null): mixed
    {
        if (isset($this->items[$key])) {
            return $this->items[$key];
        }

        if (array_key_exists($key, $this->itemsCache)) {
            return $this->itemsCache[$key];
        }

        if (! str_contains($key, '.')) {
            return $default;
        }

        $items = $this->items;

        foreach (explode('.', $key) as $segment) {
            if (! is_array($items) || ! $this->exists($items, $segment)) {
                return $default;
            }

            $items = &$items[$segment];
        }

        return $this->itemsCache[$key] = $items;
    }

    /**
     * Merge array, object or an instance of IRepository into the current Repository
     *
     * @param Repository|array<array-key, mixed>|object $items
     */
    public function merge(array|object $items, bool $recursive = false): self
    {
        if ($items instanceof Repository) {
            $items = $items->all();
        }

        if ($recursive) {
            $this->items = array_replace_recursive($this->items, (array) $items);
        } else {
            $this->items = array_replace($this->items, (array) $items);
        }

        $this->flushCache();

        return $this;
    }

    /**
     * Merge array, object or an instance of IRepository into the current Repository recursively
     *
     * @param Repository|array<array-key, mixed>|object $items
     */
    public function mergeRecursive(array|object $items): self
    {
        return $this->merge($items, true);
    }

    /**
     * Get all items from the repository.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * Remove an item from the repository.
     */
    public function remove(string|int $key): self
    {
        if (isset($this->items[$key])) {
            unset($this->items[$key]);
        }

        if (isset($this->itemsCache[$key])) {
            unset($this->itemsCache[$key]);
        }

        $items       = &$this->items;
        $segments    = explode('.', $key);
        $lastSegment = array_pop($segments);

        foreach ($segments as $segment) {
            if (! isset($items[$segment]) || ! is_array($items[$segment])) {
                continue;
            }

            $items = &$items[$segment];
        }

        unset($items[$lastSegment]);

        return $this;
    }

    public function clear(): self
    {
        $this->items = [];

        return $this;
    }

    /**
     * Check whether key exists in a given array.
     *
     * @param array<array-key, mixed> $array
     */
    protected function exists(array $array, string $key): bool
    {
        return array_key_exists($key, $array);
    }

    /**
     * Flashes the items cache
     */
    protected function flushCache(): void
    {
        $this->itemsCache = [];
    }

    /**
     * Count items in the repository.
     */
    public function count(string|int|null $key = null): int
    {
        $items = $key ? $this->get($key) : $this->items;

        return count($items);
    }

    /**
     * Return the value of a given key or all the values as JSON
     */
    public function toJson(string|int|null $key = null, int $flags = 0): string|false
    {
        if ($key !== null) {
            return json_encode($this->get($key), $flags);
        }

        return json_encode($this->items, $flags);
    }

    /**
     * --------------------------------------------------------
     * ArrayAccess Interface Implementation
     * --------------------------------------------------------
     */

    /**
     * Whether an item exists in the repository.
     *
     * @param int|string $offset
     */
    public function offsetExists(mixed $offset): bool
    {
        return $this->has($offset);
    }

    /**
     * Retrieve an item from the repository.
     *
     * @param int|string $offset
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->get($offset);
    }

    /**
     * Set an item in the repository
     *
     * @param int|string|null $offset
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->items[] = $value;

            return;
        }

        $this->set($offset, $value);
    }

    /**
     * Removes an item from the repository
     *
     * @param int|string $offset
     */
    public function offsetUnset(mixed $offset): void
    {
        $this->remove($offset);
    }

    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    /**
     * --------------------------------------------------------
     * JsonSerializable Interface Implementation
     * --------------------------------------------------------
     */

    /**
     * Return items for JSON serialization
     *
     * @return array<array-key, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->items;
    }

    /**
     * --------------------------------------------------------
     * IteratorAggregate interface implementation
     * --------------------------------------------------------
     */

    /**
     * Get an iterator for the stored items
     *
     * @return ArrayIterator<array-key, mixed>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }
}
