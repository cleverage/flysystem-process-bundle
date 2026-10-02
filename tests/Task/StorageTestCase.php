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

use CleverAge\ProcessBundle\Configuration\ProcessConfiguration;
use CleverAge\ProcessBundle\Configuration\TaskConfiguration;
use CleverAge\ProcessBundle\Context\ContextualOptionResolver;
use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\IterableTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessHistory;
use CleverAge\ProcessBundle\Model\ProcessState;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Local\LocalFilesystemAdapter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;

/**
 * Tasks run on real local storages (in a temporary directory), iterating as the process manager does.
 */
abstract class StorageTestCase extends TestCase
{
    protected string $dir;

    /** @var array<string, int> Number of listings of each storage */
    protected array $listings = [];

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/'.uniqid('flysystem_task_test_', true);
        mkdir($this->dir.'/source', 0o777, true);
        mkdir($this->dir.'/destination', 0o777, true);
    }

    protected function tearDown(): void
    {
        (new SymfonyFilesystem())->remove($this->dir);
    }

    /**
     * @param array<array-key, string> $files Contents indexed by path (a numeric path such as "0" is an int key)
     */
    protected function createFiles(string $storage, array $files): void
    {
        foreach ($files as $path => $content) {
            (new SymfonyFilesystem())->dumpFile("{$this->dir}/{$storage}/{$path}", $content);
        }
    }

    /**
     * @return list<string>
     */
    protected function getFiles(string $storage): array
    {
        $files = array_values(array_diff(scandir("{$this->dir}/{$storage}") ?: [], ['.', '..']));
        sort($files);

        return $files;
    }

    /**
     * @return ServiceLocator<FilesystemOperator>
     */
    protected function createStorages(): ServiceLocator
    {
        $factories = [];
        foreach (['source', 'destination'] as $name) {
            $listings = &$this->listings;
            $listings[$name] = 0;
            $adapter = new class("{$this->dir}/{$name}", $listings[$name]) extends LocalFilesystemAdapter {
                public function __construct(string $location, private int &$listings)
                {
                    parent::__construct($location);
                }

                #[\Override]
                public function listContents(string $path, bool $deep): iterable
                {
                    ++$this->listings;

                    return parent::listContents($path, $deep);
                }
            };
            $filesystem = new Filesystem($adapter);
            $factories[$name] = static fn (): FilesystemOperator => $filesystem;
        }

        /** @var ServiceLocator<FilesystemOperator> $storages */
        $storages = new ServiceLocator($factories);

        return $storages;
    }

    /**
     * @param array<string, mixed> $options
     */
    protected function createState(string $class, array $options): ProcessState
    {
        $processConfiguration = new ProcessConfiguration('test', []);
        $state = new ProcessState($processConfiguration, new ProcessHistory($processConfiguration));
        $state->setContextualOptionResolver(new ContextualOptionResolver());
        $state->setContext([]);
        $state->setTaskConfiguration(new TaskConfiguration('task', $class, $options));

        return $state;
    }

    /**
     * Execute the task for one input, then iterate until next() returns false, as the process manager does.
     *
     * @return list<mixed>
     */
    protected function iterate(AbstractConfigurableTask&IterableTaskInterface $task, ProcessState $state, mixed $input): array
    {
        $outputs = [];
        $state->reset(true);
        $state->setInput($input);
        do {
            $state->reset(false);
            $task->execute($state);
            if ($state->isSkipped()) {
                break;
            }
            $outputs[] = $state->getOutput();
        } while ($task->next($state));

        return $outputs;
    }
}
