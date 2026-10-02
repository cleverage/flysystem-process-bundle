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

namespace CleverAge\FlysystemProcessBundle\Tests\DependencyInjection;

use CleverAge\FlysystemProcessBundle\DependencyInjection\CleverAgeFlysystemProcessExtension;
use CleverAge\FlysystemProcessBundle\Task\FileFetchTask;
use CleverAge\FlysystemProcessBundle\Task\ListContentTask;
use CleverAge\FlysystemProcessBundle\Task\RemoveFileTask;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;

#[CoversClass(CleverAgeFlysystemProcessExtension::class)]
class CleverAgeFlysystemProcessExtensionTest extends TestCase
{
    /**
     * @return iterable<string, array{string, class-string}>
     */
    public static function provideTasks(): iterable
    {
        yield 'file_fetch' => ['cleverage_flysystem_process.task.file_fetch', FileFetchTask::class];
        yield 'list_content' => ['cleverage_flysystem_process.task.list_content', ListContentTask::class];
        yield 'remove_file' => ['cleverage_flysystem_process.task.remove_file', RemoveFileTask::class];
    }

    /**
     * @param class-string $class
     */
    #[DataProvider('provideTasks')]
    public function testTaskIsRegistered(string $id, string $class): void
    {
        $container = new ContainerBuilder();
        (new CleverAgeFlysystemProcessExtension())->load([], $container);

        $definition = $container->getDefinition($id);
        self::assertSame($class, $definition->getClass());
        // Tasks are stateful: each process execution must get its own instance
        self::assertFalse($definition->isShared());

        // The Flysystem storages, indexed by name
        $storages = array_values(array_filter(
            $definition->getArguments(),
            static fn (mixed $argument): bool => $argument instanceof ServiceLocatorArgument
        ));
        self::assertCount(1, $storages);
        $taggedIterator = $storages[0]->getTaggedIteratorArgument();
        self::assertInstanceOf(TaggedIteratorArgument::class, $taggedIterator);
        self::assertSame('flysystem.storage', $taggedIterator->getTag());
        self::assertSame('storage', $taggedIterator->getIndexAttribute());

        // Referenced as '@<class>' in process configurations
        $alias = $container->getAlias($class);
        self::assertSame($id, (string) $alias);
        self::assertTrue($alias->isPublic());
    }

    public function testEveryTaskIsTested(): void
    {
        $container = new ContainerBuilder();
        (new CleverAgeFlysystemProcessExtension())->load([], $container);

        $ids = array_filter(
            array_keys($container->getDefinitions()),
            static fn (string $id): bool => str_starts_with($id, 'cleverage_flysystem_process.task.')
        );
        self::assertEqualsCanonicalizing(array_column(iterator_to_array(self::provideTasks()), 0), array_values($ids));
    }
}
