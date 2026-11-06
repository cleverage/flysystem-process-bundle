<?php

declare(strict_types=1);

/*
 * This file is part of the CleverAge/FlysystemProcessBundle package.
 *
 * Copyright (c) Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\FlysystemProcessBundle\Tests\Task;

use CleverAge\FlysystemProcessBundle\Task\ListContentTask;
use CleverAge\ProcessBundle\Model\ProcessState;
use League\Flysystem\DirectoryListing;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\StorageAttributes;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

#[CoversClass(ListContentTask::class)]
class ListContentTaskTest extends StorageTestCase
{
    public function testListContent(): void
    {
        $this->createFiles('source', ['a.txt' => 'a', 'b.csv' => 'b', 'dir/c.txt' => 'c']);
        [$task, $state] = $this->createTask([]);

        // Files and directories, at the root of the storage only
        self::assertSame(['a.txt:file', 'b.csv:file', 'dir:dir'], $this->paths($this->iterate($task, $state, null)));
    }

    public function testFilePattern(): void
    {
        $this->createFiles('source', ['a.txt' => 'a', 'b.csv' => 'b', 'dir/c.txt' => 'c']);
        [$task, $state] = $this->createTask(['file_pattern' => '/\.txt$/']);

        self::assertSame(['a.txt:file'], $this->paths($this->iterate($task, $state, null)));
    }

    public function testEmptyStorage(): void
    {
        [$task, $state] = $this->createTask([]);

        self::assertSame([], $this->iterate($task, $state, null));
    }

    public function testEachExecutionListsTheStorageAgain(): void
    {
        $this->createFiles('source', ['a.txt' => 'a']);
        [$task, $state] = $this->createTask([]);

        self::assertSame(['a.txt:file'], $this->paths($this->iterate($task, $state, null)));
        $this->createFiles('source', ['b.txt' => 'b']);
        self::assertSame(['a.txt:file', 'b.txt:file'], $this->paths($this->iterate($task, $state, null)));
        self::assertSame(2, $this->listings['source']);
    }

    /**
     * The listing is read item by item, not loaded in an array: only the items already output (and the one being
     * output) are read from the listing.
     */
    public function testListingIsReadLazily(): void
    {
        $read = 0;
        $listing = new DirectoryListing((static function () use (&$read): \Generator {
            foreach (['a.txt', 'b.txt', 'c.txt'] as $path) {
                ++$read;
                yield new FileAttributes($path);
            }
        })());
        $filesystem = $this->createStub(FilesystemOperator::class);
        $filesystem->method('listContents')->willReturn($listing);
        $task = new ListContentTask(new ServiceLocator(['source' => static fn (): FilesystemOperator => $filesystem]));
        $state = $this->createState(ListContentTask::class, ['filesystem' => 'source']);
        $task->initialize($state);

        $state->reset(true);
        $task->execute($state);
        self::assertSame(1, $read);
        self::assertTrue($task->next($state));
        $task->execute($state);
        self::assertSame(2, $read);
        self::assertTrue($task->next($state));
        $task->execute($state);
        self::assertFalse($task->next($state));
        self::assertSame(3, $read);
    }

    public function testNoItemMatchingThePattern(): void
    {
        $this->createFiles('source', ['a.txt' => 'a']);
        [$task, $state] = $this->createTask(['file_pattern' => '/\.csv$/']);

        self::assertSame([], $this->iterate($task, $state, null));
        self::assertFalse($task->next($state));
    }

    public function testNextBeforeExecute(): void
    {
        [$task, $state] = $this->createTask([]);

        self::assertFalse($task->next($state));
    }

    public function testRequiredOptions(): void
    {
        $this->expectException(MissingOptionsException::class);
        $task = new ListContentTask($this->createStorages());
        $task->initialize($this->createState(ListContentTask::class, []));
    }

    /**
     * @param list<mixed> $items
     *
     * @return list<string>
     */
    private function paths(array $items): array
    {
        $paths = array_map(
            static fn (mixed $item): string => $item instanceof StorageAttributes ? "{$item->path()}:{$item->type()}" : '',
            $items
        );
        sort($paths);

        return $paths;
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array{ListContentTask, ProcessState}
     */
    private function createTask(array $options): array
    {
        $state = $this->createState(ListContentTask::class, $options + ['filesystem' => 'source']);
        $task = new ListContentTask($this->createStorages());
        $task->initialize($state);

        return [$task, $state];
    }
}
