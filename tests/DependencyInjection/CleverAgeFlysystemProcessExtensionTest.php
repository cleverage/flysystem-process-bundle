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
use CleverAge\FlysystemProcessBundle\DependencyInjection\Configuration;
use CleverAge\FlysystemProcessBundle\Task\FileFetchTask;
use CleverAge\FlysystemProcessBundle\Task\ListContentTask;
use CleverAge\FlysystemProcessBundle\Task\RemoveFileTask;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Yaml\Yaml;

#[CoversClass(CleverAgeFlysystemProcessExtension::class)]
#[CoversClass(Configuration::class)]
class CleverAgeFlysystemProcessExtensionTest extends TestCase
{
    public function testEveryServiceDefinitionFileIsLoaded(): void
    {
        $container = new ContainerBuilder();
        (new CleverAgeFlysystemProcessExtension())->load([], $container);

        $files = glob(\dirname(__DIR__, 2).'/config/services/*.yaml');
        self::assertIsArray($files);
        self::assertNotEmpty($files);
        foreach ($files as $file) {
            $config = Yaml::parseFile($file, Yaml::PARSE_CUSTOM_TAGS);
            self::assertIsArray($config);
            if (!isset($config['services'])) {
                continue;
            }
            self::assertIsArray($config['services']);
            foreach (array_keys($config['services']) as $id) {
                if ('_defaults' === $id) {
                    continue;
                }
                if (str_ends_with((string) $id, '\\')) {
                    // Resource: at least one service registered under the namespace prefix
                    $registered = array_filter(
                        array_keys($container->getDefinitions()),
                        static fn (string $definitionId): bool => str_starts_with($definitionId, (string) $id)
                    );
                    self::assertNotEmpty($registered, \sprintf('No service registered for the "%s" resource of %s', $id, basename($file)));
                    continue;
                }
                self::assertTrue($container->has((string) $id), \sprintf('Service "%s" of %s is not registered', $id, basename($file)));
            }
        }
    }

    /**
     * @return iterable<string, array{class-string, string, string}>
     */
    public static function provideTasks(): iterable
    {
        yield 'file_fetch' => [FileFetchTask::class, 'flysystem_file_fetch', 'cleverage_flysystem_process.task.file_fetch'];
        yield 'list_content' => [ListContentTask::class, 'flysystem_list_content', 'cleverage_flysystem_process.task.list_content'];
        yield 'remove_file' => [RemoveFileTask::class, 'flysystem_remove_file', 'cleverage_flysystem_process.task.remove_file'];
    }

    /**
     * @param class-string $class
     */
    #[DataProvider('provideTasks')]
    public function testTaskIsRegistered(string $class, string $code, string $deprecatedId): void
    {
        $container = new ContainerBuilder();
        (new CleverAgeFlysystemProcessExtension())->load([], $container);

        // The class name is the service id
        $definition = $container->getDefinition($class);
        // Tasks are stateful: each process execution must get its own instance
        self::assertFalse($definition->isShared());
        // Referenced by its code in the process configurations (task: <code>)
        self::assertSame([['code' => $code]], $definition->getTag('cleverage.task'));
        // Public for the direct access from the service container, deprecated
        self::assertTrue($definition->isPublic());
        self::assertSame([['package' => 'cleverage/flysystem-process-bundle', 'version' => '3.3']], $definition->getTag('container.private'));

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

        // Former id, deprecated
        $alias = $container->getAlias($deprecatedId);
        self::assertSame($class, (string) $alias);
        self::assertFalse($alias->isPublic());
        self::assertTrue($alias->isDeprecated());
        self::assertSame(
            ['package' => 'cleverage/flysystem-process-bundle', 'version' => '3.3', 'message' => 'The "'.$deprecatedId.'" service alias is deprecated, use the "'.$code.'" task code or the class name of the task instead. It will be removed in 4.0.'],
            $alias->getDeprecation($deprecatedId)
        );
    }

    public function testEveryTaskIsTested(): void
    {
        $container = new ContainerBuilder();
        (new CleverAgeFlysystemProcessExtension())->load([], $container);

        $ids = array_filter(
            array_keys($container->getDefinitions()),
            static fn (string $id): bool => str_starts_with($id, 'CleverAge\\FlysystemProcessBundle\\Task\\')
        );
        self::assertEqualsCanonicalizing(array_column(iterator_to_array(self::provideTasks()), 0), array_values($ids));
    }

    public function testConfigurationKeysAreDeprecated(): void
    {
        $deprecations = $this->collectDeprecations(static function (): void {
            (new CleverAgeFlysystemProcessExtension())->load([[], ['foo' => true], ['bar' => 1, 'foo' => false]], new ContainerBuilder());
        });

        self::assertSame(['Since cleverage/flysystem-process-bundle 3.3: Configuring "clever_age_flysystem_process" is deprecated, the bundle has no configuration: the keys "foo", "bar" are ignored. They will be rejected in 4.0.'], $deprecations);
    }

    public function testNoDeprecationWithoutConfigurationKey(): void
    {
        $deprecations = $this->collectDeprecations(static function (): void {
            (new CleverAgeFlysystemProcessExtension())->load([[], []], new ContainerBuilder());
        });

        self::assertSame([], $deprecations);
    }

    public function testConfigurationHasNoOption(): void
    {
        $configuration = (new CleverAgeFlysystemProcessExtension())->getConfiguration([], new ContainerBuilder());

        self::assertInstanceOf(Configuration::class, $configuration);
        self::assertSame('clever_age_flysystem_process', $configuration->getConfigTreeBuilder()->buildTree()->getName());
    }

    /**
     * @param callable(): void $callback
     *
     * @return list<string>
     */
    private function collectDeprecations(callable $callback): array
    {
        $deprecations = [];
        set_error_handler(static function (int $errno, string $errstr) use (&$deprecations): bool {
            $deprecations[] = $errstr;

            return true;
        }, \E_USER_DEPRECATED);
        try {
            $callback();
        } finally {
            restore_error_handler();
        }

        return $deprecations;
    }
}
