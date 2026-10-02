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

use CleverAge\FlysystemProcessBundle\Task\FileFetchTask;
use CleverAge\ProcessBundle\Model\ProcessState;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

#[CoversClass(FileFetchTask::class)]
class FileFetchTaskStorageTest extends StorageTestCase
{
    public function testFilePattern(): void
    {
        $this->createFiles('source', ['a.txt' => 'a', 'b.txt' => 'b', 'c.csv' => 'c', 'dir.txt/d.txt' => 'd']);
        [$task, $state] = $this->createTask(['file_pattern' => '/\.txt$/']);

        $outputs = $this->iterate($task, $state, null);
        sort($outputs);

        // Directories are ignored
        self::assertSame(['a.txt', 'b.txt'], $outputs);
        self::assertSame(['a.txt', 'b.txt'], $this->getFiles('destination'));
        self::assertSame('a', file_get_contents($this->dir.'/destination/a.txt'));
        // Source kept by default
        self::assertSame(['a.txt', 'b.txt', 'c.csv', 'dir.txt'], $this->getFiles('source'));
    }

    public function testSourceIsListedOncePerInput(): void
    {
        $this->createFiles('source', ['a.txt' => 'a', 'b.txt' => 'b', 'c.txt' => 'c']);
        [$task, $state] = $this->createTask(['file_pattern' => '/\.txt$/']);

        self::assertCount(3, $this->iterate($task, $state, null));
        self::assertSame(1, $this->listings['source']);
    }

    public function testEachInputCopiesTheMatchingFiles(): void
    {
        $this->createFiles('source', ['a.txt' => 'a', 'b.txt' => 'b']);
        [$task, $state] = $this->createTask(['file_pattern' => '/\.txt$/']);

        self::assertCount(2, $this->iterate($task, $state, 'first'));
        self::assertCount(2, $this->iterate($task, $state, 'second'));
        self::assertSame(2, $this->listings['source']);
    }

    public function testFilesFromInput(): void
    {
        $this->createFiles('source', ['a.txt' => 'a', 'b.txt' => 'b', '0' => 'zero']);
        [$task, $state] = $this->createTask([]);

        self::assertSame(['a.txt'], $this->iterate($task, $state, 'a.txt'));
        self::assertSame(['b.txt', '0'], $this->iterate($task, $state, ['b.txt', '0', 'b.txt']));
        // A path received twice is copied again
        self::assertSame(['a.txt'], $this->iterate($task, $state, 'a.txt'));
        self::assertSame(['0'], $this->iterate($task, $state, '0'));
        self::assertSame(['0', 'a.txt', 'b.txt'], $this->getFiles('destination'));
    }

    public function testRemoveSource(): void
    {
        $this->createFiles('source', ['a.txt' => 'a', 'b.txt' => 'b']);
        [$task, $state] = $this->createTask(['remove_source' => true]);

        self::assertSame(['a.txt'], $this->iterate($task, $state, 'a.txt'));
        self::assertSame(['b.txt'], $this->getFiles('source'));
        self::assertSame(['a.txt'], $this->getFiles('destination'));
    }

    public function testExistingDestinationFileIsOverwritten(): void
    {
        $this->createFiles('source', ['a.txt' => 'new']);
        $this->createFiles('destination', ['a.txt' => 'old']);
        [$task, $state] = $this->createTask([]);

        $this->iterate($task, $state, 'a.txt');

        self::assertSame('new', file_get_contents($this->dir.'/destination/a.txt'));
    }

    public function testMissingInputFileIsIgnored(): void
    {
        $this->createFiles('source', ['a.txt' => 'a']);
        [$task, $state] = $this->createTask([]);

        self::assertSame(['a.txt'], $this->iterate($task, $state, ['missing.txt', 'a.txt']));
        self::assertSame([], $this->iterate($task, $state, 'missing.txt'));
    }

    public function testMissingInputFileFails(): void
    {
        [$task, $state] = $this->createTask(['ignore_missing' => false]);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('File missing.txt not found in source filesystem');
        $this->iterate($task, $state, 'missing.txt');
    }

    public function testNoMatchingFileIsIgnored(): void
    {
        [$task, $state] = $this->createTask(['file_pattern' => '/\.txt$/']);

        self::assertSame([], $this->iterate($task, $state, null));
    }

    public function testNoMatchingFileFails(): void
    {
        [$task, $state] = $this->createTask(['file_pattern' => '/\.txt$/', 'ignore_missing' => false]);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('File(s) not found in source filesystem');
        $this->iterate($task, $state, null);
    }

    public function testNoPatternNorInput(): void
    {
        [$task, $state] = $this->createTask([]);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('No pattern neither input provided for the Task');
        $this->iterate($task, $state, '');
    }

    public function testNextBeforeExecute(): void
    {
        [$task, $state] = $this->createTask([]);

        self::assertFalse($task->next($state));
    }

    public function testUnknownStorage(): void
    {
        $this->expectException(\Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException::class);
        $this->createTask(['source_filesystem' => 'unknown']);
    }

    public function testRequiredOptions(): void
    {
        $this->expectException(MissingOptionsException::class);
        $task = new FileFetchTask($this->createStorages());
        $task->initialize($this->createState(FileFetchTask::class, []));
    }

    public function testInvalidOptionType(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->createTask(['remove_source' => 'yes']);
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array{FileFetchTask, ProcessState}
     */
    private function createTask(array $options): array
    {
        $state = $this->createState(FileFetchTask::class, $options + [
            'source_filesystem' => 'source',
            'destination_filesystem' => 'destination',
        ]);
        $task = new FileFetchTask($this->createStorages());
        $task->initialize($state);

        return [$task, $state];
    }
}
