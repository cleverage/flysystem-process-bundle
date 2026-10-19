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

namespace CleverAge\FlysystemProcessBundle\Tests\App;

use CleverAge\FlysystemProcessBundle\CleverAgeFlysystemProcessBundle;
use CleverAge\ProcessBundle\CleverAgeProcessBundle;
use CleverAge\ProcessBundle\Registry\ProcessConfigurationRegistry;
use CleverAge\ProcessBundle\Registry\TaskRegistry;
use League\FlysystemBundle\Adapter\Builder\LocalAdapterDefinitionBuilder;
use League\FlysystemBundle\FlysystemBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Application used by the integration tests: process-bundle, FlysystemBundle with a local storage and this bundle, in
 * a temporary directory of its own (per PHP process).
 */
final class TestKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new FlysystemBundle();
        yield new CleverAgeProcessBundle();
        yield new CleverAgeFlysystemProcessBundle();
    }

    public function getProjectDir(): string
    {
        return __DIR__;
    }

    public function getCacheDir(): string
    {
        return $this->getRunDir().'/cache/'.$this->environment;
    }

    public function getLogDir(): string
    {
        return $this->getRunDir().'/logs';
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'secret' => 'test',
            'test' => true,
            'http_method_override' => false,
            'handle_all_throwables' => true,
            'php_errors' => ['log' => true],
            'validation' => ['email_validation_mode' => 'html5'],
        ]);
        if (Kernel::VERSION_ID >= 70300) {
            // Not setting it is deprecated since Symfony 7.3: its default value changes in 8.0
            $container->extension('framework', ['property_info' => ['with_constructor_extractor' => true]]);
        }
        $directory = '%kernel.cache_dir%/storage';
        $container->extension('flysystem', [
            'storages' => [
                // Configuration format of league/flysystem-bundle 3.5, the former one is deprecated there
                'test.storage' => method_exists(LocalAdapterDefinitionBuilder::class, 'addConfiguration') // @phpstan-ignore function.alreadyNarrowedType (false before 3.5)
                    ? ['local' => ['directory' => $directory]]
                    : ['adapter' => 'local', 'options' => ['directory' => $directory]],
            ],
        ]);

        // Tasks referenced by code (task: <code>): process-bundle 5.4 or later
        if (class_exists(TaskRegistry::class)) {
            $container->extension('clever_age_process', [
                'configurations' => [
                    'test.flysystem' => [
                        'tasks' => [
                            'file_fetch' => [
                                'task' => 'flysystem_file_fetch',
                                'options' => ['source_filesystem' => 'test.storage', 'destination_filesystem' => 'test.storage'],
                            ],
                            'list_content' => [
                                'task' => 'flysystem_list_content',
                                'options' => ['filesystem' => 'test.storage'],
                            ],
                            'remove_file' => [
                                'task' => 'flysystem_remove_file',
                                'options' => ['filesystem' => 'test.storage'],
                            ],
                        ],
                    ],
                ],
            ]);
            $services = $container->services();
            $services->alias('test.task_registry', TaskRegistry::class)->public();
            $services->alias('test.process_configuration_registry', ProcessConfigurationRegistry::class)->public();
        }
    }

    private function getRunDir(): string
    {
        return sys_get_temp_dir().'/flysystem_process_bundle_tests_'.getmypid();
    }
}
