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
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToWriteFile;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;

#[CoversClass(FileFetchTask::class)]
class FileFetchTaskTest extends TestCase
{
    private FilesystemOperator&MockObject $source;
    private FilesystemOperator&MockObject $destination;

    protected function setUp(): void
    {
        $this->source = $this->createMock(FilesystemOperator::class);
        $this->source->method('readStream')->willReturnCallback(static fn () => fopen('php://memory', 'r'));
        $this->destination = $this->createMock(FilesystemOperator::class);
    }

    public function testCopyWithRemoveSource(): void
    {
        $this->destination->expects($this->once())->method('writeStream')->with('file.txt');
        $this->source->expects($this->once())->method('delete')->with('file.txt');

        $state = $this->createState();
        $state->expects($this->once())->method('setOutput')->with('file.txt');

        $task = $this->createTask();
        $task->initialize($state);
        $task->execute($state);
    }

    public function testWriteFailureKeepsSourceAndFails(): void
    {
        $this->destination->expects($this->once())->method('writeStream')->willThrowException(UnableToWriteFile::atLocation('file.txt', 'Is a directory'));
        $this->source->expects($this->never())->method('delete');

        $state = $this->createState();
        $state->expects($this->never())->method('setOutput');

        $task = $this->createTask();
        $task->initialize($state);

        $this->expectException(UnableToWriteFile::class);
        $task->execute($state);
    }

    private function createTask(): FileFetchTask
    {
        /** @var ServiceLocator<FilesystemOperator> $storages */
        $storages = new ServiceLocator([
            'source' => fn (): FilesystemOperator => $this->source,
            'destination' => fn (): FilesystemOperator => $this->destination,
        ]);

        return new FileFetchTask($storages);
    }

    private function createState(): ProcessState&MockObject
    {
        $state = $this->createMock(ProcessState::class);
        $state->method('getContextualizedOptions')->willReturn([
            'source_filesystem' => 'source',
            'destination_filesystem' => 'destination',
            'remove_source' => true,
        ]);
        $state->method('getInput')->willReturn('file.txt');

        return $state;
    }
}
