<?php

declare(strict_types=1);

namespace Devly\Tests;

use Devly\Repository;
use PHPUnit\Framework\TestCase;
use Throwable;

use function count;
use function json_encode;

use const JSON_PRETTY_PRINT;

class RepositoryTest extends TestCase
{
    public function testCreateEmptyContainer(): void
    {
        $repository = new Repository();

        $this->assertEquals([], $repository->all());
    }

    public function testCreateContainerWithArray(): void
    {
        $repository = new Repository(['foo' => 'bar']);

        $this->assertEquals(['foo' => 'bar'], $repository->all());
    }

    public function testCreateContainerWithContainer(): void
    {
        $repository1 = new Repository(['foo' => 'bar']);
        $repository2 = new Repository($repository1);

        $this->assertEquals($repository1->all(), $repository2->all());
    }

    public function testCreateContainerWithString(): void
    {
        $repository = new Repository('bar');

        $this->assertEquals(['bar'], $repository->all());
    }

    public function testCreateContainerWithObject(): void
    {
        $items      = (object) ['foo' => 'bar'];
        $repository = new Repository($items);

        $this->assertEquals(['foo' => 'bar'], $repository->all());
    }

    public function testSetAndGet(): void
    {
        $repository = new Repository();

        $repository->set('name.first', 'John');
        $repository->set('name.last', 'Doe');

        $this->assertEquals(['first' => 'John', 'last' => 'Doe'], $repository->get('name'));
        $this->assertEquals('John', $repository->get('name.first'));
    }

    public function testGetWithUnknownKey(): void
    {
        $repository = new Repository();

        $this->assertNull($repository->get('foo'));
    }

    public function testGetWithDefaultValue(): void
    {
        $repository = new Repository();

        $this->assertEquals('John', $repository->get('name', 'John'));
    }

    public function testSetAndGetUsingArrayAccess(): void
    {
        $repository = new Repository();

        $repository['name.first'] = 'John';
        $repository['name.last']  = 'Doe';

        $this->assertEquals(['name' => ['first' => 'John', 'last' => 'Doe']], $repository->all());
        $this->assertEquals('John', $repository['name.first']);
    }

    public function testSetWithNoKeyName(): void
    {
        $repository = new Repository();

        $repository[] = [1, 2, 3, 4];

        $this->assertEquals([1, 2, 3, 4], $repository[0]);
        $this->assertEquals(2, $repository['0.1']);
    }

    public function testHasKey(): void
    {
        $repository = new Repository(['foo' => 'bar']);

        $this->assertTrue($repository->has('foo'));
        $this->assertTrue(isset($repository['foo']));
    }

    public function testRemove(): void
    {
        $repository = new Repository(['foo' => 'bar']);

        $repository->remove('foo');

        $this->assertFalse($repository->has('foo'));

        $repository['numbers'] = [1, 2, 3, 4];
        unset($repository['numbers.1']);

        $this->assertNotContains(2, $repository['numbers']);
    }

    public function testIsEmpty(): void
    {
        $repository = new Repository();

        $this->assertTrue($repository->isEmpty());

        $repository[] = 'foo';
        $this->assertFalse($repository->isEmpty());
    }

    public function testCount(): void
    {
        $repository = new Repository([1, 2, 3, 4]);

        $this->assertEquals(4, $repository->count());
        $this->assertEquals(4, count($repository));
    }

    public function testToJson(): void
    {
        $repository = new Repository(['first' => 'John', 'last' => 'Doe']);

        $this->assertEquals(json_encode(['first' => 'John', 'last' => 'Doe']), $repository->toJson());
    }

    public function testToJsonWithKey(): void
    {
        $repository = new Repository(['name' => ['first' => 'John', 'last' => 'Doe']]);

        $this->assertEquals(
            json_encode(['first' => 'John', 'last' => 'Doe'], JSON_PRETTY_PRINT),
            $repository->toJson('name', JSON_PRETTY_PRINT)
        );
    }

    public function testToJsonWithFlag(): void
    {
        $repository = new Repository(['first' => 'John', 'last' => 'Doe']);

        $this->assertEquals(
            json_encode(['first' => 'John', 'last' => 'Doe'], JSON_PRETTY_PRINT),
            $repository->toJson(null, JSON_PRETTY_PRINT)
        );
    }

    public function testJsonEncode(): void
    {
        $repository = new Repository(['first' => 'John', 'last' => 'Doe']);

        $this->assertEquals(
            json_encode(['first' => 'John', 'last' => 'Doe'], JSON_PRETTY_PRINT),
            json_encode($repository, JSON_PRETTY_PRINT)
        );
    }

    public function testCreateFrom(): void
    {
        $repository = new Repository(['name' => ['first' => 'John', 'last' => 'Doe']]);

        /** @noinspection PhpUnhandledExceptionInspection */
        $repository2 = $repository->createFrom('name');

        $this->assertEquals(['first' => 'John', 'last' => 'Doe'], $repository2->all());
    }

    public function testCreateFromWithInvalidKey(): void
    {
        $this->expectException(Throwable::class);
        $repository = new Repository();

        /** @noinspection PhpUnhandledExceptionInspection */
        $repository->createFrom('invalid');
    }

    public function testMergeWithArray(): void
    {
        $repository = new Repository(['name' => 'John']);

        $repository->merge(['name' => 'Johnny']);

        $this->assertEquals('Johnny', $repository['name']);
    }

    public function testMergeRecursive(): void
    {
        $repository = new Repository(['name' => ['first' => 'John', 'last' => 'Doe']]);

        $repository->mergeRecursive(['name' => ['first' => 'Johnny']]);

        $this->assertEquals('Johnny', $repository['name.first']);
        $this->assertEquals('Doe', $repository['name.last']);
    }

    public function testMergeWithContainer(): void
    {
        $repository = new Repository(['first' => 'John', 'last' => 'Doe']);

        $repository->merge(new Repository(['first' => 'Johnny']));

        $this->assertEquals('Johnny', $repository['first']);
    }

    public function testMergeWithObject(): void
    {
        $repository = new Repository(['first' => 'John', 'last' => 'Doe']);

        $repository->merge((object) ['first' => 'Johnny']);

        $this->assertEquals('Johnny', $repository['first']);
    }

    public function testIteration(): void
    {
        $repository = new Repository(['first_name' => 'John', 'last_name' => 'Doe']);

        $items = [];

        foreach ($repository as $key => $item) {
            $items[$key] = $item;
        }

        $this->assertSame($repository->all(), $items);
    }
}
