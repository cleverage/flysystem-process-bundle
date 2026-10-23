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

namespace CleverAge\FlysystemProcessBundle\Tests\Functional;

use CleverAge\FlysystemProcessBundle\Task\FileFetchTask;
use CleverAge\FlysystemProcessBundle\Task\ListContentTask;
use CleverAge\FlysystemProcessBundle\Task\RemoveFileTask;
use CleverAge\FlysystemProcessBundle\Tests\App\TestKernel;
use CleverAge\ProcessBundle\Registry\ProcessConfigurationRegistry;
use CleverAge\ProcessBundle\Registry\TaskRegistry;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\Attributes\CoversNothing;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * The bundle in an application with process-bundle and FlysystemBundle (see TestKernel): the container compiles, the
 * services of the bundle are resolved with their dependencies.
 */
#[CoversNothing]
class KernelTest extends KernelTestCase
{
    private mixed $exceptionHandler;

    protected function setUp(): void
    {
        $this->exceptionHandler = $this->getExceptionHandler();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // The ErrorHandler registered when the kernel boots is not removed by the first Symfony 6.4 versions
        if ($this->getExceptionHandler() !== $this->exceptionHandler) {
            restore_exception_handler();
        }
    }

    public function testTasksAndStorageAreRegistered(): void
    {
        $container = self::getContainer();

        self::assertTrue($container->has(FileFetchTask::class));
        self::assertTrue($container->has(ListContentTask::class));
        self::assertTrue($container->has(RemoveFileTask::class));
        self::assertInstanceOf(FilesystemOperator::class, $container->get('test.storage'));
    }

    public function testTasksAreResolvedByCode(): void
    {
        $processConfigurationRegistry = self::getContainer()->get('test.process_configuration_registry');
        self::assertInstanceOf(ProcessConfigurationRegistry::class, $processConfigurationRegistry);
        $taskRegistry = self::getContainer()->get('test.task_registry');
        self::assertInstanceOf(TaskRegistry::class, $taskRegistry);

        $processConfiguration = $processConfigurationRegistry->getProcessConfiguration('test.flysystem');
        self::assertInstanceOf(FileFetchTask::class, $taskRegistry->getTask($processConfiguration->getTaskConfiguration('file_fetch')));
        self::assertInstanceOf(ListContentTask::class, $taskRegistry->getTask($processConfiguration->getTaskConfiguration('list_content')));
        self::assertInstanceOf(RemoveFileTask::class, $taskRegistry->getTask($processConfiguration->getTaskConfiguration('remove_file')));
    }

    private function getExceptionHandler(): mixed
    {
        $handler = set_exception_handler(null);
        restore_exception_handler();

        return $handler;
    }

    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }
}
