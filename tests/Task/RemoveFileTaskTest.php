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

use CleverAge\FlysystemProcessBundle\Task\RemoveFileTask;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToDeleteFile;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Log\AbstractLogger;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

#[CoversClass(RemoveFileTask::class)]
class RemoveFileTaskTest extends StorageTestCase
{
    /** @var list<string> */
    private array $logs = [];

    public function testRemoveInputFile(): void
    {
        $this->createFiles('source', ['a.txt' => 'a', 'b.txt' => 'b']);

        $this->execute([], 'a.txt');

        self::assertSame(['b.txt'], $this->getFiles('source'));
        self::assertSame(['info: Deleted input file a.txt'], $this->logs);
    }

    public function testRemoveListOfFiles(): void
    {
        $this->createFiles('source', ['a.txt' => 'a', 'b.txt' => 'b', '0' => 'zero', 'c.txt' => 'c']);

        $this->execute([], ['a.txt', 'b.txt', '0']);

        self::assertSame(['c.txt'], $this->getFiles('source'));
    }

    public function testMissingFile(): void
    {
        $this->createFiles('source', ['dir/a.txt' => 'a']);

        $this->execute([], ['missing.txt', 'dir']);

        self::assertSame(['warning: Input file not found missing.txt', 'warning: Input file not found dir'], $this->logs);
        self::assertSame(['dir'], $this->getFiles('source'));
    }

    public function testFilePattern(): void
    {
        $this->createFiles('source', ['a.txt' => 'a', 'b.txt' => 'b', 'c.csv' => 'c', 'dir.txt/d.txt' => 'd']);

        // The input is ignored
        $this->execute(['file_pattern' => '/\.txt$/'], 'c.csv');

        self::assertSame(['c.csv', 'dir.txt'], $this->getFiles('source'));
        self::assertCount(2, $this->logs);
    }

    public function testDeletionFailureIsLogged(): void
    {
        $filesystem = $this->createStub(FilesystemOperator::class);
        $filesystem->method('fileExists')->willReturn(true);
        $filesystem->method('delete')->willThrowException(UnableToDeleteFile::atLocation('a.txt'));
        /** @var ServiceLocator<FilesystemOperator> $storages */
        $storages = new ServiceLocator(['source' => static fn (): FilesystemOperator => $filesystem]);

        $this->execute([], 'a.txt', $storages);

        self::assertSame(['warning: Failed to delete input file a.txt'], $this->logs);
    }

    public function testNoPatternNorInput(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('No pattern neither input provided for the Task');
        $this->execute([], null);
    }

    public function testRequiredOptions(): void
    {
        $this->expectException(MissingOptionsException::class);
        $task = new RemoveFileTask($this->createLogger(), $this->createStorages());
        $task->initialize($this->createState(RemoveFileTask::class, []));
    }

    /**
     * @param array<string, mixed>                    $options
     * @param ServiceLocator<FilesystemOperator>|null $storages
     */
    private function execute(array $options, mixed $input, ?ServiceLocator $storages = null): void
    {
        $state = $this->createState(RemoveFileTask::class, $options + ['filesystem' => 'source']);
        $task = new RemoveFileTask($this->createLogger(), $storages ?? $this->createStorages());
        $task->initialize($state);
        $state->setInput($input);
        $task->execute($state);
    }

    private function createLogger(): AbstractLogger
    {
        $onLog = function (string $log): void {
            $this->logs[] = $log;
        };

        return new class($onLog) extends AbstractLogger {
            public function __construct(private readonly \Closure $onLog)
            {
            }

            /**
             * @param array<mixed> $context
             */
            public function log($level, string|\Stringable $message, array $context = []): void
            {
                $file = $context['file'] ?? '';
                ($this->onLog)(\sprintf('%s: %s %s', \is_scalar($level) ? $level : '', $message, \is_string($file) ? $file : ''));
            }
        };
    }
}
